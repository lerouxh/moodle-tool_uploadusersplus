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
 * Admin settings for tool_uploadusersplus.
 *
 * @package    tool_uploadusersplus
 * @copyright  2026 eLearn Solutions
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/admin/tool/uploadusersplus/classes/local/helper.php');
require_once($CFG->dirroot . '/admin/tool/uploadusersplus/classes/local/admin_setting_disabled_checkbox.php');

$beforesibling = null;
foreach (['tooluploaduserpictures', 'tooluploaduser'] as $candidate) {
    if ($ADMIN->locate($candidate)) {
        $beforesibling = $candidate;
        break;
    }
}

$ADMIN->add('accounts', new admin_externalpage(
    'tooluploadusersplus',
    get_string('pluginname', 'tool_uploadusersplus'),
    new moodle_url('/admin/tool/uploadusersplus/index.php'),
    'moodle/site:uploadusers'
), $beforesibling);

if ($hassiteconfig) {
    $settings = new admin_settingpage('tool_uploadusersplus', get_string('pluginname', 'tool_uploadusersplus'));
    $ADMIN->add('tools', $settings);

    $settings->add(new admin_setting_heading(
        'tool_uploadusersplus/optionsheading',
        get_string('options', 'tool_uploadusersplus'),
        ''
    ));

    $settings->add(new admin_setting_configcheckbox(
        'tool_uploadusersplus/hidetemplategeneration',
        get_string('setting_hidetemplategeneration', 'tool_uploadusersplus'),
        get_string('setting_hidetemplategeneration_desc', 'tool_uploadusersplus'),
        0
    ));

    $settings->add(new admin_setting_configcheckbox(
        'tool_uploadusersplus/hidecustomprofilefieldsoption',
        get_string('setting_hidecustomprofilefieldsoption', 'tool_uploadusersplus'),
        get_string('setting_hidecustomprofilefieldsoption_desc', 'tool_uploadusersplus'),
        0
    ));

    $settings->add(new admin_setting_configcheckbox(
        'tool_uploadusersplus/hideoptionalfieldsoption',
        get_string('setting_hideoptionalfieldsoption', 'tool_uploadusersplus'),
        get_string('setting_hideoptionalfieldsoption_desc', 'tool_uploadusersplus'),
        0
    ));

    $settings->add(new admin_setting_configcheckbox(
        'tool_uploadusersplus/includepasswordfield',
        get_string('setting_includepasswordfield', 'tool_uploadusersplus'),
        get_string('setting_includepasswordfield_desc', 'tool_uploadusersplus'),
        1
    ));

    $settings->add(new admin_setting_configcheckbox(
        'tool_uploadusersplus/ignorerequiredcustomprofilefieldsmissing',
        get_string('setting_ignorerequiredcustomprofilefieldsmissing', 'tool_uploadusersplus'),
        get_string('setting_ignorerequiredcustomprofilefieldsmissing_desc', 'tool_uploadusersplus'),
        0
    ));

    $settings->add(new admin_setting_description(
        'tool_uploadusersplus/ignorerequiredcustomprofilefieldsmissingnote',
        '',
        get_string('setting_ignorerequiredcustomprofilefieldsmissing_note', 'tool_uploadusersplus')
    ));

    $settings->add(new admin_setting_configselect(
        'tool_uploadusersplus/unknownprofilefielddatatypes',
        get_string('setting_unknownprofilefielddatatypes', 'tool_uploadusersplus'),
        get_string('setting_unknownprofilefielddatatypes_desc', 'tool_uploadusersplus'),
        \tool_uploadusersplus\local\helper::UNKNOWN_PROFILE_DATATYPE_BLOCK,
        \tool_uploadusersplus\local\helper::get_unknown_profile_datatype_options()
    ));

    $customprofilefieldchoices = \tool_uploadusersplus\local\helper::get_custom_profile_field_setting_choices();
    if (!empty($customprofilefieldchoices)) {
        $settings->add(new admin_setting_configmultiselect(
            'tool_uploadusersplus/customprofilefieldstoinclude',
            get_string('setting_customprofilefieldstoinclude', 'tool_uploadusersplus'),
            get_string('setting_customprofilefieldstoinclude_desc', 'tool_uploadusersplus'),
            array_keys($customprofilefieldchoices),
            $customprofilefieldchoices
        ));
    } else {
        $settings->add(new admin_setting_description(
            'tool_uploadusersplus/customprofilefieldstoincludeempty',
            get_string('setting_customprofilefieldstoinclude', 'tool_uploadusersplus'),
            get_string('setting_customprofilefieldstoinclude_empty', 'tool_uploadusersplus')
        ));
    }

    $settings->add(new admin_setting_heading(
        'tool_uploadusersplus/enrolmentheading',
        get_string('enrolmentsection', 'tool_uploadusersplus'),
        ''
    ));

    $settings->add(new admin_setting_configcheckbox(
        'tool_uploadusersplus/hidecourseenrolmentsoption',
        get_string('setting_hidecourseenrolmentsoption', 'tool_uploadusersplus'),
        get_string('setting_hidecourseenrolmentsoption_desc', 'tool_uploadusersplus'),
        0
    ));

    $settings->add(new admin_setting_configcheckbox(
        'tool_uploadusersplus/hideoptionroleenrolments',
        get_string('setting_hideoptionroleenrolments', 'tool_uploadusersplus'),
        get_string('setting_hideoptionroleenrolments_desc', 'tool_uploadusersplus'),
        0
    ));

    $settings->add(new admin_setting_configcheckbox(
        'tool_uploadusersplus/hideoptionenroltimestart',
        get_string('setting_hideoptionenroltimestart', 'tool_uploadusersplus'),
        get_string('setting_hideoptionenroltimestart_desc', 'tool_uploadusersplus'),
        0
    ));

    $settings->add(new admin_setting_configcheckbox(
        'tool_uploadusersplus/hideoptionenrolperiod',
        get_string('setting_hideoptionenrolperiod', 'tool_uploadusersplus'),
        get_string('setting_hideoptionenrolperiod_desc', 'tool_uploadusersplus'),
        0
    ));

    $settings->add(new admin_setting_configcheckbox(
        'tool_uploadusersplus/hideoptionenrolstatus',
        get_string('setting_hideoptionenrolstatus', 'tool_uploadusersplus'),
        get_string('setting_hideoptionenrolstatus_desc', 'tool_uploadusersplus'),
        0
    ));

    $settings->add(new admin_setting_configcheckbox(
        'tool_uploadusersplus/hidecohortenrolmentsoption',
        get_string('setting_hidecohortenrolmentsoption', 'tool_uploadusersplus'),
        get_string('setting_hidecohortenrolmentsoption_desc', 'tool_uploadusersplus'),
        0
    ));

    $settings->add(new admin_setting_heading(
        'tool_uploadusersplus/deletesuspendheading',
        get_string('settingsdeletesuspend', 'tool_uploadusersplus'),
        ''
    ));

    $settings->add(new admin_setting_configcheckbox(
        'tool_uploadusersplus/hideoptiondeletedfield',
        get_string('setting_hideoptiondeletedfield', 'tool_uploadusersplus'),
        get_string('setting_hideoptiondeletedfield_desc', 'tool_uploadusersplus'),
        0
    ));

    $settings->add(new admin_setting_configcheckbox(
        'tool_uploadusersplus/hideoptionsuspendedfield',
        get_string('setting_hideoptionsuspendedfield', 'tool_uploadusersplus'),
        get_string('setting_hideoptionsuspendedfield_desc', 'tool_uploadusersplus'),
        0
    ));

    $settings->add(new admin_setting_heading(
        'tool_uploadusersplus/restrictionsheading',
        get_string('settingsrestrictions', 'tool_uploadusersplus'),
        ''
    ));

    $settings->add(new \tool_uploadusersplus\local\admin_setting_disabled_checkbox(
        'tool_uploadusersplus/enrolmentrestrictions',
        get_string('setting_enrolmentrestrictions', 'tool_uploadusersplus'),
        get_string('setting_enrolmentrestrictions_desc', 'tool_uploadusersplus'),
        0
    ));

    $settings->add(new \tool_uploadusersplus\local\admin_setting_enrolmentdays(
        'tool_uploadusersplus/enrolmentrestrictiondays',
        get_string('setting_enrolmentrestrictiondays', 'tool_uploadusersplus'),
        get_string('setting_enrolmentrestrictiondays_desc', 'tool_uploadusersplus'),
        30,
        PARAM_INT
    ));
    $settings->hide_if(
        'tool_uploadusersplus/enrolmentrestrictiondays',
        'tool_uploadusersplus/enrolmentrestrictions',
        'notchecked'
    );

    $settings->add(new \tool_uploadusersplus\local\admin_setting_disabled_checkbox(
        'tool_uploadusersplus/displayuseridindetailedreports',
        get_string('setting_displayuseridindetailedreports', 'tool_uploadusersplus'),
        get_string('setting_displayuseridindetailedreports_desc', 'tool_uploadusersplus'),
        0
    ));

    $settings->add(new \tool_uploadusersplus\local\admin_setting_disabled_checkbox(
        'tool_uploadusersplus/hidefirstnamelastnameindetailedreports',
        get_string('setting_hidefirstnamelastnameindetailedreports', 'tool_uploadusersplus'),
        get_string('setting_hidefirstnamelastnameindetailedreports_desc', 'tool_uploadusersplus'),
        0
    ));

    $settings->add(new \tool_uploadusersplus\local\admin_setting_disabled_checkbox(
        'tool_uploadusersplus/siteadminreportsonly',
        get_string('setting_siteadminreportsonly', 'tool_uploadusersplus'),
        get_string('setting_siteadminreportsonly_desc', 'tool_uploadusersplus'),
        0
    ));

    $settings->add(new \tool_uploadusersplus\local\admin_setting_disabled_checkbox(
        'tool_uploadusersplus/enableuploadsupdatesreport',
        get_string('setting_enableuploadsupdatesreport', 'tool_uploadusersplus'),
        get_string('setting_enableuploadsupdatesreport_desc', 'tool_uploadusersplus'),
        0
    ));

    $settings->add(new admin_setting_description(
        'tool_uploadusersplus/capabilitynotice',
        '',
        \html_writer::tag('strong', get_string('settingscapabilitynotice', 'tool_uploadusersplus'))
    ));

    $settings->add(new admin_setting_heading(
        'tool_uploadusersplus/proversionheading',
        get_string('proversionheading', 'tool_uploadusersplus'),
        \tool_uploadusersplus\local\helper::get_pro_purchase_link(
            get_string('purchaseproversion', 'tool_uploadusersplus')
        )
    ));
}
