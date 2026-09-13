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
 * Download one part as an archive.
 *
 * The archive is the same shape the import understands, so a part moves between
 * Moodle sites by downloading it here and uploading it there. A full export of a
 * whole presentation is a separate thing and comes later.
 *
 * @package    mod_mudeck
 * @copyright  2026 Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use core\url;
use mod_mudeck\local\media;
use mod_mudeck\local\part;

// phpcs:disable moodle.Commenting.InlineComment.TypeHintingMatch
/** @var moodle_database $DB */
// phpcs:enable moodle.Commenting.InlineComment.TypeHintingMatch

require(__DIR__ . '/../../../config.php');

$cmid = required_param('cmid', PARAM_INT);
$partid = required_param('partid', PARAM_INT);

$cm = get_coursemodule_from_id('mudeck', $cmid, 0, false, MUST_EXIST);
$course = $DB->get_record('course', ['id' => $cm->course], '*', MUST_EXIST);
$mudeck = $DB->get_record('mudeck', ['id' => $cm->instance], '*', MUST_EXIST);
$context = context_module::instance($cm->id);

require_login($course, false, $cm);
require_capability('mod/mudeck:view', $context);
// Exporting a part is part of editing it. Handing out the whole presentation to
// people who may not edit is a different feature and is not this one.
require_capability('mod/mudeck:edit', $context);

$thepart = $DB->get_record('mudeck_part', ['id' => $partid, 'mudeckid' => $mudeck->id], '*', MUST_EXIST);
if (!part::has_content($thepart)) {
    redirect(
        new url('/mod/mudeck/management/overview.php', ['cmid' => $cm->id]),
        get_string('nopartcontent', 'mod_mudeck'),
        null,
        \core\output\notification::NOTIFY_WARNING
    );
}

$zip = media::export_archive($thepart, $context);

send_temp_file($zip, clean_filename($thepart->name) . '.zip');
