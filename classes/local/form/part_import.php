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

use tool_mulib\muform\element\buttons;
use tool_mulib\muform\element\cancel;
use tool_mulib\muform\element\filemanager;
use tool_mulib\muform\element\inforawhtml;
use tool_mulib\muform\element\submit;
use tool_mulib\muform\form;

/**
 * Upload the slides, as Markdown or as a zip with their pictures.
 */
final class part_import extends form {
    #[\Override]
    protected function definition(): void {
        $this->add(new inforawhtml('description', '', get_string('import_intro', 'mod_mudeck')));

        // No accepted types: Moodle has no file type for Markdown, and restricting the
        // picker to an extension it does not know makes it refuse the upload outright.
        $archive = new filemanager('archive', get_string('import_file', 'mod_mudeck'), 1);
        $archive->set_required(true);
        $this->add($archive);

        $this->add(new buttons('buttons'));
        $this->add(new submit('submit', get_string('import_submit', 'mod_mudeck')), 'buttons');
        $this->add(new cancel(), 'buttons');
    }
}
