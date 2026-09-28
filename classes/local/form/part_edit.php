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

use core\param;
use mod_mudeck\local\media;
use mod_mudeck\muform\element\partcontainer;
use tool_mulib\muform\element\buttons;
use tool_mulib\muform\element\cancel;
use tool_mulib\muform\element\filemanager;
use tool_mulib\muform\element\hidden;
use tool_mulib\muform\element\submit;
use tool_mulib\muform\element\text;
use tool_mulib\muform\element\textarea;
use tool_mulib\muform\form;

/**
 * Edit the Markdown of one part with preview of the right.
 */
final class part_edit extends form {
    #[\Override]
    protected function definition(): void {
        $mudeck = $this->get_extra_data()['mudeck'];

        $this->add(new partcontainer('part', $mudeck));

        // Position of cursor before save, so that it can be restored
        // when continuing editing after save.
        $this->add(new hidden('caretstart', param::INT), 'part');
        $this->add(new hidden('caretend', param::INT), 'part');

        $name = new text('name', get_string('part_name', 'mod_mudeck'), ['maxlength' => 255]);
        $name->set_required(true);
        $this->add($name, 'part');

        $content = new textarea('content', get_string('part_content', 'mod_mudeck'), ['type' => 'rawtext', 'rows' => 25]);
        $content->add_help_button('part_content', 'mod_mudeck');
        $this->add($content, 'part');

        $options = media::get_filemanager_options();
        $attachments = new filemanager('attachments', get_string('part_media', 'mod_mudeck'), $options['maxfiles'], null, true);
        $this->add($attachments, 'part');

        $this->add(new buttons('buttons'), 'part');
        $this->add(new submit('saveandclose', get_string('part_save_close', 'mod_mudeck')), 'buttons');
        $this->add(new submit('saveandcontinue', get_string('part_save_continue', 'mod_mudeck')), 'buttons');
        $this->add(new cancel(), 'buttons');
    }

    #[\Override]
    protected function validation(array $data, array &$allerrors): void {
        $missing = media::find_missing_draft($data['content'], (int)$data['attachments']);
        if ($missing) {
            $allerrors['attachments'][] = get_string('part_media_missing', 'mod_mudeck', implode(', ', $missing));
        }
    }
}
