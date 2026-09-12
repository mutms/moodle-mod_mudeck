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
use mod_mudeck\route\api\part_edit_images;

/**
 * Pictures offered while editing a part.
 *
 * @package    mod_mudeck
 * @copyright  2026 Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * @covers \mod_mudeck\route\api\part_edit_images
 */
final class part_edit_images_test extends route_testcase {
    public function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
    }

    /**
     * Put a file into a user's draft area.
     *
     * @param \stdClass $user
     * @param int $draftitemid
     * @param string $filename
     * @param string $filepath
     * @param string $content
     */
    private function add_draft_file(
        \stdClass $user,
        int $draftitemid,
        string $filename,
        string $filepath = '/',
        string $content = 'x'
    ): void {
        get_file_storage()->create_file_from_string([
            'contextid' => \context_user::instance($user->id)->id,
            'component' => 'user',
            'filearea' => 'draft',
            'itemid' => $draftitemid,
            'filepath' => $filepath,
            'filename' => $filename,
        ], $content);
    }

    /**
     * A real picture, small enough to keep in a test.
     *
     * @return string
     */
    private function gif(): string {
        return base64_decode('R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7');
    }

    public function test_only_the_pictures_are_offered(): void {
        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);
        $this->add_class_routes_to_route_loader(part_edit_images::class);

        $draftitemid = file_get_unused_draft_itemid();
        $this->add_draft_file($user, $draftitemid, 'photo.gif', '/', $this->gif());
        $this->add_draft_file($user, $draftitemid, 'city.gif', '/pictures/', $this->gif());
        // Not a picture, so it has no business inside an image.
        $this->add_draft_file($user, $draftitemid, 'slides.md', '/', '# Hello');

        $response = $this->process_api_request('GET', "/part/edit/{$draftitemid}/images");

        $this->assertEquals(200, $response->getStatusCode());
        $payload = json_decode((string)$response->getBody(), true);
        // Subdirectories included, written the way the Markdown refers to them.
        $this->assertSame(['photo.gif', 'pictures/city.gif'], $payload['files']);
    }

    public function test_somebody_elses_draft_area_holds_nothing(): void {
        $owner = $this->getDataGenerator()->create_user();
        $other = $this->getDataGenerator()->create_user();

        $this->setUser($owner);
        $draftitemid = file_get_unused_draft_itemid();
        $this->add_draft_file($owner, $draftitemid, 'photo.gif', '/', $this->gif());

        $this->setUser($other);
        $this->add_class_routes_to_route_loader(part_edit_images::class);

        $response = $this->process_api_request('GET', "/part/edit/{$draftitemid}/images");

        $this->assertEquals(200, $response->getStatusCode());
        $payload = json_decode((string)$response->getBody(), true);
        // The area is looked for in the caller's own context, so the id finds nothing.
        $this->assertSame([], $payload['files']);
    }
}
