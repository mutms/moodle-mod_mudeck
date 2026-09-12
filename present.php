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
 * Play the presentation.
 *
 * @package    mod_mudeck
 * @copyright  2026 Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use core\url;
use mod_mudeck\local\completed;
use mod_mudeck\local\media;
use mod_mudeck\local\notes;
use mod_mudeck\local\part;
use mod_mudeck\local\session;
use mod_mudeck\local\theme;

// phpcs:disable moodle.Commenting.InlineComment.TypeHintingMatch
/** @var moodle_database $DB */
/** @var moodle_page $PAGE */
/** @var \core\output\core_renderer $OUTPUT */
/** @var stdClass $USER */
// phpcs:enable moodle.Commenting.InlineComment.TypeHintingMatch

require(__DIR__ . '/../../config.php');

$id = required_param('id', PARAM_INT);

$cm = get_coursemodule_from_id('mudeck', $id, 0, false, MUST_EXIST);
$course = $DB->get_record('course', ['id' => $cm->course], '*', MUST_EXIST);
$mudeck = $DB->get_record('mudeck', ['id' => $cm->instance], '*', MUST_EXIST);
$context = context_module::instance($cm->id);

require_course_login($course, true, $cm);
require_capability('mod/mudeck:present', $context);

$viewurl = new url('/mod/mudeck/view.php', ['id' => $cm->id]);

$parts = part::get_playable($mudeck->id);
if (!$parts) {
    redirect($viewurl, get_string('nopartcontent', 'mod_mudeck'), null, \core\output\notification::NOTIFY_WARNING);
}

$PAGE->set_context($context);
$PAGE->set_url(new url('/mod/mudeck/present.php', ['id' => $cm->id]));
$PAGE->set_title($mudeck->name);
$PAGE->set_pagelayout('embedded');
$PAGE->set_show_navigation_footer(false);
$PAGE->activityheader->disable();
$PAGE->add_body_class('mudeck-present-page');

\mod_mudeck\event\presentation_started::create_from_mudeck($mudeck, $context)->trigger();

$cminfo = cm_info::create($cm);
$completion = new completion_info($course);
$completion->set_module_viewed($cminfo);

$seesnotes = has_capability('mod/mudeck:fullaccess', $context);

$sessionid = 0;
if ($mudeck->allowdevicesync && $seesnotes && has_capability('mod/mudeck:syncdevices', $context)) {
    $sessionid = session::start($mudeck->id, $USER->id);
}

// Each part is rendered on its own, so one part's directives cannot leak into another.
$partsdata = [];
foreach ($parts as $onepart) {
    $content = $onepart->content;
    if (!$seesnotes) {
        $content = notes::strip($content);
    }
    $content = media::rewrite($content, $context, (int)$onepart->id);
    $partsdata[] = [
        'id' => (int)$onepart->id,
        'hash' => $onepart->contenthash,
        'markdown' => $content,
        'theme' => theme::resolve($mudeck->theme),
    ];
}

echo $OUTPUT->header();
// Props are JSON encoded here - raw Markdown newlines would break the JSON block in the template.
echo $OUTPUT->render_from_template('mod_mudeck/present', [
    'partsjson' => json_encode($partsdata),
    // Notes stay off the projector: the show is what the room sees.
    'notesjson' => json_encode(false),
    'startslidejson' => json_encode(null),
    'syncjson' => json_encode($sessionid ? [
        'url' => url::routed_path('/api/rest/v2/mod_mudeck/session/' . $sessionid)->out(false),
        'sesskey' => sesskey(),
    ] : null),
    // Reaching the last slide counts only when the activity asks for it; otherwise the
    // viewer has nowhere to report and nothing is recorded.
    'reachedendjson' => json_encode(completed::is_wanted($mudeck, $cminfo) ? [
        'url' => url::routed_path("/api/rest/v2/mod_mudeck/presentation/{$cm->id}/reached-end")->out(false),
        'sesskey' => sesskey(),
    ] : null),
    'themecssjson' => json_encode(theme::get_custom_css(new url('/mod/mudeck'))),
    'exiturljson' => json_encode($viewurl->out(false)),
    'labelsjson' => json_encode([
        'previous' => get_string('slide_previous', 'mod_mudeck'),
        'next' => get_string('slide_next', 'mod_mudeck'),
        'fullscreen' => get_string('slide_fullscreen', 'mod_mudeck'),
        'exit' => get_string('slide_exit', 'mod_mudeck'),
        'overview' => get_string('slide_overview', 'mod_mudeck'),
        'notes' => get_string('slide_notes', 'mod_mudeck'),
        'nonotes' => get_string('notes_none', 'mod_mudeck'),
        'slideof' => get_string('slide_of', 'mod_mudeck', ['current' => '{$a->current}', 'total' => '{$a->total}']),
    ]),
]);

echo $OUTPUT->footer();
