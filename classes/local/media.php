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
 * Media files used by slides.
 *
 * @package    mod_mudeck
 * @copyright  2026 Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_mudeck\local;

use mod_mudeck\event\part_exported;
use mod_mudeck\event\part_imported;

/**
 * Pictures and other files that belong to one part.
 *
 * Each part owns a file area of its own, keyed by the part id, so two authors cannot
 * collide over a file name while every slide inside one part shares the same uploads.
 */
final class media {
    /** File area holding everything a part uses. */
    public const FILEAREA = 'content';

    /** What the import page takes: the slides on their own, or zipped up with their pictures. */
    public const IMPORTTYPES = ['.md', '.markdown', '.zip'];

    /**
     * Is this a Markdown file rather than an archive?
     *
     * @param string $filename
     * @return bool
     */
    public static function is_markdown(string $filename): bool {
        return (bool)preg_match('/\.(md|markdown)$/i', $filename);
    }

    /**
     * Options for the file manager used when editing a part.
     *
     * @return array
     */
    public static function get_filemanager_options(): array {
        return [
            'subdirs' => 1,
            'maxbytes' => 0,
            'maxfiles' => -1,
            'accepted_types' => '*',
        ];
    }

    /**
     * Unpack a zip into a part: its Markdown becomes the slides, everything else the media.
     *
     * The archive itself is not kept - it was a way of carrying the files here, and the
     * files are now in the part.
     *
     * @param \context_module $context module context
     * @param int $partid
     * @param string $zippath the archive on disk
     * @return string|null the Markdown it held, or null when it held no single Markdown file
     */
    public static function unpack(\context_module $context, int $partid, string $zippath): ?string {
        global $DB;

        $tempdir = make_request_directory();
        // The packer cleans every entry name itself; asking for a boolean makes one entry
        // that failed to extract refuse the whole archive rather than import half of it.
        if (!get_file_packer('application/zip')->extract_to_pathname($zippath, $tempdir, null, null, true)) {
            return null;
        }

        $found = self::find_markdown($tempdir);
        if ($found === null) {
            return null;
        }

        $markdown = file_get_contents($found);
        if ($markdown === false) {
            return null;
        }
        $base = dirname($found);

        $fs = get_file_storage();
        // Everything that sits beside the Markdown becomes media, keeping its layout.
        foreach (self::list_files($base) as $path) {
            if ($path === $found) {
                continue;
            }
            $relative = ltrim(substr($path, strlen($base)), '/');
            $filepath = '/' . ltrim(dirname($relative), '.');
            $filepath = rtrim($filepath, '/') . '/';
            $filename = basename($relative);
            if ($existing = $fs->get_file($context->id, 'mod_mudeck', self::FILEAREA, $partid, $filepath, $filename)) {
                $existing->delete();
            }
            $fs->create_file_from_pathname((object)[
                'contextid' => $context->id,
                'component' => 'mod_mudeck',
                'filearea' => self::FILEAREA,
                'itemid' => $partid,
                'filepath' => $filepath,
                'filename' => $filename,
            ], $path);
        }

        $part = $DB->get_record('mudeck_part', ['id' => $partid], '*', MUST_EXIST);
        part_imported::create_from_part($part, $context)->trigger();

        return $markdown;
    }

    /**
     * The single Markdown file in an unpacked archive, if there is exactly one.
     *
     * @param string $dir
     * @return string|null full path of the Markdown file
     */
    private static function find_markdown(string $dir): ?string {
        $found = [];
        foreach (self::list_files($dir) as $path) {
            if (preg_match('/\.(md|markdown)$/i', $path)) {
                $found[] = $path;
            }
        }
        return count($found) === 1 ? $found[0] : null;
    }

    /**
     * Every file below a directory.
     *
     * @param string $dir
     * @return string[] full paths
     */
    private static function list_files(string $dir): array {
        $files = [];
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS)
        );
        foreach ($iterator as $file) {
            if ($file->isFile()) {
                $files[] = $file->getPathname();
            }
        }
        sort($files);
        return $files;
    }

    /**
     * Pack a presentation into one archive: the Markdown plus everything it uses.
     *
     * This is the shape unpack() expects, so an archive downloaded from one site can
     * simply be uploaded into another.
     *
     * @param \stdClass $part
     * @param \context_module $context
     * @return string path of the temporary archive
     */
    public static function export_archive(\stdClass $part, \context_module $context): string {
        $fs = get_file_storage();
        $files = ['slides.md' => [$part->content]];

        foreach ($fs->get_area_files($context->id, 'mod_mudeck', self::FILEAREA, $part->id, 'filename', false) as $file) {
            $files[ltrim($file->get_filepath(), '/') . $file->get_filename()] = $file;
        }

        $packer = get_file_packer('application/zip');
        $target = make_request_directory() . '/' . clean_filename($part->name) . '.zip';
        $packer->archive_to_pathname($files, $target);

        part_exported::create_from_part($part, $context)->trigger();

        return $target;
    }

    /**
     * File names the Markdown refers to but which have not been uploaded.
     *
     * A deck written elsewhere - by hand or by an AI assistant - usually arrives before
     * its pictures do, so the author is told exactly which ones are still missing
     * instead of finding broken images during the presentation.
     *
     * @param string $markdown raw Markdown as stored
     * @param \context $context module context
     * @param int $partid
     * @return string[] referenced names that are not in the file area
     */
    public static function find_missing(string $markdown, \context $context, int $partid): array {
        $referenced = self::referenced($markdown);
        if (!$referenced) {
            return [];
        }
        return self::missing($referenced, self::area_names($context->id, 'mod_mudeck', self::FILEAREA, $partid));
    }

    /**
     * The same check while the form is still being filled in, against the files it holds.
     *
     * Validation happens before the draft area is saved into the part, so the uploads
     * that count are the ones sitting in the draft.
     *
     * @param string $markdown raw Markdown as typed
     * @param int $draftitemid the form's file manager
     * @return string[] referenced names that have not been uploaded
     */
    public static function find_missing_draft(string $markdown, int $draftitemid): array {
        global $USER;

        $referenced = self::referenced($markdown);
        if (!$referenced) {
            return [];
        }
        $usercontext = \context_user::instance($USER->id);
        return self::missing($referenced, self::area_names($usercontext->id, 'user', 'draft', $draftitemid));
    }

    /**
     * Local files the Markdown points at.
     *
     * @param string $markdown
     * @return array keyed by name, so it can be diffed against what is stored
     */
    private static function referenced(string $markdown): array {
        $referenced = [];
        if (preg_match_all('/!\[[^\]]*\]\(\s*([^)\s]+)/', $markdown, $matches)) {
            foreach ($matches[1] as $target) {
                if (!self::is_absolute($target)) {
                    $referenced[ltrim(urldecode($target), '/')] = true;
                }
            }
        }
        return $referenced;
    }

    /**
     * Names of the files in one file area, path included.
     *
     * @param int $contextid
     * @param string $component
     * @param string $filearea
     * @param int $itemid
     * @return array keyed by name
     */
    private static function area_names(int $contextid, string $component, string $filearea, int $itemid): array {
        $names = [];
        $fs = get_file_storage();
        foreach ($fs->get_area_files($contextid, $component, $filearea, $itemid, 'filename', false) as $file) {
            $names[ltrim($file->get_filepath(), '/') . $file->get_filename()] = true;
        }
        return $names;
    }

    /**
     * What is referenced but not there.
     *
     * @param array $referenced
     * @param array $available
     * @return string[] sorted names
     */
    private static function missing(array $referenced, array $available): array {
        $missing = array_keys(array_diff_key($referenced, $available));
        sort($missing);
        return $missing;
    }

    /**
     * Turn file references in the Markdown into URLs a browser can fetch.
     *
     * Authors write ordinary Markdown - `![](picture.png)` - so a bare file name is
     * resolved against the part's own file area. The `@@PLUGINFILE@@` form is handled
     * too, because that is what Moodle itself writes when files are moved about.
     *
     * @param string $markdown raw Markdown as stored
     * @param \context $context module context
     * @param int $partid
     * @return string Markdown where every local file reference is a real URL
     */
    public static function rewrite(string $markdown, \context $context, int $partid): string {
        $base = \core\url::make_pluginfile_url(
            $context->id,
            'mod_mudeck',
            self::FILEAREA,
            $partid,
            '/',
            ''
        )->out(false);
        $base = rtrim($base, '/');

        // The token form first, so Moodle's own rewriting keeps working.
        $markdown = str_replace('@@PLUGINFILE@@', $base, $markdown);

        // Then bare file names in Markdown images and links, including Marp backgrounds.
        $pattern = '/(!?\[[^\]]*\]\(\s*)([^)\s]+)/';
        return (string)preg_replace_callback($pattern, function (array $m) use ($base): string {
            $target = $m[2];
            if (self::is_absolute($target)) {
                return $m[0];
            }
            return $m[1] . $base . '/' . ltrim($target, '/');
        }, $markdown);
    }

    /**
     * Does the reference already point somewhere on its own?
     *
     * @param string $target
     * @return bool
     */
    private static function is_absolute(string $target): bool {
        return (bool)preg_match('#^([a-z][a-z0-9+.-]*:|//|/|\#)#i', $target);
    }

    /**
     * Bring file references back to bare names before the Markdown is stored.
     *
     * The stored form is plain Markdown with file names relative to the part - the same
     * shape the export has. A reference that reaches the same files by another route is
     * turned back into the bare name: a URL of the draft area the editor previewed from,
     * which stops working once the draft expires; a pluginfile URL of the part's own
     * area; or Moodle's @@PLUGINFILE@@ token. Anything else is left as written.
     *
     * @param string $markdown Markdown as submitted
     * @param \context $context module context
     * @param int $partid the part being saved, 0 for one that does not exist yet
     * @param int $draftitemid the draft area the editor was working with, 0 for none
     * @return string Markdown to store
     */
    public static function normalise(string $markdown, \context $context, int $partid, int $draftitemid = 0): string {
        $prefixes = ['@@PLUGINFILE@@/'];
        if ($partid) {
            $prefixes = array_merge($prefixes, self::url_forms(
                \core\url::make_pluginfile_url($context->id, 'mod_mudeck', self::FILEAREA, $partid, '/', '')
            ));
        }
        if ($draftitemid) {
            $prefixes = array_merge($prefixes, self::url_forms(\core\url::make_draftfile_url($draftitemid, '/', '')));
        }

        $pattern = '/(!?\[[^\]]*\]\(\s*)([^)\s]+)/';
        return (string)preg_replace_callback($pattern, function (array $m) use ($prefixes): string {
            $target = $m[2];
            foreach ($prefixes as $prefix) {
                if (str_starts_with($target, $prefix)) {
                    $name = rawurldecode(substr($target, strlen($prefix)));
                    return $m[1] . ltrim($name, '/');
                }
            }
            return $m[0];
        }, $markdown);
    }

    /**
     * A file URL as a browser may have copied it: absolute, and relative to the host.
     *
     * @param \core\url $url
     * @return string[]
     */
    private static function url_forms(\core\url $url): array {
        $absolute = $url->out(false);
        $forms = [$absolute];
        $path = parse_url($absolute, PHP_URL_PATH);
        if (is_string($path) && $path !== '') {
            $forms[] = $path;
        }
        return $forms;
    }
}
