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
 * See one part as slides, while writing it.
 *
 * This is not the show: no session is started, nothing is completed and no event is
 * fired, so an author can look at their slides as often as they like.
 *
 * @package    mod_mudeck
 * @copyright  2026 Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use mod_mudeck\local\media;
use mod_mudeck\local\theme;

/** @var moodle_database $DB */
/** @var moodle_page $PAGE */
/** @var core_renderer $OUTPUT */

require(__DIR__ . '/../../../config.php');

$cmid = required_param('cmid', PARAM_INT);
$partid = required_param('partid', PARAM_INT);
$slide = optional_param('slide', 0, PARAM_INT);

$cm = get_coursemodule_from_id('mudeck', $cmid, 0, false, MUST_EXIST);
$course = $DB->get_record('course', ['id' => $cm->course], '*', MUST_EXIST);
$mudeck = $DB->get_record('mudeck', ['id' => $cm->instance], '*', MUST_EXIST);
$context = context_module::instance($cm->id);

require_login($course, false, $cm);
require_capability('mod/mudeck:edit', $context);

$overviewurl = new \core\url('/mod/mudeck/management/overview.php', ['cmid' => $cm->id]);
$part = $DB->get_record('mudeck_part', ['id' => $partid, 'mudeckid' => $mudeck->id], '*', MUST_EXIST);
if (!\mod_mudeck\local\part::has_content($part)) {
    redirect($overviewurl, get_string('nopartcontent', 'mod_mudeck'), null, \core\output\notification::NOTIFY_WARNING);
}

$PAGE->set_context($context);
$PAGE->set_url(new \core\url(
    '/mod/mudeck/management/part_preview.php',
    ['cmid' => $cm->id, 'partid' => $partid]
));
$PAGE->set_title($mudeck->name);
$PAGE->set_pagelayout('embedded');
// The same bare page the show gets - a preview that looked different would prove nothing.
$PAGE->set_show_navigation_footer(false);
$PAGE->activityheader->disable();

echo $OUTPUT->header();

// Props are JSON encoded here - raw Markdown newlines would break the JSON block in the template.
echo $OUTPUT->render_from_template('mod_mudeck/present', [
    'partsjson' => json_encode([[
        'id' => (int)$part->id,
        'hash' => $part->contenthash,
        'markdown' => media::rewrite($part->content, $context, (int)$part->id),
        // A theme that no longer exists falls back to the site's, never to nothing.
        'theme' => theme::resolve($mudeck->theme),
    ]]),
    'themecssjson' => json_encode(theme::get_custom_css(new \core\url('/mod/mudeck'))),
    'exiturljson' => json_encode($overviewurl->out(false)),
    // A preview is nobody's presentation, so there is nothing for another device to follow.
    'syncjson' => json_encode(null),
    'reachedendjson' => json_encode(null),
    // Clicking a slide in the overview opens the preview on that slide.
    'startslidejson' => json_encode($slide > 0 ? $slide : null),
    // Writing slides is the moment to check the notes, so the preview can show them.
    'notesjson' => json_encode(true),
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
