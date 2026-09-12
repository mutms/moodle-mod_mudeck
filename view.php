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
 * Markdown slide deck welcome page.
 *
 * This is deliberately not the show - the presentation only ever starts when
 * somebody asks for it, so that opening the activity records nothing.
 *
 * @package    mod_mudeck
 * @copyright  2026 Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// phpcs:disable moodle.Commenting.InlineComment.TypeHintingMatch
/** @var moodle_database $DB */
/** @var moodle_page $PAGE */
/** @var \core\output\core_renderer $OUTPUT */
// phpcs:enable moodle.Commenting.InlineComment.TypeHintingMatch

use core\url;

require(__DIR__ . '/../../config.php');

$id = optional_param('id', 0, PARAM_INT);
$m = optional_param('m', 0, PARAM_INT);

if ($id) {
    $cm = get_coursemodule_from_id('mudeck', $id, 0, false, MUST_EXIST);
    $course = $DB->get_record('course', ['id' => $cm->course], '*', MUST_EXIST);
    $mudeck = $DB->get_record('mudeck', ['id' => $cm->instance], '*', MUST_EXIST);
} else {
    $mudeck = $DB->get_record('mudeck', ['id' => $m], '*', MUST_EXIST);
    $cm = get_coursemodule_from_instance('mudeck', $mudeck->id, $mudeck->course, false, MUST_EXIST);
    $course = $DB->get_record('course', ['id' => $cm->course], '*', MUST_EXIST);
    $id = $cm->id;
}
$context = context_module::instance($cm->id);

require_course_login($course, true, $cm);
require_capability('mod/mudeck:view', $context);

$PAGE->set_context($context);
$PAGE->set_url(new url('/mod/mudeck/view.php', ['id' => $cm->id]));
$PAGE->set_title($mudeck->name);
$PAGE->add_body_class('limitedwidth');
mudeck_name_presentation_tab($PAGE);

$parts = \mod_mudeck\local\part::get_playable($mudeck->id);
$hasslides = (bool)$parts;
$canedit = has_capability('mod/mudeck:edit', $context);
// A presentation of one part has one obvious thing to edit, so it can be offered from
// here. With several, which one to open is a question, and the overview answers it.
$all = \mod_mudeck\local\part::get_all($mudeck->id);
$onepart = count($all) === 1 ? reset($all) : null;
\mod_mudeck\event\course_module_viewed::create_from_mudeck($mudeck, $context)->trigger();

echo $OUTPUT->header();

echo $OUTPUT->render_from_template('mod_mudeck/view', [
    'hasslides' => $hasslides,
    'presenturl' => (new url('/mod/mudeck/present.php', ['id' => $cm->id]))->out(false),
    'canpresent' => $hasslides && has_capability('mod/mudeck:present', $context),
    'printurl' => (new url('/mod/mudeck/print.php', ['id' => $cm->id]))->out(false),
    // An empty presentation is a dead end for anybody who cannot write slides, and one
    // click from being a presentation for anybody who can.
    'canedit' => !$hasslides && $canedit,
    'caneditone' => $hasslides && $canedit && $onepart !== null,
    'editurl' => $onepart
        ? (new url('/mod/mudeck/management/part_edit.php', [
            'cmid' => $cm->id,
            'partid' => $onepart->id,
            // Came from here, so go back here when the writing is done.
            'returnto' => 'view',
        ]))->out(false)
        : '',
    'overviewurl' => (new url('/mod/mudeck/management/overview.php', ['cmid' => $cm->id]))->out(false),
    'importurl' => (new url('/mod/mudeck/management/part_import.php', ['cmid' => $cm->id]))->out(false),
]);

echo $OUTPUT->footer();
