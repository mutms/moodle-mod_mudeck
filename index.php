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
 * List of presentations in a course.
 *
 * @package    mod_mudeck
 * @copyright  2026 Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use core\url;

// phpcs:disable moodle.Commenting.InlineComment.TypeHintingMatch
/** @var stdClass $CFG */
/** @var moodle_database $DB */
/** @var moodle_page $PAGE */
/** @var \core\output\core_renderer $OUTPUT */
// phpcs:enable moodle.Commenting.InlineComment.TypeHintingMatch

require(__DIR__ . '/../../config.php');

$id = required_param('id', PARAM_INT); // Course ID.

$course = $DB->get_record('course', ['id' => $id], '*', MUST_EXIST);

require_course_login($course, true);

$strname = get_string('modulenameplural', 'mod_mudeck');

$PAGE->set_pagelayout('incourse');
$PAGE->set_url('/mod/mudeck/index.php', ['id' => $course->id]);
$PAGE->set_title($course->shortname . ': ' . $strname);
$PAGE->set_heading($course->fullname);
$PAGE->navbar->add($strname);

\mod_mudeck\event\course_module_instance_list_viewed::create_from_course($course)->trigger();

echo $OUTPUT->header();

$mudecks = get_all_instances_in_course('mudeck', $course);
if (!$mudecks) {
    notice(get_string('thereareno', 'moodle', $strname), new url('/course/view.php', ['id' => $course->id]));
}

$table = new html_table();
$table->head = [get_string('name'), get_string('moduleintro')];
$table->align = ['left', 'left'];

foreach ($mudecks as $mudeck) {
    $link = \core\output\html_writer::link(
        new url('/mod/mudeck/view.php', ['id' => $mudeck->coursemodule]),
        format_string($mudeck->name),
        ['class' => $mudeck->visible ? '' : 'dimmed']
    );
    $intro = format_module_intro('mudeck', $mudeck, $mudeck->coursemodule, false);
    $table->data[] = [$link, $intro];
}

echo html_writer::table($table);

echo $OUTPUT->footer();
