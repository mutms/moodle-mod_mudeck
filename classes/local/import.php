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
 * Turning an uploaded file into a part.
 *
 * @package    mod_mudeck
 * @copyright  2026 Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_mudeck\local;

use stdClass;

/**
 * Slides arrive either on their own or zipped up with their pictures.
 *
 * Both ways add a part and never touch the ones already there, so importing twice gives
 * two parts rather than a surprise.
 */
final class import {
    /**
     * Is this something we can read slides out of?
     *
     * @param string $filename
     * @return bool
     */
    public static function is_accepted(string $filename): bool {
        foreach (media::IMPORTTYPES as $extension) {
            if (str_ends_with(strtolower($filename), $extension)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Add a part from an uploaded file.
     *
     * @param stdClass $mudeck
     * @param \context_module $context
     * @param string $filepath where the uploaded file is on disk
     * @param string $filename what it was called, which decides how it is read
     * @return int|null new part id, or null when an archive held no single Markdown file
     */
    public static function from_file(
        stdClass $mudeck,
        \context_module $context,
        string $filepath,
        string $filename
    ): ?int {
        global $DB;

        if (!self::is_accepted($filename)) {
            return null;
        }

        $name = (string)preg_replace('/\.(md|markdown|zip)$/i', '', $filename);
        $partid = part::create($mudeck, $name !== '' ? $name : $mudeck->name);

        if (media::is_markdown($filename)) {
            // Slides on their own: the file is the content, there is nothing to unpack.
            $content = (string)file_get_contents($filepath);
        } else {
            // A zip carries the slides and their pictures; only its contents are kept.
            $content = media::unpack($context, $partid, $filepath);
            if ($content === null) {
                part::delete($DB->get_record('mudeck_part', ['id' => $partid], '*', MUST_EXIST), $context);
                return null;
            }
        }

        $created = $DB->get_record('mudeck_part', ['id' => $partid], '*', MUST_EXIST);
        part::save($mudeck, $created, $created->name, media::normalise($content, $context, $partid));

        return $partid;
    }
}
