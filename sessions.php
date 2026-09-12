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
 * Own device sync sessions.
 *
 * @package    mod_mudeck
 * @copyright  2026 Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use core\url;
use mod_mudeck\local\session;

// phpcs:disable moodle.Commenting.InlineComment.TypeHintingMatch
/** @var moodle_database $DB */
/** @var moodle_page $PAGE */
/** @var \core\output\core_renderer $OUTPUT */
/** @var stdClass $USER */
// phpcs:enable moodle.Commenting.InlineComment.TypeHintingMatch

require(__DIR__ . '/../../config.php');

$cmid = required_param('cmid', PARAM_INT);
$delete = optional_param('delete', 0, PARAM_INT);
$deletefinished = optional_param('deletefinished', 0, PARAM_BOOL);

$cm = get_coursemodule_from_id('mudeck', $cmid, 0, false, MUST_EXIST);
$course = $DB->get_record('course', ['id' => $cm->course], '*', MUST_EXIST);
$mudeck = $DB->get_record('mudeck', ['id' => $cm->instance], '*', MUST_EXIST);
$context = context_module::instance($cm->id);

require_login($course, false, $cm);
require_capability('mod/mudeck:syncdevices', $context);
if (!$mudeck->allowdevicesync) {
    redirect(new url('/mod/mudeck/view.php', ['id' => $cm->id]));
}

$currenturl = new url('/mod/mudeck/sessions.php', ['cmid' => $cm->id]);

if ($delete) {
    require_sesskey();
    session::delete_own($delete, $USER->id);
    redirect($currenturl, get_string('session_deleted', 'mod_mudeck'), null, \core\output\notification::NOTIFY_SUCCESS);
}

if ($deletefinished) {
    require_sesskey();
    $deleted = session::delete_finished($mudeck->id, $USER->id);
    redirect(
        $currenturl,
        get_string('sessions_finished_deleted', 'mod_mudeck', $deleted),
        null,
        \core\output\notification::NOTIFY_SUCCESS
    );
}

$PAGE->set_context($context);
$PAGE->set_url($currenturl);
$PAGE->set_title($mudeck->name);
$PAGE->set_secondary_active_tab('mudecksessions');
$PAGE->add_body_class('limitedwidth');
$PAGE->set_show_navigation_footer(false);
mudeck_name_presentation_tab($PAGE);
$PAGE->activityheader->set_hidecompletion(true);
$PAGE->activityheader->set_description('');

// Synced notes are a full access feature only.
$seesnotes = has_capability('mod/mudeck:fullaccess', $context);

$running = [];
$finished = [];
foreach (session::get_own($mudeck->id, $USER->id) as $one) {
    $live = session::is_live($one);
    $now = time();
    $row = [
        'started' => get_string('session_ago', 'mod_mudeck', format_time($now - $one->timestarted)),
        'startedat' => userdate($one->timestarted),
        'lastseen' => get_string('session_ago', 'mod_mudeck', format_time($now - $one->timelastseen)),
        'lastseenat' => userdate($one->timelastseen),
        'where' => $one->slidetitle ?: get_string('session_unknownslide', 'mod_mudeck'),
        'deleteurl' => (new url($currenturl, ['delete' => $one->id, 'sesskey' => sesskey()]))->out(false),
        'notesurl' => $seesnotes && session::is_followable($one)
            ? (new url('/mod/mudeck/notes.php', ['cmid' => $cm->id, 'sessionid' => $one->id]))->out(false)
            : '',
    ];
    if ($live) {
        $running[] = $row;
    } else {
        $finished[] = $row;
    }
}

echo $OUTPUT->header();

echo $OUTPUT->render_from_template('mod_mudeck/sessions', [
    'hassessions' => $running || $finished,
    'running' => $running,
    'hasrunning' => (bool)$running,
    'finished' => $finished,
    'hasfinished' => (bool)$finished,
    'deletefinishedurl' => (new url($currenturl, ['deletefinished' => 1, 'sesskey' => sesskey()]))->out(false),
]);

echo $OUTPUT->footer();
