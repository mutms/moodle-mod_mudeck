<?php
// This file is part of MuTMS suite of plugins for Moodle™ LMS.
//
// This program is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with this program.  If not, see <https://www.gnu.org/licenses/>.

// phpcs:disable moodle.Files.BoilerplateComment.CommentEndedTooSoon
// phpcs:disable moodle.Commenting.DocblockDescription.Missing

namespace mod_mudeck\phpunit\backup;

use mod_mudeck\local\media;
use mod_mudeck\local\part;

/**
 * Backup and restore test.
 *
 * @package    mod_mudeck
 * @copyright  2026 Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * @coversNothing
 */
final class backup_test extends \advanced_testcase {
    public function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
        $this->setAdminUser();
    }

    /**
     * Back a course up and restore it into a new one.
     *
     * @param \stdClass $course
     * @return \stdClass the new course
     */
    private function backup_and_restore(\stdClass $course): \stdClass {
        global $CFG, $USER;
        require_once($CFG->dirroot . '/backup/util/includes/backup_includes.php');
        require_once($CFG->dirroot . '/backup/util/includes/restore_includes.php');

        $backupid = 'mudecktest';
        $bc = new \backup_controller(
            \backup::TYPE_1COURSE,
            $course->id,
            \backup::FORMAT_MOODLE,
            \backup::INTERACTIVE_NO,
            \backup::MODE_GENERAL,
            $USER->id
        );
        $bc->execute_plan();
        $results = $bc->get_results();
        $file = $results['backup_destination'];
        $dir = make_backup_temp_directory($backupid);
        get_file_packer('application/vnd.moodle.backup')->extract_to_pathname($file, $dir);
        $bc->destroy();

        $newcourse = $this->getDataGenerator()->create_course();
        $rc = new \restore_controller(
            $backupid,
            $newcourse->id,
            \backup::INTERACTIVE_NO,
            \backup::MODE_GENERAL,
            $USER->id,
            \backup::TARGET_NEW_COURSE
        );
        $rc->execute_precheck();
        $rc->execute_plan();
        $rc->destroy();

        return $newcourse;
    }

    public function test_backup_and_restore_keeps_parts_and_files(): void {
        global $DB;

        $course = $this->getDataGenerator()->create_course();
        $mudeck = $this->getDataGenerator()->create_module('mudeck', [
            'course' => $course->id,
            'name' => 'Conference talk',
            'theme' => 'orange',
        ]);
        $cm = get_coursemodule_from_instance('mudeck', $mudeck->id, $course->id, false, MUST_EXIST);
        $context = \context_module::instance($cm->id);

        $first = part::create($mudeck, 'Opening', "<!-- _class: lead -->\n\n# Hello\n");
        $second = part::create($mudeck, 'Body', "## One\n\n---\n\n## Two\n\n![](picture.png)\n");

        // A picture in the second part, which the restore has to carry across.
        get_file_storage()->create_file_from_string([
            'contextid' => $context->id,
            'component' => 'mod_mudeck',
            'filearea' => media::FILEAREA,
            'itemid' => $second,
            'filepath' => '/',
            'filename' => 'picture.png',
        ], 'not really a png');

        $newcourse = $this->backup_and_restore($course);

        $restored = $DB->get_record('mudeck', ['course' => $newcourse->id], '*', MUST_EXIST);
        $this->assertSame('Conference talk', $restored->name);
        $this->assertSame('orange', $restored->theme);
        $this->assertNotEquals($mudeck->id, $restored->id, 'the restore must create its own instance');

        $parts = array_values(part::get_all($restored->id));
        $this->assertCount(2, $parts);
        $this->assertSame('Opening', $parts[0]->name);
        $this->assertSame('Body', $parts[1]->name);
        $this->assertSame(2, part::count_slides($parts[1]));
        $this->assertSame(sha1($parts[1]->content), $parts[1]->contenthash);

        // The picture must have followed its part, under the new part id.
        $newcm = get_coursemodule_from_instance('mudeck', $restored->id, $newcourse->id, false, MUST_EXIST);
        $newcontext = \context_module::instance($newcm->id);
        $files = get_file_storage()->get_area_files(
            $newcontext->id,
            'mod_mudeck',
            media::FILEAREA,
            $parts[1]->id,
            'filename',
            false
        );
        $this->assertCount(1, $files);
        $file = reset($files);
        $this->assertSame('picture.png', $file->get_filename());
        $this->assertSame('not really a png', $file->get_content());

        // Nothing should have landed on the other part.
        $this->assertCount(0, get_file_storage()->get_area_files(
            $newcontext->id,
            'mod_mudeck',
            media::FILEAREA,
            $parts[0]->id,
            'filename',
            false
        ));
    }

    public function test_duplicated_activity_is_independent(): void {
        global $DB;

        $course = $this->getDataGenerator()->create_course();
        $mudeck = $this->getDataGenerator()->create_module('mudeck', ['course' => $course->id]);
        $cm = get_coursemodule_from_instance('mudeck', $mudeck->id, $course->id, false, MUST_EXIST);
        part::create($mudeck, 'Only part', "# Slide\n");

        $cmactions = new \core_courseformat\local\cmactions($course);
        $newcm = $cmactions->duplicate($cm->id);

        $this->assertSame(2, $DB->count_records('mudeck', ['course' => $course->id]));
        $copied = array_values(part::get_all($newcm->instance));
        $this->assertCount(1, $copied);
        $this->assertSame('Only part', $copied[0]->name);
        $this->assertNotEquals($mudeck->id, $newcm->instance);
    }
}
