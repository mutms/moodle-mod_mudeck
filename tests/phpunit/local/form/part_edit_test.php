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

namespace mod_mudeck\phpunit\local\form;

use mod_mudeck\local\form\part_edit;

/**
 * Part form test.
 *
 * @package    mod_mudeck
 * @copyright  2026 Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * @covers \mod_mudeck\local\form\part_edit
 * @covers \mod_mudeck\muform\element\partcontainer
 */
final class part_edit_test extends \advanced_testcase {
    #[\Override]
    protected function tearDown(): void {
        $_POST = [];
        unset($_SERVER['REQUEST_METHOD']);
        parent::tearDown();
    }

    public function test_form(): void {
        global $CFG, $PAGE, $USER;

        $this->resetAfterTest();
        $this->setAdminUser();

        $course = $this->getDataGenerator()->create_course();
        $mudeck = $this->getDataGenerator()->create_module('mudeck', ['course' => $course->id]);
        $context = \context_module::instance($mudeck->cmid);
        $url = new \core\url('/mod/mudeck/management/part_edit.php', ['cmid' => $mudeck->cmid]);

        $draftitemid = file_get_unused_draft_itemid();
        get_file_storage()->create_file_from_pathname([
            'contextid' => \context_user::instance($USER->id)->id, 'component' => 'user', 'filearea' => 'draft',
            'itemid' => $draftitemid, 'filepath' => '/', 'filename' => 'here.png',
        ], $CFG->dirroot . '/lib/tests/fixtures/gd-logo.png');

        $post = [
            '__formid' => str_replace('\\', '-', part_edit::class),
            '__sesskey' => sesskey(),
            'caretstart' => '3',
            'caretend' => '5',
            'name' => 'Opening',
            'content' => "# Title <b>raw</b>\n\n![A](here.png)",
            'attachments' => (string)$draftitemid,
            'saveandcontinue' => '1',
        ];
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_POST = $post;
        $form = new part_edit($url, [], ['mudeck' => $mudeck]);
        $data = $form->get_data();
        $this->assertNotNull($data);
        // Markdown is kept exactly as written.
        $this->assertSame("# Title <b>raw</b>\n\n![A](here.png)", $data->content);
        $this->assertSame(3, $data->caretstart);
        $this->assertSame(5, $data->caretend);
        $this->assertSame($draftitemid, $data->attachments);
        $this->assertNotEmpty($form->get_element('saveandcontinue')->get_value());

        $_POST = ['content' => "# Title\n\n![A](missing.png)"] + $post;
        $form = new part_edit($url, [], ['mudeck' => $mudeck]);
        $this->assertTrue($form->is_invalid());

        $PAGE->set_url($url);
        $PAGE->set_context($context);
        $html = $form->render($PAGE->get_renderer('core'));
        $this->assertStringContainsString('These files are used in the slides but have not been uploaded yet: missing.png', $html);
        // Children are placed by the partcontainer template, the preview gets its settings in the wrapper.
        $this->assertStringContainsString('data-muform-element="partcontainer"', $html);
        $this->assertStringContainsString('data-mudeck-editor="', $html);
        $this->assertStringContainsString('/api/rest/v2/mod_mudeck/part/edit/' . $draftitemid . '/images', $html);
        $this->assertMatchesRegularExpression('~data-mudeck-pane="media" hidden>\s*<div id="fitem_id_attachments~', $html);
    }
}
