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
use core\router\route;
use core\router\schema\parameters\path_parameter;
use core\router\schema\objects\scalar_type;
use core\router\schema\objects\schema_object;
use core\router\schema\request_body;
use core\router\schema\response\payload_response;
use core\router\schema\response\content\payload_response_type;
use core\router\require_login;
use mod_mudeck\local\completed;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Completion by reaching the end, posted once by the viewer.
 *
 * @package    mod_mudeck
 * @copyright  2026 Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class presentation_reached_end {
    /**
     * The user watching this presentation has reached its last slide.
     *
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @param int $cmid
     * @return payload_response
     */
    #[route(
        path: '/presentation/{cmid}/reached-end',
        method: ['POST'],
        pathtypes: [
            new path_parameter(name: 'cmid', type: param::INT),
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
    public function reached(
        ServerRequestInterface $request,
        ResponseInterface $response,
        int $cmid,
    ): payload_response {
        global $DB, $USER;

        \core\router\util::require_sesskey($request);

        [$course, $cm] = get_course_and_cm_from_cmid($cmid, 'mudeck');
        if (!can_access_course($course) || !$cm->uservisible) {
            throw new \core\exception\require_login_exception('Course or activity not accessible.');
        }
        require_capability('mod/mudeck:view', $cm->context);
        require_capability('mod/mudeck:present', $cm->context);

        $mudeck = $DB->get_record('mudeck', ['id' => $cm->instance], '*', MUST_EXIST);
        if (completed::is_wanted($mudeck, $cm)) {
            completed::record($mudeck, $cm, (int)$USER->id);
        }

        return new payload_response(
            payload: ['success' => true],
            request: $request,
            response: $response,
        );
    }
}
