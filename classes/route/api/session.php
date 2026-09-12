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
use mod_mudeck\local\session as local_session;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Device sync session REST resource.
 *
 * A session is one running presentation, kept so the same user can follow it on
 * another device. Every route here is scoped to the session's owner: there is no way
 * to reach somebody else's session through them, not even for a teacher.
 *
 * @package    mod_mudeck
 * @copyright  2026 Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class session {
    /**
     * Read where the presentation is - what the notes view polls.
     *
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @param int $sessionid
     * @return payload_response
     */
    #[route(
        path: '/session/{sessionid}',
        method: ['GET'],
        pathtypes: [
            new path_parameter(name: 'sessionid', type: param::INT),
        ],
        requirelogin: new require_login(requirelogin: true, autologinguest: false),
    )]
    public function get(
        ServerRequestInterface $request,
        ResponseInterface $response,
        int $sessionid,
    ): payload_response {
        global $USER;

        $session = local_session::require_own($sessionid, $USER->id);

        return new payload_response(
            payload: [
                'partid' => (int)$session->partid,
                'slide' => (int)$session->slide,
                'slidetitle' => (string)$session->slidetitle,
                'parthash' => (string)$session->parthash,
                'timelastseen' => (int)$session->timelastseen,
                // A second screen following a talk that has finished should say so
                // rather than sit on the last slide as if it were still up there.
                'ended' => !empty($session->timeended),
                // How long the talk has been running, worked out here so the two devices
                // do not have to agree about what time it is.
                'elapsed' => max(0, time() - (int)$session->timestarted),
            ],
            request: $request,
            response: $response,
        );
    }

    /**
     * Report where the presentation is now - a partial update, only what moved.
     *
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @param int $sessionid
     * @return payload_response
     */
    #[route(
        path: '/session/{sessionid}',
        method: ['PATCH'],
        pathtypes: [
            new path_parameter(name: 'sessionid', type: param::INT),
        ],
        requestbody: new request_body(
            content: new payload_response_type(
                schema: new schema_object(
                    content: [
                        'sesskey' => new scalar_type(param::ALPHANUM),
                        'partid' => new scalar_type(param::INT),
                        'parthash' => new scalar_type(param::ALPHANUM),
                        'slide' => new scalar_type(param::INT),
                        'slidetitle' => new scalar_type(param::TEXT),
                    ],
                ),
            ),
        ),
        requirelogin: new require_login(requirelogin: true, autologinguest: false),
    )]
    public function patch(
        ServerRequestInterface $request,
        ResponseInterface $response,
        int $sessionid,
    ): payload_response {
        global $USER;

        \core\router\util::require_sesskey($request);

        $body = (array)$request->getParsedBody();
        $session = local_session::require_own($sessionid, $USER->id);

        local_session::move(
            $session,
            !empty($body['partid']) ? (int)$body['partid'] : null,
            isset($body['slide']) ? (int)$body['slide'] : null,
            isset($body['slidetitle']) ? clean_param((string)$body['slidetitle'], PARAM_TEXT) : null,
            isset($body['parthash']) ? clean_param((string)$body['parthash'], PARAM_ALPHANUM) : null,
        );

        return new payload_response(
            payload: ['success' => true],
            request: $request,
            response: $response,
        );
    }
}
