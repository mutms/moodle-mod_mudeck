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

use mod_mudeck\local\form\theme_edit;

/**
 * Theme form test.
 *
 * @package    mod_mudeck
 * @copyright  2026 Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * @covers \mod_mudeck\local\form\theme_edit
 */
final class theme_edit_test extends \advanced_testcase {
    #[\Override]
    protected function tearDown(): void {
        $_POST = [];
        unset($_SERVER['REQUEST_METHOD']);
        parent::tearDown();
    }

    /**
     * Submit the form.
     *
     * @param string $shortname
     * @param int $id edited theme id
     * @return theme_edit
     */
    private function submit(string $shortname, int $id = 0): theme_edit {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_POST = [
            '__formid' => str_replace('\\', '-', theme_edit::class),
            '__sesskey' => sesskey(),
            'name' => 'Some theme',
            'shortname' => $shortname,
            'css' => 'section { color: red; }',
            'submit' => '1',
        ];
        return new theme_edit(new \core\url('/mod/mudeck/management/theme_edit.php'), [], ['id' => $id]);
    }

    public function test_validation(): void {
        $this->resetAfterTest();
        $this->setAdminUser();

        /** @var \mod_mudeck_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('mod_mudeck');
        $acme = $generator->create_theme(['name' => 'Acme', 'shortname' => 'acme', 'css' => 'section {}']);

        $this->assertTrue($this->submit('other')->is_valid());
        $this->assertTrue($this->submit('acme', (int)$acme->id)->is_valid());

        $form = $this->submit('acme');
        $this->assertFalse($form->is_valid());
        $this->assertStringContainsString('Another theme already uses this short name.', $this->render($form));

        $form = $this->submit('gaia');
        $this->assertStringContainsString('A theme that comes with the plugin already uses this short name.', $this->render($form));

        $form = $this->submit('other theme');
        $this->assertStringContainsString('Use letters, digits, dash and underscore only.', $this->render($form));
    }

    /**
     * Render the form.
     *
     * @param theme_edit $form
     * @return string
     */
    private function render(theme_edit $form): string {
        global $PAGE;
        $PAGE->set_url('/mod/mudeck/management/theme_edit.php');
        $PAGE->set_context(\context_system::instance());
        return $form->render($PAGE->get_renderer('core'));
    }
}
