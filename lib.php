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
 * Markdown slide deck plugin core API.
 *
 * @package    mod_mudeck
 * @copyright  2026 Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use mod_mudeck\local\media;
use mod_mudeck\local\session;
use mod_mudeck\local\theme;

/**
 * Add presentation instance.
 *
 * @param stdClass $data
 * @param stdClass $mform
 * @return int new presentation instance id
 */
function mudeck_add_instance($data, $mform) {
    global $DB;

    $data->timecreated = time();
    $data->timemodified = $data->timecreated;

    // Empty means "whatever the site says", and stays empty: resolving it here would
    // freeze today's site default into the activity for good.
    $data->theme = theme::is_valid($data->theme ?? null) ? $data->theme : '';

    $id = $DB->insert_record('mudeck', $data);

    $completiontimeexpected = !empty($data->completionexpected) ? $data->completionexpected : null;
    \core_completion\api::update_completion_date_event($data->coursemodule, 'mudeck', $id, $completiontimeexpected);

    return $id;
}

/**
 * Update presentation instance.
 *
 * @param stdClass $data
 * @param stdClass $mform
 * @return bool true
 */
function mudeck_update_instance($data, $mform) {
    global $DB;

    $data->id = $data->instance;
    $data->timemodified = time();

    if (property_exists($data, 'theme') && !theme::is_valid($data->theme)) {
        $data->theme = '';
    }

    $DB->update_record('mudeck', $data);

    // Without device sync there is nowhere to see a session.
    if (empty($data->allowdevicesync)) {
        session::delete_all($data->id);
    }

    $completiontimeexpected = !empty($data->completionexpected) ? $data->completionexpected : null;
    \core_completion\api::update_completion_date_event($data->coursemodule, 'mudeck', $data->id, $completiontimeexpected);

    return true;
}

/**
 * Delete presentation instance by activity id.
 *
 * @param int $id
 * @return bool success
 */
function mudeck_delete_instance($id) {
    global $DB;

    $mudeck = $DB->get_record('mudeck', ['id' => $id]);
    if (!$mudeck) {
        return false;
    }
    $cm = get_coursemodule_from_instance('mudeck', $mudeck->id);

    $DB->delete_records('mudeck_session', ['mudeckid' => $mudeck->id]);
    $DB->delete_records('mudeck_completed', ['mudeckid' => $mudeck->id]);
    $DB->delete_records('mudeck_part', ['mudeckid' => $mudeck->id]);

    \core_completion\api::update_completion_date_event($cm->id, 'mudeck', $id, null);

    $DB->delete_records('mudeck', ['id' => $mudeck->id]);

    return true;
}

/**
 * Supported features.
 *
 * @param string $feature FEATURE_xx constant for requested feature
 * @return bool|int|string|null
 */
function mudeck_supports($feature) {
    switch ($feature) {
        case FEATURE_MOD_ARCHETYPE:
            return MOD_ARCHETYPE_RESOURCE;
        case FEATURE_GROUPS:
            return false;
        case FEATURE_GROUPINGS:
            return false;
        case FEATURE_MOD_INTRO:
            return true;
        case FEATURE_COMPLETION_TRACKS_VIEWS:
            return true;
        case FEATURE_COMPLETION_HAS_RULES:
            return true;
        case FEATURE_GRADE_HAS_GRADE:
            return false;
        case FEATURE_GRADE_OUTCOMES:
            return false;
        case FEATURE_BACKUP_MOODLE2:
            return true;
        case FEATURE_SHOW_DESCRIPTION:
            return true;
        case FEATURE_MOD_PURPOSE:
            return MOD_PURPOSE_CONTENT;

        default:
            return null;
    }
}

/**
 * Serve the files used by slides.
 *
 * @param stdClass $course
 * @param cm_info $cm
 * @param context $context
 * @param string $filearea
 * @param array $args
 * @param bool $forcedownload
 * @param array $options
 * @return bool false if the file was not found, otherwise the file is sent
 */
function mudeck_pluginfile($course, $cm, $context, $filearea, $args, $forcedownload, array $options = []) {
    global $DB;

    require_course_login($course, true, $cm);

    if (!$context instanceof context_module) {
        return false;
    }
    if ($filearea !== media::FILEAREA) {
        return false;
    }
    // The pictures belong to the slides, so seeing the slides in any form is what it takes.
    if (!has_capability('mod/mudeck:view', $context)) {
        return false;
    }
    if (!has_any_capability(['mod/mudeck:present', 'mod/mudeck:fullaccess', 'mod/mudeck:edit'], $context)) {
        return false;
    }

    $partid = (int)array_shift($args);

    $part = $DB->get_record('mudeck_part', ['id' => $partid]);
    if (!$part || $part->mudeckid != $cm->instance) {
        return false;
    }

    $relativepath = implode('/', $args);
    $fullpath = "/{$context->id}/mod_mudeck/" . media::FILEAREA . "/{$partid}/{$relativepath}";

    $fs = get_file_storage();
    $file = $fs->get_file_by_hash(sha1($fullpath));
    if (!$file || $file->is_directory()) {
        return false;
    }

    // Note: SVG files are force downloaded autoamtically for security reasons.
    send_stored_file($file, DAYSECS, 0, $forcedownload, $options);
}

/**
 * What the course page needs to know about a presentation without loading it.
 *
 * The completion rule in use has to travel here, or core does not know it applies.
 *
 * @param stdClass $coursemodule
 * @return cached_cm_info|false
 */
function mudeck_get_coursemodule_info($coursemodule) {
    global $DB;

    $mudeck = $DB->get_record(
        'mudeck',
        ['id' => $coursemodule->instance],
        'id, name, intro, introformat, completionreachedend'
    );
    if (!$mudeck) {
        return false;
    }

    $info = new cached_cm_info();
    $info->name = $mudeck->name;
    if ($coursemodule->showdescription) {
        $info->content = format_module_intro('mudeck', $mudeck, $coursemodule->id, false);
    }
    if ($coursemodule->completion == COMPLETION_TRACKING_AUTOMATIC) {
        $info->customdata['customcompletionrules']['completionreachedend'] = $mudeck->completionreachedend;
    }

    return $info;
}

/**
 * Rename the activity's own tab from the plugin name to "Presentation".
 *
 * Core builds that tab itself, after every plugin callback has run, so the only place to
 * reach it is the page - beside Overview and Sessions, "Markdown slide deck" reads wrong.
 *
 * @param moodle_page $page
 */
function mudeck_name_presentation_tab(moodle_page $page) {
    $node = $page->secondarynav->find('modulepage', null);
    if ($node) {
        $node->text = get_string('presentation', 'mod_mudeck');
    }
}

/**
 * Activity tabs: Presentation, Overview and optionally Sessions.
 *
 * @param settings_navigation $settings
 * @param navigation_node $mudecknode
 */
function mudeck_extend_settings_navigation(settings_navigation $settings, navigation_node $mudecknode) {
    global $DB;

    $page = $settings->get_page();
    $cm = $page->cm;
    if (!$cm) {
        return;
    }
    $context = context_module::instance($cm->id);
    if (!has_capability('mod/mudeck:view', $context)) {
        return;
    }

    if (has_capability('mod/mudeck:edit', $context)) {
        $mudecknode->add(
            get_string('tab_overview', 'mod_mudeck'),
            new \core\url('/mod/mudeck/management/overview.php', ['cmid' => $cm->id]),
            navigation_node::TYPE_CUSTOM,
            null,
            'mudeckoverview'
        );
    }

    $mudeck = $DB->get_record('mudeck', ['id' => $cm->instance], 'id, allowdevicesync');
    if ($mudeck && $mudeck->allowdevicesync && has_capability('mod/mudeck:syncdevices', $context)) {
        $mudecknode->add(
            get_string('sessions', 'mod_mudeck'),
            new \core\url('/mod/mudeck/sessions.php', ['cmid' => $cm->id]),
            navigation_node::TYPE_CUSTOM,
            null,
            'mudecksessions'
        );
    }
}
