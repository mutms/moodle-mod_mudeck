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
 * Delete a part with everything in it.
 *
 * @package    mod_mudeck
 * @copyright  2026 Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use mod_mudeck\local\part;

/** @var moodle_database $DB */
/** @var moodle_page $PAGE */
/** @var core_renderer $OUTPUT */

require(__DIR__ . '/../../../config.php');

$cmid = required_param('cmid', PARAM_INT);
$partid = required_param('partid', PARAM_INT);
$confirm = optional_param('confirm', 0, PARAM_BOOL);

$cm = get_coursemodule_from_id('mudeck', $cmid, 0, false, MUST_EXIST);
$course = $DB->get_record('course', ['id' => $cm->course], '*', MUST_EXIST);
$mudeck = $DB->get_record('mudeck', ['id' => $cm->instance], '*', MUST_EXIST);
$context = context_module::instance($cm->id);

require_login($course, false, $cm);
require_capability('mod/mudeck:edit', $context);

$thepart = $DB->get_record('mudeck_part', ['id' => $partid, 'mudeckid' => $mudeck->id], '*', MUST_EXIST);

$overviewurl = new \core\url('/mod/mudeck/management/overview.php', ['cmid' => $cm->id]);
$currenturl = new \core\url('/mod/mudeck/management/part_delete.php', ['cmid' => $cm->id, 'partid' => $partid]);

if ($confirm) {
    require_sesskey();
    part::delete($thepart, $context);
    redirect($overviewurl, get_string('part_deleted', 'mod_mudeck'), null, \core\output\notification::NOTIFY_SUCCESS);
}

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

echo $OUTPUT->header();
echo $OUTPUT->confirm(
    get_string('part_delete_confirm', 'mod_mudeck', format_string($thepart->name)),
    new \core\url($currenturl, ['confirm' => 1, 'sesskey' => sesskey()]),
    $overviewurl
);
echo $OUTPUT->footer();
