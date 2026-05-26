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
 * Asynchronous CSV template download endpoint.
 *
 * @package    tool_uploadusersplus
 * @copyright  2026 eLearn Solutions
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../../config.php');
require_once($CFG->libdir . '/adminlib.php');
require_once($CFG->dirroot . '/user/profile/lib.php');

require_login();
require_sesskey();

$context = context_system::instance();
require_capability('moodle/site:uploadusers', $context);

$settings = \tool_uploadusersplus\local\helper::get_admin_settings();
if (!\tool_uploadusersplus\local\helper::template_generation_enabled()) {
    throw new moodle_exception('error_templatedisabled', 'tool_uploadusersplus');
}

$includecustomprofilefields = optional_param('includecustomprofilefields', 0, PARAM_BOOL);
$includeoptionalfields = optional_param('includeoptionalfields', 0, PARAM_BOOL);
$courseenrolments = optional_param('courseenrolments', 0, PARAM_BOOL);
$cohortenrolments = optional_param('cohortenrolments', 0, PARAM_BOOL);
$numberofcourses = optional_param('numberofcourses', 1, PARAM_INT);
$numberofcohorts = optional_param('numberofcohorts', 1, PARAM_INT);

if (!empty($settings->hidecustomprofilefieldsoption)) {
    $includecustomprofilefields = 0;
}
if (!empty($settings->hideoptionalfieldsoption)) {
    $includeoptionalfields = 0;
}
if (!empty($settings->hidecourseenrolmentsoption)) {
    $courseenrolments = 0;
    $numberofcourses = 1;
}
if (!empty($settings->hidecohortenrolmentsoption)) {
    $cohortenrolments = 0;
    $numberofcohorts = 1;
}

if ($courseenrolments && !\tool_uploadusersplus\local\helper::is_valid_int_in_range($numberofcourses, 1, 99)) {
    throw new moodle_exception('error_numberofcourses', 'tool_uploadusersplus');
}

if ($cohortenrolments && !\tool_uploadusersplus\local\helper::is_valid_int_in_range($numberofcohorts, 1, 10)) {
    throw new moodle_exception('error_numberofcohorts', 'tool_uploadusersplus');
}

$generator = new \tool_uploadusersplus\local\template_generator();
$headers = $generator->build_headers(
    (bool)$includecustomprofilefields,
    (bool)$includeoptionalfields,
    max(1, $numberofcourses),
    max(1, $numberofcohorts),
    (bool)$courseenrolments,
    (bool)$cohortenrolments
);
$csv = $generator->build_csv($headers);
$filename = \tool_uploadusersplus\local\helper::get_template_filename();

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Cache-Control: private, must-revalidate, max-age=0');
header('Pragma: no-cache');

echo $csv;
die;
