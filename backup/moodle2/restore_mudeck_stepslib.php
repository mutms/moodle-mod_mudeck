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
 * Restore steps for the Markdown slide deck.
 *
 * @package    mod_mudeck
 * @copyright  2026 Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use mod_mudeck\local\media;

/**
 * Read the structure back out of a backup file.
 */
class restore_mudeck_activity_structure_step extends restore_activity_structure_step {
    #[\Override]
    protected function define_structure() {
        $paths = [];

        $paths[] = new restore_path_element('mudeck', '/activity/mudeck');
        $paths[] = new restore_path_element('mudeck_part', '/activity/mudeck/parts/part');
        if ($this->get_setting_value('userinfo')) {
            $paths[] = new restore_path_element('mudeck_completed', '/activity/mudeck/completions/completed');
        }

        return $this->prepare_activity_structure($paths);
    }

    /**
     * Restore the presentation itself.
     *
     * @param array $data
     */
    protected function process_mudeck($data) {
        global $DB;

        $data = (object)$data;
        $data->course = $this->get_courseid();

        unset($data->id);
        $newitemid = $DB->insert_record('mudeck', $data);
        $this->apply_activity_instance($newitemid);
    }

    /**
     * Restore one part.
     *
     * @param array $data
     */
    protected function process_mudeck_part($data) {
        global $DB;

        $data = (object)$data;
        $oldid = $data->id;

        $data->mudeckid = $this->get_new_parentid('mudeck');
        $data->usermodified = $this->get_mappingid('user', $data->usermodified) ?: null;

        unset($data->id);
        $newitemid = $DB->insert_record('mudeck_part', $data);
        // The file area is keyed by the part id, so the mapping has to carry the files.
        $this->set_mapping('mudeck_part', $oldid, $newitemid, true);
    }

    /**
     * Restore one record of a user reaching the end.
     *
     * @param array $data
     */
    protected function process_mudeck_completed($data) {
        global $DB;

        $data = (object)$data;
        $data->mudeckid = $this->get_new_parentid('mudeck');
        $data->userid = $this->get_mappingid('user', $data->userid);
        if (!$data->userid) {
            return;
        }

        unset($data->id);
        $DB->insert_record('mudeck_completed', $data);
    }

    #[\Override]
    protected function after_execute() {
        $this->add_related_files('mod_mudeck', 'intro', null);
        $this->add_related_files('mod_mudeck', media::FILEAREA, 'mudeck_part');
    }
}
