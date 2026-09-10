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
 * Presenter notes.
 *
 * @package    mod_mudeck
 * @copyright  2026 Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_mudeck\local;

/**
 * Presenter notes carried inside the Markdown.
 *
 * Marp treats an HTML comment that holds no directives as the presenter note of
 * that slide, and keeps it off the slide. Off the slide is not the same as away
 * from the viewer though - the note is still a substring of the Markdown, so it
 * has to be removed on the server for anybody who may not see it.
 */
final class notes {
    /** Matches an HTML comment - how Marp carries both directives and notes. */
    private const COMMENT = '/<!--([\s\S]*?)-->/';

    /** Matches a directive line, optionally in its spot form. */
    private const DIRECTIVE = '/^\s*_?[A-Za-z][\w-]*\s*:/m';

    /**
     * Remove presenter notes, keeping directive comments untouched.
     *
     * @param string $markdown raw Marp Markdown
     * @return string Markdown without the notes
     */
    public static function strip(string $markdown): string {
        return (string)preg_replace_callback(self::COMMENT, function (array $matches): string {
            // A comment holding any directive is configuration, not a note.
            if (preg_match(self::DIRECTIVE, $matches[1])) {
                return $matches[0];
            }
            return '';
        }, $markdown);
    }

    /**
     * Does the Markdown contain any presenter note?
     *
     * @param string $markdown
     * @return bool
     */
    public static function exist(string $markdown): bool {
        return self::strip($markdown) !== $markdown;
    }
}
