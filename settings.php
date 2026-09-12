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

/**
 * Markdown slide deck admin settings.
 *
 * @package    mod_mudeck
 * @copyright  2026 Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

use core\lang_string;
use core\url;
use core_admin\setting\setting\configselect;
use core_admin\setting\settingpage\settingpage;
use core_admin\setting\tree\category;
use core_admin\setting\tree\externalpage;
use mod_mudeck\local\theme;

// phpcs:disable moodle.Commenting.InlineComment.TypeHintingMatch
/** @var \core_admin\setting\tree\root $ADMIN */
/** @var \core\plugininfo\mod $module */
/** @var string $section */
// phpcs:enable moodle.Commenting.InlineComment.TypeHintingMatch

$ADMIN->add('modsettings', new category(
    'modmudeckfolder',
    new lang_string('pluginname', 'mod_mudeck'),
    $module->is_enabled() === false
));

$page = new settingpage(
    $section,
    get_string('settings'),
    'moodle/site:config',
    $module->is_enabled() === false
);

if ($ADMIN->fulltree) {
    $page->add(new configselect(
        'mod_mudeck/defaulttheme',
        get_string('theme_default_site', 'mod_mudeck'),
        get_string('theme_default_site_desc', 'mod_mudeck'),
        theme::DEFAULTTHEME,
        theme::get_menu(...)
    ));
}

$ADMIN->add('modmudeckfolder', $page);

$ADMIN->add('modmudeckfolder', new externalpage(
    'mudeckthemes',
    get_string('theme_manage', 'mod_mudeck'),
    new url('/mod/mudeck/management/themes.php'),
    'mod/mudeck:managethemes'
));

// Do not use standard settings page.
$settings = null;
