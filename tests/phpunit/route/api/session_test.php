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

/**
 * Device sync session REST resource test - reporting and reading a position.
 *
 * @package    mod_mudeck
 * @copyright  2026 Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * @covers \mod_mudeck\route\api\session
 */
final class session_test extends route_testcase {
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

    public function test_the_showing_device_reports_where_it_is(): void {
        global $DB;

        $this->setUser($this->teacher);
        $this->add_class_routes_to_route_loader(session_route::class);

        $response = $this->process_api_request(
            'PATCH',
            "/session/{$this->sessionid}",
            body: Utils::streamFor(json_encode([
                'sesskey' => sesskey(),
                'partid' => 12,
                'parthash' => sha1('# Hello'),
                'slide' => 4,
                'slidetitle' => 'Where we are',
            ])),
        );

        $this->assertEquals(200, $response->getStatusCode());
        $session = $DB->get_record('mudeck_session', ['id' => $this->sessionid], '*', MUST_EXIST);
        $this->assertEquals(4, $session->slide);
        $this->assertSame('Where we are', $session->slidetitle);
        // The part and what it held travel with the position, so the other device can
        // tell whether it is looking at the same slides.
        $this->assertEquals(12, $session->partid);
        $this->assertSame(sha1('# Hello'), $session->parthash);
    }

    public function test_the_other_device_reads_it_back(): void {
        $this->setUser($this->teacher);
        $this->add_class_routes_to_route_loader(session_route::class);

        session::move(
            (object)['id' => $this->sessionid],
            12,
            7,
            'Half way',
            sha1('# Hello')
        );

        $response = $this->process_api_request('GET', "/session/{$this->sessionid}");
        $payload = json_decode((string)$response->getBody(), true);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals(7, $payload['slide']);
        $this->assertSame('Half way', $payload['slidetitle']);
        $this->assertEquals(12, $payload['partid']);
        $this->assertSame(sha1('# Hello'), $payload['parthash']);
        // The clock on the speaker's second screen counts from here, not from the moment
        // that page was opened.
        $this->assertIsInt($payload['elapsed']);
        $this->assertGreaterThanOrEqual(0, $payload['elapsed']);
    }

    public function test_a_session_of_another_user_is_not_readable(): void {
        $stranger = $this->getDataGenerator()->create_and_enrol(
            get_course($this->mudeck->course),
            'editingteacher'
        );

        $this->setUser($stranger);
        $this->add_class_routes_to_route_loader(session_route::class);

        $response = $this->process_api_request('GET', "/session/{$this->sessionid}");
        $this->assertGreaterThanOrEqual(400, $response->getStatusCode());
    }

    public function test_a_report_without_a_sesskey_is_refused(): void {
        global $DB;

        $this->setUser($this->teacher);
        $this->add_class_routes_to_route_loader(session_route::class);

        $response = $this->process_api_request(
            'PATCH',
            "/session/{$this->sessionid}",
            body: Utils::streamFor(json_encode(['slide' => 2])),
        );

        $this->assertGreaterThanOrEqual(400, $response->getStatusCode());
        $this->assertEquals(0, $DB->get_field('mudeck_session', 'slide', ['id' => $this->sessionid]));
    }
}
