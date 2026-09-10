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
 * Markdown slide deck themes.
 *
 * @package    mod_mudeck
 * @copyright  2026 Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_mudeck\local;

/**
 * Marp themes available for presentations.
 */
final class theme {
    /** @var string default theme used when nothing else is set */
    public const DEFAULTTHEME = 'default';

    /** Directory holding the CSS themes shipped with the plugin. */
    public const THEMEDIR = __DIR__ . '/../../themes';

    /** The database table holding the themes a site added for itself. */
    public const TABLE = 'mudeck_theme';

    /** @var \stdClass[]|null the site's own themes, read once per request */
    private static ?array $sitethemes = null;

    /**
     * Themes that may be selected, keyed by Marp theme name.
     *
     * The three built into marp-core, every CSS file shipped in themes/, and every theme
     * the site added for itself.
     *
     * @return array theme name => human readable name
     */
    public static function get_menu(): array {
        $menu = [
            'default' => get_string('theme_default', 'mod_mudeck'),
            'gaia' => get_string('theme_gaia', 'mod_mudeck'),
            'uncover' => get_string('theme_uncover', 'mod_mudeck'),
        ];
        foreach (self::get_custom_names() as $name) {
            $identifier = 'theme_' . $name;
            $menu[$name] = get_string_manager()->string_exists($identifier, 'mod_mudeck')
                ? get_string($identifier, 'mod_mudeck')
                : $name;
        }
        foreach (self::get_site_themes() as $theme) {
            $menu[$theme->shortname] = format_string($theme->name);
        }
        return $menu;
    }

    /**
     * The themes an activity may choose from, starting with not choosing at all.
     *
     * An activity that picks nothing follows the site, so changing the site's theme later
     * changes it too - which is what a site-wide default is for.
     *
     * @return array theme name => human readable name, keyed '' for the site's choice
     */
    public static function get_activity_menu(): array {
        $menu = self::get_menu();
        $sitedefault = self::get_site_default();
        return ['' => get_string('theme_follow_site', 'mod_mudeck', $menu[$sitedefault] ?? $sitedefault)] + $menu;
    }

    /**
     * The themes this site added, keyed by short name.
     *
     * @return \stdClass[]
     */
    public static function get_site_themes(): array {
        global $DB;

        if (self::$sitethemes === null) {
            self::$sitethemes = $DB->get_records(self::TABLE, null, 'name ASC', '*');
            self::$sitethemes = array_combine(
                array_map(fn($theme) => $theme->shortname, self::$sitethemes),
                array_values(self::$sitethemes)
            );
        }
        return self::$sitethemes;
    }

    /**
     * One of the site's own themes.
     *
     * @param int $id
     * @return \stdClass|null
     */
    public static function get_site_theme(int $id): ?\stdClass {
        global $DB;
        return $DB->get_record(self::TABLE, ['id' => $id]) ?: null;
    }

    /**
     * Names nobody may take: the built-ins and everything shipped in themes/.
     *
     * @return string[]
     */
    public static function get_reserved_names(): array {
        return array_merge(['default', 'gaia', 'uncover'], self::get_custom_names());
    }

    /**
     * Put the short name into the CSS, where Marp looks for it.
     *
     * Marp registers a theme under the name in its own "@theme" comment rather than
     * under anything we say, so pasted CSS would otherwise arrive under whatever name it
     * was written with - or under none at all. The header is rewritten on the way in, and
     * the short name stays the one true name.
     *
     * @param string $css as written
     * @param string $shortname
     * @return string CSS Marp will register under $shortname
     */
    public static function normalise_css(string $css, string $shortname): string {
        $css = preg_replace('~/\*\s*@theme\s+[^*]*\*/~', '', $css, 1);
        return "/* @theme {$shortname} */\n" . ltrim((string)$css);
    }

    /**
     * Add or update one of the site's themes.
     *
     * @param \stdClass $theme shortname, name, css and optionally id
     * @return int the theme id
     */
    public static function save(\stdClass $theme): int {
        global $DB, $USER;

        $now = time();
        $record = new \stdClass();
        $record->shortname = clean_param($theme->shortname, PARAM_SAFEDIR);
        $record->name = clean_param($theme->name, PARAM_NOTAGS);
        $record->css = self::normalise_css((string)$theme->css, $record->shortname);
        $record->usermodified = $USER->id;
        $record->timemodified = $now;

        self::$sitethemes = null;

        if (!empty($theme->id)) {
            $record->id = $theme->id;
            $DB->update_record(self::TABLE, $record);
            return (int)$record->id;
        }

        $record->timecreated = $now;
        return (int)$DB->insert_record(self::TABLE, $record);
    }

    /**
     * Remove one of the site's themes.
     *
     * Presentations naming it are left alone: they fall back to Marp's own look, which
     * says more than refusing to let a site tidy up its themes ever would.
     *
     * @param int $id
     */
    public static function delete(int $id): void {
        global $DB;

        $DB->delete_records(self::TABLE, ['id' => $id]);
        self::$sitethemes = null;
    }

    /**
     * How many presentations name a theme.
     *
     * @param string $shortname
     * @return int
     */
    public static function count_uses(string $shortname): int {
        global $DB;
        return $DB->count_records('mudeck', ['theme' => $shortname]);
    }

    /**
     * Names of the CSS themes shipped with the plugin.
     *
     * @return string[]
     */
    public static function get_custom_names(): array {
        $names = [];
        foreach (glob(self::THEMEDIR . '/*.css') ?: [] as $file) {
            $names[] = basename($file, '.css');
        }
        sort($names);
        return $names;
    }

    /**
     * The CSS of every shipped theme, ready to be handed to Marp.
     *
     * @param \moodle_url|string $pluginbase URL the theme may load its own images from
     * @return string[] theme name => CSS
     */
    public static function get_custom_css($pluginbase = ''): array {
        $css = [];
        foreach (self::get_custom_names() as $name) {
            $content = file_get_contents(self::THEMEDIR . '/' . $name . '.css');
            if ($content === false) {
                continue;
            }
            // The file name is the name of the theme, so the header inside the CSS is
            // made to agree with it - a shipped theme whose comment says something else
            // would register under that other name and be unusable by the one it is
            // listed under.
            $content = self::normalise_css($content, $name);
            $css[$name] = str_replace('%%WWWROOT%%', (string)$pluginbase, $content);
        }
        // The site's own themes are handed over the same way, so every page that shows
        // slides gets them without knowing where they came from.
        foreach (self::get_site_themes() as $theme) {
            $content = self::normalise_css($theme->css, $theme->shortname);
            $css[$theme->shortname] = str_replace('%%WWWROOT%%', (string)$pluginbase, $content);
        }
        return $css;
    }

    /**
     * Is the given theme name selectable?
     *
     * @param string|null $name
     * @return bool
     */
    public static function is_valid(?string $name): bool {
        return $name !== null && isset(self::get_menu()[$name]);
    }

    /**
     * The theme the site starts everything with.
     *
     * @return string
     */
    public static function get_site_default(): string {
        $configured = get_config('mod_mudeck', 'defaulttheme');
        return self::is_valid($configured) ? $configured : self::DEFAULTTHEME;
    }

    /**
     * The theme to actually use, given the one that was asked for.
     *
     * Themes come and go - a site theme is deleted, a deck is restored from another
     * site, somebody mistypes a name in the front matter - and a presentation must never
     * be left with nothing. So a name that means nothing here falls back to what the site
     * chose, and that to Marp's own.
     *
     * @param string|null $name what the deck or the activity asked for
     * @return string a theme that exists
     */
    public static function resolve(?string $name): string {
        if (self::is_valid($name)) {
            return (string)$name;
        }
        return self::get_site_default();
    }
}
