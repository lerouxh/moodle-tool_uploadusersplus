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
 * English strings for tool_uploadusersplus.
 *
 * @package    tool_uploadusersplus
 * @copyright  2026 eLearn Solutions
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$string['pluginname'] = 'Upload users PLUS';
$string['privacy:metadata'] = 'The Upload users PLUS plugin stores uploader-linked summaries for successful committed runs.';
$string['logoalt'] = 'Upload users PLUS logo';
$string['logomissingnotice'] = 'Logo file not found. Add the image to admin/tool/uploadusersplus/pix/uup_100x100.png to display it on this page.';

$string['createuploadtemplate'] = 'Create an upload template';
$string['options'] = 'Options';
$string['options_help'] = 'Choose which fields should be included when generating your upload template.';
$string['settingsrestrictions'] = 'Restrictions (Pro version)';
$string['settingscapabilitynotice'] = 'Users with the capability moodle/site:uploadusers can upload users with this plugin';
$string['setting_hidetemplategeneration'] = 'Hide option to Generate an upload template';
$string['setting_hidetemplategeneration_desc'] = 'Hide the entire Create an upload template section on the Upload users PLUS page.';
$string['templatevisibletositeadminsonly'] = 'Visible to Site Administrators only';
$string['setting_hidecustomprofilefieldsoption'] = 'Hide option to Include custom profile fields';
$string['setting_hidecustomprofilefieldsoption_desc'] = 'Hide the Include custom profile fields option from the Create an upload template section.';
$string['setting_hideoptionalfieldsoption'] = 'Hide option to Include all Moodle optional fields';
$string['setting_hideoptionalfieldsoption_desc'] = 'Hide the Include all Moodle optional fields option from the Create an upload template section.';
$string['setting_includepasswordfield'] = 'Include password field';
$string['setting_includepasswordfield_desc'] = 'Include the password column in generated upload templates. This affects template generation only and does not remove support for password columns in uploaded CSV files.';
$string['setting_ignorerequiredcustomprofilefieldsmissing'] = 'Ignore required custom profile fields if missing from upload file';
$string['setting_ignorerequiredcustomprofilefieldsmissing_desc'] = 'Allow uploads to continue when required custom profile field columns are missing from the upload file. This applies only to missing required custom profile field columns, not to invalid values that are supplied.';
$string['setting_ignorerequiredcustomprofilefieldsmissing_note'] = 'Ignoring required custom profile fields will trigger the user to complete these at first login';
$string['setting_unknownprofilefielddatatypes'] = 'Unknown custom profile field datatypes';
$string['setting_unknownprofilefielddatatypes_desc'] = 'Choose whether uploads should be blocked for custom profile field datatypes that are not explicitly supported by this plugin, or whether raw values should be accepted without datatype-specific validation.';
$string['setting_unknownprofilefielddatatypes_block'] = 'Block upload';
$string['setting_unknownprofilefielddatatypes_allowraw'] = 'Allow raw value and skip datatype-specific validation';
$string['setting_customprofilefieldstoinclude'] = 'Custom profile fields to include';
$string['setting_customprofilefieldstoinclude_desc'] = 'Choose which custom profile fields should be included in generated upload templates when Include custom profile fields is selected on the upload page.';
$string['setting_customprofilefieldstoinclude_empty'] = 'No custom profile fields are currently defined on this site.';
$string['setting_hidecourseenrolmentsoption'] = 'Hide option for Course enrolments';
$string['setting_hidecourseenrolmentsoption_desc'] = 'Hide the Course enrolments option and number-of-courses input from the Create an upload template section.';
$string['setting_hidecohortenrolmentsoption'] = 'Hide option for Cohort enrolments';
$string['setting_hidecohortenrolmentsoption_desc'] = 'Hide the Cohort enrolments option and number-of-cohorts input from the Create an upload template section.';
$string['setting_enrolmentrestrictions'] = 'Enrolment restrictions';
$string['setting_enrolmentrestrictions_desc'] = 'Block uploads that try to enrol users into courses more than the configured number of days after the course start date.';
$string['setting_enrolmentrestrictiondays'] = 'Users cannot be enrolled _ days after course start date';
$string['setting_enrolmentrestrictiondays_desc'] = 'Enter a whole number from 1 to 999. This setting is enforced only when Enrolment restrictions is enabled.';
$string['setting_displayuseridindetailedreports'] = 'Display Moodle user ID instead of username in detailed reports';
$string['setting_displayuseridindetailedreports_desc'] = 'Show the Moodle user ID in detailed onscreen and emailed results instead of the username. Where no Moodle user ID is available yet, the detailed report shows -.';
$string['setting_hidefirstnamelastnameindetailedreports'] = 'Do not display lastname and firstname in detailed results';
$string['setting_hidefirstnamelastnameindetailedreports_desc'] = 'Hide firstname and lastname in detailed onscreen and emailed results. This does not affect whether username or Moodle user ID is shown.';
$string['setting_siteadminreportsonly'] = 'Allow only site administrators to view the uploads/updates report';
$string['setting_siteadminreportsonly_desc'] = 'Restrict the Successful uploads/updates report page to users with moodle/site:config. Other permitted upload users can still use the main upload page.';
$string['setting_enableuploadsupdatesreport'] = 'Enable uploads/updates report';
$string['setting_enableuploadsupdatesreport_desc'] = 'Enable the Successful uploads/updates report page and its report button. This setting is available in the Pro version.';
$string['proversionheading'] = 'PRO version';
$string['purchaseproversion'] = 'Purchase the Pro version of this plugin';
$string['includecustomprofilefields'] = 'Include custom profile fields';
$string['includecustomprofilefields_help'] = 'Add the custom profile fields selected in the plugin settings to the generated template using profile_field_{shortname} headers.';
$string['includeoptionalfields'] = 'Include all Moodle optional fields';
$string['includeoptionalfields_help'] = 'Add these optional Moodle upload fields to the generated template: institution, department, city, country, lang, auth, timezone, idnumber, icq, phone1, phone2, address, url, description, mailformat, maildisplay, maildigest, htmleditor, autosubscribe, interests, theme.';

$string['enrolmentsection'] = 'Enrollment';
$string['enrolmentsection_help'] = 'Choose whether the generated template should include course enrolment columns, cohort enrolment columns, or both.';
$string['courseenrolments'] = 'Course enrolments';
$string['courseenrolments_help'] = 'Include repeated course and optional group columns in the generated template.';
$string['numberofcourses'] = 'Number of courses';
$string['numberofcourses_help'] = 'Enter a whole number from 1 to 99. The generated template will include course1, group1 through courseN, groupN.';
$string['cohortenrolments'] = 'Cohort enrolments';
$string['cohortenrolments_help'] = 'Include repeated cohort columns in the generated template.';
$string['numberofcohorts'] = 'Number of cohorts';
$string['numberofcohorts_help'] = 'Enter a whole number from 1 to 10. The generated template will include cohort1 through cohortN.';
$string['generatetemplate'] = 'Generate the upload template';

$string['uploaduserssection'] = 'Upload users';
$string['uploaduserssection_help'] = 'Upload a CSV file using the generated template or another file with the supported headers.';
$string['uploadinstructions'] = 'Add the users to your template and upload the file below.';
$string['userfile'] = 'CSV file';
$string['userfile_help'] = 'Upload a CSV file. Comma-separated and semicolon-separated files are supported.';

$string['uploadsettings'] = 'Upload settings';
$string['uploadsettings_help'] = 'Choose how existing users should be treated and whether to perform a dry run before writing anything to the database.';
$string['uploadtype'] = 'Upload type';
$string['uploadtype_help'] = 'Choose whether to add new users only, add new users and update existing users, or update existing users only.';
$string['uploadtype_addnew'] = 'Add new only, skip existing users';
$string['uploadtype_addupdate'] = 'Add new and update existing users';
$string['uploadtype_updateonly'] = 'Update existing users only';
$string['newpasswords'] = 'New passwords';
$string['newpasswords_help'] = 'For add-user modes, either create passwords automatically and send them by email, or require passwords in the CSV file.';
$string['newpasswords_create'] = 'Create password if needed and send via email';
$string['newpasswords_file'] = 'Use passwords in upload file';
$string['existingpasswords'] = 'Existing user passwords';
$string['existingpasswords_help'] = 'For update-only mode, keep existing passwords unchanged or update them using password values from the CSV file.';
$string['existingpasswords_nochanges'] = 'No changes';
$string['dryrun'] = 'Dry run (no users will be uploaded/updated)';
$string['dryrun_help'] = 'Validate the whole file and show results without creating users, updating users, or adding enrolments. During a dry run, report email delivery is not available.';
$string['reporttype'] = 'Report - Results of user upload';
$string['reporttype_help'] = 'Choose whether to show a summary only or detailed results. Detailed and emailed detailed results are available in the Pro version.';
$string['report_summary'] = 'Summary';
$string['report_detailed'] = 'Display detailed results';
$string['report_detailed_pro'] = 'Display detailed results (Pro version)';
$string['report_detailedemail'] = 'Display and email detailed results';
$string['report_detailedemail_pro'] = 'Display and email detailed results (Pro version)';
$string['report_detailedemailsubject'] = 'Upload users PLUS: Detailed upload results';
$string['emailrecipient'] = 'Email recipient';
$string['emailrecipient_help'] = 'Enter the email address that should receive the detailed report after a successful non-dry-run upload with no blocking validation errors.';
$string['uploadbutton'] = 'Upload';
$string['reportrecipient'] = 'Report recipient';

$string['error_numberofcourses'] = 'Number of courses must be an integer from 1 to 99.';
$string['error_numberofcohorts'] = 'Number of cohorts must be an integer from 1 to 10.';
$string['error_settingsenrolmentrestrictiondays'] = 'Users cannot be enrolled _ days after course start date must be an integer from 1 to 999.';
$string['error_emptycsv'] = 'The uploaded CSV file is empty.';
$string['error_missingfile'] = 'A CSV file is required.';
$string['error_duplicateheader'] = 'Duplicate header found: {$a}.';
$string['error_unexpectedheader'] = 'The following is not a valid profile field: {$a}';
$string['error_missingrequiredheader'] = 'Required header missing: {$a}.';
$string['error_malformedrow'] = 'The row does not contain the same number of columns as the header row.';
$string['error_emptyrow'] = 'The row is empty.';
$string['error_missingvalue'] = 'Missing required value for {$a}.';
$string['error_invalidusername'] = 'Invalid username.';
$string['error_duplicateusernameinfile'] = 'Duplicate username found in this upload file: {$a}.';
$string['error_invalidemailfield'] = 'Invalid email address.';
$string['error_duplicateemail'] = 'The email address is already used by another account.';
$string['error_invalidfieldvalue'] = 'Invalid value for {$a}.';
$string['error_invalidchoice'] = 'Invalid value for {$a}. Allowed values are limited to the site-supported options.';
$string['error_invalidauth'] = 'Invalid authentication method.';
$string['error_invalidlang'] = 'Invalid language code.';
$string['error_invalidtimezone'] = 'Invalid timezone.';
$string['error_invalidtheme'] = 'Invalid theme.';
$string['error_invalidcountry'] = 'Invalid country code.';
$string['error_invalidmailformat'] = 'Invalid mail format.';
$string['error_invalidmaildisplay'] = 'Invalid mail display setting.';
$string['error_invalidmaildigest'] = 'Invalid mail digest setting.';
$string['error_invalidautosubscribe'] = 'Invalid autosubscribe value.';
$string['error_invalidhtmleditor'] = 'Invalid HTML editor value.';
$string['error_invalidcheckbox'] = 'Invalid checkbox value.';
$string['error_invaliddatetime'] = 'Invalid datetime value.';
$string['error_unsupportedprofilefielddatatype'] = 'Custom profile field {$a->fieldname} uses unsupported datatype {$a->datatype}.';
$string['error_profilefieldrequired'] = 'Required custom profile field is missing: {$a}.';
$string['error_profilefieldinvalid'] = 'Invalid custom profile field value for {$a}.';
$string['error_coursemissing'] = 'Course not found: {$a}.';
$string['error_courseambiguous'] = 'Course fullname is ambiguous: {$a}.';
$string['error_groupmissing'] = 'Group not found in the selected course: {$a}.';
$string['error_groupambiguous'] = 'Group name is ambiguous in the selected course: {$a}.';
$string['error_groupwithoutcourse'] = 'A group value was provided without a matching course value.';
$string['error_cohortmissing'] = 'Cohort not found: {$a}.';
$string['error_cohortambiguous'] = 'Cohort name is ambiguous: {$a}.';
$string['error_externalcohort'] = 'The cohort {$a} is synchronised externally and cannot be modified safely.';
$string['error_manualenrolmissing'] = 'The course {$a} does not have a manual enrolment instance available for this upload.';
$string['error_templatedisabled'] = 'Template generation is disabled by the plugin settings.';
$string['error_enrolmentrestriction'] = 'Course enrolment for {$a->course} is not allowed because the course started more than {$a->days} days ago.';
$string['error_passwordrequired'] = 'A password is required for this row.';
$string['error_missingpasswordheaderrequiredmode'] = 'The selected password mode requires a password column in the uploaded file.';
$string['error_passwordpolicy'] = 'The password does not meet the site password policy.';
$string['error_runtimerollback'] = 'The import failed during writing and all changes were rolled back.';
$string['error_adminupdate'] = 'Site administrator accounts cannot be updated by this upload.';
$string['error_deleteduser'] = 'Deleted users cannot be updated by this upload.';

$string['dryrunresults'] = 'Dry run results:';
$string['dryrunwarning'] = 'Dry-run. No new users were uploaded and no users were updated.';
$string['uploadsuccessnotice'] = 'Users successfully uploaded/updated';
$string['uploadresultsheading'] = 'Upload results:';
$string['globalissues'] = 'Global issues';
$string['rowvalidationissues'] = 'Row validation issues';
$string['rowvalidationissue'] = 'Line {$a->line} - {$a->field}: {$a->message}';
$string['blockingmessage'] = 'No new users were uploaded and no users were updated.';
$string['detailedresults'] = 'Detailed results';
$string['details'] = 'Details';
$string['csvline'] = 'CSV line';
$string['moodleuserid'] = 'Moodle user ID';
$string['detailsemailsent'] = 'Detailed results were emailed successfully.';
$string['detailsemailfailed'] = 'The detailed results email could not be sent.';
$string['missingrequiredcustomprofilefieldsnotice'] = 'Required custom profile fields missing and will be skipped.';
$string['warning_unknownprofiledatatypevalidationskipped'] = 'Datatype-specific validation was skipped for custom profile field {$a->fieldname} ({$a->datatype}).';
$string['warning_unknownprofiledatatypevalidationskipped_prefix'] = 'Datatype-specific validation was skipped for custom profile field';

$string['summary_rowsread'] = 'Rows in upload file read: {$a}';
$string['summary_validrows'] = 'Valid rows: {$a}';
$string['summary_invalidrows'] = 'Invalid rows: {$a}';
$string['summary_newusersdetected'] = 'No. of new users detected: {$a}';
$string['summary_existingusersdetected'] = 'No. of existing users detected: {$a}';
$string['processinglimitnotice'] = 'Processing only the first {$a->limit} rows. Please purchase the {$a->prolink}.';
$string['proversion'] = 'Pro version';

$string['status_valid'] = 'Valid';
$string['status_invalid'] = 'Invalid';
$string['status_warning'] = 'Warning';
$string['status_success'] = 'Success';
$string['status_skipped'] = 'Skipped';

$string['action_create'] = 'Create user';
$string['action_update'] = 'Update user';
$string['action_skipexisting'] = 'Skip existing user';
$string['action_skipmissing'] = 'Skip missing user';
$string['action_none'] = 'No action';
$string['action_enrol'] = 'Apply enrolments';

$string['warning_skipexisting'] = 'The user already exists and will be skipped in this upload mode.';
$string['warning_skipmissing'] = 'The user does not exist and will be skipped in this upload mode.';
$string['warning_nochanges'] = 'No field changes were detected for this row.';

$string['message_usercreated'] = 'User will be created.';
$string['message_userupdated'] = 'User will be updated.';
$string['message_usercreateddone'] = 'User created.';
$string['message_userupdateddone'] = 'User updated.';
$string['message_useruptodate'] = 'User is already up to date.';
$string['message_courseenrolment'] = 'Course enrolment prepared for {$a}.';
$string['message_groupmembership'] = 'Group membership prepared for {$a}.';
$string['message_cohortmembership'] = 'Cohort membership prepared for {$a}.';
$string['message_courseenrolmentdone'] = 'Course enrolment applied for {$a}.';
$string['message_groupmembershipdone'] = 'Group membership applied for {$a}.';
$string['message_cohortmembershipdone'] = 'Cohort membership applied for {$a}.';
$string['message_passwordemailqueued'] = 'A password email will be sent for this new account.';

$string['email_rowheading'] = 'Row {$a}';
$string['email_rowstatus'] = 'Status: {$a}';
$string['email_rowaction'] = 'Action: {$a}';
$string['email_details_heading'] = 'Detailed upload results';
$string['reportsbutton'] = 'Successful uploads/updates report (Pro version)';
$string['backtouploadusersplus'] = 'Back to Upload users PLUS';
$string['reportsheading'] = 'Successful uploads & Enrolments report';
$string['reports_uploaddate'] = 'Upload date';
$string['reports_uploadedby'] = 'Uploaded by';
$string['reports_usersuploaded'] = 'No. of users uploaded';
$string['reports_usersupdated'] = 'No. of users updated';
$string['reports_usersenrolled'] = 'No. of users enrolled';
$string['reports_enrolmentscreated'] = 'No. of enrolments created';
$string['reports_enrolledcourses'] = 'Enrolled courses';
$string['noreportsyet'] = 'No successful upload runs have been logged yet.';
$string['reportloggingfailed'] = 'The successful run could not be added to the Upload users PLUS reports log.';
$string['reportunavailable'] = 'Report unavailable.';
$string['reportsavailablepro'] = 'This report is available in the {$a}.';

$string['privacy:metadata:run'] = 'Successful committed Upload users PLUS runs are stored in plugin-owned tables.';
$string['privacy:metadata:run:userid'] = 'The user ID of the uploader who ran the successful upload.';
$string['privacy:metadata:run:filename'] = 'The uploaded CSV filename for the successful run.';
$string['privacy:metadata:run:uploadtype'] = 'The selected upload mode used for the successful run.';
$string['privacy:metadata:run:csvseparator'] = 'The detected CSV separator used for the successful run.';
$string['privacy:metadata:run:usersuploaded'] = 'The number of newly created users in the successful run.';
$string['privacy:metadata:run:usersupdated'] = 'The number of existing-user update rows completed in the successful run.';
$string['privacy:metadata:run:usersenrolleddistinct'] = 'The number of distinct users who received at least one new direct course enrolment in the successful run.';
$string['privacy:metadata:run:enrolmentscreated'] = 'The number of new direct course enrolment actions created in the successful run.';
$string['privacy:metadata:run:coursescount'] = 'The number of distinct courses affected by new direct course enrolments in the successful run.';
$string['privacy:metadata:run:cohortscount'] = 'The number of distinct cohorts processed successfully in the successful run.';
$string['privacy:metadata:run:status'] = 'The stored outcome status for the run.';
$string['privacy:metadata:run:timecreated'] = 'The time when the successful run was logged.';
$string['privacy:metadata:run:timemodified'] = 'The time when the successful run record was last updated.';
$string['privacy:metadata:runcourse'] = 'Per-course enrolment summaries are stored for successful committed runs.';
$string['privacy:metadata:runcourse:runid'] = 'The parent successful run record.';
$string['privacy:metadata:runcourse:courseid'] = 'The course affected by the successful run.';
$string['privacy:metadata:runcourse:courseshortname'] = 'The course shortname stored for reporting display.';
$string['privacy:metadata:runcourse:coursefullname'] = 'The course fullname stored for reporting display.';
$string['privacy:metadata:runcourse:usersenrolled'] = 'The number of users newly enrolled in the course during the successful run.';
$string['privacy:metadata:runcourse:timecreated'] = 'The time when the per-course summary row was created.';
