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

use mod_mudeck\local\theme;

/**
 * Theme registry test.
 *
 * @package    mod_mudeck
 * @copyright  2026 Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * @covers \mod_mudeck\local\theme
 */
final class theme_test extends \advanced_testcase {
    public function test_get_menu(): void {
        $menu = theme::get_menu();

        $this->assertArrayHasKey(theme::DEFAULTTHEME, $menu);
        foreach ($menu as $name => $label) {
            $this->assertIsString($name);
            $this->assertNotEmpty($label, "theme {$name} must have a label");
        }
    }

    public function test_is_valid(): void {
        $this->assertTrue(theme::is_valid(theme::DEFAULTTHEME));
        $this->assertFalse(theme::is_valid('no-such-theme'));
        $this->assertFalse(theme::is_valid(''));
        $this->assertFalse(theme::is_valid(null));
    }

    /**
     * Add a theme the way the administration form does.
     *
     * @param string $shortname
     * @param string $name
     * @param string $css
     * @return int
     */
    private function add(string $shortname, string $name, string $css = 'section { color: red; }'): int {
        return theme::save((object)[
            'shortname' => $shortname,
            'name' => $name,
            'css' => $css,
        ]);
    }

    public function test_a_saved_theme_can_be_chosen_and_is_served(): void {
        $this->resetAfterTest();
        $this->setAdminUser();
        $this->add('acme', 'Acme corporate');

        $menu = theme::get_menu();
        $this->assertArrayHasKey('acme', $menu);
        $this->assertSame('Acme corporate', $menu['acme']);
        // Everything a shipped theme gets, one added by the site gets too.
        $this->assertTrue(theme::is_valid('acme'));
        $this->assertArrayHasKey('acme', theme::get_custom_css());
        $this->assertStringContainsString('color: red', theme::get_custom_css()['acme']);
    }

    public function test_the_short_name_is_written_into_the_css(): void {
        $this->resetAfterTest();
        $this->setAdminUser();

        // Marp registers a theme under the name in the CSS, so that is the one that has
        // to match - whatever the author of the CSS wrote there, or did not write.
        $this->add('acme', 'Acme', "/* @theme somethingelse */\nsection { color: red; }");
        $this->assertStringStartsWith('/* @theme acme */', theme::get_custom_css()['acme']);

        $this->add('plain', 'Plain', 'section { color: blue; }');
        $this->assertStringStartsWith('/* @theme plain */', theme::get_custom_css()['plain']);
        $this->assertStringContainsString('color: blue', theme::get_custom_css()['plain']);
    }

    public function test_shipped_themes_are_registered_under_their_file_name(): void {
        // A shipped theme whose "@theme" comment says something else - a copy somebody
        // renamed, say - would otherwise be listed under one name and register under
        // another, and nothing would ever pick it.
        foreach (theme::get_custom_css() as $name => $css) {
            $this->assertStringStartsWith("/* @theme {$name} */", $css);
        }
    }

    public function test_the_name_keeps_no_markup(): void {
        $this->resetAfterTest();
        $this->setAdminUser();

        $id = $this->add('acme', '<b>Acme</b> corporate');
        $this->assertSame('Acme corporate', theme::get_site_theme($id)->name);
    }

    public function test_shipped_themes_cannot_be_shadowed(): void {
        // The form refuses these; this is the list it refuses them from.
        $reserved = theme::get_reserved_names();

        $this->assertContains('default', $reserved);
        $this->assertContains('gaia', $reserved);
        $this->assertContains('uncover', $reserved);
        foreach (theme::get_custom_names() as $shipped) {
            $this->assertContains($shipped, $reserved, 'every themes/ file is reserved');
        }
    }

    public function test_a_theme_knows_how_many_presentations_use_it(): void {
        $this->resetAfterTest();
        $this->setAdminUser();
        $this->add('acme', 'Acme');

        $course = $this->getDataGenerator()->create_course();
        $this->getDataGenerator()->create_module('mudeck', ['course' => $course->id, 'theme' => 'acme']);
        $this->getDataGenerator()->create_module('mudeck', ['course' => $course->id, 'theme' => 'acme']);
        $this->getDataGenerator()->create_module('mudeck', ['course' => $course->id, 'theme' => 'default']);

        $this->assertSame(2, theme::count_uses('acme'));
        $this->assertSame(0, theme::count_uses('nobody'));
    }

    public function test_deleting_leaves_the_presentations_alone(): void {
        global $DB;

        $this->resetAfterTest();
        $this->setAdminUser();
        $id = $this->add('acme', 'Acme');

        $course = $this->getDataGenerator()->create_course();
        $mudeck = $this->getDataGenerator()->create_module('mudeck', ['course' => $course->id, 'theme' => 'acme']);

        theme::delete($id);

        $this->assertArrayNotHasKey('acme', theme::get_menu());
        // The deck still names it, and falls back to Marp's own look.
        $this->assertSame('acme', $DB->get_field('mudeck', 'theme', ['id' => $mudeck->id]));
    }

    public function test_an_activity_may_simply_follow_the_site(): void {
        $this->resetAfterTest();
        $this->setAdminUser();

        // Nothing chosen resolves to the site's theme, and keeps resolving to it when
        // the site changes its mind - which is the point of not choosing.
        $this->assertSame(theme::DEFAULTTHEME, theme::resolve(''));
        $this->add('acme', 'Acme');
        set_config('defaulttheme', 'acme', 'mod_mudeck');
        $this->assertSame('acme', theme::resolve(''));
        $this->assertSame('acme', theme::get_site_default());

        // A theme that was deleted since falls the same way.
        $this->assertSame('acme', theme::resolve('deletedtheme'));
        // One that exists is left alone.
        $this->assertSame('gaia', theme::resolve('gaia'));

        $menu = theme::get_activity_menu();
        $this->assertArrayHasKey('', $menu);
        $this->assertStringContainsString('Acme', $menu['']);
    }

    public function test_a_site_default_that_disappears_falls_back_again(): void {
        $this->resetAfterTest();
        $this->setAdminUser();
        $id = $this->add('acme', 'Acme');
        set_config('defaulttheme', 'acme', 'mod_mudeck');

        theme::delete($id);

        // Nothing is left to fall back to but Marp's own.
        $this->assertSame(theme::DEFAULTTHEME, theme::get_site_default());
        $this->assertSame(theme::DEFAULTTHEME, theme::resolve(''));
    }

    public function test_editing_keeps_the_same_theme(): void {
        $this->resetAfterTest();
        $this->setAdminUser();
        $id = $this->add('acme', 'Acme');

        theme::save((object)[
            'id' => $id,
            'shortname' => 'acme',
            'name' => 'Acme renamed',
            'css' => 'section { color: green; }',
        ]);

        $this->assertCount(1, theme::get_site_themes());
        $this->assertSame('Acme renamed', theme::get_menu()['acme']);
        $this->assertStringContainsString('color: green', theme::get_custom_css()['acme']);
    }
}
