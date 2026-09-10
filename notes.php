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
 * Speaker notes of a presentation running on another device.
 *
 * @package    mod_mudeck
 * @copyright  2026 Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use mod_mudeck\local\media;
use mod_mudeck\local\part;
use mod_mudeck\local\session;
use mod_mudeck\local\theme;

/** @var moodle_database $DB */
/** @var moodle_page $PAGE */
/** @var core_renderer $OUTPUT */
/** @var stdClass $USER */

require(__DIR__ . '/../../config.php');

$cmid = required_param('cmid', PARAM_INT);
$sessionid = required_param('sessionid', PARAM_INT);

$cm = get_coursemodule_from_id('mudeck', $cmid, 0, false, MUST_EXIST);
$course = $DB->get_record('course', ['id' => $cm->course], '*', MUST_EXIST);
$mudeck = $DB->get_record('mudeck', ['id' => $cm->instance], '*', MUST_EXIST);
$context = context_module::instance($cm->id);

require_login($course, false, $cm);
require_capability('mod/mudeck:syncdevices', $context);
require_capability('mod/mudeck:fullaccess', $context);

$sessionsurl = new \core\url('/mod/mudeck/sessions.php', ['cmid' => $cm->id]);
$thesession = session::get_own_one($sessionid, $USER->id);

if (!$mudeck->allowdevicesync || !$thesession || !session::is_followable($thesession)) {
    redirect($sessionsurl, get_string('notes_gone', 'mod_mudeck'), null, \core\output\notification::NOTIFY_WARNING);
}

$PAGE->set_context($context);
$PAGE->set_url(new \core\url('/mod/mudeck/notes.php', ['cmid' => $cm->id, 'sessionid' => $sessionid]));
$PAGE->set_title($mudeck->name);
$PAGE->set_pagelayout('embedded');
$PAGE->set_show_navigation_footer(false);
$PAGE->activityheader->disable();

$partsdata = [];
foreach (part::get_playable($mudeck->id) as $onepart) {
    $partsdata[] = [
        'id' => (int)$onepart->id,
        'hash' => $onepart->contenthash,
        'markdown' => media::rewrite($onepart->content, $context, (int)$onepart->id),
        'theme' => theme::resolve($mudeck->theme),
    ];
}

echo $OUTPUT->header();

echo $OUTPUT->render_from_template('mod_mudeck/notes', [
    'partsjson' => json_encode($partsdata),
    'themecssjson' => json_encode(theme::get_custom_css(new \core\url('/mod/mudeck'))),
    'pollurljson' => json_encode(
        (new \core\url('/api/rest/v2/mod_mudeck/session/' . $sessionid))->out(false)
    ),
    'exiturljson' => json_encode($sessionsurl->out(false)),
    'labelsjson' => json_encode([
        'notes' => get_string('notes_heading', 'mod_mudeck'),
        'nonotes' => get_string('notes_none', 'mod_mudeck'),
        'waiting' => get_string('notes_waiting', 'mod_mudeck'),
        'stale' => get_string('notes_stale', 'mod_mudeck'),
        'gone' => get_string('notes_gone', 'mod_mudeck'),
        'reconnect' => get_string('notes_reconnect', 'mod_mudeck'),
        'current' => get_string('notes_current', 'mod_mudeck'),
        'elapsed' => get_string('notes_elapsed', 'mod_mudeck'),
        'exit' => get_string('notes_exit', 'mod_mudeck'),
        'fullscreen' => get_string('slide_fullscreen', 'mod_mudeck'),
    ]),
]);

echo $OUTPUT->footer();
