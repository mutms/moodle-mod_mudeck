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
 * Generator test.
 *
 * @package    mod_mudeck
 * @copyright  2026 Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * @covers \mod_mudeck_generator
 */
final class generator_test extends \advanced_testcase {
    public function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
        $this->setAdminUser();
    }

    public function test_create_instance(): void {
        global $DB;

        $course = $this->getDataGenerator()->create_course();
        $this->assertSame(0, $DB->count_records('mudeck', ['course' => $course->id]));

        $mudeck = $this->getDataGenerator()->create_module('mudeck', ['course' => $course->id]);

        $this->assertSame(1, $DB->count_records('mudeck', ['course' => $course->id]));
        $record = $DB->get_record('mudeck', ['id' => $mudeck->id], '*', MUST_EXIST);
        $this->assertSame(0, (int)$record->allowdevicesync, 'device sync must be off unless asked for');
    }

    public function test_create_part(): void {
        global $DB;

        $course = $this->getDataGenerator()->create_course();
        $mudeck = $this->getDataGenerator()->create_module('mudeck', ['course' => $course->id]);

        /** @var \mod_mudeck_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('mod_mudeck');
        $part = $generator->create_part(['mudeckid' => $mudeck->id]);

        $this->assertSame(1, $DB->count_records('mudeck_part', ['mudeckid' => $mudeck->id]));
        $this->assertSame('Part 1', $part->name);
        $this->assertNotEmpty($part->content);
        $this->assertSame(sha1($part->content), $part->contenthash);
    }

    public function test_create_part_with_own_content(): void {
        $course = $this->getDataGenerator()->create_course();
        $mudeck = $this->getDataGenerator()->create_module('mudeck', ['course' => $course->id]);

        /** @var \mod_mudeck_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('mod_mudeck');
        $part = $generator->create_part([
            'mudeckid' => $mudeck->id,
            'name' => 'Intro',
            'content' => "# Only one slide\n",
        ]);

        $this->assertSame('Intro', $part->name);
        $this->assertSame("# Only one slide\n", $part->content);
    }
}
