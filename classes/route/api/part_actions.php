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
 * Reordering parts from the overview.
 *
 * @package    mod_mudeck
 * @copyright  2026 Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

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
 * What the overview does to a part without leaving the page.
 *
 * Dragging a part and choosing its position from a list both end up here, saying where it
 * should be rather than which way it moved; deleting says so outright.
 */
class part_actions {
    /**
     * Delete a part, with everything that belongs to it.
     *
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @param int $partid
     * @return payload_response
     */
    #[route(
        path: '/part/{partid}/delete',
        method: ['POST'],
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
        global $DB;

        \core\router\util::require_sesskey($request);

        [$part, $mudeck, $context] = $this->find($partid);
        part::delete($part, $context);

        return new payload_response(
            payload: ['order' => $this->order($mudeck->id)],
            request: $request,
            response: $response,
        );
    }

    /**
     * Put a part at a given place in the presentation.
     *
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @param int $partid
     * @return payload_response
     */
    #[route(
        path: '/part/{partid}/position',
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
        global $DB;

        \core\router\util::require_sesskey($request);

        [$part, $mudeck] = $this->find($partid);

        $body = (array)$request->getParsedBody();
        part::move_to($part, (int)($body['position'] ?? 0));

        return new payload_response(
            payload: ['order' => $this->order($mudeck->id)],
            request: $request,
            response: $response,
        );
    }

    /**
     * The part, its presentation and its context - if the caller may edit it.
     *
     * Login is the route's business (see the attributes); this is about permission.
     *
     * @param int $partid
     * @return array{\stdClass, \stdClass, \context_module}
     */
    private function find(int $partid): array {
        global $DB;

        $part = $DB->get_record('mudeck_part', ['id' => $partid], '*', MUST_EXIST);
        $mudeck = $DB->get_record('mudeck', ['id' => $part->mudeckid], '*', MUST_EXIST);
        $cm = get_coursemodule_from_instance('mudeck', $mudeck->id, $mudeck->course, false, MUST_EXIST);
        $context = \context_module::instance($cm->id);

        require_capability('mod/mudeck:edit', $context);

        return [$part, $mudeck, $context];
    }

    /**
     * The parts of a presentation, in the order they are shown.
     *
     * @param int $mudeckid
     * @return int[]
     */
    private function order(int $mudeckid): array {
        return array_values(array_map(fn($one) => (int)$one->id, part::get_all($mudeckid)));
    }
}
