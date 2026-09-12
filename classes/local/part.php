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
 * Parts of a presentation.
 *
 * @package    mod_mudeck
 * @copyright  2026 Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_mudeck\local;

use mod_mudeck\event\part_created;
use mod_mudeck\event\part_deleted;
use mod_mudeck\event\part_updated;
use stdClass;

/**
 * A part is one Markdown document - the unit of ownership, ordering and editing.
 */
final class part {
    /**
     * Module context of a presentation, needed to report what happened to its parts.
     *
     * @param stdClass $mudeck
     * @return \context_module
     */
    private static function context(stdClass $mudeck): \context_module {
        $cm = get_coursemodule_from_instance('mudeck', $mudeck->id, $mudeck->course, false, MUST_EXIST);
        return \context_module::instance($cm->id);
    }

    /**
     * Report what happened to a part, reading it back so the event carries the whole record.
     *
     * @param int $partid
     * @param stdClass $mudeck
     * @param string $eventclass one of the part events
     */
    private static function report(int $partid, stdClass $mudeck, string $eventclass): void {
        global $DB;

        $part = $DB->get_record('mudeck_part', ['id' => $partid], '*', MUST_EXIST);
        $eventclass::create_from_part($part, self::context($mudeck))->trigger();
    }

    /**
     * Where the next part goes: at the end.
     *
     * @param int $mudeckid
     * @return int
     */
    private static function next_sortorder(int $mudeckid): int {
        global $DB;
        return (int)$DB->get_field_sql(
            'SELECT COALESCE(MAX(sortorder), 0) + 1 FROM {mudeck_part} WHERE mudeckid = ?',
            [$mudeckid]
        );
    }

    /**
     * The single part of a presentation.
     *
     * Only one part per presentation is supported for now, more come with the part modes.
     *
     * @param int $mudeckid
     * @return stdClass|null
     */
    public static function get_single(int $mudeckid): ?stdClass {
        global $DB;
        $records = $DB->get_records('mudeck_part', ['mudeckid' => $mudeckid], 'sortorder ASC, id ASC', '*', 0, 1);
        return $records ? reset($records) : null;
    }

    /**
     * Does the part hold any slides?
     *
     * Empty parts render no slides at all, they are not shown as a blank slide.
     *
     * @param stdClass|null $part
     * @return bool
     */
    public static function has_content(?stdClass $part): bool {
        return $part !== null && trim($part->content ?? '') !== '';
    }

    /**
     * Every part of a presentation, in the order they are shown.
     *
     * @param int $mudeckid
     * @return stdClass[] keyed by part id
     */
    public static function get_all(int $mudeckid): array {
        global $DB;
        return $DB->get_records('mudeck_part', ['mudeckid' => $mudeckid], 'sortorder ASC, id ASC');
    }

    /**
     * The part ids of a presentation, in the order they are shown.
     *
     * @param int $mudeckid
     * @return int[]
     */
    public static function get_order(int $mudeckid): array {
        return array_values(array_map(fn($one) => (int)$one->id, self::get_all($mudeckid)));
    }

    /**
     * A part, its presentation and its context - if the current user may edit it.
     *
     * @param int $partid
     * @return array{stdClass, stdClass, \context_module}
     */
    public static function require_editable(int $partid): array {
        global $DB;

        $part = $DB->get_record('mudeck_part', ['id' => $partid], '*', MUST_EXIST);
        $mudeck = $DB->get_record('mudeck', ['id' => $part->mudeckid], '*', MUST_EXIST);
        $cm = get_coursemodule_from_instance('mudeck', $mudeck->id, $mudeck->course, false, MUST_EXIST);
        $context = \context_module::instance($cm->id);

        require_capability('mod/mudeck:edit', $context);

        return [$part, $mudeck, $context];
    }

    /**
     * The parts that actually hold slides.
     *
     * An empty part is skipped by the show, it is not a blank slide.
     *
     * @param int $mudeckid
     * @return stdClass[]
     */
    public static function get_playable(int $mudeckid): array {
        return array_filter(self::get_all($mudeckid), fn($part) => self::has_content($part));
    }

    /**
     * Add a part at the end of the presentation.
     *
     * @param stdClass $mudeck
     * @param string $name
     * @param string $content
     * @return int new part id
     */
    public static function create(stdClass $mudeck, string $name, string $content = ''): int {
        global $DB, $USER;

        $now = time();
        $record = new stdClass();
        $record->mudeckid = $mudeck->id;
        $record->name = $name;
        $record->sortorder = self::next_sortorder($mudeck->id);
        $record->content = $content;
        $record->contenthash = sha1($content);
        $record->usermodified = $USER->id;
        $record->timecreated = $now;
        $record->timemodified = $now;

        $partid = $DB->insert_record('mudeck_part', $record);
        self::report($partid, $mudeck, part_created::class);

        return $partid;
    }

    /**
     * Delete a part together with its files.
     *
     * @param stdClass $part
     * @param \context_module $context
     */
    public static function delete(stdClass $part, \context_module $context): void {
        global $DB;

        // Built while the part is still there, reported once it is gone.
        $event = part_deleted::create_from_part($part, $context);

        get_file_storage()->delete_area_files($context->id, 'mod_mudeck', media::FILEAREA, $part->id);
        $DB->delete_records('mudeck_part', ['id' => $part->id]);

        $event->trigger();
    }

    /**
     * Move a part one place towards the start or the end.
     *
     * @param stdClass $part
     * @param int $direction -1 earlier, 1 later
     */
    public static function move(stdClass $part, int $direction): void {
        $all = array_values(self::get_all((int)$part->mudeckid));
        $index = null;
        foreach ($all as $i => $one) {
            if ((int)$one->id === (int)$part->id) {
                $index = $i;
                break;
            }
        }
        if ($index === null) {
            return;
        }

        self::move_to($part, $index + 1 + ($direction < 0 ? -1 : 1));
    }

    /**
     * Put a part at a given place in the running order.
     *
     * Dragging a part and choosing its position from a list are the same thing to the
     * server: both say where it should end up.
     *
     * @param stdClass $part
     * @param int $position 1-based place in the presentation
     */
    public static function move_to(stdClass $part, int $position): void {
        global $DB;

        $all = array_values(self::get_all((int)$part->mudeckid));
        $from = null;
        foreach ($all as $i => $one) {
            if ((int)$one->id === (int)$part->id) {
                $from = $i;
                break;
            }
        }
        $to = $position - 1;
        if ($from === null || $to < 0 || $to >= count($all) || $to === $from) {
            return;
        }

        // Take it out, put it back where it belongs, then renumber - the list is short.
        array_splice($all, $from, 1);
        array_splice($all, $to, 0, [$part]);
        foreach ($all as $i => $one) {
            $DB->set_field('mudeck_part', 'sortorder', $i + 1, ['id' => $one->id]);
        }

        $mudeck = $DB->get_record('mudeck', ['id' => $part->mudeckid], '*', MUST_EXIST);
        self::report($part->id, $mudeck, part_updated::class);
    }

    /**
     * How many slides the part holds.
     *
     * Marp starts a new slide at every thematic break, so counting them is enough
     * for the welcome page and avoids rendering anything on the server.
     *
     * @param stdClass|null $part
     * @return int
     */
    public static function count_slides(?stdClass $part): int {
        if (!self::has_content($part)) {
            return 0;
        }
        // Front matter is not a slide separator.
        $content = (string)preg_replace('/^\s*---\r?\n.*?\r?\n---[ \t]*(\r?\n|$)/s', '', $part->content);

        $slides = 1;
        $fence = null;
        $previousblank = true;
        foreach (preg_split('/\r?\n/', $content) as $line) {
            // Nothing inside a code block separates anything.
            // \x60 is a backtick - written this way to keep it out of the string itself.
            if (preg_match('/^[ \t]{0,3}(\x60{3,}|~{3,})/', $line, $match)) {
                $marker = substr(trim($match[1]), 0, 3);
                $fence = $fence === null ? $marker : ($fence === $marker ? null : $fence);
                $previousblank = false;
                continue;
            }
            if ($fence === null && $previousblank && preg_match('/^[ \t]{0,3}(-{3,}|_{3,}|\*{3,})[ \t]*$/', $line)) {
                // Dashes directly under a line of prose underline it as a heading instead
                // of separating slides - everywhere else they are a slide break.
                $slides++;
                $previousblank = false;
                continue;
            }
            // A paragraph line is the only thing dashes can underline.
            $previousblank = trim($line) === ''
                || (bool)preg_match('/^[ \t]{0,3}([#>|]|[-*+][ \t]|\d+[.)][ \t])/', $line);
        }

        return $slides;
    }

    /**
     * Create or update the single part of a presentation.
     *
     * @param stdClass $mudeck
     * @param stdClass|null $part existing part or null to create one
     * @param string $name
     * @param string $content raw Marp Markdown
     * @return int part id
     */
    public static function save(stdClass $mudeck, ?stdClass $part, string $name, string $content): int {
        global $DB, $USER;

        // Two people editing the same part is last save wins, deliberately: one whole
        // version survives instead of two halves of one.

        $now = time();
        $record = new stdClass();
        $record->name = $name;
        $record->content = $content;
        // Media is deliberately not part of the hash, images may be replaced freely.
        $record->contenthash = sha1($content);
        $record->usermodified = $USER->id;
        $record->timemodified = $now;

        if ($part) {
            $record->id = $part->id;
            $DB->update_record('mudeck_part', $record);
            self::report($part->id, $mudeck, part_updated::class);
            return $part->id;
        }

        $record->mudeckid = $mudeck->id;
        $record->sortorder = self::next_sortorder($mudeck->id);
        $record->timecreated = $now;
        $partid = $DB->insert_record('mudeck_part', $record);
        self::report($partid, $mudeck, part_created::class);

        return $partid;
    }
}
