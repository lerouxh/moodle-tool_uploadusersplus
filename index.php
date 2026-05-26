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
 * Main page for Upload users PLUS.
 *
 * @package    tool_uploadusersplus
 * @copyright  2026 eLearn Solutions
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require('../../../config.php');
require_once($CFG->libdir . '/adminlib.php');
require_once($CFG->dirroot . '/user/lib.php');
require_once($CFG->dirroot . '/user/editlib.php');
require_once($CFG->dirroot . '/user/profile/lib.php');
require_once($CFG->dirroot . '/cohort/lib.php');
require_once($CFG->dirroot . '/group/lib.php');

$returnurl = new moodle_url('/admin/category.php', ['category' => 'accounts']);
$url = new moodle_url('/admin/tool/uploadusersplus/index.php');
require_once($CFG->dirroot . '/admin/tool/uploadusersplus/classes/local/helper.php');

admin_externalpage_setup('tooluploadusersplus');

$context = context_system::instance();
require_capability('moodle/site:uploadusers', $context);

$PAGE->set_url($url);
$PAGE->set_title(get_string('pluginname', 'tool_uploadusersplus'));
$PAGE->set_heading(get_string('pluginname', 'tool_uploadusersplus'));
$settings = \tool_uploadusersplus\local\helper::get_admin_settings();
$showtemplategeneration = \tool_uploadusersplus\local\helper::current_user_can_see_template_generation();
$showtemplateadminnotice = \tool_uploadusersplus\local\helper::current_user_sees_template_admin_notice();
$canaccessreports = !\tool_uploadusersplus\local\helper::is_free_version()
    && \tool_uploadusersplus\local\helper::current_user_can_access_reports();
$defaults = \tool_uploadusersplus\local\helper::get_default_form_data();
$currentdryrun = optional_param('dryrun', $defaults->dryrun, PARAM_BOOL);
$disabledreportoptions = array_flip(\tool_uploadusersplus\local\helper::get_disabled_report_options());
$PAGE->requires->js_call_amd('tool_uploadusersplus/form', 'init', [[
    'templateUrl' => (new moodle_url('/admin/tool/uploadusersplus/template.php', ['sesskey' => sesskey()]))->out(false),
    'freeVersion' => \tool_uploadusersplus\local\helper::is_free_version(),
    'reportOptions' => [
        'summary' => [
            'value' => \tool_uploadusersplus\local\helper::REPORT_SUMMARY,
            'disabled' => isset($disabledreportoptions[\tool_uploadusersplus\local\helper::REPORT_SUMMARY]),
        ],
        'detailed' => [
            'value' => \tool_uploadusersplus\local\helper::REPORT_DETAILED,
            'disabled' => isset($disabledreportoptions[\tool_uploadusersplus\local\helper::REPORT_DETAILED]),
        ],
        'email' => [
            'value' => \tool_uploadusersplus\local\helper::REPORT_EMAIL,
            'disabled' => isset($disabledreportoptions[\tool_uploadusersplus\local\helper::REPORT_EMAIL]),
        ],
    ],
]]);

$mform = new \tool_uploadusersplus\form\main_form(null, [
    'settings' => $settings,
    'dryrun' => $currentdryrun,
    'showtemplategeneration' => $showtemplategeneration,
    'showtemplateadminnotice' => $showtemplateadminnotice,
]);

$report = null;
$showdetails = false;

if ($mform->is_cancelled()) {
    redirect($returnurl);
} else if ($data = $mform->get_data()) {
    $data = \tool_uploadusersplus\local\helper::normalise_form_data($data);
    $validator = new \tool_uploadusersplus\local\validator();
    $importer = new \tool_uploadusersplus\local\importer();
    $reportlogger = new \tool_uploadusersplus\local\report_logger();
    $reportbuilder = new \tool_uploadusersplus\local\report_builder();

    $content = $mform->get_file_content('userfile');
    $filename = $mform->get_new_filename('userfile') ?? '';
    $validationresult = $validator->validate($content, $data);
    $importresult = null;

    if (empty($data->dryrun) && !$validationresult['hasblockingerrors']) {
        $importresult = $importer->import($validationresult, $data);
        if (empty($importresult['rolledback'])) {
            $logged = $reportlogger->log_successful_run($USER->id, $filename, $data, $validationresult, $importresult);
            if (!$logged) {
                $importresult['globalmessages'][] = get_string('reportloggingfailed', 'tool_uploadusersplus');
            }
        }
    }

    $report = $reportbuilder->build($validationresult, !empty($data->dryrun), $importresult);
    $showdetails = in_array((int)$data->reporttype, [
        \tool_uploadusersplus\local\helper::REPORT_DETAILED,
        \tool_uploadusersplus\local\helper::REPORT_EMAIL,
    ], true);

    if (!$report['hasblockingerrors']
            && empty($data->dryrun)
            && !\tool_uploadusersplus\local\helper::is_free_version()
            && (int)$data->reporttype === \tool_uploadusersplus\local\helper::REPORT_EMAIL) {
        $mailresult = $importer->email_detailed_report($report, $data->emailrecipient);
        $report['emailsent'] = $mailresult['sent'];
        $report['emailerror'] = $mailresult['error'];
    }

    $mform->set_data($data);
} else {
    $mform->set_data($defaults);
}

$renderer = $PAGE->get_renderer('tool_uploadusersplus');

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('pluginname', 'tool_uploadusersplus'));
echo $renderer->render_reports_button($canaccessreports);
echo $renderer->render_logo();

if ($report !== null) {
    echo $renderer->render_report($report, $showdetails);
}

$mform->display();
echo $OUTPUT->footer();
