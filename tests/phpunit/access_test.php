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
 * Default capabilities test - what a student may do without any override.
 *
 * @package    mod_mudeck
 * @copyright  2026 Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * @coversNothing
 */
final class access_test extends \advanced_testcase {
    public function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
    }

    public function test_student_may_only_watch(): void {
        $course = $this->getDataGenerator()->create_course();
        $mudeck = $this->getDataGenerator()->create_module('mudeck', ['course' => $course->id]);
        $context = \context_module::instance($mudeck->cmid);
        $student = $this->getDataGenerator()->create_and_enrol($course, 'student');

        $this->setUser($student);
        $this->assertTrue(has_capability('mod/mudeck:view', $context));
        $this->assertTrue(has_capability('mod/mudeck:present', $context));
        // The management pages and everything behind the scenes stay closed.
        $this->assertFalse(has_capability('mod/mudeck:edit', $context));
        $this->assertFalse(has_capability('mod/mudeck:fullaccess', $context));
        // Device sync is off for students by default - a session says where somebody is in a deck.
        $this->assertFalse(has_capability('mod/mudeck:syncdevices', $context));
    }

    public function test_teacher_may_edit_and_see_everything(): void {
        $course = $this->getDataGenerator()->create_course();
        $mudeck = $this->getDataGenerator()->create_module('mudeck', ['course' => $course->id]);
        $context = \context_module::instance($mudeck->cmid);
        $teacher = $this->getDataGenerator()->create_and_enrol($course, 'editingteacher');

        $this->setUser($teacher);
        $this->assertTrue(has_capability('mod/mudeck:edit', $context));
        $this->assertTrue(has_capability('mod/mudeck:fullaccess', $context));
        $this->assertTrue(has_capability('mod/mudeck:present', $context));
        $this->assertTrue(has_capability('mod/mudeck:syncdevices', $context));
    }
}
