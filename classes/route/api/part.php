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
use mod_mudeck\local\part as local_part;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Part REST resource.
 *
 * A part is one Markdown document of a presentation, the unit of editing and ordering.
 * Every route here requires the capability to edit the presentation it belongs to.
 *
 * @package    mod_mudeck
 * @copyright  2026 Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class part {
    /**
     * Delete a part, with everything that belongs to it.
     *
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @param int $partid
     * @return payload_response the remaining part ids, in order
     */
    #[route(
        path: '/part/{partid}',
        method: ['DELETE'],
        pathtypes: [
            new path_parameter(name: 'partid', type: param::INT),
        ],
        requestbody: new request_body(
            content: new payload_response_type(
                schema: new schema_object(
                    content: [
                        'sesskey' => new scalar_type(param::ALPHANUM),
                    ],
                ),
            ),
        ),
        requirelogin: new require_login(requirelogin: true, autologinguest: false),
    )]
    public function delete(
        ServerRequestInterface $request,
        ResponseInterface $response,
        int $partid,
    ): payload_response {
        \core\router\util::require_sesskey($request);

        [$part, $mudeck, $context] = local_part::require_editable($partid);
        local_part::delete($part, $context);

        return new payload_response(
            payload: ['order' => local_part::get_order($mudeck->id)],
            request: $request,
            response: $response,
        );
    }
}
