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
 * Admin setting validation for enrolment restriction days.
 *
 * @package    tool_uploadusersplus
 * @copyright  2026 eLearn Solutions
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace tool_uploadusersplus\local;

defined('MOODLE_INTERNAL') || die();

/**
 * Validates the enrolment restriction day setting.
 *
 * @package    tool_uploadusersplus
 * @copyright  2026 eLearn Solutions
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class admin_setting_enrolmentdays extends \admin_setting_configtext {
    /**
     * Validate the setting before storage.
     *
     * @param string $data
     * @return bool|string
     */
    public function validate($data) {
        $validated = parent::validate($data);
        if ($validated !== true) {
            return $validated;
        }

        if (!helper::is_valid_int_in_range($data, 1, 999)) {
            return get_string('error_settingsenrolmentrestrictiondays', 'tool_uploadusersplus');
        }

        return true;
    }
}
