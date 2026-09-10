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

/**
 * The pictures an author has to hand while writing.
 *
 * @package    mod_mudeck
 * @copyright  2026 Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_mudeck\route\api;

use core\param;
use core\router\route;
use core\router\require_login;
use core\router\schema\parameters\path_parameter;
use core\router\schema\response\payload_response;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * The images sitting in a draft file area, so the editor can offer them by name.
 *
 * A draft area belongs to the user who owns it, and this only ever looks in the caller's
 * own user context - an id belonging to somebody else simply finds nothing there.
 */
class draft_images {
    /**
     * Every image in one draft area, by the name the Markdown would use.
     *
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @param int $draftitemid the file manager's draft area
     * @return payload_response
     */
    #[route(
        path: '/draft/{draftitemid}/images',
        method: ['GET'],
        pathtypes: [
            new path_parameter(name: 'draftitemid', type: param::INT),
        ],
        requirelogin: new require_login(requirelogin: true, autologinguest: false),
    )]
    public function images(
        ServerRequestInterface $request,
        ResponseInterface $response,
        int $draftitemid,
    ): payload_response {
        global $USER;

        $files = [];
        $fs = get_file_storage();
        $usercontext = \context_user::instance($USER->id);
        $stored = $fs->get_area_files($usercontext->id, 'user', 'draft', $draftitemid, 'filepath, filename', false);
        foreach ($stored as $file) {
            // Only pictures: everything else in the area is of no use inside an image.
            if (!$file->is_valid_image()) {
                continue;
            }
            // Subdirectories included, written the way a slide refers to them.
            $files[] = ltrim($file->get_filepath(), '/') . $file->get_filename();
        }

        return new payload_response(
            payload: ['files' => $files],
            request: $request,
            response: $response,
        );
    }
}
