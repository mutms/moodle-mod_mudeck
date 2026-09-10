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
 * Reaching the end of a presentation.
 *
 * @package    mod_mudeck
 * @copyright  2026 Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_mudeck\local;

use cm_info;
use completion_info;
use stdClass;

/**
 * Who reached the last slide, written once and never recomputed.
 */
final class completed {
    /**
     * Is reaching the end worth recording for this user?
     *
     * Only when the activity asks for it: recording everybody's progress for a rule
     * nobody switched on would be data kept for no reason.
     *
     * @param stdClass $mudeck
     * @param cm_info $cm
     * @return bool
     */
    public static function is_wanted(stdClass $mudeck, cm_info $cm): bool {
        if (empty($mudeck->completionreachedend) || isguestuser()) {
            return false;
        }
        $completion = new completion_info($cm->get_course());
        return $completion->is_enabled($cm) == COMPLETION_TRACKING_AUTOMATIC;
    }

    /**
     * The user reached the last slide.
     *
     * @param stdClass $mudeck
     * @param cm_info $cm
     * @param int $userid
     */
    public static function record(stdClass $mudeck, cm_info $cm, int $userid): void {
        global $DB;

        if (!$DB->record_exists('mudeck_completed', ['mudeckid' => $mudeck->id, 'userid' => $userid])) {
            $DB->insert_record('mudeck_completed', (object)[
                'mudeckid' => $mudeck->id,
                'userid' => $userid,
                'timecompleted' => time(),
            ]);
        }

        $completion = new completion_info($cm->get_course());
        if ($completion->is_enabled($cm) == COMPLETION_TRACKING_AUTOMATIC && !empty($mudeck->completionreachedend)) {
            $completion->update_state($cm, COMPLETION_COMPLETE, $userid);
        }
    }
}
