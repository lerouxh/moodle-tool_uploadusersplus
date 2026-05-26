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
 * Successful uploads and enrolments reports page.
 *
 * @package    tool_uploadusersplus
 * @copyright  2026 eLearn Solutions
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require('../../../config.php');
require_once($CFG->libdir . '/adminlib.php');

$url = new moodle_url('/admin/tool/uploadusersplus/reports.php');

admin_externalpage_setup('tooluploadusersplus');

$context = context_system::instance();
require_capability('moodle/site:uploadusers', $context);
if (!\tool_uploadusersplus\local\helper::is_free_version()
        && !\tool_uploadusersplus\local\helper::current_user_can_access_reports()) {
    require_capability('moodle/site:config', $context);
}

$PAGE->set_url($url);
$PAGE->set_title(get_string('reportsheading', 'tool_uploadusersplus'));
$PAGE->set_heading(get_string('reportsheading', 'tool_uploadusersplus'));

$renderer = $PAGE->get_renderer('tool_uploadusersplus');

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('reportsheading', 'tool_uploadusersplus'));
echo $renderer->render_back_to_upload_button();

if (\tool_uploadusersplus\local\helper::is_free_version()) {
    $prolink = \tool_uploadusersplus\local\helper::get_pro_purchase_link(
        get_string('proversion', 'tool_uploadusersplus')
    );
    echo $OUTPUT->notification(
        get_string('reportunavailable', 'tool_uploadusersplus') . ' '
            . get_string('reportsavailablepro', 'tool_uploadusersplus', $prolink),
        'info'
    );
    echo $OUTPUT->footer();
    exit;
}

$repository = new \tool_uploadusersplus\local\report_repository();
$table = new \tool_uploadusersplus\output\reports_table($repository);
$table->define_baseurl($url);

if ($repository->count_successful_runs() === 0) {
    echo $renderer->render_empty_reports_state();
} else {
    $table->out(25, false);
}

echo $OUTPUT->footer();
