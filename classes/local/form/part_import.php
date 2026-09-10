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
 * Archive import form.
 *
 * @package    mod_mudeck
 * @copyright  2026 Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_mudeck\local\form;

/**
 * Upload the slides, as Markdown or as a zip with their pictures.
 */
final class part_import extends \moodleform {
    #[\Override]
    protected function definition() {
        $mform = $this->_form;

        $mform->addElement('static', 'description', '', get_string('import_intro', 'mod_mudeck'));

        $mform->addElement(
            'filemanager',
            'archive',
            get_string('import_file', 'mod_mudeck'),
            null,
            // No accepted_types: Moodle has no file type for Markdown, and restricting the
            // picker to an extension it does not know makes it refuse the upload outright.
            ['maxfiles' => 1, 'subdirs' => 0]
        );

        $this->add_action_buttons(true, get_string('import_submit', 'mod_mudeck'));
    }
}
