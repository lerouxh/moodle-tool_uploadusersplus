<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Privacy provider for tool_uploadusersplus.
 *
 * @package    tool_uploadusersplus
 * @copyright  2026 eLearn Solutions
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace tool_uploadusersplus\privacy;

defined('MOODLE_INTERNAL') || die();

use context_user;
use core_privacy\local\metadata\collection;
use core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\approved_userlist;
use core_privacy\local\request\contextlist;
use core_privacy\local\request\transform;
use core_privacy\local\request\userlist;
use core_privacy\local\request\writer;
use tool_uploadusersplus\local\report_repository;

/**
 * Privacy provider for uploader-owned successful run report data.
 *
 * @package    tool_uploadusersplus
 * @copyright  2026 eLearn Solutions
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class provider implements
        \core_privacy\local\metadata\provider,
        \core_privacy\local\request\core_userlist_provider,
        \core_privacy\local\request\plugin\provider {
    /**
     * Describe stored data.
     *
     * @param collection $collection
     * @return collection
     */
    public static function get_metadata(collection $collection): collection {
        $collection->add_database_table('tool_uploadusersplus_run', [
            'userid' => 'privacy:metadata:run:userid',
            'filename' => 'privacy:metadata:run:filename',
            'uploadtype' => 'privacy:metadata:run:uploadtype',
            'csvseparator' => 'privacy:metadata:run:csvseparator',
            'usersuploaded' => 'privacy:metadata:run:usersuploaded',
            'usersupdated' => 'privacy:metadata:run:usersupdated',
            'usersenrolleddistinct' => 'privacy:metadata:run:usersenrolleddistinct',
            'enrolmentscreated' => 'privacy:metadata:run:enrolmentscreated',
            'coursescount' => 'privacy:metadata:run:coursescount',
            'cohortscount' => 'privacy:metadata:run:cohortscount',
            'status' => 'privacy:metadata:run:status',
            'timecreated' => 'privacy:metadata:run:timecreated',
            'timemodified' => 'privacy:metadata:run:timemodified',
        ], 'privacy:metadata:run');

        $collection->add_database_table('tool_uploadusersplus_run_course', [
            'runid' => 'privacy:metadata:runcourse:runid',
            'courseid' => 'privacy:metadata:runcourse:courseid',
            'courseshortname' => 'privacy:metadata:runcourse:courseshortname',
            'coursefullname' => 'privacy:metadata:runcourse:coursefullname',
            'usersenrolled' => 'privacy:metadata:runcourse:usersenrolled',
            'timecreated' => 'privacy:metadata:runcourse:timecreated',
        ], 'privacy:metadata:runcourse');

        return $collection;
    }

    /**
     * Get contexts containing data for an uploader.
     *
     * @param int $userid
     * @return contextlist
     */
    public static function get_contexts_for_userid(int $userid): contextlist {
        $params = [
            'userid' => $userid,
            'contextlevel' => CONTEXT_USER,
        ];
        $sql = "SELECT DISTINCT ctx.id
                  FROM {tool_uploadusersplus_run} r
                  JOIN {context} ctx
                    ON ctx.instanceid = r.userid
                   AND ctx.contextlevel = :contextlevel
                 WHERE r.userid = :userid";

        $contextlist = new contextlist();
        $contextlist->add_from_sql($sql, $params);

        return $contextlist;
    }

    /**
     * Get the list of users who have data in a context.
     *
     * @param userlist $userlist
     * @return void
     */
    public static function get_users_in_context(userlist $userlist) {
        $context = $userlist->get_context();

        if (!$context instanceof context_user) {
            return;
        }

        $sql = "SELECT userid
                  FROM {tool_uploadusersplus_run}
                 WHERE userid = :userid";
        $userlist->add_from_sql('userid', $sql, ['userid' => $context->instanceid]);
    }

    /**
     * Export uploader-owned run data.
     *
     * @param approved_contextlist $contextlist
     * @return void
     */
    public static function export_user_data(approved_contextlist $contextlist) {
        $contexts = $contextlist->get_contexts();
        if (empty($contexts)) {
            return;
        }

        $repository = new report_repository();
        $userid = $contextlist->get_user()->id;
        $runs = $repository->get_runs_for_userid($userid);
        if (!$runs) {
            return;
        }

        $courserows = $repository->get_course_rows_for_runs(array_keys($runs));
        $coursesbyrun = [];
        foreach ($courserows as $course) {
            if (!isset($coursesbyrun[$course->runid])) {
                $coursesbyrun[$course->runid] = [];
            }
            $coursesbyrun[$course->runid][] = (object)[
                'courseid' => $course->courseid,
                'courseshortname' => $course->courseshortname,
                'coursefullname' => $course->coursefullname,
                'usersenrolled' => $course->usersenrolled,
                'timecreated' => transform::datetime($course->timecreated),
            ];
        }

        foreach ($contexts as $context) {
            if (!$context instanceof context_user || (int)$context->instanceid !== $userid) {
                continue;
            }

            $data = [];
            foreach ($runs as $run) {
                $data[] = (object)[
                    'filename' => $run->filename,
                    'uploadtype' => $run->uploadtype,
                    'csvseparator' => $run->csvseparator,
                    'usersuploaded' => $run->usersuploaded,
                    'usersupdated' => $run->usersupdated,
                    'usersenrolleddistinct' => $run->usersenrolleddistinct,
                    'enrolmentscreated' => $run->enrolmentscreated,
                    'coursescount' => $run->coursescount,
                    'cohortscount' => $run->cohortscount,
                    'status' => $run->status,
                    'timecreated' => transform::datetime($run->timecreated),
                    'timemodified' => transform::datetime($run->timemodified),
                    'courses' => $coursesbyrun[$run->id] ?? [],
                ];
            }

            writer::with_context($context)->export_data(
                [get_string('pluginname', 'tool_uploadusersplus'), get_string('reportsheading', 'tool_uploadusersplus')],
                (object)['runs' => $data]
            );
        }
    }

    /**
     * Delete all run data for all users in a user context.
     *
     * @param \context $context
     * @return void
     */
    public static function delete_data_for_all_users_in_context(\context $context) {
        if (!$context instanceof context_user) {
            return;
        }

        $repository = new report_repository();
        $repository->delete_runs_for_userid($context->instanceid);
    }

    /**
     * Delete all run data for an uploader.
     *
     * @param approved_contextlist $contextlist
     * @return void
     */
    public static function delete_data_for_user(approved_contextlist $contextlist) {
        $repository = new report_repository();
        $repository->delete_runs_for_userid($contextlist->get_user()->id);
    }

    /**
     * Delete multiple users within a single context.
     *
     * @param approved_userlist $userlist
     * @return void
     */
    public static function delete_data_for_users(approved_userlist $userlist) {
        $context = $userlist->get_context();
        $userids = $userlist->get_userids();
        $userid = reset($userids);

        if (!$context instanceof context_user || count($userids) !== 1 || (int)$context->instanceid !== (int)$userid) {
            return;
        }

        $repository = new report_repository();
        $repository->delete_runs_for_userid($userid);
    }
}
