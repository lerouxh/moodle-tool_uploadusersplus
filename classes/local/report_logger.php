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
 * Successful run logger for tool_uploadusersplus.
 *
 * @package    tool_uploadusersplus
 * @copyright  2026 eLearn Solutions
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace tool_uploadusersplus\local;

defined('MOODLE_INTERNAL') || die();

/**
 * Stores successful committed upload summaries in plugin-owned tables.
 *
 * @package    tool_uploadusersplus
 * @copyright  2026 eLearn Solutions
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class report_logger {
    /**
     * Store a successful committed run.
     *
     * @param int $userid
     * @param string $filename
     * @param \stdClass $formdata
     * @param array $validationresult
     * @param array $importresult
     * @return bool
     */
    public function log_successful_run(
        int $userid,
        string $filename,
        \stdClass $formdata,
        array $validationresult,
        array $importresult
    ): bool {
        global $DB;

        if (!empty($importresult['rolledback']) || empty($importresult['reportstats'])) {
            return false;
        }

        $stats = $importresult['reportstats'];
        $time = time();

        $run = (object)[
            'timecreated' => $time,
            'timemodified' => $time,
            'userid' => $userid,
            'filename' => $filename,
            'uploadtype' => helper::get_upload_type_key((int)$formdata->uploadtype),
            'csvseparator' => (string)($validationresult['delimitername'] ?? ''),
            'usersuploaded' => $this->count_rows_by_action($importresult['rows'], 'create'),
            'usersupdated' => $this->count_rows_by_action($importresult['rows'], 'update'),
            // "usersenrolleddistinct" means distinct users who received at least one new direct course enrolment.
            'usersenrolleddistinct' => count($stats['usersenrolleddistinct']),
            'enrolmentscreated' => (int)$stats['enrolmentscreated'],
            'coursescount' => count($stats['courses']),
            'cohortscount' => count($stats['cohorts']),
            'status' => helper::RUN_STATUS_SUCCESS,
        ];

        $transaction = $DB->start_delegated_transaction();
        try {
            $runid = $DB->insert_record('tool_uploadusersplus_run', $run);

            foreach ($stats['courses'] as $courseid => $course) {
                $row = (object)[
                    'runid' => $runid,
                    'courseid' => $courseid,
                    'courseshortname' => $course['courseshortname'],
                    'coursefullname' => $course['coursefullname'],
                    'usersenrolled' => count($course['userids']),
                    'timecreated' => $time,
                ];
                $DB->insert_record('tool_uploadusersplus_run_course', $row);
            }

            $transaction->allow_commit();
        } catch (\Throwable $e) {
            $transaction->rollback($e);
            return false;
        }

        return true;
    }

    /**
     * Count successful rows for a given action.
     *
     * @param array $rows
     * @param string $action
     * @return int
     */
    protected function count_rows_by_action(array $rows, string $action): int {
        $count = 0;
        foreach ($rows as $row) {
            if (($row['action'] ?? '') === $action && ($row['status'] ?? '') === 'success') {
                $count++;
            }
        }

        return $count;
    }
}
