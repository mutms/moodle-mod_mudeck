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

namespace mod_mudeck\phpunit\route\api;

use core\tests\router\route_testcase;
use GuzzleHttp\Psr7\Utils;
use mod_mudeck\route\api\presentation_reached_end;

/**
 * Reaching the last slide, reported by the viewer.
 *
 * @package    mod_mudeck
 * @copyright  2026 Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * @covers \mod_mudeck\route\api\presentation_reached_end
 */
final class presentation_reached_end_test extends route_testcase {
    /** @var \stdClass the course, with completion on */
    private \stdClass $course;
    /** @var \stdClass the presentation, asking for the last slide */
    private \stdClass $mudeck;
    /** @var \stdClass a student enrolled in the course */
    private \stdClass $student;

    public function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
        set_config('enablecompletion', 1);

        $this->course = $this->getDataGenerator()->create_course(['enablecompletion' => 1]);
        $this->mudeck = $this->getDataGenerator()->create_module('mudeck', [
            'course' => $this->course->id,
            'completion' => COMPLETION_TRACKING_AUTOMATIC,
            'completionreachedend' => 1,
        ]);
        $this->student = $this->getDataGenerator()->create_and_enrol($this->course, 'student');
    }

    public function test_reaching_the_end_is_recorded_and_completes(): void {
        global $DB;

        $this->setUser($this->student);
        $this->add_class_routes_to_route_loader(presentation_reached_end::class);

        $response = $this->process_api_request(
            'POST',
            "/presentation/{$this->mudeck->cmid}/reached-end",
            body: Utils::streamFor(json_encode(['sesskey' => sesskey()])),
        );

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertTrue($DB->record_exists('mudeck_completed', [
            'mudeckid' => $this->mudeck->id,
            'userid' => $this->student->id,
        ]));
        $cm = get_fast_modinfo($this->course, $this->student->id)->get_cm($this->mudeck->cmid);
        $completion = new \completion_info($this->course);
        $this->assertEquals(COMPLETION_COMPLETE, $completion->get_data($cm, false, $this->student->id)->completionstate);
    }

    public function test_a_report_without_a_sesskey_is_refused(): void {
        global $DB;

        $this->setUser($this->student);
        $this->add_class_routes_to_route_loader(presentation_reached_end::class);

        $response = $this->process_api_request(
            'POST',
            "/presentation/{$this->mudeck->cmid}/reached-end",
            body: Utils::streamFor(json_encode([])),
        );

        $this->assertGreaterThanOrEqual(400, $response->getStatusCode());
        $this->assertFalse($DB->record_exists('mudeck_completed', ['mudeckid' => $this->mudeck->id]));
    }

    public function test_somebody_outside_the_course_is_refused(): void {
        global $DB;

        $stranger = $this->getDataGenerator()->create_user();
        $this->setUser($stranger);
        $this->add_class_routes_to_route_loader(presentation_reached_end::class);

        $response = $this->process_api_request(
            'POST',
            "/presentation/{$this->mudeck->cmid}/reached-end",
            body: Utils::streamFor(json_encode(['sesskey' => sesskey()])),
        );

        $this->assertGreaterThanOrEqual(400, $response->getStatusCode());
        $this->assertFalse($DB->record_exists('mudeck_completed', ['mudeckid' => $this->mudeck->id]));
    }
}
