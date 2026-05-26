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
 * Reports table for tool_uploadusersplus.
 *
 * @package    tool_uploadusersplus
 * @copyright  2026 eLearn Solutions
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace tool_uploadusersplus\output;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/tablelib.php');

use tool_uploadusersplus\local\report_repository;

/**
 * Paginated successful-run reports table.
 *
 * @package    tool_uploadusersplus
 * @copyright  2026 eLearn Solutions
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class reports_table extends \table_sql {
    /** @var report_repository */
    protected report_repository $repository;
    /** @var array */
    protected array $coursemap = [];

    /**
     * Constructor.
     *
     * @param report_repository $repository
     */
    public function __construct(report_repository $repository) {
        parent::__construct('tool-uploadusersplus-reports');

        $this->repository = $repository;
        $this->define_columns([
            'timecreated',
            'uploadedby',
            'usersuploaded',
            'usersupdated',
            'usersenrolleddistinct',
            'enrolmentscreated',
            'enrolledcourses',
        ]);
        $this->define_headers([
            get_string('reports_uploaddate', 'tool_uploadusersplus'),
            get_string('reports_uploadedby', 'tool_uploadusersplus'),
            get_string('reports_usersuploaded', 'tool_uploadusersplus'),
            get_string('reports_usersupdated', 'tool_uploadusersplus'),
            get_string('reports_usersenrolled', 'tool_uploadusersplus'),
            get_string('reports_enrolmentscreated', 'tool_uploadusersplus'),
            get_string('reports_enrolledcourses', 'tool_uploadusersplus'),
        ]);

        $this->sortable(true, 'timecreated', SORT_DESC);
        $this->collapsible(false);
        $this->pageable(true);
        $this->no_sorting('uploadedby', 'enrolledcourses');

        $this->set_sql(
            $this->repository->get_table_fields_sql(),
            $this->repository->get_table_from_sql(),
            $this->repository->get_table_where_sql(),
            $this->repository->get_table_params()
        );
    }

    /**
     * Load the rows for the current page and cache child course data.
     *
     * @param int $pagesize
     * @param bool $useinitialsbar
     * @return void
     */
    public function query_db($pagesize, $useinitialsbar = true) {
        parent::query_db($pagesize, false);

        $runids = array_keys($this->rawdata ?? []);
        $this->coursemap = $this->repository->get_courses_for_runs($runids);
    }

    /**
     * Format upload date.
     *
     * @param \stdClass $row
     * @return string
     */
    public function col_timecreated($row): string {
        return userdate($row->timecreated);
    }

    /**
     * Format uploader full name.
     *
     * @param \stdClass $row
     * @return string
     */
    public function col_uploadedby($row): string {
        return fullname($row);
    }

    /**
     * Format enrolled courses.
     *
     * @param \stdClass $row
     * @return string
     */
    public function col_enrolledcourses($row): string {
        if (empty($this->coursemap[$row->id])) {
            return '-';
        }

        return s(implode(', ', array_values($this->coursemap[$row->id])));
    }
}
