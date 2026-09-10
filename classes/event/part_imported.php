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
 * Part of a presentation replaced by an uploaded archive.
 *
 * @package    mod_mudeck
 * @copyright  2026 Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_mudeck\event;

/**
 * Part of a presentation replaced by an uploaded archive.
 */
final class part_imported extends part_base {
    #[\Override]
    protected function init() {
        parent::init();
        $this->data['crud'] = 'u';
    }

    #[\Override]
    public static function get_name() {
        return get_string('event_part_imported', 'mod_mudeck');
    }

    #[\Override]
    public function get_description() {
        return "The user with id '$this->userid' imported an archive into the part '{$this->other['name']}' "
            . "of the presentation with course module id '$this->contextinstanceid'.";
    }
}
