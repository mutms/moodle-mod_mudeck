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

namespace mod_mudeck\route\api;

use core\param;
use core\router\require_login;
use core\router\route;
use core\router\schema\objects\scalar_type;
use core\router\schema\objects\schema_object;
use core\router\schema\parameters\path_parameter;
use core\router\schema\request_body;
use core\router\schema\response\content\payload_response_type;
use core\router\schema\response\payload_response;
use mod_mudeck\local\part;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Put a part at a given place in the presentation.
 *
 * Dragging a part and choosing its position from a list both end up here, saying where
 * it should be rather than which way it moved.
 *
 * @package    mod_mudeck
 * @copyright  2026 Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class part_move {
    /**
     * Move a part to a position, counted from one.
     *
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @param int $partid
     * @return payload_response the part ids in their new order
     */
    #[route(
        path: '/part/{partid}/move',
        method: ['POST'],
        pathtypes: [
            new path_parameter(name: 'partid', type: param::INT),
        ],
        requestbody: new request_body(
            content: new payload_response_type(
                schema: new schema_object(
                    content: [
                        'sesskey' => new scalar_type(param::ALPHANUM),
                        'position' => new scalar_type(param::INT),
                    ],
                ),
            ),
        ),
        requirelogin: new require_login(requirelogin: true, autologinguest: false),
    )]
    public function move(
        ServerRequestInterface $request,
        ResponseInterface $response,
        int $partid,
    ): payload_response {
        \core\router\util::require_sesskey($request);

        [$part, $mudeck] = part::require_editable($partid);

        $body = (array)$request->getParsedBody();
        part::move_to($part, (int)($body['position'] ?? 0));

        return new payload_response(
            payload: ['order' => part::get_order($mudeck->id)],
            request: $request,
            response: $response,
        );
    }
}
