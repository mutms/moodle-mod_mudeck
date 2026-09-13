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
use mod_mudeck\local\part;
use mod_mudeck\route\api\part as part_route;

/**
 * Part REST resource test - deleting a part.
 *
 * @package    mod_mudeck
 * @copyright  2026 Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * @covers \mod_mudeck\route\api\part
 */
final class part_test extends route_testcase {
    /** @var \stdClass the presentation under test */
    private \stdClass $mudeck;
    /** @var \stdClass the teacher who may edit */
    private \stdClass $teacher;
    /** @var int[] the three parts, in the order they were created */
    private array $partids = [];

    public function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();

        $course = $this->getDataGenerator()->create_course();
        $this->mudeck = $this->getDataGenerator()->create_module('mudeck', ['course' => $course->id]);
        $this->teacher = $this->getDataGenerator()->create_and_enrol($course, 'editingteacher');

        $this->setUser($this->teacher);
        foreach (['One', 'Two', 'Three'] as $name) {
            $this->partids[] = part::create($this->mudeck, $name);
        }
    }

    public function test_without_view_even_an_editor_is_refused(): void {
        global $DB;

        // View comes from the authenticated user role, so that is where it is taken away;
        // the teacher role keeps the capability to edit.
        $roleid = $DB->get_field('role', 'id', ['shortname' => 'user'], MUST_EXIST);
        assign_capability('mod/mudeck:view', CAP_PREVENT, $roleid, \core\context\course::instance($this->mudeck->course)->id, true);
        accesslib_clear_all_caches_for_unit_testing();
        $this->add_class_routes_to_route_loader(part_route::class);

        $response = $this->process_api_request(
            'DELETE',
            "/part/{$this->partids[1]}",
            body: Utils::streamFor(json_encode(['sesskey' => sesskey()])),
        );

        $this->assertGreaterThanOrEqual(400, $response->getStatusCode());
        $this->assertTrue($DB->record_exists('mudeck_part', ['id' => $this->partids[1]]));
    }

    public function test_a_part_can_be_deleted(): void {
        global $DB;

        $this->add_class_routes_to_route_loader(part_route::class);

        $response = $this->process_api_request(
            'DELETE',
            "/part/{$this->partids[1]}",
            body: Utils::streamFor(json_encode(['sesskey' => sesskey()])),
        );

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertFalse($DB->record_exists('mudeck_part', ['id' => $this->partids[1]]));
        $this->assertSame([$this->partids[0], $this->partids[2]], part::get_order($this->mudeck->id));
        $payload = json_decode((string)$response->getBody(), true);
        $this->assertSame(part::get_order($this->mudeck->id), $payload['order']);
    }

    public function test_somebody_who_may_not_edit_cannot_delete(): void {
        global $DB;

        $student = $this->getDataGenerator()->create_and_enrol(
            get_course($this->mudeck->course),
            'student'
        );
        $this->setUser($student);
        $this->add_class_routes_to_route_loader(part_route::class);

        $response = $this->process_api_request(
            'DELETE',
            "/part/{$this->partids[1]}",
            body: Utils::streamFor(json_encode(['sesskey' => sesskey()])),
        );

        $this->assertGreaterThanOrEqual(400, $response->getStatusCode());
        $this->assertTrue($DB->record_exists('mudeck_part', ['id' => $this->partids[1]]));
    }

    public function test_a_delete_without_a_sesskey_is_refused(): void {
        global $DB;

        $this->add_class_routes_to_route_loader(part_route::class);

        $response = $this->process_api_request(
            'DELETE',
            "/part/{$this->partids[1]}",
            body: Utils::streamFor(json_encode([])),
        );

        $this->assertGreaterThanOrEqual(400, $response->getStatusCode());
        $this->assertTrue($DB->record_exists('mudeck_part', ['id' => $this->partids[1]]));
    }
}
