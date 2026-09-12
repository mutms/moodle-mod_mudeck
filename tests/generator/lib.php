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

use mod_mudeck\local\part;

/**
 * Markdown slide deck generator.
 *
 * @package    mod_mudeck
 * @copyright  2026 Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_mudeck_generator extends \testing_module_generator {
    /** @var int number of parts created, used for default names */
    protected $partcount = 0;

    #[\Override]
    public function reset(): void {
        $this->partcount = 0;
        parent::reset();
    }

    #[\Override]
    public function create_instance($record = null, ?array $options = null): stdClass {
        $record = (object)(array)$record;

        return parent::create_instance($record, (array)$options);
    }

    /**
     * Create a part with some slides in it.
     *
     * @param stdClass|array $record must contain mudeckid, may contain name and content
     * @return stdClass the part record
     */
    public function create_part(stdClass|array $record): stdClass {
        global $DB;

        $record = (object)(array)$record;

        if (empty($record->mudeckid)) {
            throw new \coding_exception('mudeckid is required');
        }
        $mudeck = $DB->get_record('mudeck', ['id' => $record->mudeckid], '*', MUST_EXIST);

        $this->partcount++;
        if (!isset($record->name)) {
            $record->name = 'Part ' . $this->partcount;
        }
        if (!isset($record->content)) {
            $record->content = "# Slide one\n\n---\n\n## Slide two\n";
        }
        // Feature files cannot hold real newlines in a table cell, so accept the escape.
        $record->content = str_replace('\n', "\n", $record->content);

        // Every row is a part of its own - a presentation is a list, not a single document.
        $id = part::create($mudeck, $record->name, $record->content);

        return $DB->get_record('mudeck_part', ['id' => $id], '*', MUST_EXIST);
    }

    /**
     * Create one of the site's own themes.
     *
     * @param stdClass|array $record must contain shortname, may contain name and css
     * @return stdClass the theme record
     */
    public function create_theme(stdClass|array $record): stdClass {
        global $DB;

        $record = (object)(array)$record;

        if (empty($record->shortname)) {
            throw new \coding_exception('shortname is required');
        }
        if (!isset($record->name)) {
            $record->name = ucfirst($record->shortname);
        }
        if (!isset($record->css)) {
            $record->css = "section { color: #333; }\n";
        }

        $id = \mod_mudeck\local\theme::save($record);

        return $DB->get_record('mudeck_theme', ['id' => $id], '*', MUST_EXIST);
    }
}
