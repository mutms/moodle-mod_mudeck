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
 * Shared parent of the events about one part of a presentation.
 *
 * @package    mod_mudeck
 * @copyright  2026 Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_mudeck\event;

use stdClass;

/**
 * Everything the part events have in common - only the verb differs.
 */
abstract class part_base extends \core\event\base {
    /**
     * Create instance of event.
     *
     * @param stdClass $part
     * @param \context_module $context
     * @return static
     */
    public static function create_from_part(stdClass $part, \context_module $context): static {
        /** @var static $event */
        $event = static::create([
            'context' => $context,
            'objectid' => $part->id,
            'other' => ['name' => $part->name],
        ]);
        $event->add_record_snapshot('mudeck_part', $part);
        return $event;
    }

    #[\Override]
    protected function init() {
        $this->data['edulevel'] = self::LEVEL_TEACHING;
        $this->data['objecttable'] = 'mudeck_part';
    }

    #[\Override]
    public function get_url() {
        return new \core\url('/mod/mudeck/management/overview.php', ['cmid' => $this->contextinstanceid]);
    }

    #[\Override]
    public static function get_objectid_mapping() {
        return ['db' => 'mudeck_part', 'restore' => 'mudeck_part'];
    }

    #[\Override]
    public static function get_other_mapping() {
        return ['name' => \core\event\base::NOT_MAPPED];
    }
}
