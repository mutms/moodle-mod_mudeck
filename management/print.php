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
 * The whole presentation on paper, notes and all.
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
require_capability('mod/mudeck:fullaccess', $context);

$overviewurl = new url('/mod/mudeck/management/overview.php', ['cmid' => $cm->id]);

$PAGE->set_context($context);
$PAGE->set_url(new url('/mod/mudeck/management/print.php', ['cmid' => $cm->id]));
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
        // A theme that no longer exists falls back to the site's, never to nothing.
        'theme' => theme::resolve($mudeck->theme),
    ];
}

$modinfo = get_fast_modinfo($course);
$cminfo = $modinfo->get_cm($cm->id);
$section = $cminfo->get_section_info();
$activityicon = \core_course\output\activity_icon::from_cm_info($cminfo);

echo $OUTPUT->header();

echo $OUTPUT->render_from_template('mod_mudeck/print', [
    'deckname' => format_string($mudeck->name),
    'activityicon' => $OUTPUT->render($activityicon),
    'deckurl' => (new url('/mod/mudeck/view.php', ['id' => $cm->id]))->out(false),
    'coursename' => format_string($course->fullname),
    'courseurl' => (new url('/course/view.php', ['id' => $course->id]))->out(false),
    'sectionname' => $section ? format_string(get_section_name($course, $section)) : '',
    'sectionurl' => $section ? course_get_url($course, $section->section)->out(false) : '',
    'intro' => $mudeck->intro ? format_module_intro('mudeck', $mudeck, $cm->id) : '',
    'printed' => userdate(time()),
    'partsjson' => json_encode($partsdata),
    'themecssjson' => json_encode(theme::get_custom_css(new url('/mod/mudeck'))),
    'exiturljson' => json_encode($overviewurl->out(false)),
    // The presenter copy: the notes go under every slide.
    'notesjson' => json_encode(true),
    'labelsjson' => json_encode([
        'print' => get_string('print', 'mod_mudeck'),
        'exit' => get_string('back', 'mod_mudeck'),
        'notes' => get_string('notes_heading', 'mod_mudeck'),
        'nonotes' => get_string('notes_none', 'mod_mudeck'),
    ]),
]);

echo $OUTPUT->footer();
