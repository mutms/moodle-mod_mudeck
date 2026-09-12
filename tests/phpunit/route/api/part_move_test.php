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
use mod_mudeck\route\api\part_move;

/**
 * Part move REST route test - putting a part at a given place.
 *
 * @package    mod_mudeck
 * @copyright  2026 Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * @covers \mod_mudeck\route\api\part_move
 */
final class part_move_test extends route_testcase {
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

    public function test_a_part_moves_to_the_place_it_is_given(): void {
        $this->add_class_routes_to_route_loader(part_move::class);

        // The last part becomes the first.
        $response = $this->process_api_request(
            'POST',
            "/part/{$this->partids[2]}/move",
            body: Utils::streamFor(json_encode(['sesskey' => sesskey(), 'position' => 1])),
        );

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertSame(
            [$this->partids[2], $this->partids[0], $this->partids[1]],
            part::get_order($this->mudeck->id)
        );
        // What the browser is told matches what was stored.
        $payload = json_decode((string)$response->getBody(), true);
        $this->assertSame(part::get_order($this->mudeck->id), $payload['order']);
    }

    public function test_a_position_outside_the_list_changes_nothing(): void {
        $this->add_class_routes_to_route_loader(part_move::class);

        $this->process_api_request(
            'POST',
            "/part/{$this->partids[0]}/move",
            body: Utils::streamFor(json_encode(['sesskey' => sesskey(), 'position' => 9])),
        );

        $this->assertSame($this->partids, part::get_order($this->mudeck->id));
    }

    public function test_somebody_who_may_not_edit_cannot_reorder(): void {
        $student = $this->getDataGenerator()->create_and_enrol(
            get_course($this->mudeck->course),
            'student'
        );
        $this->setUser($student);
        $this->add_class_routes_to_route_loader(part_move::class);

        $response = $this->process_api_request(
            'POST',
            "/part/{$this->partids[2]}/move",
            body: Utils::streamFor(json_encode(['sesskey' => sesskey(), 'position' => 1])),
        );

        $this->assertGreaterThanOrEqual(400, $response->getStatusCode());
        $this->assertSame($this->partids, part::get_order($this->mudeck->id));
    }

    public function test_a_move_without_a_sesskey_is_refused(): void {
        $this->add_class_routes_to_route_loader(part_move::class);

        $response = $this->process_api_request(
            'POST',
            "/part/{$this->partids[2]}/move",
            body: Utils::streamFor(json_encode(['position' => 1])),
        );

        $this->assertGreaterThanOrEqual(400, $response->getStatusCode());
        $this->assertSame($this->partids, part::get_order($this->mudeck->id));
    }
}
