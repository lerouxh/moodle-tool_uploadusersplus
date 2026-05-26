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
 * Resolver helpers for courses, groups, cohorts, and users.
 *
 * @package    tool_uploadusersplus
 * @copyright  2026 eLearn Solutions
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace tool_uploadusersplus\local;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/enrollib.php');
require_once($CFG->dirroot . '/cohort/lib.php');
require_once($CFG->dirroot . '/group/lib.php');
require_once($CFG->dirroot . '/user/lib.php');

/**
 * Resolves identifiers from CSV values.
 *
 * @package    tool_uploadusersplus
 * @copyright  2026 eLearn Solutions
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class resolver {
    /** @var array */
    protected array $coursecache = [];
    /** @var array */
    protected array $groupcache = [];
    /** @var array */
    protected array $cohortcache = [];
    /** @var array */
    protected array $usercache = [];
    /** @var array */
    protected array $manualinstancecache = [];

    /**
     * Resolve a local user by username.
     *
     * @param string $username
     * @return \stdClass|null
     */
    public function resolve_user(string $username): ?\stdClass {
        global $CFG;

        if (array_key_exists($username, $this->usercache)) {
            return $this->usercache[$username];
        }

        $this->usercache[$username] = \core_user::get_user_by_username(
            $username,
            '*',
            $CFG->mnet_localhost_id,
            IGNORE_MISSING
        );

        return $this->usercache[$username] ?: null;
    }

    /**
     * Resolve a course by shortname, then exact fullname.
     *
     * @param string $value
     * @return array
     */
    public function resolve_course(string $value): array {
        global $DB;

        if (array_key_exists($value, $this->coursecache)) {
            return $this->coursecache[$value];
        }

        $course = $DB->get_record('course', ['shortname' => $value], 'id, shortname, fullname, startdate');
        if ($course) {
            $this->coursecache[$value] = ['record' => $course];
            return $this->coursecache[$value];
        }

        $matches = $DB->get_records('course', ['fullname' => $value], '', 'id, shortname, fullname, startdate');
        if (!$matches) {
            $this->coursecache[$value] = [
                'error' => get_string('error_coursemissing', 'tool_uploadusersplus', s($value)),
            ];
            return $this->coursecache[$value];
        }

        if (count($matches) > 1) {
            $this->coursecache[$value] = [
                'error' => get_string('error_courseambiguous', 'tool_uploadusersplus', s($value)),
            ];
            return $this->coursecache[$value];
        }

        $this->coursecache[$value] = ['record' => reset($matches)];
        return $this->coursecache[$value];
    }

    /**
     * Resolve a group inside a course by id or name.
     *
     * @param int $courseid
     * @param string $value
     * @return array
     */
    public function resolve_group(int $courseid, string $value): array {
        global $DB;

        $cachekey = $courseid . ':' . $value;
        if (array_key_exists($cachekey, $this->groupcache)) {
            return $this->groupcache[$cachekey];
        }

        if (ctype_digit($value)) {
            $group = $DB->get_record('groups', ['id' => (int)$value, 'courseid' => $courseid], 'id, courseid, name');
            if ($group) {
                $this->groupcache[$cachekey] = ['record' => $group];
                return $this->groupcache[$cachekey];
            }
        }

        $matches = $DB->get_records('groups', ['courseid' => $courseid, 'name' => $value], '', 'id, courseid, name');
        if (!$matches) {
            $this->groupcache[$cachekey] = [
                'error' => get_string('error_groupmissing', 'tool_uploadusersplus', s($value)),
            ];
            return $this->groupcache[$cachekey];
        }

        if (count($matches) > 1) {
            $this->groupcache[$cachekey] = [
                'error' => get_string('error_groupambiguous', 'tool_uploadusersplus', s($value)),
            ];
            return $this->groupcache[$cachekey];
        }

        $this->groupcache[$cachekey] = ['record' => reset($matches)];
        return $this->groupcache[$cachekey];
    }

    /**
     * Resolve a cohort by idnumber, then exact name.
     *
     * @param string $value
     * @return array
     */
    public function resolve_cohort(string $value): array {
        global $DB;

        if (array_key_exists($value, $this->cohortcache)) {
            return $this->cohortcache[$value];
        }

        $cohort = $DB->get_record('cohort', ['idnumber' => $value], 'id, name, idnumber, component');
        if ($cohort) {
            $this->cohortcache[$value] = ['record' => $cohort];
            return $this->cohortcache[$value];
        }

        $matches = $DB->get_records('cohort', ['name' => $value], '', 'id, name, idnumber, component');
        if (!$matches) {
            $this->cohortcache[$value] = [
                'error' => get_string('error_cohortmissing', 'tool_uploadusersplus', s($value)),
            ];
            return $this->cohortcache[$value];
        }

        if (count($matches) > 1) {
            $this->cohortcache[$value] = [
                'error' => get_string('error_cohortambiguous', 'tool_uploadusersplus', s($value)),
            ];
            return $this->cohortcache[$value];
        }

        $this->cohortcache[$value] = ['record' => reset($matches)];
        return $this->cohortcache[$value];
    }

    /**
     * Get the first manual enrolment instance for a course.
     *
     * @param int $courseid
     * @return \stdClass|null
     */
    public function get_manual_enrol_instance(int $courseid): ?\stdClass {
        if (array_key_exists($courseid, $this->manualinstancecache)) {
            return $this->manualinstancecache[$courseid];
        }

        $instance = null;
        foreach (enrol_get_instances($courseid, true) as $enrolinstance) {
            if ($enrolinstance->enrol === 'manual') {
                $instance = $enrolinstance;
                break;
            }
        }

        $this->manualinstancecache[$courseid] = $instance;
        return $instance;
    }
}
