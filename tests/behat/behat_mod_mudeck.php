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
 * Behat page resolvers for the Markdown slide deck.
 *
 * @package    mod_mudeck
 * @copyright  2026 Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class behat_mod_mudeck extends behat_base {
    /**
     * Pages of one presentation, named after the presentation.
     *
     * Recognised page types:
     *   Overview   - the parts a presentation is made of
     *   Present    - the presentation itself
     *
     * @param string $type
     * @param string $identifier the presentation name
     * @return moodle_url
     */
    protected function resolve_page_instance_url(string $type, string $identifier): moodle_url {
        $cm = $this->get_cm_by_mudeck_name($identifier);

        switch (strtolower($type)) {
            case 'overview':
                return new moodle_url('/mod/mudeck/management/overview.php', ['cmid' => $cm->id]);
            case 'present':
                return new moodle_url('/mod/mudeck/present.php', ['id' => $cm->id]);
            default:
                throw new Exception("Unrecognised mudeck page type '{$type}'");
        }
    }

    /**
     * The course module of a presentation, found by its name.
     *
     * @param string $name
     * @return cm_info
     */
    protected function get_cm_by_mudeck_name(string $name): cm_info {
        global $DB;

        $mudeck = $DB->get_record('mudeck', ['name' => $name], '*', MUST_EXIST);
        return get_fast_modinfo($mudeck->course)->instances['mudeck'][$mudeck->id];
    }
}
