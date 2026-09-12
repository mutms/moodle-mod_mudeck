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
 * Restore task for the Markdown slide deck.
 *
 * @package    mod_mudeck
 * @copyright  2026 Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

// phpcs:disable moodle.Commenting.InlineComment.TypeHintingMatch
/** @var stdClass $CFG */
// phpcs:enable moodle.Commenting.InlineComment.TypeHintingMatch

require_once($CFG->dirroot . '/mod/mudeck/backup/moodle2/restore_mudeck_stepslib.php');

/**
 * Restore of one presentation.
 */
class restore_mudeck_activity_task extends restore_activity_task {
    #[\Override]
    protected function define_my_settings() {
        // No settings of our own.
    }

    #[\Override]
    protected function define_my_steps() {
        $this->add_step(new restore_mudeck_activity_structure_step('mudeck_structure', 'mudeck.xml'));
    }

    #[\Override]
    public static function define_decode_contents() {
        return [
            new restore_decode_content('mudeck', ['intro'], 'mudeck'),
        ];
    }

    #[\Override]
    public static function define_decode_rules() {
        return [
            new restore_decode_rule('MUDECKINDEX', '/mod/mudeck/index.php?id=$1', 'course'),
            new restore_decode_rule('MUDECKVIEWBYID', '/mod/mudeck/view.php?id=$1', 'course_module'),
        ];
    }
}
