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
 * Add a part by importing one archive.
 *
 * @package    mod_mudeck
 * @copyright  2026 Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use core\url;
use mod_mudeck\local\form\part_import;
use mod_mudeck\local\import;
use mod_mudeck\local\media;
use mod_mudeck\local\part;

// phpcs:disable moodle.Commenting.InlineComment.TypeHintingMatch
/** @var stdClass $CFG */
/** @var moodle_database $DB */
/** @var moodle_page $PAGE */
/** @var \core\output\core_renderer $OUTPUT */
/** @var stdClass $USER */
// phpcs:enable moodle.Commenting.InlineComment.TypeHintingMatch

require(__DIR__ . '/../../../config.php');
require_once($CFG->libdir . '/formslib.php');

$cmid = required_param('cmid', PARAM_INT);

$cm = get_coursemodule_from_id('mudeck', $cmid, 0, false, MUST_EXIST);
$course = $DB->get_record('course', ['id' => $cm->course], '*', MUST_EXIST);
$mudeck = $DB->get_record('mudeck', ['id' => $cm->instance], '*', MUST_EXIST);
$context = context_module::instance($cm->id);

require_login($course, false, $cm);
require_capability('mod/mudeck:edit', $context);

$viewurl = new url('/mod/mudeck/management/overview.php', ['cmid' => $cm->id]);
$currenturl = new url('/mod/mudeck/management/part_import.php', ['cmid' => $cm->id]);

$PAGE->set_context($context);
$PAGE->set_url($currenturl);
$PAGE->set_title($mudeck->name);
$PAGE->set_secondary_active_tab('mudeckoverview');
$PAGE->add_body_class('limitedwidth');
mudeck_name_presentation_tab($PAGE);
// The description and the completion tick belong on the welcome page, not over every
// management screen.
$PAGE->activityheader->set_hidecompletion(true);
$PAGE->activityheader->set_description('');

$form = new part_import($currenturl->out(false));
if ($form->is_cancelled()) {
    redirect($viewurl);
}

// The upload needs a draft area of its own before the file manager can put anything in it.
$draftitemid = file_get_submitted_draft_itemid('archive');
file_prepare_draft_area($draftitemid, null, null, null, null, ['maxfiles' => 1, 'subdirs' => 0]);
$form->set_data(['archive' => $draftitemid]);
if ($data = $form->get_data()) {
    $uploaded = null;
    foreach (
        get_file_storage()->get_area_files(
            context_user::instance($USER->id)->id,
            'user',
            'draft',
            $data->archive,
            'itemid',
            false
        ) as $file
    ) {
        $uploaded = $file;
        break;
    }
    if (!$uploaded) {
        redirect($currenturl, get_string('import_nothing', 'mod_mudeck'), null, \core\output\notification::NOTIFY_WARNING);
    }

    $filename = clean_filename($uploaded->get_filename());
    $tempfile = make_request_directory() . '/' . $filename;
    $uploaded->copy_content_to($tempfile);

    $partid = import::from_file($mudeck, $context, $tempfile, $filename);
    if ($partid === null) {
        redirect(
            $currenturl,
            get_string('import_nothing', 'mod_mudeck'),
            null,
            \core\output\notification::NOTIFY_WARNING
        );
    }

    $content = $DB->get_field('mudeck_part', 'content', ['id' => $partid]);
    $missing = media::find_missing((string)$content, $context, $partid);
    if ($missing) {
        redirect(
            $viewurl,
            get_string('part_media_missing', 'mod_mudeck', implode(', ', $missing)),
            null,
            \core\output\notification::NOTIFY_WARNING
        );
    }
    redirect($viewurl, get_string('part_imported', 'mod_mudeck'), null, \core\output\notification::NOTIFY_SUCCESS);
}

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('import', 'mod_mudeck'));
$form->display();
echo $OUTPUT->footer();
