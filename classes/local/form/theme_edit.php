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
 * Site theme editing form.
 *
 * @package    mod_mudeck
 * @copyright  2026 Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_mudeck\local\form;

use mod_mudeck\local\theme;
use tool_mulib\muform\element\buttons;
use tool_mulib\muform\element\cancel;
use tool_mulib\muform\element\submit;
use tool_mulib\muform\element\text;
use tool_mulib\muform\element\textarea;
use tool_mulib\muform\form;

/**
 * Add or edit one of the site's own Marp themes.
 */
final class theme_edit extends form {
    #[\Override]
    protected function definition(): void {
        $name = new text('name', get_string('theme_name', 'mod_mudeck'), ['maxlength' => 255]);
        $name->set_required(true);
        $name->add_help_button('theme_name', 'mod_mudeck');
        $this->add($name);

        $shortname = new text('shortname', get_string('theme_shortname', 'mod_mudeck'), ['width' => 'medium']);
        $shortname->set_required(true);
        $shortname->add_help_button('theme_shortname', 'mod_mudeck');
        $this->add($shortname);

        $css = new textarea('css', get_string('theme_css', 'mod_mudeck'), ['type' => 'rawtext', 'rows' => 25]);
        $css->set_required(true);
        $css->add_help_button('theme_css', 'mod_mudeck');
        $this->add($css);

        $this->add(new buttons('buttons'));
        $this->add(new submit(), 'buttons');
        $this->add(new cancel(), 'buttons');
    }

    #[\Override]
    protected function validation(array $data, array &$allerrors): void {
        global $DB;

        $shortname = clean_param($data['shortname'], PARAM_SAFEDIR);
        if ($shortname === '' || $shortname !== $data['shortname']) {
            // PARAM_SAFEDIR is what Marp and the theme directive can carry: letters,
            // digits, underscore and dash, nothing else.
            $allerrors['shortname'][] = get_string('theme_shortname_invalid', 'mod_mudeck');
        } else if (in_array($shortname, theme::get_reserved_names(), true)) {
            // Shadowing a shipped theme would leave nobody able to say which one they meant.
            $allerrors['shortname'][] = get_string('theme_shortname_reserved', 'mod_mudeck');
        } else {
            $clash = $DB->get_record(theme::TABLE, ['shortname' => $shortname], 'id');
            if ($clash && (int)$clash->id !== (int)($this->get_extra_data()['id'] ?? 0)) {
                $allerrors['shortname'][] = get_string('theme_shortname_taken', 'mod_mudeck');
            }
        }
    }
}
