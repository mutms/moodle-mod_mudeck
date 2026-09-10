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
 * Part editing form.
 *
 * @package    mod_mudeck
 * @copyright  2026 Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_mudeck\local\form;

use mod_mudeck\local\media;

/**
 * Edit the Markdown of one part with preview of the right.
 */
final class part_edit extends \moodleform {
    #[\Override]
    protected function definition() {
        $mform = $this->_form;

        $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);

        // Position of cursor before save, so that it can be restored
        // when continuing editing after save.
        $mform->addElement('hidden', 'caretstart');
        $mform->setType('caretstart', PARAM_INT);
        $mform->addElement('hidden', 'caretend');
        $mform->setType('caretend', PARAM_INT);

        $mform->addElement('text', 'name', get_string('part_name', 'mod_mudeck'), ['size' => '48', 'maxlength' => '255']);
        $mform->setType('name', PARAM_TEXT);
        $mform->addRule('name', null, 'required', null, 'client');
        $mform->addRule('name', get_string('maximumchars', '', 255), 'maxlength', 255, 'client');

        $mform->addElement(
            'textarea',
            'content',
            get_string('part_content', 'mod_mudeck'),
            ['rows' => 25, 'cols' => 80, 'spellcheck' => 'true']
        );
        $mform->setType('content', PARAM_RAW);
        $mform->addHelpButton('content', 'part_content', 'mod_mudeck');

        $mform->addElement(
            'filemanager',
            'attachments',
            get_string('part_media', 'mod_mudeck'),
            null,
            media::get_filemanager_options()
        );

        $buttons = [
            $mform->createElement(
                'submit',
                'saveandclose',
                get_string('part_save_close', 'mod_mudeck'),
                null,
                false
            ),
            $mform->createElement(
                'submit',
                'saveandcontinue',
                get_string('part_save_continue', 'mod_mudeck')
            ),
            // Empty on purpose: this just makes the button groups separate.
            $mform->createElement('static', 'buttongap', '', '<span class="flex-fill"></span>'),
            $mform->createElement('cancel'),
        ];
        $mform->addGroup($buttons, 'buttonar', '', ' ', false);
    }

    #[\Override]
    public function validation($data, $files) {
        $errors = parent::validation($data, $files);

        $missing = media::find_missing_draft($data['content'], (int)$data['attachments']);
        if ($missing) {
            $errors['attachments'] = get_string('part_media_missing', 'mod_mudeck', implode(', ', $missing));
        }

        return $errors;
    }
}
