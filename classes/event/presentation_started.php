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
 * Slides put on the screen.
 *
 * @package    mod_mudeck
 * @copyright  2026 Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_mudeck\event;

use stdClass;

/**
 * The presentation was started - by a teacher presenting or a learner watching.
 */
final class presentation_started extends \core\event\base {
    /**
     * Create instance of event.
     *
     * @param stdClass $mudeck
     * @param \context_module $context
     * @return self
     */
    public static function create_from_mudeck(stdClass $mudeck, \context_module $context): self {
        /** @var self $event */
        $event = self::create([
            'context' => $context,
            'objectid' => $mudeck->id,
        ]);
        $event->add_record_snapshot('mudeck', $mudeck);
        return $event;
    }

    #[\Override]
    protected function init() {
        $this->data['crud'] = 'r';
        $this->data['edulevel'] = self::LEVEL_PARTICIPATING;
        $this->data['objecttable'] = 'mudeck';
    }

    #[\Override]
    public static function get_name() {
        return get_string('event_presentation_started', 'mod_mudeck');
    }

    #[\Override]
    public function get_description() {
        return "The user with id '$this->userid' started the presentation "
            . "with course module id '$this->contextinstanceid'.";
    }

    #[\Override]
    public function get_url() {
        return new \core\url('/mod/mudeck/present.php', ['id' => $this->contextinstanceid]);
    }

    #[\Override]
    public static function get_objectid_mapping() {
        return ['db' => 'mudeck', 'restore' => 'mudeck'];
    }
}
