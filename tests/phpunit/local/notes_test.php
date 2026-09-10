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

use mod_mudeck\local\notes;

/**
 * Presenter notes test.
 *
 * @package    mod_mudeck
 * @copyright  2026 Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * @covers \mod_mudeck\local\notes
 */
final class notes_test extends \advanced_testcase {
    public function test_strip_removes_notes(): void {
        $markdown = "# Title\n\n<!-- Remember the equation here. -->\n\nBody\n";
        $result = notes::strip($markdown);

        $this->assertStringNotContainsString('Remember the equation', $result);
        $this->assertStringContainsString('# Title', $result);
        $this->assertStringContainsString('Body', $result);
    }

    public function test_strip_keeps_directives(): void {
        $markdown = "<!-- _class: lead -->\n\n# Title\n\n<!-- paginate: true -->\n";
        $result = notes::strip($markdown);

        $this->assertStringContainsString('_class: lead', $result);
        $this->assertStringContainsString('paginate: true', $result);
    }

    public function test_strip_keeps_a_comment_that_mixes_directive_and_prose(): void {
        // A comment holding a directive is configuration, it stays as it is.
        $markdown = "<!--\n_class: lead\nsay hello first\n-->\n";
        $result = notes::strip($markdown);

        $this->assertStringContainsString('_class: lead', $result);
    }

    public function test_strip_handles_several_notes(): void {
        $markdown = "<!-- first note -->\n# A\n<!-- second note -->\n# B\n";
        $result = notes::strip($markdown);

        $this->assertStringNotContainsString('first note', $result);
        $this->assertStringNotContainsString('second note', $result);
        $this->assertStringContainsString('# A', $result);
        $this->assertStringContainsString('# B', $result);
    }

    public function test_strip_leaves_plain_markdown_alone(): void {
        $markdown = "# Title\n\nJust text.\n";
        $this->assertSame($markdown, notes::strip($markdown));
    }

    public function test_exist(): void {
        $this->assertTrue(notes::exist("# A\n<!-- a note -->\n"));
        $this->assertFalse(notes::exist("# A\n<!-- paginate: true -->\n"));
        $this->assertFalse(notes::exist("# A\n"));
    }
}
