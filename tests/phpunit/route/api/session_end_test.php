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
use mod_mudeck\local\session;
use mod_mudeck\route\api\session as session_route;
use mod_mudeck\route\api\session_end;

/**
 * Session end REST route test.
 *
 * @package    mod_mudeck
 * @copyright  2026 Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * @covers \mod_mudeck\route\api\session_end
 */
final class session_end_test extends route_testcase {
    /** @var \stdClass the presentation under test */
    private \stdClass $mudeck;
    /** @var \stdClass the presenter */
    private \stdClass $teacher;
    /** @var int the presenter's session */
    private int $sessionid;

    public function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();

        $course = $this->getDataGenerator()->create_course();
        $this->mudeck = $this->getDataGenerator()->create_module(
            'mudeck',
            ['course' => $course->id, 'allowdevicesync' => 1]
        );
        $this->teacher = $this->getDataGenerator()->create_and_enrol($course, 'editingteacher');
        $this->sessionid = session::start($this->mudeck->id, $this->teacher->id);
    }

    public function test_leaving_the_presentation_ends_the_session(): void {
        global $DB;

        $this->setUser($this->teacher);
        $this->add_class_routes_to_route_loader(session_end::class);
        // Reading the result back goes through the resource itself.
        $this->add_class_routes_to_route_loader(session_route::class);

        $response = $this->process_api_request(
            'POST',
            "/session/{$this->sessionid}/end",
            body: Utils::streamFor(json_encode(['sesskey' => sesskey()])),
        );

        $this->assertEquals(200, $response->getStatusCode());
        $session = $DB->get_record('mudeck_session', ['id' => $this->sessionid], '*', MUST_EXIST);
        $this->assertNotEmpty($session->timeended);
        // A session that has ended is not one another device should still be following.
        $this->assertFalse(session::is_live($session));

        $reading = $this->process_api_request('GET', "/session/{$this->sessionid}");
        $payload = json_decode((string)$reading->getBody(), true);
        $this->assertTrue($payload['ended']);
    }
}
