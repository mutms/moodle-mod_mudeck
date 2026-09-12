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
 * Device sync sessions.
 *
 * @package    mod_mudeck
 * @copyright  2026 Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_mudeck\local;

use stdClass;

/**
 * A session is one running presentation, so the same user can follow it on another device.
 *
 * It is never a record of what somebody else watched: every query here is scoped to one
 * user, and the only way to see another person's session is to be that person.
 */
final class session {
    /** Nobody talks about one slide for half an hour, so a quiet session is over. */
    public const STALEAFTER = 30 * MINSECS;

    /**
     * Is this session still worth following on another device?
     *
     * Three ways a session is done with: it went quiet, it reached the end of the
     * presentation, or the slides were edited underneath it - in the last case the other
     * device cannot show what is on screen, so offering it notes would be a lie.
     *
     * @param stdClass $session
     * @return bool
     */
    public static function is_live(stdClass $session): bool {
        global $DB;

        if (!empty($session->timeended) || time() - $session->timelastseen > self::STALEAFTER) {
            return false;
        }
        if (empty($session->partid) || empty($session->slide)) {
            // Started, but nothing shown yet - the presenter is still on the first slide.
            return true;
        }

        $part = $DB->get_record('mudeck_part', ['id' => $session->partid, 'mudeckid' => $session->mudeckid]);
        if (!$part || $part->contenthash !== $session->parthash) {
            return false;
        }

        // The end of the last part is the end of the show.
        $parts = part::get_playable((int)$session->mudeckid);
        $last = $parts ? end($parts) : null;
        return !($last && (int)$last->id === (int)$session->partid && $session->slide >= part::count_slides($part));
    }

    /**
     * Can another device follow this session yet?
     *
     * A session that has not said where it is has nothing to follow - it still counts as
     * running, it just cannot be synced to until the first slide is reported.
     *
     * @param stdClass $session
     * @return bool
     */
    public static function is_followable(stdClass $session): bool {
        return !empty($session->partid) && !empty($session->slide) && self::is_live($session);
    }

    /**
     * Delete the sessions of one user that are over.
     *
     * @param int $mudeckid
     * @param int $userid
     * @return int how many were deleted
     */
    public static function delete_finished(int $mudeckid, int $userid): int {
        $deleted = 0;
        foreach (self::get_own($mudeckid, $userid) as $session) {
            if (!self::is_live($session)) {
                self::delete_own((int)$session->id, $userid);
                $deleted++;
            }
        }
        return $deleted;
    }

    /**
     * Start a session - one per press of the start button.
     *
     * Reusing a session that is already open would be an optimisation; for now every
     * show is its own row and the user deletes what they no longer want.
     *
     * @param int $mudeckid
     * @param int $userid
     * @return int new session id
     */
    public static function start(int $mudeckid, int $userid): int {
        global $DB;

        $now = time();
        return $DB->insert_record('mudeck_session', (object)[
            'mudeckid' => $mudeckid,
            'userid' => $userid,
            'timestarted' => $now,
            'timelastseen' => $now,
        ]);
    }

    /**
     * The show is over.
     *
     * Leaving the presentation says so, rather than letting the session look live for
     * half an hour after the speaker has walked away from the lectern.
     *
     * @param stdClass $session
     */
    public static function end(stdClass $session): void {
        global $DB;

        if (!empty($session->timeended)) {
            return;
        }
        $now = time();
        $DB->set_field_select(
            'mudeck_session',
            'timeended',
            $now,
            'id = ? AND timeended IS NULL',
            [$session->id]
        );
        $DB->set_field('mudeck_session', 'timelastseen', $now, ['id' => $session->id]);
    }

    /**
     * One session, but only if it belongs to the given user.
     *
     * @param int $sessionid
     * @param int $userid
     * @return stdClass|null
     */
    public static function get_own_one(int $sessionid, int $userid): ?stdClass {
        global $DB;
        $record = $DB->get_record('mudeck_session', ['id' => $sessionid, 'userid' => $userid]);
        return $record ?: null;
    }

    /**
     * The session, if it is the given user's and device sync is still allowed.
     *
     * Synced notes are a full access feature, so both ends check the same capability -
     * a device that may not see the notes has no business following the slides either.
     *
     * @param int $sessionid
     * @param int $userid
     * @return stdClass
     */
    public static function require_own(int $sessionid, int $userid): stdClass {
        global $DB;

        $session = self::get_own_one($sessionid, $userid);
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

    /**
     * Record where the presentation is now.
     *
     * @param stdClass $session
     * @param int|null $partid
     * @param int|null $slide 1-based within the whole show
     * @param string|null $slidetitle
     * @param string|null $parthash
     */
    public static function move(
        stdClass $session,
        ?int $partid,
        ?int $slide,
        ?string $slidetitle,
        ?string $parthash
    ): void {
        global $DB;

        $DB->update_record('mudeck_session', (object)[
            'id' => $session->id,
            'partid' => $partid,
            'slide' => $slide,
            'slidetitle' => $slidetitle,
            'parthash' => $parthash,
            'timelastseen' => time(),
        ]);
    }

    /**
     * Sessions of one user in one presentation, newest first.
     *
     * @param int $mudeckid
     * @param int $userid
     * @return stdClass[] keyed by session id
     */
    public static function get_own(int $mudeckid, int $userid): array {
        global $DB;
        return $DB->get_records('mudeck_session', ['mudeckid' => $mudeckid, 'userid' => $userid], 'timestarted DESC, id DESC');
    }

    /**
     * Delete one session, but only if it belongs to the given user.
     *
     * @param int $sessionid
     * @param int $userid
     * @return bool true when something was deleted
     */
    public static function delete_own(int $sessionid, int $userid): bool {
        global $DB;

        if (!$DB->record_exists('mudeck_session', ['id' => $sessionid, 'userid' => $userid])) {
            return false;
        }
        $DB->delete_records('mudeck_session', ['id' => $sessionid, 'userid' => $userid]);
        return true;
    }

    /**
     * Delete every session of a presentation.
     *
     * Used when device sync is switched off - without the feature there is nothing to
     * show the rows in, so keeping them would only be data nobody asked for.
     *
     * @param int $mudeckid
     */
    public static function delete_all(int $mudeckid): void {
        global $DB;
        $DB->delete_records('mudeck_session', ['mudeckid' => $mudeckid]);
    }
}
