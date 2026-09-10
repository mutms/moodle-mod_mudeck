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
 * Device sync REST routes.
 *
 * @package    mod_mudeck
 * @copyright  2026 Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

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
use mod_mudeck\local\session;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Where a running presentation is, written by the device showing it and read by the others.
 *
 * Both routes are scoped to the session's owner, so there is no way to watch somebody
 * else's presentation through them - not even for a teacher.
 */
class session_position {
    /**
     * Report where the presentation is now.
     *
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @param int $sessionid
     * @return payload_response
     */
    #[route(
        path: '/session/{sessionid}',
        method: ['POST'],
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
    public function move(
        ServerRequestInterface $request,
        ResponseInterface $response,
        int $sessionid,
    ): payload_response {
        \core\router\util::require_sesskey($request);

        $body = (array)$request->getParsedBody();
        $session = $this->get_own_session($sessionid);

        session::move(
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

    /**
     * The presentation has been left.
     *
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @param int $sessionid
     * @return payload_response
     */
    #[route(
        path: '/session/{sessionid}/end',
        method: ['POST'],
        pathtypes: [
            new path_parameter(name: 'sessionid', type: param::INT),
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
    public function finish(
        ServerRequestInterface $request,
        ResponseInterface $response,
        int $sessionid,
    ): payload_response {
        \core\router\util::require_sesskey($request);

        session::end($this->get_own_session($sessionid));

        return new payload_response(
            payload: ['success' => true],
            request: $request,
            response: $response,
        );
    }

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
    public function position(
        ServerRequestInterface $request,
        ResponseInterface $response,
        int $sessionid,
    ): payload_response {
        $session = $this->get_own_session($sessionid);

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
     * The session, if it is the current user's and device sync is still allowed.
     *
     * Synced notes are a full access feature, so both ends check the same capability -
     * a device that may not see the notes has no business following the slides either.
     *
     * @param int $sessionid
     * @return \stdClass
     */
    private function get_own_session(int $sessionid): \stdClass {
        global $DB, $USER;

        $session = session::get_own_one($sessionid, $USER->id);
        if (!$session) {
            throw new \core\exception\moodle_exception('invalidrecord', 'error');
        }

        $mudeck = $DB->get_record('mudeck', ['id' => $session->mudeckid], '*', MUST_EXIST);
        $cm = get_coursemodule_from_instance('mudeck', $mudeck->id, $mudeck->course, false, MUST_EXIST);
        $context = \context_module::instance($cm->id);

        require_capability('mod/mudeck:syncdevices', $context);
        require_capability('mod/mudeck:fullaccess', $context);
        if (!$mudeck->allowdevicesync) {
            throw new \core\exception\moodle_exception('nopermissions', 'error', '', 'device sync');
        }

        return $session;
    }
}
