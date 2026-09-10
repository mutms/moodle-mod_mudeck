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

use mod_mudeck\local\theme;

/** @var moodle_page $PAGE */
/** @var core_renderer $OUTPUT */

require(__DIR__ . '/../../../config.php');

/** @var stdClass $CFG */
require_once($CFG->libdir . '/adminlib.php');

$delete = optional_param('delete', 0, PARAM_INT);
$confirm = optional_param('confirm', 0, PARAM_BOOL);

admin_externalpage_setup('mudeckthemes');

$currenturl = new \core\url('/mod/mudeck/management/themes.php');

if ($delete) {
    $existing = theme::get_site_theme($delete);
    if (!$existing) {
        redirect($currenturl);
    }
    if (!$confirm) {
        echo $OUTPUT->header();
        // How many presentations lose their look is the one fact worth knowing here.
        $used = theme::count_uses($existing->shortname);
        echo $OUTPUT->confirm(
            get_string('theme_delete_confirm', 'mod_mudeck', (object)[
                'name' => format_string($existing->name),
                'used' => $used,
            ]),
            new \core\url($currenturl, ['delete' => $delete, 'confirm' => 1, 'sesskey' => sesskey()]),
            $currenturl
        );
        echo $OUTPUT->footer();
        return;
    }
    require_sesskey();
    theme::delete($delete);
    redirect($currenturl, get_string('theme_deleted', 'mod_mudeck'), null, \core\output\notification::NOTIFY_SUCCESS);
}

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('theme_manage', 'mod_mudeck'));

$rows = [];
foreach (theme::get_site_themes() as $sitetheme) {
    $rows[] = [
        'name' => format_string($sitetheme->name),
        'shortname' => s($sitetheme->shortname),
        'used' => theme::count_uses($sitetheme->shortname),
        'editurl' => (new \core\url('/mod/mudeck/management/theme_edit.php', ['id' => $sitetheme->id]))->out(false),
        'deleteurl' => (new \core\url($currenturl, ['delete' => $sitetheme->id]))->out(false),
    ];
}

echo $OUTPUT->render_from_template('mod_mudeck/themes', [
    'hasthemes' => (bool)$rows,
    'themes' => $rows,
    'addurl' => (new \core\url('/mod/mudeck/management/theme_edit.php'))->out(false),
]);

echo $OUTPUT->footer();
