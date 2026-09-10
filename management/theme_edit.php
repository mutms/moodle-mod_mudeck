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
 * Add or edit one of the site's own themes.
 *
 * @package    mod_mudeck
 * @copyright  2026 Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use mod_mudeck\local\form\theme_edit;
use mod_mudeck\local\theme;

/** @var moodle_page $PAGE */
/** @var core_renderer $OUTPUT */

require(__DIR__ . '/../../../config.php');

/** @var stdClass $CFG */
require_once($CFG->libdir . '/adminlib.php');

$id = optional_param('id', 0, PARAM_INT);

admin_externalpage_setup('mudeckthemes');

$returnurl = new \core\url('/mod/mudeck/management/themes.php');
$currenturl = new \core\url('/mod/mudeck/management/theme_edit.php', $id ? ['id' => $id] : []);
$PAGE->set_url($currenturl);

$existing = $id ? theme::get_site_theme($id) : null;
if ($id && !$existing) {
    redirect($returnurl);
}

$form = new theme_edit($currenturl->out(false));
if ($form->is_cancelled()) {
    redirect($returnurl);
}
if ($data = $form->get_data()) {
    theme::save((object)[
        'id' => $data->id ?: null,
        'shortname' => $data->shortname,
        'name' => $data->name,
        'css' => $data->css,
    ]);
    redirect($returnurl, get_string('theme_saved', 'mod_mudeck'), null, \core\output\notification::NOTIFY_SUCCESS);
}

$form->set_data([
    'id' => $existing->id ?? 0,
    'name' => $existing->name ?? '',
    'shortname' => $existing->shortname ?? '',
    // A theme that only changes a colour or two starts by importing one that exists.
    'css' => $existing->css ?? get_string('theme_css_starter', 'mod_mudeck'),
]);

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string($existing ? 'theme_edit' : 'theme_add', 'mod_mudeck'));
$form->display();
echo $OUTPUT->footer();
