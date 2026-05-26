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
 * Report repository for tool_uploadusersplus.
 *
 * @package    tool_uploadusersplus
 * @copyright  2026 eLearn Solutions
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace tool_uploadusersplus\local;

defined('MOODLE_INTERNAL') || die();

/**
 * Reads successful run report data from plugin-owned tables.
 *
 * @package    tool_uploadusersplus
 * @copyright  2026 eLearn Solutions
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class report_repository {
    /**
     * Get the total number of successful runs.
     *
     * @return int
     */
    public function count_successful_runs(): int {
        global $DB;

        return $DB->count_records('tool_uploadusersplus_run', ['status' => helper::RUN_STATUS_SUCCESS]);
    }

    /**
     * Get the SQL fields used by the report table.
     *
     * @return string
     */
    public function get_table_fields_sql(): string {
        return 'r.id, r.timecreated, r.usersuploaded, r.usersupdated, r.usersenrolleddistinct, r.enrolmentscreated,
                u.id AS uploaderid, u.firstname, u.lastname, u.firstnamephonetic, u.lastnamephonetic, u.middlename,
                u.alternatename';
    }

    /**
     * Get the SQL FROM clause used by the report table.
     *
     * @return string
     */
    public function get_table_from_sql(): string {
        return '{tool_uploadusersplus_run} r
                JOIN {user} u ON u.id = r.userid';
    }

    /**
     * Get the SQL WHERE clause used by the report table.
     *
     * @return string
     */
    public function get_table_where_sql(): string {
        return 'r.status = :status';
    }

    /**
     * Get the SQL params for the report table.
     *
     * @return array
     */
    public function get_table_params(): array {
        return ['status' => helper::RUN_STATUS_SUCCESS];
    }

    /**
     * Fetch distinct course lists keyed by run ID.
     *
     * @param array $runids
     * @return array
     */
    public function get_courses_for_runs(array $runids): array {
        global $DB;

        if (empty($runids)) {
            return [];
        }

        [$insql, $params] = $DB->get_in_or_equal($runids, SQL_PARAMS_NAMED);
        $sql = "SELECT runid, courseid, courseshortname, coursefullname
                  FROM {tool_uploadusersplus_run_course}
                 WHERE runid {$insql}
              ORDER BY runid ASC, courseshortname ASC, coursefullname ASC";
        $grouped = [];
        $recordset = $DB->get_recordset_sql($sql, $params);
        foreach ($recordset as $record) {
            if (!isset($grouped[$record->runid])) {
                $grouped[$record->runid] = [];
            }

            $label = trim((string)$record->courseshortname);
            if ($label === '') {
                $label = trim((string)$record->coursefullname);
            }
            if ($label === '') {
                $label = (string)$record->courseid;
            }

            $grouped[$record->runid][$record->courseid] = $label;
        }
        $recordset->close();

        return $grouped;
    }

    /**
     * Export run rows for a specific uploader.
     *
     * @param int $userid
     * @return array
     */
    public function get_runs_for_userid(int $userid): array {
        global $DB;

        return $DB->get_records('tool_uploadusersplus_run', ['userid' => $userid], 'timecreated DESC');
    }

    /**
     * Export child course rows for a set of runs.
     *
     * @param array $runids
     * @return array
     */
    public function get_course_rows_for_runs(array $runids): array {
        global $DB;

        if (empty($runids)) {
            return [];
        }

        [$insql, $params] = $DB->get_in_or_equal($runids, SQL_PARAMS_NAMED);
        return $DB->get_records_select('tool_uploadusersplus_run_course', "runid {$insql}", $params, 'runid ASC, courseid ASC');
    }

    /**
     * Delete logged run data for a specific uploader.
     *
     * @param int $userid
     * @return void
     */
    public function delete_runs_for_userid(int $userid): void {
        global $DB;

        $runs = $DB->get_records('tool_uploadusersplus_run', ['userid' => $userid], '', 'id');
        if (!$runs) {
            return;
        }

        $runids = array_keys($runs);
        [$insql, $params] = $DB->get_in_or_equal($runids, SQL_PARAMS_NAMED);
        $DB->delete_records_select('tool_uploadusersplus_run_course', "runid {$insql}", $params);
        $DB->delete_records('tool_uploadusersplus_run', ['userid' => $userid]);
    }
}
