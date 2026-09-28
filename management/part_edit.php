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
 * Edit the slides of a presentation.
 *
 * @package    mod_mudeck
 * @copyright  2026 Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use core\url;
use mod_mudeck\local\media;
use mod_mudeck\local\part;
use mod_mudeck\local\form\part_edit;
use tool_mulib\muform\util\file_area;

// phpcs:disable moodle.Commenting.InlineComment.TypeHintingMatch
/** @var stdClass $CFG */
/** @var moodle_database $DB */
/** @var moodle_page $PAGE */
/** @var \core\output\core_renderer $OUTPUT */
// phpcs:enable moodle.Commenting.InlineComment.TypeHintingMatch

require(__DIR__ . '/../../../config.php');

$cmid = required_param('cmid', PARAM_INT);
$partid = optional_param('partid', 0, PARAM_INT);
$returnto = optional_param('returnto', '', PARAM_ALPHA);
// Caret position restored after save and continue.
$caretstart = optional_param('caretstart', 0, PARAM_INT);
$caretend = optional_param('caretend', 0, PARAM_INT);

$cm = get_coursemodule_from_id('mudeck', $cmid, 0, false, MUST_EXIST);
$course = $DB->get_record('course', ['id' => $cm->course], '*', MUST_EXIST);
$mudeck = $DB->get_record('mudeck', ['id' => $cm->instance], '*', MUST_EXIST);
$context = context_module::instance($cm->id);

require_login($course, false, $cm); // Includes the 'mod/mudeck:view' check.
require_capability('mod/mudeck:edit', $context);

$viewurl = $returnto === 'view'
    ? new url('/mod/mudeck/view.php', ['id' => $cm->id])
    : new url('/mod/mudeck/management/overview.php', ['cmid' => $cm->id]);
$currenturl = new url('/mod/mudeck/management/part_edit.php', ['cmid' => $cm->id, 'partid' => $partid]);
if ($returnto !== '') {
    $currenturl->param('returnto', $returnto);
}

$PAGE->set_context($context);
$PAGE->set_url($currenturl);
$PAGE->set_title($mudeck->name);

$PAGE->set_pagelayout('embedded');
$PAGE->set_show_navigation_footer(false);
$PAGE->activityheader->disable();
$PAGE->add_body_class('mudeck-editor-page');

$existing = null;
if ($partid) {
    $existing = $DB->get_record('mudeck_part', ['id' => $partid, 'mudeckid' => $mudeck->id], '*', MUST_EXIST);
}

$currentdata = [
    'name' => $existing->name ?? get_string('part_new', 'mod_mudeck', count(part::get_all($mudeck->id)) + 1),
    'content' => $existing->content ?? get_string('part_starter', 'mod_mudeck'),
    'attachments' => new file_area($context, 'mod_mudeck', media::FILEAREA, $existing->id ?? null),
    'caretstart' => $caretstart,
    'caretend' => $caretend,
];
$form = new part_edit($currenturl, $currentdata, ['mudeck' => $mudeck]);

if ($form->is_cancelled()) {
    redirect($viewurl);
}
if ($data = $form->get_data()) {
    // A pasted draft URL or pluginfile URL is brought back to the bare file name, so the
    // stored Markdown stays the portable form and keeps working after the draft is gone.
    $content = media::normalise($data->content, $context, $existing->id ?? 0, (int)$data->attachments);
    // A brand new part has to exist before its files can be attached to it,
    // because the file area is keyed by the part id.
    $partid = part::save($mudeck, $existing, $data->name, $content);
    $form->get_element('attachments')->export_to_file_area(new file_area($context, 'mod_mudeck', media::FILEAREA, $partid));
    if (!$form->get_element('saveandcontinue')->get_value()) {
        redirect($viewurl, get_string('part_saved', 'mod_mudeck'), null, \core\output\notification::NOTIFY_SUCCESS);
    }
    // Continue editing the saved part, a created part has an id from now on.
    $currenturl->param('partid', $partid);
    $currenturl->param('caretstart', $data->caretstart);
    $currenturl->param('caretend', $data->caretend);
    redirect($currenturl, get_string('part_saved', 'mod_mudeck'), null, \core\output\notification::NOTIFY_SUCCESS);
}

echo $OUTPUT->header();

echo $OUTPUT->render_from_template('mod_mudeck/editor', [
    'formhtml' => $form->render($OUTPUT),
]);

echo $OUTPUT->footer();
