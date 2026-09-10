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

/**
 * Add or edit one of the site's own Marp themes.
 */
final class theme_edit extends \moodleform {
    #[\Override]
    protected function definition() {
        $mform = $this->_form;

        $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);

        $mform->addElement('text', 'name', get_string('theme_name', 'mod_mudeck'), ['size' => '48']);
        $mform->setType('name', PARAM_TEXT);
        $mform->addRule('name', null, 'required', null, 'client');
        $mform->addRule('name', get_string('maximumchars', '', 255), 'maxlength', 255, 'client');
        $mform->addHelpButton('name', 'theme_name', 'mod_mudeck');

        $mform->addElement('text', 'shortname', get_string('theme_shortname', 'mod_mudeck'), ['size' => '24']);
        $mform->setType('shortname', PARAM_SAFEDIR);
        $mform->addRule('shortname', null, 'required', null, 'client');
        $mform->addHelpButton('shortname', 'theme_shortname', 'mod_mudeck');

        $mform->addElement(
            'textarea',
            'css',
            get_string('theme_css', 'mod_mudeck'),
            ['rows' => 25, 'cols' => 80, 'spellcheck' => 'false']
        );
        $mform->setType('css', PARAM_RAW);
        $mform->addRule('css', null, 'required', null, 'client');
        $mform->addHelpButton('css', 'theme_css', 'mod_mudeck');

        $this->add_action_buttons();
    }

    #[\Override]
    public function validation($data, $files) {
        global $DB;

        $errors = parent::validation($data, $files);

        $shortname = clean_param($data['shortname'], PARAM_SAFEDIR);
        if ($shortname === '' || $shortname !== $data['shortname']) {
            // PARAM_SAFEDIR is what Marp and the theme directive can carry: letters,
            // digits, underscore and dash, nothing else.
            $errors['shortname'] = get_string('theme_shortname_invalid', 'mod_mudeck');
        } else if (in_array($shortname, theme::get_reserved_names(), true)) {
            // Shadowing a shipped theme would leave nobody able to say which one they meant.
            $errors['shortname'] = get_string('theme_shortname_reserved', 'mod_mudeck');
        } else {
            $clash = $DB->get_record(theme::TABLE, ['shortname' => $shortname], 'id');
            if ($clash && (int)$clash->id !== (int)($data['id'] ?? 0)) {
                $errors['shortname'] = get_string('theme_shortname_taken', 'mod_mudeck');
            }
        }

        return $errors;
    }
}
