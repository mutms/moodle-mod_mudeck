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

namespace mod_mudeck\phpunit\event;

use mod_mudeck\local\media;
use mod_mudeck\local\part;

/**
 * Event test - what shows up in the logs when parts are written, imported or exported.
 *
 * The events fire inside the API, so the pages cannot forget to report a change.
 *
 * @package    mod_mudeck
 * @copyright  2026 Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * @covers \mod_mudeck\event\part_base
 */
final class events_test extends \advanced_testcase {
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

    public function test_creating_a_part_is_reported(): void {
        $sink = $this->redirectEvents();
        $partid = part::create($this->mudeck, 'Opening', "# Hello\n");
        $events = $sink->get_events();

        $this->assertCount(1, $events);
        $event = reset($events);
        $this->assertInstanceOf(\mod_mudeck\event\part_created::class, $event);
        $this->assertEquals($this->context, $event->get_context());
        $this->assertEquals($partid, $event->objectid);
        $this->assertEquals('Opening', $event->other['name']);
        $this->assertStringContainsString('created the part', $event->get_description());
    }

    public function test_saving_a_part_reports_a_creation_and_then_an_update(): void {
        global $DB;

        $sink = $this->redirectEvents();
        $partid = part::save($this->mudeck, null, 'Opening', "# Hello\n");
        $part = $DB->get_record('mudeck_part', ['id' => $partid], '*', MUST_EXIST);
        part::save($this->mudeck, $part, 'Opening again', "# Hello again\n");
        $events = $sink->get_events();

        $this->assertCount(2, $events);
        $this->assertInstanceOf(\mod_mudeck\event\part_created::class, $events[0]);
        $this->assertInstanceOf(\mod_mudeck\event\part_updated::class, $events[1]);
        $this->assertEquals('Opening again', $events[1]->other['name']);
    }

    public function test_moving_a_part_is_an_update(): void {
        global $DB;

        part::create($this->mudeck, 'One');
        $secondid = part::create($this->mudeck, 'Two');
        $second = $DB->get_record('mudeck_part', ['id' => $secondid], '*', MUST_EXIST);

        $sink = $this->redirectEvents();
        part::move($second, -1);
        $events = $sink->get_events();

        $this->assertCount(1, $events);
        $this->assertInstanceOf(\mod_mudeck\event\part_updated::class, $events[0]);
        $this->assertEquals($secondid, $events[0]->objectid);
    }

    public function test_deleting_a_part_is_reported_after_it_is_gone(): void {
        global $DB;

        $partid = part::create($this->mudeck, 'Opening', "# Hello\n");
        $part = $DB->get_record('mudeck_part', ['id' => $partid], '*', MUST_EXIST);

        $sink = $this->redirectEvents();
        part::delete($part, $this->context);
        $events = $sink->get_events();

        $this->assertCount(1, $events);
        $this->assertInstanceOf(\mod_mudeck\event\part_deleted::class, $events[0]);
        $this->assertEquals($partid, $events[0]->objectid);
        $this->assertFalse($DB->record_exists('mudeck_part', ['id' => $partid]));
    }

    public function test_importing_and_exporting_are_reported(): void {
        global $DB;

        $partid = part::create($this->mudeck, 'Opening', "# Hello\n");
        $zip = $this->make_archive(['slides.md' => "# Imported\n"]);

        $sink = $this->redirectEvents();
        media::unpack($this->context, $partid, $zip);
        $part = $DB->get_record('mudeck_part', ['id' => $partid], '*', MUST_EXIST);
        media::export_archive($part, $this->context);
        $events = $sink->get_events();

        $this->assertCount(2, $events);
        $this->assertInstanceOf(\mod_mudeck\event\part_imported::class, $events[0]);
        $this->assertInstanceOf(\mod_mudeck\event\part_exported::class, $events[1]);
        $this->assertEquals($partid, $events[0]->objectid);
    }

    public function test_an_upload_that_is_not_a_deck_is_not_an_import(): void {
        $partid = part::create($this->mudeck, 'Opening', "# Hello\n");
        $zip = $this->make_archive(['one.md' => '# One', 'two.md' => '# Two']);

        $sink = $this->redirectEvents();
        media::unpack($this->context, $partid, $zip);

        $this->assertCount(0, $sink->get_events());
    }

    /**
     * Build a zip on disk from the given files.
     *
     * @param array $files path in the archive => content
     * @return string full path of the archive
     */
    private function make_archive(array $files): string {
        $target = make_request_directory() . '/deck.zip';
        get_file_packer('application/zip')->archive_to_pathname(
            array_map(fn($content) => [$content], $files),
            $target
        );
        return $target;
    }
}
