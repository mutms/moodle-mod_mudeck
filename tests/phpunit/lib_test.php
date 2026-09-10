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

namespace mod_mudeck\phpunit;

/**
 * Plugin core API test.
 *
 * @package    mod_mudeck
 * @copyright  2026 Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * @coversNothing
 */
final class lib_test extends \advanced_testcase {
    public function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
        $this->setAdminUser();
    }

    public function test_delete_instance_takes_everything_with_it(): void {
        global $DB;

        $course = $this->getDataGenerator()->create_course();
        $mudeck = $this->getDataGenerator()->create_module('mudeck', ['course' => $course->id]);
        /** @var \mod_mudeck_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('mod_mudeck');
        $generator->create_part(['mudeckid' => $mudeck->id]);

        // A device sync session and a completion record, as a finished show would leave behind.
        $DB->insert_record('mudeck_session', (object)[
            'mudeckid' => $mudeck->id,
            'userid' => get_admin()->id,
            'timestarted' => time(),
            'timelastseen' => time(),
        ]);
        $DB->insert_record('mudeck_completed', (object)[
            'mudeckid' => $mudeck->id,
            'userid' => get_admin()->id,
            'timecompleted' => time(),
        ]);

        $this->assertSame(1, $DB->count_records('mudeck_part', ['mudeckid' => $mudeck->id]));

        require_once(__DIR__ . '/../../lib.php');
        mudeck_delete_instance($mudeck->id);

        $this->assertSame(0, $DB->count_records('mudeck', ['id' => $mudeck->id]));
        $this->assertSame(0, $DB->count_records('mudeck_part', ['mudeckid' => $mudeck->id]));
        $this->assertSame(0, $DB->count_records('mudeck_session', ['mudeckid' => $mudeck->id]));
        $this->assertSame(0, $DB->count_records('mudeck_completed', ['mudeckid' => $mudeck->id]));
    }

    public function test_supports(): void {
        require_once(__DIR__ . '/../../lib.php');

        $this->assertTrue(mudeck_supports(FEATURE_MOD_INTRO));
        $this->assertFalse(mudeck_supports(FEATURE_GROUPS), 'one presentation, shown to everybody the same way');
        $this->assertTrue(mudeck_supports(FEATURE_COMPLETION_TRACKS_VIEWS));
        $this->assertFalse(mudeck_supports(FEATURE_GRADE_HAS_GRADE));
        $this->assertTrue(mudeck_supports(FEATURE_BACKUP_MOODLE2));
    }
}
