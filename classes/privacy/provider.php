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
 * Privacy provider.
 *
 * @package    mod_mudeck
 * @copyright  2026 Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_mudeck\privacy;

use core_privacy\local\metadata\collection;
use core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\approved_userlist;
use core_privacy\local\request\contextlist;
use core_privacy\local\request\userlist;
use core_privacy\local\request\transform;
use core_privacy\local\request\writer;

/**
 * What mod_mudeck stores about a user, and how to get rid of it.
 *
 * Four things are personal: a viewing session says when somebody watched and how far
 * they got, a completion record says they reached the end, and a part or a site theme
 * remembers who saved it last. Parts and themes are content, so they stay when a user
 * is forgotten - only the link to the user goes.
 */
class provider implements
    \core_privacy\local\metadata\provider,
    \core_privacy\local\request\core_userlist_provider,
    \core_privacy\local\request\plugin\provider {
    #[\Override]
    public static function get_metadata(collection $collection): collection {
        $collection->add_database_table('mudeck_session', [
            'userid' => 'privacy:metadata:mudeck_session:userid',
            'partid' => 'privacy:metadata:mudeck_session:partid',
            'slide' => 'privacy:metadata:mudeck_session:slide',
            'slidetitle' => 'privacy:metadata:mudeck_session:slidetitle',
            'sessionjson' => 'privacy:metadata:mudeck_session:sessionjson',
            'timestarted' => 'privacy:metadata:mudeck_session:timestarted',
            'timelastseen' => 'privacy:metadata:mudeck_session:timelastseen',
            'timeended' => 'privacy:metadata:mudeck_session:timeended',
        ], 'privacy:metadata:mudeck_session');

        $collection->add_database_table('mudeck_completed', [
            'userid' => 'privacy:metadata:mudeck_completed:userid',
            'timecompleted' => 'privacy:metadata:mudeck_completed:timecompleted',
        ], 'privacy:metadata:mudeck_completed');

        $collection->add_database_table('mudeck_part', [
            'usermodified' => 'privacy:metadata:mudeck_part:usermodified',
            'timemodified' => 'privacy:metadata:mudeck_part:timemodified',
        ], 'privacy:metadata:mudeck_part');

        $collection->add_database_table('mudeck_theme', [
            'usermodified' => 'privacy:metadata:mudeck_theme:usermodified',
            'timemodified' => 'privacy:metadata:mudeck_theme:timemodified',
        ], 'privacy:metadata:mudeck_theme');

        return $collection;
    }

    #[\Override]
    public static function get_contexts_for_userid(int $userid): contextlist {
        $contextlist = new contextlist();

        $sql = "SELECT ctx.id
                  FROM {context} ctx
                  JOIN {course_modules} cm ON cm.id = ctx.instanceid AND ctx.contextlevel = :modulelevel
                  JOIN {modules} m ON m.id = cm.module AND m.name = :modname
                  JOIN {mudeck} d ON d.id = cm.instance
             LEFT JOIN {mudeck_session} s ON s.mudeckid = d.id AND s.userid = :userid1
             LEFT JOIN {mudeck_completed} c ON c.mudeckid = d.id AND c.userid = :userid2
             LEFT JOIN {mudeck_part} p ON p.mudeckid = d.id AND p.usermodified = :userid3
                 WHERE s.id IS NOT NULL OR c.id IS NOT NULL OR p.id IS NOT NULL";

        $contextlist->add_from_sql($sql, [
            'modulelevel' => CONTEXT_MODULE,
            'modname' => 'mudeck',
            'userid1' => $userid,
            'userid2' => $userid,
            'userid3' => $userid,
        ]);

        // Site themes live in the system context.
        $contextlist->add_from_sql("SELECT ctx.id
                                      FROM {context} ctx
                                      JOIN {mudeck_theme} t ON t.usermodified = :userid
                                     WHERE ctx.contextlevel = :systemlevel", [
            'userid' => $userid,
            'systemlevel' => CONTEXT_SYSTEM,
        ]);

        return $contextlist;
    }

    #[\Override]
    public static function get_users_in_context(userlist $userlist) {
        $context = $userlist->get_context();
        if ($context instanceof \context_system) {
            $userlist->add_from_sql('usermodified', "SELECT t.usermodified FROM {mudeck_theme} t", []);
            return;
        }
        if (!$context instanceof \context_module) {
            return;
        }

        $params = ['instanceid' => $context->instanceid, 'modname' => 'mudeck'];
        $join = "JOIN {course_modules} cm ON cm.instance = d.id AND cm.id = :instanceid
                 JOIN {modules} m ON m.id = cm.module AND m.name = :modname";

        $userlist->add_from_sql('userid', "SELECT s.userid FROM {mudeck_session} s
            JOIN {mudeck} d ON d.id = s.mudeckid {$join}", $params);
        $userlist->add_from_sql('userid', "SELECT c.userid FROM {mudeck_completed} c
            JOIN {mudeck} d ON d.id = c.mudeckid {$join}", $params);
        $userlist->add_from_sql('usermodified', "SELECT p.usermodified FROM {mudeck_part} p
            JOIN {mudeck} d ON d.id = p.mudeckid {$join}", $params);
    }

    #[\Override]
    public static function export_user_data(approved_contextlist $contextlist) {
        global $DB;

        $userid = $contextlist->get_user()->id;

        foreach ($contextlist->get_contexts() as $context) {
            if (!$context instanceof \context_module) {
                continue;
            }
            $cm = get_coursemodule_from_id('mudeck', $context->instanceid);
            if (!$cm) {
                continue;
            }

            $sessions = $DB->get_records('mudeck_session', ['mudeckid' => $cm->instance, 'userid' => $userid]);
            if ($sessions) {
                $data = [];
                foreach ($sessions as $session) {
                    $data[] = (object)[
                        'slide' => $session->slide,
                        'slidetitle' => $session->slidetitle,
                        'timestarted' => transform::datetime($session->timestarted),
                        'timelastseen' => transform::datetime($session->timelastseen),
                        'timeended' => $session->timeended ? transform::datetime($session->timeended) : null,
                    ];
                }
                writer::with_context($context)->export_data(
                    [get_string('privacy:sessions', 'mod_mudeck')],
                    (object)['sessions' => $data]
                );
            }

            $completed = $DB->get_record('mudeck_completed', ['mudeckid' => $cm->instance, 'userid' => $userid]);
            if ($completed) {
                writer::with_context($context)->export_data(
                    [get_string('privacy:completed', 'mod_mudeck')],
                    (object)['timecompleted' => transform::datetime($completed->timecompleted)]
                );
            }
        }
    }

    #[\Override]
    public static function delete_data_for_all_users_in_context(\context $context) {
        global $DB;

        if ($context instanceof \context_system) {
            $DB->set_field('mudeck_theme', 'usermodified', null);
            return;
        }
        if (!$context instanceof \context_module) {
            return;
        }
        $cm = get_coursemodule_from_id('mudeck', $context->instanceid);
        if (!$cm) {
            return;
        }

        $DB->delete_records('mudeck_session', ['mudeckid' => $cm->instance]);
        $DB->delete_records('mudeck_completed', ['mudeckid' => $cm->instance]);
        $DB->set_field('mudeck_part', 'usermodified', null, ['mudeckid' => $cm->instance]);
    }

    #[\Override]
    public static function delete_data_for_user(approved_contextlist $contextlist) {
        global $DB;

        $userid = $contextlist->get_user()->id;

        foreach ($contextlist->get_contexts() as $context) {
            if ($context instanceof \context_system) {
                $DB->set_field('mudeck_theme', 'usermodified', null, ['usermodified' => $userid]);
                continue;
            }
            if (!$context instanceof \context_module) {
                continue;
            }
            $cm = get_coursemodule_from_id('mudeck', $context->instanceid);
            if (!$cm) {
                continue;
            }
            $DB->delete_records('mudeck_session', ['mudeckid' => $cm->instance, 'userid' => $userid]);
            $DB->delete_records('mudeck_completed', ['mudeckid' => $cm->instance, 'userid' => $userid]);
            $DB->set_field('mudeck_part', 'usermodified', null, ['mudeckid' => $cm->instance, 'usermodified' => $userid]);
        }
    }

    #[\Override]
    public static function delete_data_for_users(approved_userlist $userlist) {
        global $DB;

        $context = $userlist->get_context();
        $userids = $userlist->get_userids();
        if (!$userids) {
            return;
        }
        [$insql, $inparams] = $DB->get_in_or_equal($userids, SQL_PARAMS_NAMED);

        if ($context instanceof \context_system) {
            $DB->set_field_select('mudeck_theme', 'usermodified', null, "usermodified {$insql}", $inparams);
            return;
        }
        if (!$context instanceof \context_module) {
            return;
        }
        $cm = get_coursemodule_from_id('mudeck', $context->instanceid);
        if (!$cm) {
            return;
        }
        $params = array_merge(['mudeckid' => $cm->instance], $inparams);

        $DB->delete_records_select('mudeck_session', "mudeckid = :mudeckid AND userid {$insql}", $params);
        $DB->delete_records_select('mudeck_completed', "mudeckid = :mudeckid AND userid {$insql}", $params);
        $DB->set_field_select('mudeck_part', 'usermodified', null, "mudeckid = :mudeckid AND usermodified {$insql}", $params);
    }
}
