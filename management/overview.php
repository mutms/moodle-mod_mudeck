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
 * Overview of the parts a presentation is made of.
 *
 * Only lists them - each action has a page of its own beside this one.
 *
 * @package    mod_mudeck
 * @copyright  2026 Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use core\url;
use mod_mudeck\local\media;
use mod_mudeck\local\part;
use mod_mudeck\local\theme;

// phpcs:disable moodle.Commenting.InlineComment.TypeHintingMatch
/** @var moodle_database $DB */
/** @var moodle_page $PAGE */
/** @var \core\output\core_renderer $OUTPUT */
// phpcs:enable moodle.Commenting.InlineComment.TypeHintingMatch

require(__DIR__ . '/../../../config.php');

$cmid = required_param('cmid', PARAM_INT);

$cm = get_coursemodule_from_id('mudeck', $cmid, 0, false, MUST_EXIST);
$course = $DB->get_record('course', ['id' => $cm->course], '*', MUST_EXIST);
$mudeck = $DB->get_record('mudeck', ['id' => $cm->instance], '*', MUST_EXIST);
$context = context_module::instance($cm->id);

require_login($course, false, $cm);
require_capability('mod/mudeck:view', $context);
require_capability('mod/mudeck:edit', $context);

$currenturl = new url('/mod/mudeck/management/overview.php', ['cmid' => $cm->id]);

$PAGE->set_context($context);
$PAGE->set_url($currenturl);
$PAGE->set_title($mudeck->name);
$PAGE->set_secondary_active_tab('mudeckoverview');
$PAGE->add_body_class('limitedwidth');
$PAGE->set_show_navigation_footer(false);
mudeck_name_presentation_tab($PAGE);
// The description and the completion tick belong on the welcome page, not over every
// management screen.
$PAGE->activityheader->set_hidecompletion(true);
$PAGE->activityheader->set_description('');

$parts = array_values(part::get_all($mudeck->id));
$rows = [];
foreach ($parts as $index => $onepart) {
    $rows[] = [
        'id' => (int)$onepart->id,
        'hash' => $onepart->contenthash,
        // The slides are rendered in the browser, exactly as the show renders them.
        'markdown' => media::rewrite($onepart->content, $context, (int)$onepart->id),
        // A theme that no longer exists falls back to the site's, never to nothing.
        'theme' => theme::resolve($mudeck->theme),
        'name' => format_string($onepart->name),
        'slides' => part::count_slides($onepart),
        'empty' => !part::has_content($onepart),
        'editurl' => (new url(
            '/mod/mudeck/management/part_edit.php',
            ['cmid' => $cm->id, 'partid' => $onepart->id]
        ))->out(false),
        'previewurl' => !part::has_content($onepart) ? '' : (new url(
            '/mod/mudeck/management/part_preview.php',
            ['cmid' => $cm->id, 'partid' => $onepart->id]
        ))->out(false),
        'exporturl' => (new url(
            '/mod/mudeck/management/part_export.php',
            ['cmid' => $cm->id, 'partid' => $onepart->id]
        ))->out(false),
        'upurl' => $index > 0 ? (new url(
            '/mod/mudeck/management/part_move.php',
            ['cmid' => $cm->id, 'partid' => $onepart->id, 'direction' => 'up', 'sesskey' => sesskey()]
        ))->out(false) : '',
        'downurl' => $index < count($parts) - 1 ? (new url(
            '/mod/mudeck/management/part_move.php',
            ['cmid' => $cm->id, 'partid' => $onepart->id, 'direction' => 'down', 'sesskey' => sesskey()]
        ))->out(false) : '',
        'deleteurl' => (new url(
            '/mod/mudeck/management/part_delete.php',
            ['cmid' => $cm->id, 'partid' => $onepart->id]
        ))->out(false),
    ];
}

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('overview', 'mod_mudeck'));
echo $OUTPUT->render_from_template('mod_mudeck/overview', [
    'parts' => $rows,
    'partsjson' => json_encode($rows),
    'themecssjson' => json_encode(theme::get_custom_css(new url('/mod/mudeck'))),
    // Where a part reports its new place; the id is appended by the browser.
    'parturljson' => json_encode(url::routed_path('/api/rest/v2/mod_mudeck/part/')->out(false)),
    'sesskeyjson' => json_encode(sesskey()),
    'labelsjson' => json_encode([
        'edit' => get_string('part_edit', 'mod_mudeck'),
        'export' => get_string('export', 'mod_mudeck'),
        'delete' => get_string('delete'),
        'deletetitle' => get_string('part_delete', 'mod_mudeck'),
        'deleteconfirm' => get_string('part_delete_confirm', 'mod_mudeck', '{$a}'),
        'move' => get_string('part_move', 'mod_mudeck'),
        'moveto' => get_string('part_moveto', 'mod_mudeck'),
        'moved' => get_string('part_moved', 'mod_mudeck', '{$a}'),
        'empty' => get_string('part_empty', 'mod_mudeck'),
        'show' => get_string('part_preview', 'mod_mudeck'),
        'noparts' => get_string('nopartcontent', 'mod_mudeck'),
    ]),
    'hasparts' => (bool)$rows,
    'addurl' => (new url('/mod/mudeck/management/part_edit.php', ['cmid' => $cm->id]))->out(false),
    'importurl' => (new url('/mod/mudeck/management/part_import.php', ['cmid' => $cm->id]))->out(false),
    'printurl' => $rows && has_capability('mod/mudeck:fullaccess', $context)
        ? (new url('/mod/mudeck/management/print.php', ['cmid' => $cm->id]))->out(false)
        : '',
    'backurl' => (new url('/mod/mudeck/view.php', ['id' => $cm->id]))->out(false),
]);
echo $OUTPUT->footer();
