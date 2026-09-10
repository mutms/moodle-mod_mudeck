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
use mod_mudeck\local\session;

/**
 * Device sync session test.
 *
 * @package    mod_mudeck
 * @copyright  2026 Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * @covers \mod_mudeck\local\session
 */
final class session_test extends \advanced_testcase {
    /** @var \stdClass the presentation under test */
    private \stdClass $mudeck;
    /** @var \stdClass whose devices we follow */
    private \stdClass $owner;
    /** @var \stdClass somebody else entirely */
    private \stdClass $other;
    /** @var int the only part, two slides long */
    private int $partid;

    public function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();

        $course = $this->getDataGenerator()->create_course();
        $this->mudeck = $this->getDataGenerator()->create_module(
            'mudeck',
            ['course' => $course->id, 'allowdevicesync' => 1]
        );
        $this->owner = $this->getDataGenerator()->create_and_enrol($course, 'editingteacher');
        $this->other = $this->getDataGenerator()->create_and_enrol($course, 'student');
        $this->partid = part::create($this->mudeck, 'Opening', "# One\n\n---\n\n# Two\n");
    }

    public function test_only_own_sessions_are_listed(): void {
        $this->add_session($this->owner->id, 100, 'Second');
        $this->add_session($this->owner->id, 200, 'First');
        $this->add_session($this->other->id, 300, 'Not yours');

        $own = array_values(session::get_own($this->mudeck->id, $this->owner->id));

        $this->assertCount(2, $own);
        // Newest first.
        $this->assertSame('First', $own[0]->slidetitle);
        $this->assertSame('Second', $own[1]->slidetitle);
    }

    public function test_a_session_can_only_be_deleted_by_its_owner(): void {
        global $DB;

        $theirs = $this->add_session($this->other->id, 100, 'Theirs');

        $this->assertFalse(session::delete_own($theirs, $this->owner->id));
        $this->assertTrue($DB->record_exists('mudeck_session', ['id' => $theirs]));

        $this->assertTrue(session::delete_own($theirs, $this->other->id));
        $this->assertFalse($DB->record_exists('mudeck_session', ['id' => $theirs]));
    }

    public function test_turning_device_sync_off_deletes_every_session(): void {
        global $DB;

        $this->add_session($this->owner->id, 100, 'Mine');
        $this->add_session($this->other->id, 100, 'Theirs');

        $update = (object)[
            'instance' => $this->mudeck->id,
            'coursemodule' => $this->mudeck->cmid,
            'course' => $this->mudeck->course,
            'name' => $this->mudeck->name,
            'allowdevicesync' => 0,
        ];
        mudeck_update_instance($update, null);

        $this->assertCount(0, $DB->get_records('mudeck_session', ['mudeckid' => $this->mudeck->id]));
    }

    public function test_sessions_survive_an_unrelated_edit(): void {
        global $DB;

        $this->add_session($this->owner->id, 100, 'Mine');

        $update = (object)[
            'instance' => $this->mudeck->id,
            'coursemodule' => $this->mudeck->cmid,
            'course' => $this->mudeck->course,
            'name' => 'Renamed',
            'allowdevicesync' => 1,
        ];
        mudeck_update_instance($update, null);

        $this->assertCount(1, $DB->get_records('mudeck_session', ['mudeckid' => $this->mudeck->id]));
    }

    public function test_a_quiet_session_is_over(): void {
        global $DB;

        $id = $this->add_session($this->owner->id, 100, 'Mine');
        $DB->set_field('mudeck_session', 'timelastseen', time() - session::STALEAFTER - 60, ['id' => $id]);

        $this->assertFalse(session::is_live($DB->get_record('mudeck_session', ['id' => $id])));
    }

    public function test_a_session_on_the_last_slide_is_over(): void {
        global $DB;

        $part = $DB->get_record('mudeck_part', ['id' => $this->partid], '*', MUST_EXIST);
        $id = $this->add_session($this->owner->id, time(), 'Mine');
        $session = $DB->get_record('mudeck_session', ['id' => $id]);

        // Two slides in the only part, so the second one is the end of the show.
        session::move($session, (int)$part->id, 1, 'One', $part->contenthash);
        $this->assertTrue(session::is_live($DB->get_record('mudeck_session', ['id' => $id])));

        session::move($session, (int)$part->id, 2, 'Two', $part->contenthash);
        $this->assertFalse(session::is_live($DB->get_record('mudeck_session', ['id' => $id])));
    }

    public function test_a_session_whose_slides_changed_is_over(): void {
        global $DB;

        $part = $DB->get_record('mudeck_part', ['id' => $this->partid], '*', MUST_EXIST);
        $id = $this->add_session($this->owner->id, time(), 'Mine');
        session::move($DB->get_record('mudeck_session', ['id' => $id]), (int)$part->id, 1, 'One', $part->contenthash);
        $this->assertTrue(session::is_live($DB->get_record('mudeck_session', ['id' => $id])));

        // The author edits the part the presenter is standing in.
        part::save($this->mudeck, $part, $part->name, "# One\n\n---\n\n# Two\n\n---\n\n# Three\n");

        $this->assertFalse(session::is_live($DB->get_record('mudeck_session', ['id' => $id])));
    }

    public function test_a_session_that_has_not_started_yet_is_running_but_cannot_be_followed(): void {
        global $DB;

        $id = $this->add_session($this->owner->id, time(), null);
        $session = $DB->get_record('mudeck_session', ['id' => $id]);

        $this->assertTrue(session::is_live($session), 'it is listed as running');
        $this->assertFalse(session::is_followable($session), 'but there is no slide to follow yet');
    }

    public function test_only_finished_sessions_are_deleted_in_bulk(): void {
        global $DB;

        $live = $this->add_session($this->owner->id, time(), 'Mine');
        $old = $this->add_session($this->owner->id, 100, 'Older');
        $DB->set_field('mudeck_session', 'timelastseen', time() - session::STALEAFTER - 60, ['id' => $old]);
        $theirs = $this->add_session($this->other->id, 100, 'Theirs');
        $DB->set_field('mudeck_session', 'timelastseen', time() - session::STALEAFTER - 60, ['id' => $theirs]);

        $this->assertSame(1, session::delete_finished($this->mudeck->id, $this->owner->id));

        $this->assertTrue($DB->record_exists('mudeck_session', ['id' => $live]));
        $this->assertFalse($DB->record_exists('mudeck_session', ['id' => $old]));
        // Somebody else's old session is not ours to tidy up.
        $this->assertTrue($DB->record_exists('mudeck_session', ['id' => $theirs]));
    }

    /**
     * Write one session straight into the table - nothing creates them yet.
     *
     * @param int $userid
     * @param int $started
     * @param string|null $slidetitle
     * @return int session id
     */
    private function add_session(int $userid, int $started, ?string $slidetitle): int {
        global $DB;

        return $DB->insert_record('mudeck_session', (object)[
            'mudeckid' => $this->mudeck->id,
            'userid' => $userid,
            'slidetitle' => $slidetitle,
            'timestarted' => $started,
            'timelastseen' => $started,
        ]);
    }
}
