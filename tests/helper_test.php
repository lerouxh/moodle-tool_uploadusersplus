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
 * Tests for helper behaviour.
 *
 * @package    tool_uploadusersplus
 * @category   test
 * @copyright  2026 eLearn Solutions
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Helper tests.
 *
 * @package    tool_uploadusersplus
 * @category   test
 * @copyright  2026 eLearn Solutions
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class tool_uploadusersplus_helper_test extends advanced_testcase {
    /**
     * Test report option filtering for dry run mode.
     *
     * @return void
     */
    public function test_get_report_options_for_dry_run(): void {
        $options = \tool_uploadusersplus\local\helper::get_report_options(true);

        $this->assertArrayHasKey(\tool_uploadusersplus\local\helper::REPORT_SUMMARY, $options);
        $this->assertArrayHasKey(\tool_uploadusersplus\local\helper::REPORT_DETAILED, $options);
        $this->assertArrayHasKey(\tool_uploadusersplus\local\helper::REPORT_EMAIL, $options);
        $this->assertSame(
            get_string('report_detailed_pro', 'tool_uploadusersplus'),
            $options[\tool_uploadusersplus\local\helper::REPORT_DETAILED]
        );
    }

    /**
     * Test report type normalisation for invalid dry run values.
     *
     * @return void
     */
    public function test_normalise_report_type_for_dry_run(): void {
        $normalised = \tool_uploadusersplus\local\helper::normalise_report_type(
            \tool_uploadusersplus\local\helper::REPORT_DETAILED,
            false
        );

        $this->assertSame(\tool_uploadusersplus\local\helper::REPORT_SUMMARY, $normalised);
    }

    /**
     * Test form normalisation forces summary-only reports in the free version.
     *
     * @return void
     */
    public function test_normalise_form_data_forces_summary_and_clears_email_in_free_version(): void {
        $data = (object)[
            'uploadtype' => \tool_uploadusersplus\local\helper::UPLOADTYPE_ADDNEW,
            'newpasswords' => \tool_uploadusersplus\local\helper::NEWPASSWORDS_CREATE,
            'existingpasswords' => \tool_uploadusersplus\local\helper::EXISTINGPASSWORDS_NOCHANGES,
            'dryrun' => 0,
            'reporttype' => \tool_uploadusersplus\local\helper::REPORT_EMAIL,
            'emailrecipient' => 'reports@example.com',
        ];

        $normalised = \tool_uploadusersplus\local\helper::normalise_form_data($data);

        $this->assertSame(\tool_uploadusersplus\local\helper::REPORT_SUMMARY, $normalised->reporttype);
        $this->assertSame('', $normalised->emailrecipient);
    }

    /**
     * Test hidden template options and course-dependent fields are normalised server-side.
     *
     * @return void
     */
    public function test_normalise_form_data_forces_hidden_template_options_off(): void {
        $this->resetAfterTest(true);
        set_config('hideoptionroleenrolments', 1, 'tool_uploadusersplus');
        set_config('hideoptiondeletedfield', 1, 'tool_uploadusersplus');

        $data = (object)[
            'includecustomprofilefields' => 1,
            'includeoptionalfields' => 1,
            'courseenrolments' => 0,
            'numberofcourses' => 3,
            'includerolefields' => 1,
            'includeenroltimestart' => 1,
            'includeenrolperiod' => 1,
            'includeenrolstatus' => 1,
            'cohortenrolments' => 0,
            'numberofcohorts' => 2,
            'includedeletedfield' => 1,
            'includesuspendedfield' => 1,
            'uploadtype' => \tool_uploadusersplus\local\helper::UPLOADTYPE_ADDNEW,
            'newpasswords' => \tool_uploadusersplus\local\helper::NEWPASSWORDS_CREATE,
            'existingpasswords' => \tool_uploadusersplus\local\helper::EXISTINGPASSWORDS_NOCHANGES,
            'dryrun' => 1,
            'reporttype' => \tool_uploadusersplus\local\helper::REPORT_SUMMARY,
            'emailrecipient' => '',
        ];

        $normalised = \tool_uploadusersplus\local\helper::normalise_form_data($data);

        $this->assertSame(0, $normalised->courseenrolments);
        $this->assertSame(1, $normalised->numberofcourses);
        $this->assertSame(0, $normalised->includerolefields);
        $this->assertSame(0, $normalised->includeenroltimestart);
        $this->assertSame(0, $normalised->includeenrolperiod);
        $this->assertSame(0, $normalised->includeenrolstatus);
        $this->assertSame(0, $normalised->includedeletedfield);
        $this->assertSame(1, $normalised->includesuspendedfield);
    }

    /**
     * Test Pro-only admin settings are forced to free-version defaults.
     *
     * @return void
     */
    public function test_get_admin_settings_forces_pro_only_settings_to_free_defaults(): void {
        $this->resetAfterTest(true);
        set_config('enrolmentrestrictions', 1, 'tool_uploadusersplus');
        set_config('siteadminreportsonly', 1, 'tool_uploadusersplus');
        set_config('displayuseridindetailedreports', 1, 'tool_uploadusersplus');
        set_config('hidefirstnamelastnameindetailedreports', 1, 'tool_uploadusersplus');
        set_config('enableuploadsupdatesreport', 1, 'tool_uploadusersplus');
        set_config('enrolmentrestrictiondays', 99, 'tool_uploadusersplus');

        $settings = \tool_uploadusersplus\local\helper::get_admin_settings();

        $this->assertFalse($settings->enrolmentrestrictions);
        $this->assertFalse($settings->siteadminreportsonly);
        $this->assertFalse($settings->displayuseridindetailedreports);
        $this->assertFalse($settings->hidefirstnamelastnameindetailedreports);
        $this->assertFalse($settings->enableuploadsupdatesreport);
        $this->assertSame(30, $settings->enrolmentrestrictiondays);
    }

    /**
     * Test report access is disabled in the free version.
     *
     * @return void
     */
    public function test_current_user_can_access_reports_when_setting_disabled(): void {
        $this->resetAfterTest(true);
        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);

        $this->assertFalse(\tool_uploadusersplus\local\helper::current_user_can_access_reports());
    }

    /**
     * Test report access is disabled for site administrators in the free version.
     *
     * @return void
     */
    public function test_current_user_can_access_reports_when_siteadmin_only_enabled(): void {
        $this->resetAfterTest(true);
        set_config('siteadminreportsonly', 1, 'tool_uploadusersplus');

        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);
        $this->assertFalse(\tool_uploadusersplus\local\helper::current_user_can_access_reports());

        $this->setAdminUser();
        $this->assertFalse(\tool_uploadusersplus\local\helper::current_user_can_access_reports());
    }
}
