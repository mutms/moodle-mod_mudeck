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
 * The themes a site added for itself.
 *
 * An administration page: the CSS written here is served to everybody who sees a
 * presentation, so getting to it takes mod/mudeck:managethemes, which no role holds
 * by default.
 *
 * @package    mod_mudeck
 * @copyright  2026 Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use core\url;
use mod_mudeck\local\theme;

// phpcs:disable moodle.Commenting.InlineComment.TypeHintingMatch
/** @var stdClass $CFG */
/** @var moodle_page $PAGE */
/** @var \core\output\core_renderer $OUTPUT */
// phpcs:enable moodle.Commenting.InlineComment.TypeHintingMatch

require(__DIR__ . '/../../../config.php');

require_once($CFG->libdir . '/adminlib.php');

admin_externalpage_setup('mudeckthemes');

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('theme_manage', 'mod_mudeck'));

$rows = [];
foreach (theme::get_site_themes() as $sitetheme) {
    $rows[] = [
        'name' => format_string($sitetheme->name),
        'shortname' => s($sitetheme->shortname),
        'used' => theme::count_uses($sitetheme->shortname),
        'editurl' => (new url('/mod/mudeck/management/theme_edit.php', ['id' => $sitetheme->id]))->out(false),
        'deleteurl' => (new url('/mod/mudeck/management/theme_delete.php', ['id' => $sitetheme->id]))->out(false),
    ];
}

echo $OUTPUT->render_from_template('mod_mudeck/themes', [
    'hasthemes' => (bool)$rows,
    'themes' => $rows,
    'addurl' => (new url('/mod/mudeck/management/theme_edit.php'))->out(false),
]);

echo $OUTPUT->footer();
