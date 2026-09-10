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

use mod_mudeck\local\import;
use mod_mudeck\local\media;
use mod_mudeck\local\part;

/**
 * Import test - slides arriving as a Markdown file or as a zip.
 *
 * @package    mod_mudeck
 * @copyright  2026 Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * @covers \mod_mudeck\local\import
 */
final class import_test extends \advanced_testcase {
    /** @var \stdClass the presentation under test */
    private \stdClass $mudeck;
    /** @var \context_module its module context */
    private \context_module $context;

    public function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
        $this->setAdminUser();

        $course = $this->getDataGenerator()->create_course();
        $this->mudeck = $this->getDataGenerator()->create_module('mudeck', ['course' => $course->id]);
        $this->context = \context_module::instance($this->mudeck->cmid);
    }

    public function test_a_markdown_file_becomes_a_part(): void {
        $file = make_request_directory() . '/Opening talk.md';
        file_put_contents($file, "# Hello\n\n---\n\n# Second\n");

        $partid = import::from_file($this->mudeck, $this->context, $file, 'Opening talk.md');

        $parts = part::get_all($this->mudeck->id);
        $this->assertCount(1, $parts);
        $part = $parts[$partid];
        // The file name becomes the part name, without the extension.
        $this->assertSame('Opening talk', $part->name);
        $this->assertSame("# Hello\n\n---\n\n# Second\n", $part->content);
        $this->assertSame(2, part::count_slides($part));
        // Nothing was stored beside the slides - a lone Markdown file has no pictures.
        $this->assertSame([], media::find_missing($part->content, $this->context, $partid));
    }

    public function test_a_zip_becomes_a_part_with_its_pictures(): void {
        $zip = make_request_directory() . '/deck.zip';
        get_file_packer('application/zip')->archive_to_pathname([
            'slides.md' => ["# Imported\n\n![](photo.png)\n"],
            'photo.png' => ['not really a png'],
        ], $zip);

        $partid = import::from_file($this->mudeck, $this->context, $zip, 'deck.zip');

        $part = part::get_all($this->mudeck->id)[$partid];
        $this->assertSame('deck', $part->name);
        $this->assertStringContainsString('# Imported', $part->content);
        // The picture came with it, so nothing is missing.
        $this->assertSame([], media::find_missing($part->content, $this->context, $partid));
    }

    public function test_an_archive_without_slides_imports_nothing(): void {
        $zip = make_request_directory() . '/nothing.zip';
        get_file_packer('application/zip')->archive_to_pathname([
            'one.md' => ['# One'],
            'two.md' => ['# Two'],
        ], $zip);

        $this->assertNull(import::from_file($this->mudeck, $this->context, $zip, 'nothing.zip'));
        // The part created for it is gone again - a failed import leaves nothing behind.
        $this->assertSame([], part::get_all($this->mudeck->id));
    }

    public function test_importing_twice_adds_two_parts(): void {
        $file = make_request_directory() . '/deck.md';
        file_put_contents($file, "# Hello\n");

        import::from_file($this->mudeck, $this->context, $file, 'deck.md');
        import::from_file($this->mudeck, $this->context, $file, 'deck.md');

        $this->assertCount(2, part::get_all($this->mudeck->id));
    }
}
