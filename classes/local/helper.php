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
 * Helper methods for tool_uploadusersplus.
 *
 * @package    tool_uploadusersplus
 * @copyright  2026 eLearn Solutions
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace tool_uploadusersplus\local;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/user/profile/lib.php');

/**
 * Shared helper class.
 *
 * @package    tool_uploadusersplus
 * @copyright  2026 eLearn Solutions
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class helper {
    /** @var int */
    public const UPLOADTYPE_ADDNEW = 1;
    /** @var int */
    public const UPLOADTYPE_ADDUPDATE = 2;
    /** @var int */
    public const UPLOADTYPE_UPDATEONLY = 3;

    /** @var int */
    public const NEWPASSWORDS_CREATE = 1;
    /** @var int */
    public const NEWPASSWORDS_FILE = 2;

    /** @var int */
    public const EXISTINGPASSWORDS_NOCHANGES = 1;
    /** @var int */
    public const EXISTINGPASSWORDS_UPDATE = 2;

    /** @var int */
    public const REPORT_SUMMARY = 1;
    /** @var int */
    public const REPORT_DETAILED = 2;
    /** @var int */
    public const REPORT_EMAIL = 3;
    /** @var int */
    public const UNKNOWN_PROFILE_DATATYPE_BLOCK = 0;
    /** @var int */
    public const UNKNOWN_PROFILE_DATATYPE_ALLOWRAW = 1;
    /** @var string */
    public const RUN_STATUS_SUCCESS = 'success';
    /** @var bool */
    public const FREE_VERSION = true;
    /** @var int */
    public const FREE_ROW_PROCESSING_LIMIT = 50;
    /** @var string */
    public const PRO_PURCHASE_URL = 'https://shop.elearnsolutions.co.za/shop/upload-users-plug-pro-activation-key-for-1-year/';

    /**
     * Determine whether this build is the free version.
     *
     * @return bool
     */
    public static function is_free_version(): bool {
        return self::FREE_VERSION;
    }

    /**
     * Get the free-version row processing limit.
     *
     * @return int
     */
    public static function get_free_row_processing_limit(): int {
        return self::FREE_ROW_PROCESSING_LIMIT;
    }

    /**
     * Get the Pro version purchase URL.
     *
     * @return \moodle_url
     */
    public static function get_pro_purchase_url(): \moodle_url {
        return new \moodle_url(self::PRO_PURCHASE_URL);
    }

    /**
     * Build a purchase link for the Pro version.
     *
     * @param string $text
     * @return string
     */
    public static function get_pro_purchase_link(string $text): string {
        return \html_writer::link(self::get_pro_purchase_url(), $text, [
            'target' => '_blank',
            'rel' => 'noopener noreferrer',
        ]);
    }

    /**
     * Get the base headers always present in generated templates.
     *
     * @return array
     */
    public static function get_supported_standard_headers(): array {
        return ['username', 'password', 'firstname', 'lastname', 'email', 'deleted', 'suspended'];
    }

    /**
     * Get the base headers used when generating templates.
     *
     * @return array
     */
    public static function get_template_default_headers(): array {
        $headers = ['username'];

        if (self::get_admin_settings()->includepasswordfield) {
            $headers[] = 'password';
        }

        $headers[] = 'firstname';
        $headers[] = 'lastname';
        $headers[] = 'email';

        return $headers;
    }

    /**
     * Get optional user fields supported by this plugin.
     *
     * @return array
     */
    public static function get_optional_user_fields(): array {
        return [
            'institution',
            'department',
            'city',
            'country',
            'lang',
            'auth',
            'timezone',
            'idnumber',
            'icq',
            'phone1',
            'phone2',
            'address',
            'url',
            'description',
            'mailformat',
            'maildisplay',
            'maildigest',
            'htmleditor',
            'autosubscribe',
            'interests',
            'theme',
        ];
    }

    /**
     * Fields stored as user preferences rather than in the user table.
     *
     * @return array
     */
    public static function get_user_preference_fields(): array {
        return ['htmleditor'];
    }

    /**
     * Supported custom profile datatypes which can be validated safely here.
     *
     * @return array
     */
    public static function get_supported_profile_datatypes(): array {
        return ['text', 'textarea', 'menu', 'checkbox', 'datetime', 'textonce', 'conditional'];
    }

    /**
     * Get unknown custom profile datatype handling options.
     *
     * @return array
     */
    public static function get_unknown_profile_datatype_options(): array {
        return [
            self::UNKNOWN_PROFILE_DATATYPE_BLOCK => get_string('setting_unknownprofilefielddatatypes_block', 'tool_uploadusersplus'),
            self::UNKNOWN_PROFILE_DATATYPE_ALLOWRAW => get_string(
                'setting_unknownprofilefielddatatypes_allowraw',
                'tool_uploadusersplus'
            ),
        ];
    }

    /**
     * Get upload type options.
     *
     * @return array
     */
    public static function get_upload_type_options(): array {
        return [
            self::UPLOADTYPE_ADDNEW => get_string('uploadtype_addnew', 'tool_uploadusersplus'),
            self::UPLOADTYPE_ADDUPDATE => get_string('uploadtype_addupdate', 'tool_uploadusersplus'),
            self::UPLOADTYPE_UPDATEONLY => get_string('uploadtype_updateonly', 'tool_uploadusersplus'),
        ];
    }

    /**
     * Get new-user password handling options.
     *
     * @return array
     */
    public static function get_new_password_options(): array {
        return [
            self::NEWPASSWORDS_CREATE => get_string('newpasswords_create', 'tool_uploadusersplus'),
            self::NEWPASSWORDS_FILE => get_string('newpasswords_file', 'tool_uploadusersplus'),
        ];
    }

    /**
     * Get existing-user password handling options.
     *
     * @return array
     */
    public static function get_existing_password_options(): array {
        return [
            self::EXISTINGPASSWORDS_NOCHANGES => get_string('existingpasswords_nochanges', 'tool_uploadusersplus'),
            self::EXISTINGPASSWORDS_UPDATE => get_string('update'),
        ];
    }

    /**
     * Get reporting options for the current mode.
     *
     * @param bool $dryrun
     * @return array
     */
    public static function get_report_options(bool $dryrun): array {
        if (self::is_free_version()) {
            return [
                self::REPORT_SUMMARY => get_string('report_summary', 'tool_uploadusersplus'),
                self::REPORT_DETAILED => get_string('report_detailed_pro', 'tool_uploadusersplus'),
                self::REPORT_EMAIL => get_string('report_detailedemail_pro', 'tool_uploadusersplus'),
            ];
        }

        $options = [
            self::REPORT_SUMMARY => get_string('report_summary', 'tool_uploadusersplus'),
            self::REPORT_DETAILED => get_string('report_detailed', 'tool_uploadusersplus'),
        ];

        if (!$dryrun) {
            $options[self::REPORT_EMAIL] = get_string('report_detailedemail', 'tool_uploadusersplus');
        }

        return $options;
    }

    /**
     * Get report option values which are visible but disabled in the current build.
     *
     * @return array
     */
    public static function get_disabled_report_options(): array {
        if (!self::is_free_version()) {
            return [];
        }

        return [
            self::REPORT_DETAILED,
            self::REPORT_EMAIL,
        ];
    }

    /**
     * Get the stable upload type key stored in reporting tables.
     *
     * @param int $uploadtype
     * @return string
     */
    public static function get_upload_type_key(int $uploadtype): string {
        $map = [
            self::UPLOADTYPE_ADDNEW => 'addnew',
            self::UPLOADTYPE_ADDUPDATE => 'addupdate',
            self::UPLOADTYPE_UPDATEONLY => 'updateonly',
        ];

        return $map[$uploadtype] ?? 'unknown';
    }

    /**
     * Get default form values.
     *
     * @return \stdClass
     */
    public static function get_default_form_data(): \stdClass {
        $data = new \stdClass();
        $data->includecustomprofilefields = 0;
        $data->includeoptionalfields = 0;
        $data->courseenrolments = 0;
        $data->numberofcourses = 1;
        $data->includerolefields = 0;
        $data->includeenroltimestart = 0;
        $data->includeenrolperiod = 0;
        $data->includeenrolstatus = 0;
        $data->cohortenrolments = 0;
        $data->numberofcohorts = 1;
        $data->includedeletedfield = 0;
        $data->includesuspendedfield = 0;
        $data->uploadtype = self::UPLOADTYPE_ADDNEW;
        $data->newpasswords = self::NEWPASSWORDS_CREATE;
        $data->existingpasswords = self::EXISTINGPASSWORDS_NOCHANGES;
        $data->dryrun = 1;
        $data->reporttype = self::REPORT_SUMMARY;
        $data->emailrecipient = '';

        return self::normalise_form_data($data);
    }

    /**
     * Get plugin admin settings with defaults applied.
     *
     * @return \stdClass
     */
    public static function get_admin_settings(): \stdClass {
        $config = get_config('tool_uploadusersplus');
        $settings = new \stdClass();
        $settings->hidetemplategeneration = !empty($config->hidetemplategeneration);
        $settings->hidecustomprofilefieldsoption = !empty($config->hidecustomprofilefieldsoption);
        $settings->hideoptionalfieldsoption = !empty($config->hideoptionalfieldsoption);
        $settings->hidecourseenrolmentsoption = !empty($config->hidecourseenrolmentsoption);
        $settings->hideoptionroleenrolments = !empty($config->hideoptionroleenrolments);
        $settings->hideoptionenroltimestart = !empty($config->hideoptionenroltimestart);
        $settings->hideoptionenrolperiod = !empty($config->hideoptionenrolperiod);
        $settings->hideoptionenrolstatus = !empty($config->hideoptionenrolstatus);
        $settings->hidecohortenrolmentsoption = !empty($config->hidecohortenrolmentsoption);
        $settings->hideoptiondeletedfield = !empty($config->hideoptiondeletedfield);
        $settings->hideoptionsuspendedfield = !empty($config->hideoptionsuspendedfield);
        $settings->enrolmentrestrictions = !empty($config->enrolmentrestrictions);
        $settings->siteadminreportsonly = !empty($config->siteadminreportsonly);
        $settings->displayuseridindetailedreports = !empty($config->displayuseridindetailedreports);
        $settings->hidefirstnamelastnameindetailedreports = !empty($config->hidefirstnamelastnameindetailedreports);
        $settings->enableuploadsupdatesreport = !empty($config->enableuploadsupdatesreport);
        $settings->ignorerequiredcustomprofilefieldsmissing = !empty($config->ignorerequiredcustomprofilefieldsmissing);
        $unknownprofilefielddatatypes = (int)($config->unknownprofilefielddatatypes ?? self::UNKNOWN_PROFILE_DATATYPE_BLOCK);
        $settings->unknownprofilefielddatatypes = array_key_exists(
            $unknownprofilefielddatatypes,
            self::get_unknown_profile_datatype_options()
        ) ? $unknownprofilefielddatatypes : self::UNKNOWN_PROFILE_DATATYPE_BLOCK;
        $settings->includepasswordfield = !property_exists($config, 'includepasswordfield')
            ? true
            : !empty($config->includepasswordfield);
        $settings->customprofilefieldstoinclude = self::get_template_custom_profile_field_shortnames();

        $days = $config->enrolmentrestrictiondays ?? 30;
        if (!self::is_valid_int_in_range($days, 1, 999)) {
            $days = 30;
        }
        $settings->enrolmentrestrictiondays = (int)$days;

        if (self::is_free_version()) {
            $settings->enrolmentrestrictions = false;
            $settings->siteadminreportsonly = false;
            $settings->displayuseridindetailedreports = false;
            $settings->hidefirstnamelastnameindetailedreports = false;
            $settings->enableuploadsupdatesreport = false;
            $settings->enrolmentrestrictiondays = 30;
        }

        return $settings;
    }

    /**
     * Determine whether template generation is enabled.
     *
     * @return bool
     */
    public static function template_generation_enabled(): bool {
        $settings = self::get_admin_settings();

        return empty($settings->hidetemplategeneration) || self::current_user_is_site_administrator();
    }

    /**
     * Determine whether the current user can see the template generation section.
     *
     * @return bool
     */
    public static function current_user_can_see_template_generation(): bool {
        return self::template_generation_enabled();
    }

    /**
     * Determine whether the current user can see the site-admin-only template notice.
     *
     * @return bool
     */
    public static function current_user_sees_template_admin_notice(): bool {
        $settings = self::get_admin_settings();

        return !empty($settings->hidetemplategeneration) && self::current_user_is_site_administrator();
    }

    /**
     * Determine whether the reports page is restricted to site administrators.
     *
     * @return bool
     */
    public static function reports_are_siteadmin_only(): bool {
        return !empty(self::get_admin_settings()->siteadminreportsonly);
    }

    /**
     * Determine whether the current user can access the reports page.
     *
     * @return bool
     */
    public static function current_user_can_access_reports(): bool {
        if (self::is_free_version()) {
            return false;
        }

        if (!self::reports_are_siteadmin_only()) {
            return true;
        }

        return self::current_user_is_site_administrator();
    }

    /**
     * Determine whether the current user has site-administrator configuration access.
     *
     * @return bool
     */
    public static function current_user_is_site_administrator(): bool {
        return has_capability('moodle/site:config', \context_system::instance());
    }

    /**
     * Normalise a form data object against config and allowed values.
     *
     * @param \stdClass $data
     * @return \stdClass
     */
    public static function normalise_form_data(\stdClass $data): \stdClass {
        $normalised = clone($data);
        $settings = self::get_admin_settings();

        $normalised->includecustomprofilefields = $settings->hidecustomprofilefieldsoption
            ? 0
            : (int)!empty($normalised->includecustomprofilefields);
        $normalised->includeoptionalfields = $settings->hideoptionalfieldsoption
            ? 0
            : (int)!empty($normalised->includeoptionalfields);
        $normalised->courseenrolments = $settings->hidecourseenrolmentsoption
            ? 0
            : (int)!empty($normalised->courseenrolments);
        $normalised->includerolefields = $settings->hideoptionroleenrolments
            ? 0
            : (int)!empty($normalised->includerolefields);
        $normalised->includeenroltimestart = $settings->hideoptionenroltimestart
            ? 0
            : (int)!empty($normalised->includeenroltimestart);
        $normalised->includeenrolperiod = $settings->hideoptionenrolperiod
            ? 0
            : (int)!empty($normalised->includeenrolperiod);
        $normalised->includeenrolstatus = $settings->hideoptionenrolstatus
            ? 0
            : (int)!empty($normalised->includeenrolstatus);
        $normalised->cohortenrolments = $settings->hidecohortenrolmentsoption
            ? 0
            : (int)!empty($normalised->cohortenrolments);
        $normalised->includedeletedfield = $settings->hideoptiondeletedfield
            ? 0
            : (int)!empty($normalised->includedeletedfield);
        $normalised->includesuspendedfield = $settings->hideoptionsuspendedfield
            ? 0
            : (int)!empty($normalised->includesuspendedfield);

        $normalised->numberofcourses = self::is_valid_int_in_range($normalised->numberofcourses ?? '', 1, 99)
            ? (int)$normalised->numberofcourses
            : 1;
        if (empty($normalised->courseenrolments)) {
            $normalised->numberofcourses = 1;
            $normalised->includerolefields = 0;
            $normalised->includeenroltimestart = 0;
            $normalised->includeenrolperiod = 0;
            $normalised->includeenrolstatus = 0;
        }

        $normalised->numberofcohorts = self::is_valid_int_in_range($normalised->numberofcohorts ?? '', 1, 10)
            ? (int)$normalised->numberofcohorts
            : 1;
        if (empty($normalised->cohortenrolments)) {
            $normalised->numberofcohorts = 1;
        }

        $normalised->uploadtype = array_key_exists((int)($normalised->uploadtype ?? 0), self::get_upload_type_options())
            ? (int)$normalised->uploadtype
            : self::UPLOADTYPE_ADDNEW;
        $normalised->newpasswords = array_key_exists((int)($normalised->newpasswords ?? 0), self::get_new_password_options())
            ? (int)$normalised->newpasswords
            : self::NEWPASSWORDS_CREATE;
        $normalised->existingpasswords = array_key_exists(
            (int)($normalised->existingpasswords ?? 0),
            self::get_existing_password_options()
        ) ? (int)$normalised->existingpasswords : self::EXISTINGPASSWORDS_NOCHANGES;

        $normalised->dryrun = (int)!empty($normalised->dryrun);
        $normalised->emailrecipient = trim((string)($normalised->emailrecipient ?? ''));
        if (self::is_free_version()) {
            $normalised->reporttype = self::REPORT_SUMMARY;
            $normalised->emailrecipient = '';
            return $normalised;
        }

        $normalised->reporttype = self::normalise_report_type(
            (int)($normalised->reporttype ?? self::REPORT_SUMMARY),
            !empty($normalised->dryrun)
        );
        if (!empty($normalised->dryrun) || (int)$normalised->reporttype !== self::REPORT_EMAIL) {
            $normalised->emailrecipient = '';
        }

        return $normalised;
    }

    /**
     * Normalise an array of form data values.
     *
     * @param array $data
     * @return array
     */
    public static function normalise_form_data_array(array $data): array {
        return (array)self::normalise_form_data((object)$data);
    }

    /**
     * Normalise a report type for the current mode.
     *
     * @param int $reporttype
     * @param bool $dryrun
     * @return int
     */
    public static function normalise_report_type(int $reporttype, bool $dryrun): int {
        if (self::is_free_version()) {
            return self::REPORT_SUMMARY;
        }

        $allowed = array_keys(self::get_report_options($dryrun));
        if (!in_array($reporttype, $allowed, true)) {
            return self::REPORT_SUMMARY;
        }

        return $reporttype;
    }

    /**
     * Get all custom profile field objects indexed by header name.
     *
     * @return array
     */
    public static function get_custom_profile_fields(?array $selectedshortnames = null): array {
        $fields = [];
        $selectedlookup = null;
        if ($selectedshortnames !== null) {
            $selectedlookup = array_fill_keys($selectedshortnames, true);
        }

        foreach (profile_get_user_fields_with_data(0) as $fieldobject) {
            $shortname = $fieldobject->get_shortname();
            if ($selectedlookup !== null && !isset($selectedlookup[$shortname])) {
                continue;
            }

            $fields['profile_field_' . $shortname] = $fieldobject;
        }
        return $fields;
    }

    /**
     * Get custom profile field records suitable for admin settings.
     *
     * @return array
     */
    public static function get_custom_profile_field_records(): array {
        global $DB;

        return $DB->get_records('user_info_field', null, 'sortorder ASC, id ASC', 'id, shortname, name');
    }

    /**
     * Get admin-setting choices for custom profile field selection.
     *
     * @return array
     */
    public static function get_custom_profile_field_setting_choices(): array {
        $choices = [];

        foreach (self::get_custom_profile_field_records() as $field) {
            if ($field->shortname === '') {
                continue;
            }

            $label = trim((string)$field->name);
            if ($label === '') {
                $label = $field->shortname;
            }

            $choices[$field->shortname] = $field->shortname . ' - ' . $label;
        }

        return $choices;
    }

    /**
     * Get the configured custom profile field shortnames for template generation.
     *
     * @return array
     */
    public static function get_template_custom_profile_field_shortnames(): array {
        $choices = self::get_custom_profile_field_setting_choices();
        if (empty($choices)) {
            return [];
        }

        $configured = get_config('tool_uploadusersplus', 'customprofilefieldstoinclude');
        if ($configured === false || $configured === null) {
            return array_keys($choices);
        }

        if ($configured === '') {
            return [];
        }

        $selected = array_map('trim', explode(',', (string)$configured));
        $selectedlookup = array_fill_keys(array_filter($selected, static function(string $shortname): bool {
            return $shortname !== '';
        }), true);

        $filtered = [];
        foreach (array_keys($choices) as $shortname) {
            if (isset($selectedlookup[$shortname])) {
                $filtered[] = $shortname;
            }
        }

        return $filtered;
    }

    /**
     * Get the custom profile fields that should be included in generated templates.
     *
     * @return array
     */
    public static function get_template_custom_profile_fields(): array {
        return self::get_custom_profile_fields(self::get_template_custom_profile_field_shortnames());
    }

    /**
     * Determine whether the selected upload mode requires a password column in the uploaded CSV.
     *
     * @param \stdClass $formdata
     * @return bool
     */
    public static function upload_requires_password_column(\stdClass $formdata): bool {
        if ((int)$formdata->uploadtype !== self::UPLOADTYPE_UPDATEONLY
                && (int)$formdata->newpasswords === self::NEWPASSWORDS_FILE) {
            return true;
        }

        if ((int)$formdata->uploadtype === self::UPLOADTYPE_UPDATEONLY
                && (int)$formdata->existingpasswords === self::EXISTINGPASSWORDS_UPDATE) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether a header is a course column.
     *
     * @param string $header
     * @return bool
     */
    public static function is_course_header(string $header): bool {
        return (bool)preg_match('/^course([1-9][0-9]?)$/', $header);
    }

    /**
     * Determine whether a header is a group column.
     *
     * @param string $header
     * @return bool
     */
    public static function is_group_header(string $header): bool {
        return (bool)preg_match('/^group([1-9][0-9]?)$/', $header);
    }

    /**
     * Determine whether a header is a course role column.
     *
     * @param string $header
     * @return bool
     */
    public static function is_role_header(string $header): bool {
        return (bool)preg_match('/^role([1-9][0-9]?)$/', $header);
    }

    /**
     * Determine whether a header is an enrolment start date column.
     *
     * @param string $header
     * @return bool
     */
    public static function is_enroltimestart_header(string $header): bool {
        return (bool)preg_match('/^enroltimestart([1-9][0-9]?)$/', $header);
    }

    /**
     * Determine whether a header is an enrolment period column.
     *
     * @param string $header
     * @return bool
     */
    public static function is_enrolperiod_header(string $header): bool {
        return (bool)preg_match('/^enrolperiod([1-9][0-9]?)$/', $header);
    }

    /**
     * Determine whether a header is an enrolment status column.
     *
     * @param string $header
     * @return bool
     */
    public static function is_enrolstatus_header(string $header): bool {
        return (bool)preg_match('/^enrolstatus([1-9][0-9]?)$/', $header);
    }

    /**
     * Determine whether a header is a course enrolment detail column.
     *
     * @param string $header
     * @return bool
     */
    public static function is_course_enrolment_detail_header(string $header): bool {
        return self::is_group_header($header)
            || self::is_role_header($header)
            || self::is_enroltimestart_header($header)
            || self::is_enrolperiod_header($header)
            || self::is_enrolstatus_header($header);
    }

    /**
     * Determine whether a header is a cohort column.
     *
     * @param string $header
     * @return bool
     */
    public static function is_cohort_header(string $header): bool {
        return (bool)preg_match('/^cohort([1-9]|10)$/', $header);
    }

    /**
     * Get the numeric suffix from a repeated header.
     *
     * @param string $header
     * @return int
     */
    public static function get_index_from_header(string $header): int {
        if (preg_match('/(\d+)$/', $header, $matches)) {
            return (int)$matches[1];
        }

        return 0;
    }

    /**
     * Validate an integer within a required range.
     *
     * @param mixed $value
     * @param int $min
     * @param int $max
     * @return bool
     */
    public static function is_valid_int_in_range($value, int $min, int $max): bool {
        if ($value === null || $value === '') {
            return false;
        }

        if (!preg_match('/^\d+$/', (string)$value)) {
            return false;
        }

        $intvalue = (int)$value;
        return $intvalue >= $min && $intvalue <= $max;
    }

    /**
     * Build a timestamped template filename.
     *
     * @return string
     */
    public static function get_template_filename(): string {
        return 'my_upload_template_' . userdate(time(), '%Y-%m-%d_%H_%M') . '.csv';
    }

    /**
     * Get a readable course name for messaging.
     *
     * @param \stdClass $course
     * @return string
     */
    public static function get_course_display_name(\stdClass $course): string {
        if (!empty($course->shortname)) {
            return $course->shortname;
        }

        return $course->fullname ?? (string)$course->id;
    }

    /**
     * Normalise a CSV header for validation.
     *
     * @param string $header
     * @return string
     */
    public static function normalise_header(string $header): string {
        return trim(\core_text::strtolower($header));
    }
}
