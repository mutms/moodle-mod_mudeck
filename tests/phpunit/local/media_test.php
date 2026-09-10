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
// phpcs:disable moodle.Commenting.DocblockDescription.Missing

namespace mod_mudeck\phpunit\local;

use mod_mudeck\local\media;
use mod_mudeck\local\part;

/**
 * Media test.
 *
 * @package    mod_mudeck
 * @copyright  2026 Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * @covers \mod_mudeck\local\media
 */
final class media_test extends \advanced_testcase {
    /** @var \stdClass */
    private $mudeck;
    /** @var \context_module */
    private $context;
    /** @var int */
    private $partid;

    public function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
        $this->setAdminUser();

        $course = $this->getDataGenerator()->create_course();
        $this->mudeck = $this->getDataGenerator()->create_module('mudeck', ['course' => $course->id]);
        $cm = get_coursemodule_from_instance('mudeck', $this->mudeck->id, $course->id, false, MUST_EXIST);
        $this->context = \context_module::instance($cm->id);
        $this->partid = part::create($this->mudeck, 'Part');
    }

    /**
     * Put a file into the part's own area.
     *
     * @param string $filename
     * @param string $filepath
     * @param string $content
     */
    private function add_file(string $filename, string $filepath = '/', string $content = 'x'): void {
        get_file_storage()->create_file_from_string([
            'contextid' => $this->context->id,
            'component' => 'mod_mudeck',
            'filearea' => media::FILEAREA,
            'itemid' => $this->partid,
            'filepath' => $filepath,
            'filename' => $filename,
        ], $content);
    }

    public function test_rewrite_turns_names_into_urls(): void {
        $result = media::rewrite("![](photo.jpg)\n", $this->context, $this->partid);

        $this->assertStringContainsString('/pluginfile.php/', $result);
        $this->assertStringContainsString("/mod_mudeck/content/{$this->partid}/photo.jpg", $result);
    }

    public function test_rewrite_handles_backgrounds_and_subfolders(): void {
        $result = media::rewrite("![bg right](pictures/city.jpg)\n", $this->context, $this->partid);

        $this->assertStringContainsString("/content/{$this->partid}/pictures/city.jpg", $result);
        $this->assertStringContainsString('![bg right](', $result, 'the Marp keywords must survive');
    }

    public function test_rewrite_leaves_absolute_references_alone(): void {
        $markdown = "![](https://example.com/a.png)\n[link](https://example.com)\n![](/already/absolute.png)\n";

        $this->assertSame($markdown, media::rewrite($markdown, $this->context, $this->partid));
    }

    public function test_rewrite_understands_the_pluginfile_token(): void {
        $result = media::rewrite("![](@@PLUGINFILE@@/photo.jpg)\n", $this->context, $this->partid);

        $this->assertStringNotContainsString('@@PLUGINFILE@@', $result);
        $this->assertStringContainsString("/content/{$this->partid}/photo.jpg", $result);
    }

    public function test_normalise_turns_own_urls_back_into_names(): void {
        global $USER;

        $draftitemid = file_get_unused_draft_itemid();
        $draft = \core\url::make_draftfile_url($draftitemid, '/pictures/', 'city one.jpg')->out(false);
        $draftpath = parse_url($draft, PHP_URL_PATH);
        $own = \core\url::make_pluginfile_url(
            $this->context->id,
            'mod_mudeck',
            media::FILEAREA,
            $this->partid,
            '/',
            'photo.jpg'
        )->out(false);

        $markdown = "![bg]({$draft})\n![]({$draftpath})\n![]({$own})\n![](@@PLUGINFILE@@/logo.png)\n";
        $result = media::normalise($markdown, $this->context, $this->partid, $draftitemid);

        $this->assertSame(
            "![bg](pictures/city one.jpg)\n![](pictures/city one.jpg)\n![](photo.jpg)\n![](logo.png)\n",
            $result
        );
        // What comes back is what the display rewrite expects.
        $shown = media::rewrite($result, $this->context, $this->partid);
        $this->assertStringContainsString("/content/{$this->partid}/photo.jpg", $shown);
    }

    public function test_normalise_leaves_everything_else_alone(): void {
        $otherpart = \core\url::make_pluginfile_url(
            $this->context->id,
            'mod_mudeck',
            media::FILEAREA,
            $this->partid + 1,
            '/',
            'photo.jpg'
        )->out(false);
        $markdown = "![](photo.jpg)\n![](https://example.com/a.png)\n[x]({$otherpart})\n@@PLUGINFILE@@ in prose\n";

        $this->assertSame($markdown, media::normalise($markdown, $this->context, $this->partid, 0));
        // A new part has no area of its own yet, and no draft was given: nothing to match.
        $this->assertSame($markdown, media::normalise($markdown, $this->context, 0));
    }

    public function test_find_missing(): void {
        $this->add_file('there.png');
        $this->add_file('deep.png', '/sub/');

        $markdown = "![](there.png)\n![](missing.png)\n![bg](sub/deep.png)\n![](https://example.com/x.png)\n";
        $missing = media::find_missing($markdown, $this->context, $this->partid);

        $this->assertSame(['missing.png'], $missing, 'only local files that are not there count');
    }

    public function test_find_missing_returns_nothing_without_pictures(): void {
        $this->assertSame([], media::find_missing("# Just text\n", $this->context, $this->partid));
    }

    public function test_find_missing_draft_looks_at_the_form_not_the_part(): void {
        global $USER;

        // In the part but not in the draft: as far as the form is concerned it is gone.
        $this->add_file('there.png');

        $draftitemid = file_get_unused_draft_itemid();
        get_file_storage()->create_file_from_string([
            'contextid' => \context_user::instance($USER->id)->id,
            'component' => 'user',
            'filearea' => 'draft',
            'itemid' => $draftitemid,
            'filepath' => '/sub/',
            'filename' => 'uploaded.png',
        ], 'x');

        $markdown = "![](there.png)\n![bg](sub/uploaded.png)\n";

        $this->assertSame(['there.png'], media::find_missing_draft($markdown, $draftitemid));
        $this->assertSame([], media::find_missing_draft("# Just text\n", $draftitemid));
    }

    public function test_unpack_takes_the_markdown_and_the_pictures(): void {
        $zip = $this->make_archive([
            'deck/slides.md' => "# Imported\n\n![](img/photo.png)\n",
            'deck/img/photo.png' => 'binary',
        ]);

        $markdown = media::unpack($this->context, $this->partid, $zip);

        $this->assertSame("# Imported\n\n![](img/photo.png)\n", $markdown);
        // The wrapping folder is stripped, so the reference still resolves.
        $this->assertSame([], media::find_missing((string)$markdown, $this->context, $this->partid));
    }

    public function test_unpack_keeps_no_archive_behind(): void {
        $zip = $this->make_archive(['slides.md' => "# One\n"]);

        media::unpack($this->context, $this->partid, $zip);

        // Only what the archive carried is stored - the archive itself was just transport.
        $stored = get_file_storage()->get_area_files(
            $this->context->id,
            'mod_mudeck',
            media::FILEAREA,
            $this->partid,
            'filename',
            false
        );
        $this->assertSame([], array_map(fn($file) => $file->get_filename(), $stored));
    }

    public function test_unpack_refuses_an_archive_without_exactly_one_markdown(): void {
        $zip = $this->make_archive(['a.md' => '# A', 'b.md' => '# B']);

        $this->assertNull(media::unpack($this->context, $this->partid, $zip));
    }

    public function test_export_round_trip(): void {
        $this->add_file('photo.png', '/', 'the picture');
        $content = "# Slide\n\n![](photo.png)\n";
        part::save($this->mudeck, $this->get_part(), 'Part', $content);

        $zipfile = media::export_archive($this->get_part(), $this->context);
        $this->assertFileExists($zipfile);

        // Unpack the very same archive into a second part.
        $second = part::create($this->mudeck, 'Second');
        $imported = media::unpack($this->context, $second, $zipfile);

        $this->assertSame($content, $imported, 'what comes out must be what went in');
        $this->assertSame([], media::find_missing((string)$imported, $this->context, $second));
        $file = get_file_storage()->get_file(
            $this->context->id,
            'mod_mudeck',
            media::FILEAREA,
            $second,
            '/',
            'photo.png'
        );
        $this->assertSame('the picture', $file->get_content());
    }

    /**
     * The part record as it stands now.
     *
     * @return \stdClass
     */
    private function get_part(): \stdClass {
        global $DB;
        return $DB->get_record('mudeck_part', ['id' => $this->partid], '*', MUST_EXIST);
    }

    /**
     * Build a zip on disk.
     *
     * @param array $files path inside the archive => contents
     * @return string full path of the archive
     */
    private function make_archive(array $files): string {
        $dir = make_request_directory();
        $target = $dir . '/archive.zip';
        $entries = [];
        foreach ($files as $path => $contents) {
            $entries[$path] = [$contents];
        }
        get_file_packer('application/zip')->archive_to_pathname($entries, $target);
        return $target;
    }
}
