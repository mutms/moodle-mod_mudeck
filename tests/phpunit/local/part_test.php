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

use mod_mudeck\local\part;

/**
 * Part model test.
 *
 * @package    mod_mudeck
 * @copyright  2026 Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * @covers \mod_mudeck\local\part
 */
final class part_test extends \advanced_testcase {
    public function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
        $this->setAdminUser();
    }

    public function test_get_single_returns_null_without_parts(): void {
        $course = $this->getDataGenerator()->create_course();
        $mudeck = $this->getDataGenerator()->create_module('mudeck', ['course' => $course->id]);

        $this->assertNull(part::get_single($mudeck->id));
    }

    public function test_save_creates_and_hashes(): void {
        global $DB;

        $course = $this->getDataGenerator()->create_course();
        $mudeck = $this->getDataGenerator()->create_module('mudeck', ['course' => $course->id]);
        $content = "# Hello\n\n---\n\n## There\n";

        $id = part::save($mudeck, null, 'My part', $content);
        $part = $DB->get_record('mudeck_part', ['id' => $id], '*', MUST_EXIST);

        $this->assertSame('My part', $part->name);
        $this->assertSame($content, $part->content);
        $this->assertSame(sha1($content), $part->contenthash);
        $this->assertSame((int)$mudeck->id, (int)$part->mudeckid);
        $this->assertSame(1, (int)$part->sortorder);
    }

    public function test_save_updates_existing_and_rehashes(): void {
        global $DB;

        $course = $this->getDataGenerator()->create_course();
        $mudeck = $this->getDataGenerator()->create_module('mudeck', ['course' => $course->id]);

        $id = part::save($mudeck, null, 'One', "# One\n");
        $first = $DB->get_record('mudeck_part', ['id' => $id], '*', MUST_EXIST);

        $id2 = part::save($mudeck, $first, 'Two', "# Two\n");
        $second = $DB->get_record('mudeck_part', ['id' => $id2], '*', MUST_EXIST);

        $this->assertSame($id, $id2, 'saving an existing part must not create another one');
        $this->assertSame('Two', $second->name);
        $this->assertSame(sha1("# Two\n"), $second->contenthash);
        $this->assertNotSame($first->contenthash, $second->contenthash);
        $this->assertSame(1, $DB->count_records('mudeck_part', ['mudeckid' => $mudeck->id]));
    }

    /**
     * Slide counts, checked against what Marp actually draws.
     *
     * @dataProvider slide_count_provider
     * @param string $content
     * @param int $expected
     */
    public function test_count_slides(string $content, int $expected): void {
        $this->assertSame($expected, part::count_slides((object)['content' => $content]));
    }

    /**
     * Decks and the number of slides Marp renders from them.
     *
     * @return array[]
     */
    public static function slide_count_provider(): array {
        return [
            'one slide' => ["# Only one\n", 1],
            'two slides' => ["# One\n\n---\n\n# Two\n", 2],
            'front matter is not a break' => ["---\ntheme: orange\n---\n\n# One\n\n---\n\n# Two\n", 2],
            // Three dashes under a line of prose make it a heading, not a new slide.
            'dashes under prose are a heading' => ["# Title\nSubtitle\n---\n# Next\n", 1],
            'dashes after a list still break' => ["# Title\n\n- one\n- two\n---\n\n# Next\n", 2],
            'dashes inside a code block are code' => [
                "# One\n\n" . str_repeat('~', 3) . "\n---\n" . str_repeat('~', 3) . "\n\n---\n\n# Two\n",
                2,
            ],
            'underscores and stars break too' => ["# One\n\n___\n\n# Two\n\n***\n\n# Three\n", 3],
            'the starter deck' => [get_string('part_starter', 'mod_mudeck'), 3],
        ];
    }

    /**
     * The first slide of a part, cut by the same rules that count slides.
     *
     * @dataProvider first_slide_provider
     * @param string $content
     * @param string $expected
     */
    public function test_first_slide(string $content, string $expected): void {
        $this->assertSame($expected, part::first_slide($content));
    }

    /**
     * @return array[]
     */
    public static function first_slide_provider(): array {
        return [
            'one slide' => ["# Hello\n\nText", "# Hello\n\nText"],
            'cut at the break' => ["# One\n\n---\n\n# Two", "# One\n"],
            'front matter kept' => ["---\ntheme: gaia\n---\n\n# One\n\n---\n\n# Two", "---\ntheme: gaia\n---\n\n# One\n"],
            'dashes in a fence are not a break' => ["```\n---\n```\n\n# Still one\n\n---\n\n# Two", "```\n---\n```\n\n# Still one\n"],
            'dashes under prose underline it' => ["Title\n---\n\nText\n\n---\n\n# Two", "Title\n---\n\nText\n"],
        ];
    }

    public function test_has_content(): void {
        $this->assertFalse(part::has_content(null));
        $this->assertFalse(part::has_content((object)['content' => '']));
        $this->assertFalse(part::has_content((object)['content' => "  \n\t "]));
        $this->assertTrue(part::has_content((object)['content' => '# Slide']));
    }

    public function test_media_is_not_part_of_the_hash(): void {
        // The hash covers the Markdown only, so images may be replaced without
        // invalidating anything that recorded the hash.
        $content = "# Slide\n\n![](picture.png)\n";
        $course = $this->getDataGenerator()->create_course();
        $mudeck = $this->getDataGenerator()->create_module('mudeck', ['course' => $course->id]);

        $id = part::save($mudeck, null, 'With image', $content);

        global $DB;
        $this->assertSame(sha1($content), $DB->get_field('mudeck_part', 'contenthash', ['id' => $id]));
    }
}
