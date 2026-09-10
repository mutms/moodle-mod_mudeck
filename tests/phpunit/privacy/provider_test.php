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

namespace mod_mudeck\phpunit\privacy;

use core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\approved_userlist;
use core_privacy\local\request\userlist;
use core_privacy\local\request\writer;
use mod_mudeck\privacy\provider;

/**
 * Privacy provider test.
 *
 * Sessions and completions are written straight into their tables here: what
 * matters is what happens to them when a user asks to be forgotten.
 *
 * @package    mod_mudeck
 * @copyright  2026 Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * @covers \mod_mudeck\privacy\provider
 */
final class provider_test extends \core_privacy\tests\provider_testcase {
    /** @var \stdClass course with two presentations */
    private \stdClass $course;
    /** @var \stdClass the presentation the watchers watched */
    private \stdClass $mudeck;
    /** @var \stdClass another presentation nobody touched */
    private \stdClass $other;
    /** @var \context_module context of $mudeck */
    private \context_module $context;
    /** @var \stdClass a user who watched to the end */
    private \stdClass $watcher;
    /** @var \stdClass another user who only started watching */
    private \stdClass $watcher2;
    /** @var \stdClass the teacher who wrote the slides */
    private \stdClass $author;
    /** @var \stdClass the administrator who added a site theme */
    private \stdClass $themer;
    /** @var int id of that theme */
    private int $themeid;

    public function setUp(): void {
        global $DB;

        parent::setUp();
        $this->resetAfterTest();

        $this->course = $this->getDataGenerator()->create_course();
        $this->mudeck = $this->getDataGenerator()->create_module('mudeck', ['course' => $this->course->id]);
        $this->other = $this->getDataGenerator()->create_module('mudeck', ['course' => $this->course->id]);
        $this->context = \context_module::instance($this->mudeck->cmid);

        $this->author = $this->getDataGenerator()->create_and_enrol($this->course, 'editingteacher');
        $this->watcher = $this->getDataGenerator()->create_and_enrol($this->course, 'student');
        $this->watcher2 = $this->getDataGenerator()->create_and_enrol($this->course, 'student');

        // The slides remember who saved them last.
        $this->setUser($this->author);
        /** @var \mod_mudeck_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('mod_mudeck');
        $generator->create_part(['mudeckid' => $this->mudeck->id]);

        // So does a site theme.
        $this->themer = $this->getDataGenerator()->create_user();
        $this->setUser($this->themer);
        $this->themeid = \mod_mudeck\local\theme::save((object)[
            'shortname' => 'ours',
            'name' => 'Ours',
            'css' => "/* @theme ours */\nsection { color: red; }",
        ]);
        $this->setUser();

        $DB->insert_record('mudeck_session', (object)[
            'mudeckid' => $this->mudeck->id,
            'userid' => $this->watcher->id,
            'slide' => 3,
            'slidetitle' => 'Where we got to',
            'timestarted' => 100,
            'timelastseen' => 200,
            'timeended' => 300,
        ]);
        $DB->insert_record('mudeck_session', (object)[
            'mudeckid' => $this->mudeck->id,
            'userid' => $this->watcher2->id,
            'slide' => 1,
            'timestarted' => 100,
            'timelastseen' => 150,
        ]);
        $DB->insert_record('mudeck_completed', (object)[
            'mudeckid' => $this->mudeck->id,
            'userid' => $this->watcher->id,
            'timecompleted' => 300,
        ]);
    }

    public function test_metadata_names_every_table_that_stores_something_personal(): void {
        $collection = provider::get_metadata(new \core_privacy\local\metadata\collection('mod_mudeck'));
        $tables = array_map(fn($item) => $item->get_name(), $collection->get_collection());
        $this->assertEqualsCanonicalizing(
            ['mudeck_session', 'mudeck_completed', 'mudeck_part', 'mudeck_theme'],
            $tables
        );
    }

    public function test_a_user_is_found_wherever_they_watched_or_wrote(): void {
        $watchercontexts = provider::get_contexts_for_userid($this->watcher->id)->get_contextids();
        $this->assertEquals([$this->context->id], $watchercontexts);

        // Writing the slides counts too - the part remembers who saved it.
        $authorcontexts = provider::get_contexts_for_userid($this->author->id)->get_contextids();
        $this->assertEquals([$this->context->id], $authorcontexts);

        // A site theme puts its author in the system context.
        $themercontexts = provider::get_contexts_for_userid($this->themer->id)->get_contextids();
        $this->assertEquals([\context_system::instance()->id], $themercontexts);

        $stranger = $this->getDataGenerator()->create_user();
        $this->assertEmpty(provider::get_contexts_for_userid($stranger->id)->get_contextids());
    }

    public function test_theme_authors_are_listed_in_the_system_context(): void {
        $userlist = new userlist(\context_system::instance(), 'mod_mudeck');
        provider::get_users_in_context($userlist);
        $this->assertEquals([$this->themer->id], $userlist->get_userids());
    }

    public function test_everybody_in_one_presentation_is_listed(): void {
        $userlist = new userlist($this->context, 'mod_mudeck');
        provider::get_users_in_context($userlist);
        $this->assertEqualsCanonicalizing(
            [$this->watcher->id, $this->watcher2->id, $this->author->id],
            $userlist->get_userids()
        );

        // Nobody has been near the other presentation.
        $otherlist = new userlist(\context_module::instance($this->other->cmid), 'mod_mudeck');
        provider::get_users_in_context($otherlist);
        $this->assertEmpty($otherlist->get_userids());
    }

    public function test_export_gives_a_user_their_own_watching_back(): void {
        provider::export_user_data(new approved_contextlist(
            $this->watcher,
            'mod_mudeck',
            [$this->context->id]
        ));

        $writer = writer::with_context($this->context);
        $sessions = $writer->get_data([get_string('privacy:sessions', 'mod_mudeck')]);
        $this->assertCount(1, $sessions->sessions);
        $this->assertEquals(3, $sessions->sessions[0]->slide);
        $this->assertEquals('Where we got to', $sessions->sessions[0]->slidetitle);

        $completed = $writer->get_data([get_string('privacy:completed', 'mod_mudeck')]);
        $this->assertNotEmpty($completed->timecompleted);
    }

    public function test_export_of_a_user_who_only_wrote_the_slides_is_empty(): void {
        provider::export_user_data(new approved_contextlist(
            $this->author,
            'mod_mudeck',
            [$this->context->id]
        ));

        $this->assertFalse(writer::with_context($this->context)->has_any_data());
    }

    public function test_deleting_the_activity_takes_all_watching_with_it(): void {
        global $DB;

        provider::delete_data_for_all_users_in_context($this->context);

        $this->assertCount(0, $DB->get_records('mudeck_session', ['mudeckid' => $this->mudeck->id]));
        $this->assertCount(0, $DB->get_records('mudeck_completed', ['mudeckid' => $this->mudeck->id]));
        // The slides are course content, not personal data - they stay, without the author.
        $parts = $DB->get_records('mudeck_part', ['mudeckid' => $this->mudeck->id]);
        $this->assertCount(1, $parts);
        $this->assertNull(reset($parts)->usermodified);
    }

    public function test_forgetting_the_site_keeps_the_themes_but_not_their_authors(): void {
        global $DB;

        provider::delete_data_for_all_users_in_context(\context_system::instance());

        $theme = $DB->get_record('mudeck_theme', ['id' => $this->themeid]);
        $this->assertNotEmpty($theme);
        $this->assertNull($theme->usermodified);
    }

    public function test_forgetting_the_author_unlinks_the_slides_and_the_theme(): void {
        global $DB;

        provider::delete_data_for_user(new approved_contextlist(
            $this->author,
            'mod_mudeck',
            [$this->context->id]
        ));
        $parts = $DB->get_records('mudeck_part', ['mudeckid' => $this->mudeck->id]);
        $this->assertCount(1, $parts);
        $this->assertNull(reset($parts)->usermodified);

        provider::delete_data_for_user(new approved_contextlist(
            $this->themer,
            'mod_mudeck',
            [\context_system::instance()->id]
        ));
        $this->assertNull($DB->get_field('mudeck_theme', 'usermodified', ['id' => $this->themeid]));

        provider::delete_data_for_users(new approved_userlist(
            \context_system::instance(),
            'mod_mudeck',
            [$this->themer->id]
        ));
        $this->assertCount(1, $DB->get_records('mudeck_theme', ['id' => $this->themeid]));
    }

    public function test_deleting_one_user_leaves_the_others_alone(): void {
        global $DB;

        provider::delete_data_for_user(new approved_contextlist(
            $this->watcher,
            'mod_mudeck',
            [$this->context->id]
        ));

        $this->assertCount(0, $DB->get_records('mudeck_session', ['userid' => $this->watcher->id]));
        $this->assertCount(0, $DB->get_records('mudeck_completed', ['userid' => $this->watcher->id]));
        $this->assertCount(1, $DB->get_records('mudeck_session', ['userid' => $this->watcher2->id]));
    }

    public function test_deleting_a_group_of_users_leaves_the_rest_alone(): void {
        global $DB;

        provider::delete_data_for_users(new approved_userlist(
            $this->context,
            'mod_mudeck',
            [$this->watcher2->id]
        ));

        $this->assertCount(0, $DB->get_records('mudeck_session', ['userid' => $this->watcher2->id]));
        $this->assertCount(1, $DB->get_records('mudeck_session', ['userid' => $this->watcher->id]));
        $this->assertCount(1, $DB->get_records('mudeck_completed', ['userid' => $this->watcher->id]));
    }
}
