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

namespace mod_mudeck\local;

use mod_mudeck\completion\custom_completion;

/**
 * Completion by reaching the last slide.
 *
 * @package    mod_mudeck
 * @copyright  2026 Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * @covers \mod_mudeck\local\completed
 * @covers \mod_mudeck\completion\custom_completion
 */
final class completed_test extends \advanced_testcase {
    /** @var \stdClass */
    private \stdClass $course;
    /** @var \stdClass */
    private \stdClass $student;

    public function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
        set_config('enablecompletion', 1);

        $this->course = $this->getDataGenerator()->create_course(['enablecompletion' => 1]);
        $this->student = $this->getDataGenerator()->create_and_enrol($this->course, 'student');
    }

    /**
     * A presentation with the given completion settings, and its cm_info.
     *
     * @param array $settings
     * @return array [mudeck record, cm_info]
     */
    private function make(array $settings): array {
        global $DB;

        $mudeck = $this->getDataGenerator()->create_module('mudeck', ['course' => $this->course->id] + $settings);
        $cm = get_fast_modinfo($this->course, $this->student->id)->get_cm($mudeck->cmid);
        return [$DB->get_record('mudeck', ['id' => $mudeck->id], '*', MUST_EXIST), $cm];
    }

    public function test_reaching_the_end_is_wanted_only_when_the_rule_is_on(): void {
        $this->setUser($this->student);

        [$off, $cmoff] = $this->make(['completion' => COMPLETION_TRACKING_AUTOMATIC, 'completionview' => 1]);
        $this->assertFalse(completed::is_wanted($off, $cmoff));

        [$manual, $cmmanual] = $this->make(['completion' => COMPLETION_TRACKING_MANUAL, 'completionreachedend' => 1]);
        $this->assertFalse(completed::is_wanted($manual, $cmmanual));

        [$on, $cmon] = $this->make(['completion' => COMPLETION_TRACKING_AUTOMATIC, 'completionreachedend' => 1]);
        $this->assertTrue(completed::is_wanted($on, $cmon));

        $this->setGuestUser();
        $this->assertFalse(completed::is_wanted($on, $cmon));
    }

    public function test_recording_completes_the_activity_once(): void {
        global $DB;

        [$mudeck, $cm] = $this->make(['completion' => COMPLETION_TRACKING_AUTOMATIC, 'completionreachedend' => 1]);
        $completion = new \completion_info($this->course);

        $this->assertEquals(COMPLETION_INCOMPLETE, $completion->get_data($cm, false, $this->student->id)->completionstate);
        $custom = new custom_completion($cm, (int)$this->student->id);
        $this->assertEquals(COMPLETION_INCOMPLETE, $custom->get_state('completionreachedend'));

        completed::record($mudeck, $cm, (int)$this->student->id);
        completed::record($mudeck, $cm, (int)$this->student->id);

        $records = $DB->get_records('mudeck_completed', ['mudeckid' => $mudeck->id]);
        $this->assertCount(1, $records);
        $this->assertEquals($this->student->id, reset($records)->userid);

        $custom = new custom_completion($cm, (int)$this->student->id);
        $this->assertEquals(COMPLETION_COMPLETE, $custom->get_state('completionreachedend'));
        $this->assertEquals(COMPLETION_COMPLETE, $completion->get_data($cm, false, $this->student->id)->completionstate);
    }

    public function test_the_rule_describes_itself(): void {
        [, $cm] = $this->make(['completion' => COMPLETION_TRACKING_AUTOMATIC, 'completionreachedend' => 1]);
        $custom = new custom_completion($cm, (int)$this->student->id);

        $this->assertEquals(['completionreachedend'], custom_completion::get_defined_custom_rules());
        $this->assertArrayHasKey('completionreachedend', $custom->get_custom_rule_descriptions());
        $this->assertEquals(['completionview', 'completionreachedend'], $custom->get_sort_order());
    }
}
