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
 * CSV template generator.
 *
 * @package    tool_uploadusersplus
 * @copyright  2026 eLearn Solutions
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace tool_uploadusersplus\local;

defined('MOODLE_INTERNAL') || die();

/**
 * Generates CSV templates in memory.
 *
 * @package    tool_uploadusersplus
 * @copyright  2026 eLearn Solutions
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class template_generator {
    /**
     * Build ordered CSV headers from the current options.
     *
     * @param bool $includecustomprofilefields
     * @param bool $includeoptionalfields
     * @param int $numberofcourses
     * @param int $numberofcohorts
     * @param bool $courseenrolments
     * @param bool $cohortenrolments
     * @return array
     */
    public function build_headers(
        bool $includecustomprofilefields,
        bool $includeoptionalfields,
        int $numberofcourses,
        int $numberofcohorts,
        bool $courseenrolments,
        bool $cohortenrolments
    ): array {
        $headers = helper::get_template_default_headers();

        if ($includecustomprofilefields) {
            $headers = array_merge($headers, array_keys(helper::get_template_custom_profile_fields()));
        }

        if ($includeoptionalfields) {
            $headers = array_merge($headers, helper::get_optional_user_fields());
        }

        if ($courseenrolments) {
            for ($i = 1; $i <= $numberofcourses; $i++) {
                $headers[] = 'course' . $i;
                $headers[] = 'group' . $i;
            }
        }

        if ($cohortenrolments) {
            for ($i = 1; $i <= $numberofcohorts; $i++) {
                $headers[] = 'cohort' . $i;
            }
        }

        return $headers;
    }

    /**
     * Build a UTF-8 CSV document with a byte order mark.
     *
     * @param array $headers
     * @return string
     */
    public function build_csv(array $headers): string {
        $stream = fopen('php://temp', 'w+');
        fputcsv($stream, $headers, ',', '"', '\\');
        rewind($stream);
        $csv = stream_get_contents($stream);
        fclose($stream);

        return \core_text::UTF8_BOM . $csv;
    }
}
