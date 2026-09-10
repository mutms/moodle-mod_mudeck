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
 * Custom completion rules.
 *
 * @package    mod_mudeck
 * @copyright  2026 Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_mudeck\completion;

use core_completion\activity_custom_completion;

/**
 * Reaching the last slide, beside the standard rule of having started the presentation.
 */
class custom_completion extends activity_custom_completion {
    #[\Override]
    public function get_state(string $rule): int {
        global $DB;

        $this->validate_rule($rule);

        $reached = $DB->record_exists('mudeck_completed', [
            'mudeckid' => $this->cm->instance,
            'userid' => $this->userid,
        ]);
        return $reached ? COMPLETION_COMPLETE : COMPLETION_INCOMPLETE;
    }

    #[\Override]
    public static function get_defined_custom_rules(): array {
        return ['completionreachedend'];
    }

    #[\Override]
    public function get_custom_rule_descriptions(): array {
        return [
            'completionreachedend' => get_string('completiondetail:reachedend', 'mod_mudeck'),
        ];
    }

    #[\Override]
    public function get_sort_order(): array {
        return [
            'completionview',
            'completionreachedend',
        ];
    }
}
