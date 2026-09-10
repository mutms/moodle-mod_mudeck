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
 * Backup steps for the Markdown slide deck.
 *
 * @package    mod_mudeck
 * @copyright  2026 Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use mod_mudeck\local\media;

/**
 * Define the structure written into the backup file.
 */
class backup_mudeck_activity_structure_step extends backup_activity_structure_step {
    #[\Override]
    protected function define_structure() {
        $mudeck = new backup_nested_element('mudeck', ['id'], [
            'name',
            'intro',
            'introformat',
            'theme',
            'paginate',
            'allowdevicesync',
            'completionreachedend',
            'timecreated',
            'timemodified',
        ]);

        $completions = new backup_nested_element('completions');
        $completed = new backup_nested_element('completed', ['id'], [
            'userid',
            'timecompleted',
        ]);

        $parts = new backup_nested_element('parts');
        $part = new backup_nested_element('part', ['id'], [
            'name',
            'sortorder',
            'content',
            'contenthash',
            'usermodified',
            'timecreated',
            'timemodified',
        ]);

        $mudeck->add_child($parts);
        $parts->add_child($part);
        $mudeck->add_child($completions);
        $completions->add_child($completed);

        $mudeck->set_source_table('mudeck', ['id' => backup::VAR_ACTIVITYID]);
        $part->set_source_table('mudeck_part', ['mudeckid' => backup::VAR_PARENTID], 'sortorder ASC, id ASC');
        // Who reached the end is user data; sessions are transient and are not kept.
        if ($this->get_setting_value('userinfo')) {
            $completed->set_source_table('mudeck_completed', ['mudeckid' => backup::VAR_PARENTID]);
        }

        $part->annotate_ids('user', 'usermodified');
        $completed->annotate_ids('user', 'userid');

        $mudeck->annotate_files('mod_mudeck', 'intro', null);
        // Pictures belong to a part, keyed by its id.
        $part->annotate_files('mod_mudeck', media::FILEAREA, 'id');

        return $this->prepare_activity_structure($mudeck);
    }
}
