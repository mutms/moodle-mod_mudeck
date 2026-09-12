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
use mod_mudeck\local\theme;
use mod_mudeck\local\form\part_edit;

// phpcs:disable moodle.Commenting.InlineComment.TypeHintingMatch
/** @var stdClass $CFG */
/** @var moodle_database $DB */
/** @var moodle_page $PAGE */
/** @var \core\output\core_renderer $OUTPUT */
// phpcs:enable moodle.Commenting.InlineComment.TypeHintingMatch

require(__DIR__ . '/../../../config.php');

require_once($CFG->libdir . '/formslib.php');

$cmid = required_param('cmid', PARAM_INT);
$partid = optional_param('partid', 0, PARAM_INT);
// Where the writing started, so it can end there. A word rather than a URL: nothing
// arriving in a parameter should be able to say where this page sends somebody next.
$returnto = optional_param('returnto', '', PARAM_ALPHA);

$cm = get_coursemodule_from_id('mudeck', $cmid, 0, false, MUST_EXIST);
$course = $DB->get_record('course', ['id' => $cm->course], '*', MUST_EXIST);
$mudeck = $DB->get_record('mudeck', ['id' => $cm->instance], '*', MUST_EXIST);
$context = context_module::instance($cm->id);

require_login($course, false, $cm);
require_capability('mod/mudeck:edit', $context);

// Back to the welcome page when that is where this started and the presentation is
// still the single part it was. Once there are several, the overview is the place that
// can show what happened to them.
$viewurl = $returnto === 'view' && count(part::get_all($mudeck->id)) === 1
    ? new url('/mod/mudeck/view.php', ['id' => $cm->id])
    : new url('/mod/mudeck/management/overview.php', ['cmid' => $cm->id]);
$currenturl = new url('/mod/mudeck/management/part_edit.php', ['cmid' => $cm->id, 'partid' => $partid]);
if ($returnto !== '') {
    $currenturl->param('returnto', $returnto);
}

$PAGE->set_context($context);
$PAGE->set_url($currenturl);
$PAGE->set_title($mudeck->name);

// Nothing around the editor: with unsaved text on screen, the linear navigation at
// the bottom of a course page is a trap rather than a convenience.
$PAGE->set_pagelayout('embedded');
$PAGE->set_show_navigation_footer(false);
$PAGE->activityheader->disable();
// Lets the stylesheet keep notifications out from under the preview column.
$PAGE->add_body_class('mudeck-editor-page');

$existing = null;
if ($partid) {
    $existing = $DB->get_record('mudeck_part', ['id' => $partid, 'mudeckid' => $mudeck->id], '*', MUST_EXIST);
}
$options = media::get_filemanager_options();

$form = new part_edit($currenturl->out(false));
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
    file_save_draft_area_files(
        $data->attachments,
        $context->id,
        'mod_mudeck',
        media::FILEAREA,
        $partid,
        $options
    );
    if (empty($data->saveandcontinue)) {
        redirect($viewurl, get_string('part_saved', 'mod_mudeck'), null, \core\output\notification::NOTIFY_SUCCESS);
    }

    // Saving without leaving answers the POST with the editor itself. No redirect, so
    // reloading is the browser's own "send the form again?" question, which is a better
    // thing to answer than losing what is on screen.
    \core\notification::success(get_string('part_saved', 'mod_mudeck'));
    $existing = $DB->get_record('mudeck_part', ['id' => $partid], '*', MUST_EXIST);
    // A part created just now has an id, and everything from here on belongs to it.
    $currenturl->param('partid', $partid);
    $PAGE->set_url($currenturl);
    $form = new part_edit($currenturl->out(false));
}

$draftitemid = file_get_submitted_draft_itemid('attachments');
file_prepare_draft_area(
    $draftitemid,
    $context->id,
    'mod_mudeck',
    media::FILEAREA,
    $existing->id ?? null,
    $options
);

$form->set_data([
    'id' => $existing->id ?? 0,
    // A name is required, so a new part arrives with one that is at least true: where it
    // is going to sit in the presentation.
    'name' => $existing->name ?? get_string('part_new', 'mod_mudeck', count(part::get_all($mudeck->id)) + 1),
    'content' => $existing->content ?? get_string('part_starter', 'mod_mudeck'),
    'attachments' => $draftitemid,
    'caretstart' => 0,
    'caretend' => 0,
]);

echo $OUTPUT->header();

// The form is rendered as usual and stays the only copy of the text; the editor arranges
// the page around it and draws the preview beside it.
ob_start();
$form->display();
$formhtml = ob_get_clean();

echo $OUTPUT->render_from_template('mod_mudeck/editor', [
    'form' => $formhtml,
    'themecssjson' => json_encode(theme::get_custom_css(new url('/mod/mudeck'))),
    'themejson' => json_encode(theme::resolve($mudeck->theme)),
    // The pictures worth offering are the ones in the form, uploads included, which is
    // the draft area rather than the part.
    'imagesurljson' => json_encode(
        url::routed_path("/api/rest/v2/mod_mudeck/part/edit/{$draftitemid}/images")->out(false)
    ),
    // The preview reads the pictures from the same place, so an upload shows in the
    // slides before the part has been saved anywhere.
    'mediabasejson' => json_encode(url::make_draftfile_url($draftitemid, '/', '')->out(false)),
    'labelsjson' => json_encode([
        'preview' => get_string('editor_preview', 'mod_mudeck'),
        'slide' => get_string('editor_slide', 'mod_mudeck', '{$a}'),
        'markdownhelp' => get_string('part_content', 'mod_mudeck'),
        'mediahelp' => get_string('part_media', 'mod_mudeck'),
        'media' => get_string('part_media', 'mod_mudeck'),
        'mediaintro' => get_string('media_intro', 'mod_mudeck'),
        'help' => get_string('editor_help', 'mod_mudeck'),
        'fullscreen' => get_string('slide_fullscreen', 'mod_mudeck'),
    ]),
    // Help that stays open and can be copied from, which a popover cannot do.
    'helpjson' => json_encode([
        'markdown' => markdown_to_html(get_string('help_markdown', 'mod_mudeck')),
        'media' => markdown_to_html(get_string('help_media', 'mod_mudeck')),
    ]),
]);

echo $OUTPUT->footer();
