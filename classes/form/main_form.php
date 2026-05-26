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
 * Main form for tool_uploadusersplus.
 *
 * @package    tool_uploadusersplus
 * @copyright  2026 eLearn Solutions
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace tool_uploadusersplus\form;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/formslib.php');

use html_writer;
use tool_uploadusersplus\local\helper;

/**
 * Main page form.
 *
 * @package    tool_uploadusersplus
 * @copyright  2026 eLearn Solutions
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class main_form extends \moodleform {
    /**
     * Define the form structure.
     *
     * @return void
     */
    public function definition(): void {
        $mform = $this->_form;
        $settings = $this->_customdata['settings'] ?? helper::get_admin_settings();
        $dryrun = !empty($this->_customdata['dryrun']);
        $showtemplategeneration = !empty($this->_customdata['showtemplategeneration']);
        $showtemplateadminnotice = !empty($this->_customdata['showtemplateadminnotice']);

        if ($showtemplategeneration) {
            $mform->addElement('header', 'createuploadtemplateheader', get_string('createuploadtemplate', 'tool_uploadusersplus'));
            $mform->setExpanded('createuploadtemplateheader', true, true);

            if (empty($settings->hidecustomprofilefieldsoption) || empty($settings->hideoptionalfieldsoption)) {
                $mform->addElement('html', html_writer::tag('h3', get_string('options', 'tool_uploadusersplus'), [
                    'class' => 'h5 mt-2 mb-3',
                ]));

                if (empty($settings->hidecustomprofilefieldsoption)) {
                    $customprofilefieldselement = $mform->addElement(
                        'advcheckbox',
                        'includecustomprofilefields',
                        get_string('includecustomprofilefields', 'tool_uploadusersplus')
                    );
                    $customprofilefieldselement->updateAttributes([
                        'data-template-field' => 'includecustomprofilefields',
                    ]);
                    $mform->setType('includecustomprofilefields', PARAM_BOOL);
                }

                if (empty($settings->hideoptionalfieldsoption)) {
                    $optionalfieldselement = $mform->addElement(
                        'advcheckbox',
                        'includeoptionalfields',
                        get_string('includeoptionalfields', 'tool_uploadusersplus')
                    );
                    $optionalfieldselement->updateAttributes([
                        'data-template-field' => 'includeoptionalfields',
                    ]);
                    $mform->setType('includeoptionalfields', PARAM_BOOL);
                }
            }

            if (empty($settings->hidecourseenrolmentsoption) || empty($settings->hidecohortenrolmentsoption)) {
                $mform->addElement('html', html_writer::tag('h3', get_string('enrolmentsection', 'tool_uploadusersplus'), [
                    'class' => 'h5 mt-4 mb-3',
                ]));

                if (empty($settings->hidecourseenrolmentsoption)) {
                    $courseenrolmentselement = $mform->addElement(
                        'advcheckbox',
                        'courseenrolments',
                        get_string('courseenrolments', 'tool_uploadusersplus')
                    );
                    $courseenrolmentselement->updateAttributes([
                        'data-template-field' => 'courseenrolments',
                    ]);
                    $mform->setType('courseenrolments', PARAM_BOOL);

                    $numberofcourseselement = $mform->addElement(
                        'text',
                        'numberofcourses',
                        get_string('numberofcourses', 'tool_uploadusersplus'),
                        ['size' => 3]
                    );
                    $numberofcourseselement->updateAttributes([
                        'data-template-field' => 'numberofcourses',
                    ]);
                    $mform->setType('numberofcourses', PARAM_INT);
                    $mform->hideIf('numberofcourses', 'courseenrolments', 'notchecked');
                }

                if (empty($settings->hidecohortenrolmentsoption)) {
                    $cohortenrolmentselement = $mform->addElement(
                        'advcheckbox',
                        'cohortenrolments',
                        get_string('cohortenrolments', 'tool_uploadusersplus')
                    );
                    $cohortenrolmentselement->updateAttributes([
                        'data-template-field' => 'cohortenrolments',
                    ]);
                    $mform->setType('cohortenrolments', PARAM_BOOL);

                    $numberofcohortselement = $mform->addElement(
                        'text',
                        'numberofcohorts',
                        get_string('numberofcohorts', 'tool_uploadusersplus'),
                        ['size' => 3]
                    );
                    $numberofcohortselement->updateAttributes([
                        'data-template-field' => 'numberofcohorts',
                    ]);
                    $mform->setType('numberofcohorts', PARAM_INT);
                    $mform->hideIf('numberofcohorts', 'cohortenrolments', 'notchecked');
                }
            }

            $templatebutton = html_writer::link('#', get_string('generatetemplate', 'tool_uploadusersplus'), [
                'id' => 'id_generatetemplate',
                'role' => 'button',
                'class' => 'btn btn-secondary',
                'data-action' => 'tool-uploadusersplus-template',
                'data-template-url' => '',
            ]);
            $downloadframe = html_writer::tag('iframe', '', [
                'id' => 'id_generatetemplateframe',
                'name' => 'uup_template_download',
                'class' => 'd-none',
                'data-region' => 'tool-uploadusersplus-template-frame',
                'tabindex' => '-1',
                'aria-hidden' => 'true',
            ]);
            $mform->addElement('html', html_writer::div(
                $templatebutton . $downloadframe,
                'mt-3 mb-2'
            ));

            if ($showtemplateadminnotice) {
                $mform->addElement('html', html_writer::div(
                    s(get_string('templatevisibletositeadminsonly', 'tool_uploadusersplus')),
                    'text-muted mt-2'
                ));
            }
        }

        $mform->addElement('header', 'uploadusersheader', get_string('uploaduserssection', 'tool_uploadusersplus'));
        $mform->setExpanded('uploadusersheader', true, true);
        $mform->addElement('static', 'uploadinstructions', '', get_string('uploadinstructions', 'tool_uploadusersplus'));

        $mform->addElement('filepicker', 'userfile', get_string('file'), null, [
            'accepted_types' => ['.csv'],
        ]);
        $mform->addRule('userfile', null, 'required', null, 'client');

        $mform->addElement('header', 'uploadsettingsheader', get_string('uploadsettings', 'tool_uploadusersplus'));
        $mform->setExpanded('uploadsettingsheader', true, true);

        $mform->addElement(
            'select',
            'uploadtype',
            get_string('uploadtype', 'tool_uploadusersplus'),
            helper::get_upload_type_options(),
            ['data-field' => 'uploadtype']
        );
        $mform->setType('uploadtype', PARAM_INT);

        $mform->addElement(
            'select',
            'newpasswords',
            get_string('newpasswords', 'tool_uploadusersplus'),
            helper::get_new_password_options(),
            ['data-field' => 'newpasswords']
        );
        $mform->setType('newpasswords', PARAM_INT);
        $mform->hideIf('newpasswords', 'uploadtype', 'eq', helper::UPLOADTYPE_UPDATEONLY);

        $mform->addElement(
            'select',
            'existingpasswords',
            get_string('existingpasswords', 'tool_uploadusersplus'),
            helper::get_existing_password_options(),
            ['data-field' => 'existingpasswords']
        );
        $mform->setType('existingpasswords', PARAM_INT);
        $mform->hideIf('existingpasswords', 'uploadtype', 'neq', helper::UPLOADTYPE_UPDATEONLY);

        $dryrunelement = $mform->addElement('advcheckbox', 'dryrun', get_string('dryrun', 'tool_uploadusersplus'));
        $dryrunelement->updateAttributes(['data-field' => 'dryrun']);
        $mform->setType('dryrun', PARAM_BOOL);

        $reportselect = $mform->addElement('select', 'reporttype', get_string('reporttype', 'tool_uploadusersplus'), []);
        $reportselect->updateAttributes([
            'data-region' => 'tool-uploadusersplus-report-options',
            'data-field' => 'reporttype',
        ]);
        $disabledreportoptions = array_flip(helper::get_disabled_report_options());
        $reportoptionkeys = [
            helper::REPORT_SUMMARY => 'summary',
            helper::REPORT_DETAILED => 'detailed',
            helper::REPORT_EMAIL => 'email',
        ];
        foreach (helper::get_report_options($dryrun) as $value => $label) {
            $attributes = [
                'data-option' => $reportoptionkeys[$value],
            ];
            if (isset($disabledreportoptions[$value])) {
                $attributes['disabled'] = 'disabled';
            }
            $reportselect->addOption($label, $value, $attributes);
        }
        $mform->setType('reporttype', PARAM_INT);

        if (!helper::is_free_version()) {
            $mform->addElement('text', 'emailrecipient', get_string('emailrecipient', 'tool_uploadusersplus'), [
                'size' => 40,
                'data-field' => 'emailrecipient',
            ]);
            $mform->setType('emailrecipient', PARAM_EMAIL);
            $mform->hideIf('emailrecipient', 'dryrun', 'checked');
            $mform->hideIf('emailrecipient', 'reporttype', 'neq', helper::REPORT_EMAIL);
        }

        $this->add_action_buttons(true, get_string('uploadbutton', 'tool_uploadusersplus'));
    }

    /**
     * Server-side validation for form options.
     *
     * @param array $data
     * @param array $files
     * @return array
     */
    public function validation($data, $files): array {
        $errors = parent::validation($data, $files);
        $data = helper::normalise_form_data_array($data);

        if (!empty($data['courseenrolments']) && !helper::is_valid_int_in_range($data['numberofcourses'] ?? '', 1, 99)) {
            $errors['numberofcourses'] = get_string('error_numberofcourses', 'tool_uploadusersplus');
        }

        if (!empty($data['cohortenrolments']) && !helper::is_valid_int_in_range($data['numberofcohorts'] ?? '', 1, 10)) {
            $errors['numberofcohorts'] = get_string('error_numberofcohorts', 'tool_uploadusersplus');
        }

        if (!helper::is_free_version() && empty($data['dryrun']) && (int)$data['reporttype'] === helper::REPORT_EMAIL) {
            if (empty($data['emailrecipient'])) {
                $errors['emailrecipient'] = get_string('required');
            } else if (!validate_email($data['emailrecipient'])) {
                $errors['emailrecipient'] = get_string('invalidemail');
            }
        }

        return $errors;
    }
}
