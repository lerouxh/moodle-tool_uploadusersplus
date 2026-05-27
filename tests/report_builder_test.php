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
 * Tests for the report builder.
 *
 * @package    tool_uploadusersplus
 * @category   test
 * @copyright  2026 eLearn Solutions
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Report builder tests.
 *
 * @package    tool_uploadusersplus
 * @category   test
 * @copyright  2026 eLearn Solutions
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class tool_uploadusersplus_report_builder_test extends advanced_testcase {
    /**
     * Test dry-run summary building.
     *
     * @return void
     */
    public function test_builds_dry_run_summary(): void {
        $builder = new \tool_uploadusersplus\local\report_builder();
        $validationresult = [
            'hasblockingerrors' => false,
            'globalerrors' => [],
            'rows' => [],
            'summary' => [
                'rowsread' => 4,
                'validrows' => 4,
                'invalidrows' => 0,
                'newusersdetected' => 2,
                'existingusersdetected' => 2,
            ],
        ];

        $report = $builder->build($validationresult, true);

        $this->assertSame(get_string('dryrunresults', 'tool_uploadusersplus'), $report['summary']['heading']);
        $this->assertTrue($report['summary']['showdetectedcounts']);
        $this->assertFalse($report['showblockingmessage']);
        $this->assertTrue($report['showdryrunnotice']);
        $this->assertSame(get_string('dryrunwarning', 'tool_uploadusersplus'), $report['dryrunnotice']);
    }

    /**
     * Test blocking message on non-dry-run errors.
     *
     * @return void
     */
    public function test_builds_blocking_message_for_errors(): void {
        $builder = new \tool_uploadusersplus\local\report_builder();
        $validationresult = [
            'hasblockingerrors' => true,
            'globalerrors' => [get_string('error_emptycsv', 'tool_uploadusersplus')],
            'rows' => [],
            'summary' => [
                'rowsread' => 0,
                'validrows' => 0,
                'invalidrows' => 0,
                'newusersdetected' => 0,
                'existingusersdetected' => 0,
            ],
        ];

        $report = $builder->build($validationresult, false);

        $this->assertTrue($report['showblockingmessage']);
        $this->assertSame(get_string('blockingmessage', 'tool_uploadusersplus'), $report['blockingmessage']);
    }

    /**
     * Test row validation messages are exposed for summary reporting.
     *
     * @return void
     */
    public function test_builds_row_validation_messages(): void {
        $builder = new \tool_uploadusersplus\local\report_builder();
        $validationresult = [
            'hasblockingerrors' => true,
            'globalerrors' => [],
            'globalmessages' => [],
            'rows' => [[
                'line' => 3,
                'blocking' => true,
                'messages' => [[
                    'fieldlabel' => 'course1',
                    'text' => 'Restriction failed.',
                    'level' => 'error',
                ]],
            ]],
            'summary' => [
                'rowsread' => 1,
                'validrows' => 0,
                'invalidrows' => 1,
                'newusersdetected' => 0,
                'existingusersdetected' => 0,
            ],
        ];

        $report = $builder->build($validationresult, false);

        $this->assertCount(1, $report['rowmessages']);
        $this->assertStringContainsString('Line 3 - course1: Restriction failed.', $report['rowmessages'][0]);
    }

    /**
     * Test profile field row validation messages are masked in the free version.
     *
     * @return void
     */
    public function test_masks_profile_field_row_validation_messages_in_free_version(): void {
        $builder = new \tool_uploadusersplus\local\report_builder();
        $validationresult = $this->get_validation_result_with_messages(12, [[
            'field' => 'profile_field_region',
            'fieldlabel' => 'profile_field_region',
            'text' => 'Invalid custom profile field value for profile_field_region: Atlantis. Expected North, South.',
            'level' => 'error',
        ]]);

        $report = $builder->build($validationresult, true);
        $rowmessage = $report['rowmessages'][0]['html'];
        $emailtext = $builder->build_email_text($report);

        $this->assertStringContainsString('Line 12:', $rowmessage);
        $this->assertStringContainsString(
            get_string('invalidprofilefieldfree', 'tool_uploadusersplus', get_string('proversion', 'tool_uploadusersplus')),
            strip_tags($rowmessage)
        );
        $this->assertStringContainsString('target="_blank"', $rowmessage);
        $this->assertStringContainsString('rel="noopener noreferrer"', $rowmessage);
        $this->assertStringNotContainsString('profile_field_region', $rowmessage);
        $this->assertStringNotContainsString('Atlantis', $rowmessage);
        $this->assertStringNotContainsString('North', $rowmessage);
        $this->assertStringNotContainsString('profile_field_region', $emailtext);
        $this->assertStringNotContainsString('Atlantis', $emailtext);
        $this->assertStringContainsString('Profile fields', $emailtext);
        $this->assertStringContainsString(
            get_string('invalidprofilefieldfree', 'tool_uploadusersplus', get_string('proversion', 'tool_uploadusersplus')),
            $emailtext
        );
    }

    /**
     * Test mixed row validation messages keep non-profile detail and mask profile detail.
     *
     * @return void
     */
    public function test_mixed_row_validation_messages_mask_only_profile_field_issues(): void {
        $builder = new \tool_uploadusersplus\local\report_builder();
        $validationresult = $this->get_validation_result_with_messages(7, [[
            'field' => 'email',
            'fieldlabel' => 'email',
            'text' => 'Invalid email address.',
            'level' => 'error',
        ], [
            'field' => 'profile_field_region',
            'fieldlabel' => 'profile_field_region',
            'text' => 'Invalid profile field value: Atlantis.',
            'level' => 'error',
        ]]);

        $report = $builder->build($validationresult, true);

        $this->assertCount(2, $report['rowmessages']);
        $this->assertSame('Line 7 - email: Invalid email address.', $report['rowmessages'][0]);
        $this->assertStringContainsString('Line 7:', $report['rowmessages'][1]['html']);
        $this->assertStringNotContainsString('profile_field_region', $report['rowmessages'][1]['html']);
        $this->assertStringNotContainsString('Atlantis', $report['rowmessages'][1]['html']);
    }

    /**
     * Test repeated profile field row validation messages are collapsed.
     *
     * @return void
     */
    public function test_multiple_profile_field_row_validation_messages_are_collapsed(): void {
        $builder = new \tool_uploadusersplus\local\report_builder();
        $validationresult = $this->get_validation_result_with_messages(9, [[
            'field' => 'profile_field_region',
            'fieldlabel' => 'profile_field_region',
            'text' => 'Invalid profile field value: Atlantis.',
            'level' => 'error',
        ], [
            'field' => 'profile_field_department',
            'fieldlabel' => 'profile_field_department',
            'text' => 'Invalid profile field value: Secret.',
            'level' => 'error',
        ]]);

        $report = $builder->build($validationresult, true);

        $this->assertCount(1, $report['rowmessages']);
        $this->assertStringContainsString('Line 9:', $report['rowmessages'][0]['html']);
        $this->assertStringNotContainsString('profile_field_region', $report['rowmessages'][0]['html']);
        $this->assertStringNotContainsString('profile_field_department', $report['rowmessages'][0]['html']);
        $this->assertStringNotContainsString('Atlantis', $report['rowmessages'][0]['html']);
        $this->assertStringNotContainsString('Secret', $report['rowmessages'][0]['html']);
        $this->assertCount(1, $report['detailrows'][0]['details']);
    }

    /**
     * Test non-blocking global messages are included in the report output.
     *
     * @return void
     */
    public function test_includes_non_blocking_global_messages(): void {
        $builder = new \tool_uploadusersplus\local\report_builder();
        $validationresult = [
            'hasblockingerrors' => false,
            'globalerrors' => [],
            'globalmessages' => [get_string('missingrequiredcustomprofilefieldsnotice', 'tool_uploadusersplus')],
            'rows' => [],
            'summary' => [
                'rowsread' => 1,
                'validrows' => 1,
                'invalidrows' => 0,
                'newusersdetected' => 1,
                'existingusersdetected' => 0,
            ],
        ];

        $report = $builder->build($validationresult, true);

        $this->assertContains(
            get_string('missingrequiredcustomprofilefieldsnotice', 'tool_uploadusersplus'),
            $report['globalmessages']
        );
    }

    /**
     * Create a validation result with blocking row messages.
     *
     * @param int $line
     * @param array $messages
     * @return array
     */
    private function get_validation_result_with_messages(int $line, array $messages): array {
        return [
            'hasblockingerrors' => true,
            'globalerrors' => [],
            'globalmessages' => [],
            'rows' => [[
                'line' => $line,
                'blocking' => true,
                'messages' => $messages,
                'username' => 'student' . $line,
                'statuslabel' => get_string('status_invalid', 'tool_uploadusersplus'),
                'actionlabel' => get_string('action_none', 'tool_uploadusersplus'),
            ]],
            'summary' => [
                'rowsread' => 1,
                'validrows' => 0,
                'invalidrows' => 1,
                'newusersdetected' => 0,
                'existingusersdetected' => 0,
            ],
        ];
    }

    /**
     * Test processing limit flags are included in report output data.
     *
     * @return void
     */
    public function test_includes_processing_limit_data(): void {
        $builder = new \tool_uploadusersplus\local\report_builder();
        $limit = \tool_uploadusersplus\local\helper::get_free_row_processing_limit();
        $validationresult = [
            'hasblockingerrors' => false,
            'processinglimited' => true,
            'processinglimit' => $limit,
            'globalerrors' => [],
            'globalmessages' => [],
            'rows' => [],
            'summary' => [
                'rowsread' => $limit,
                'validrows' => $limit,
                'invalidrows' => 0,
                'newusersdetected' => $limit,
                'existingusersdetected' => 0,
            ],
        ];

        $report = $builder->build($validationresult, true);

        $this->assertTrue($report['processinglimited']);
        $this->assertSame($limit, $report['processinglimit']);
    }


    /**
     * Test unknown datatype skip warnings are carried into report output.
     *
     * @return void
     */
    public function test_includes_unknown_datatype_skip_warning_messages(): void {
        $builder = new \tool_uploadusersplus\local\report_builder();
        $warning = get_string('warning_unknownprofiledatatypevalidationskipped', 'tool_uploadusersplus', (object)[
            'fieldname' => get_string('webpage', 'profilefield_social'),
            'datatype' => 'social',
        ]);
        $validationresult = [
            'hasblockingerrors' => false,
            'globalerrors' => [],
            'globalmessages' => [$warning],
            'rows' => [[
                'line' => 2,
                'blocking' => false,
                'messages' => [[
                    'fieldlabel' => 'profile_field_socialfield',
                    'text' => $warning,
                    'level' => 'warning',
                ]],
                'username' => 'alice',
                'statuslabel' => get_string('status_valid', 'tool_uploadusersplus'),
                'actionlabel' => get_string('action_create', 'tool_uploadusersplus'),
            ]],
            'summary' => [
                'rowsread' => 1,
                'validrows' => 1,
                'invalidrows' => 0,
                'newusersdetected' => 1,
                'existingusersdetected' => 0,
            ],
        ];

        $report = $builder->build($validationresult, true);

        $this->assertContains($warning, $report['globalmessages']);
        $this->assertSame($warning, $report['details'][0]['messages'][0]['text']);
    }

    /**
     * Test detailed results show username, firstname, and lastname by default.
     *
     * @return void
     */
    public function test_detailed_results_show_username_and_names_by_default(): void {
        $this->resetAfterTest(true);
        $builder = new \tool_uploadusersplus\local\report_builder();
        $validationresult = [
            'hasblockingerrors' => false,
            'globalerrors' => [],
            'globalmessages' => [],
            'rows' => [[
                'line' => 2,
                'blocking' => false,
                'messages' => [[
                    'fieldlabel' => 'row',
                    'text' => 'User created.',
                    'level' => 'info',
                ]],
                'username' => 'alice',
                'statuslabel' => get_string('status_success', 'tool_uploadusersplus'),
                'actionlabel' => get_string('action_create', 'tool_uploadusersplus'),
                'prepared' => ['user' => ['firstname' => 'Alice', 'lastname' => 'Jones']],
                'existinguser' => (object)['id' => 25, 'firstname' => 'Alice', 'lastname' => 'Jones'],
            ]],
            'summary' => [
                'rowsread' => 1,
                'validrows' => 1,
                'invalidrows' => 0,
                'newusersdetected' => 1,
                'existingusersdetected' => 0,
            ],
        ];

        $report = $builder->build($validationresult, false);

        $this->assertSame(
            [get_string('csvline', 'tool_uploadusersplus'), get_string('username'), get_string('firstname'),
                get_string('lastname'), get_string('status'), get_string('action'), get_string('details', 'tool_uploadusersplus')],
            array_column($report['detailcolumns'], 'label')
        );
        $this->assertSame('alice', $report['detailrows'][0]['identity']);
        $this->assertSame('Alice', $report['detailrows'][0]['firstname']);
        $this->assertSame('Jones', $report['detailrows'][0]['lastname']);
        $this->assertStringContainsString('alice | Alice | Jones |', $builder->build_email_text($report));
    }

    /**
     * Test free version ignores Pro-only detailed report visibility settings.
     *
     * @return void
     */
    public function test_detailed_results_ignore_pro_visibility_settings_in_free_version(): void {
        $this->resetAfterTest(true);
        set_config('displayuseridindetailedreports', 1, 'tool_uploadusersplus');
        set_config('hidefirstnamelastnameindetailedreports', 1, 'tool_uploadusersplus');

        $builder = new \tool_uploadusersplus\local\report_builder();
        $validationresult = [
            'hasblockingerrors' => false,
            'globalerrors' => [],
            'globalmessages' => [],
            'rows' => [[
                'line' => 4,
                'blocking' => false,
                'messages' => [[
                    'fieldlabel' => 'row',
                    'text' => 'User updated.',
                    'level' => 'info',
                ]],
                'username' => 'bob',
                'statuslabel' => get_string('status_success', 'tool_uploadusersplus'),
                'actionlabel' => get_string('action_update', 'tool_uploadusersplus'),
                'prepared' => ['user' => ['firstname' => 'Bob', 'lastname' => 'Smith']],
                'existinguser' => (object)['id' => 42, 'firstname' => 'Bob', 'lastname' => 'Smith'],
            ]],
            'summary' => [
                'rowsread' => 1,
                'validrows' => 1,
                'invalidrows' => 0,
                'newusersdetected' => 0,
                'existingusersdetected' => 1,
            ],
        ];

        $report = $builder->build($validationresult, false);

        $this->assertSame(
            [get_string('csvline', 'tool_uploadusersplus'), get_string('username'), get_string('firstname'),
                get_string('lastname'), get_string('status'), get_string('action'), get_string('details', 'tool_uploadusersplus')],
            array_column($report['detailcolumns'], 'label')
        );
        $this->assertSame('bob', $report['detailrows'][0]['identity']);
        $this->assertSame('Bob', $report['detailrows'][0]['firstname']);
        $this->assertSame('Smith', $report['detailrows'][0]['lastname']);
        $this->assertStringContainsString('4 | bob | Bob | Smith |', $builder->build_email_text($report));
    }

    /**
     * Test free version ignores Moodle user ID display mode.
     *
     * @return void
     */
    public function test_detailed_results_ignore_userid_mode_in_free_version(): void {
        $this->resetAfterTest(true);
        set_config('displayuseridindetailedreports', 1, 'tool_uploadusersplus');

        $builder = new \tool_uploadusersplus\local\report_builder();
        $validationresult = [
            'hasblockingerrors' => false,
            'globalerrors' => [],
            'globalmessages' => [],
            'rows' => [[
                'line' => 6,
                'blocking' => false,
                'messages' => [],
                'username' => 'carol',
                'statuslabel' => get_string('status_valid', 'tool_uploadusersplus'),
                'actionlabel' => get_string('action_create', 'tool_uploadusersplus'),
                'prepared' => ['user' => ['firstname' => 'Carol', 'lastname' => 'Ng']],
                'existinguser' => null,
            ]],
            'summary' => [
                'rowsread' => 1,
                'validrows' => 1,
                'invalidrows' => 0,
                'newusersdetected' => 1,
                'existingusersdetected' => 0,
            ],
        ];

        $report = $builder->build($validationresult, true);

        $this->assertSame('carol', $report['detailrows'][0]['identity']);
    }

    /**
     * Test dry run keeps the preparatory user action message.
     *
     * @return void
     */
    public function test_dry_run_keeps_user_will_be_messages(): void {
        $builder = new \tool_uploadusersplus\local\report_builder();
        $validationresult = [
            'hasblockingerrors' => false,
            'globalerrors' => [],
            'globalmessages' => [],
            'rows' => [[
                'line' => 2,
                'blocking' => false,
                'messages' => [[
                    'fieldlabel' => 'row',
                    'text' => get_string('message_usercreated', 'tool_uploadusersplus'),
                    'level' => 'info',
                ]],
                'username' => 'dana',
                'status' => 'valid',
                'action' => 'create',
                'statuslabel' => get_string('status_valid', 'tool_uploadusersplus'),
                'actionlabel' => get_string('action_create', 'tool_uploadusersplus'),
                'prepared' => ['user' => ['firstname' => 'Dana', 'lastname' => 'West']],
                'existinguser' => null,
            ]],
            'summary' => [
                'rowsread' => 1,
                'validrows' => 1,
                'invalidrows' => 0,
                'newusersdetected' => 1,
                'existingusersdetected' => 0,
            ],
        ];

        $report = $builder->build($validationresult, true);

        $this->assertContains('row: ' . get_string('message_usercreated', 'tool_uploadusersplus'), $report['detailrows'][0]['details']);
        $this->assertStringContainsString(get_string('message_usercreated', 'tool_uploadusersplus'), $builder->build_email_text($report));
    }

    /**
     * Test live runs remove the redundant preparatory user action message.
     *
     * @return void
     */
    public function test_live_run_removes_user_will_be_messages(): void {
        $builder = new \tool_uploadusersplus\local\report_builder();
        $validationresult = [
            'hasblockingerrors' => false,
            'globalerrors' => [],
            'globalmessages' => [],
            'rows' => [[
                'line' => 2,
                'blocking' => false,
                'messages' => [[
                    'fieldlabel' => 'row',
                    'text' => get_string('message_usercreated', 'tool_uploadusersplus'),
                    'level' => 'info',
                ]],
                'username' => 'dana',
                'status' => 'valid',
                'action' => 'create',
                'statuslabel' => get_string('status_valid', 'tool_uploadusersplus'),
                'actionlabel' => get_string('action_create', 'tool_uploadusersplus'),
                'prepared' => ['user' => ['firstname' => 'Dana', 'lastname' => 'West']],
                'existinguser' => null,
            ]],
            'summary' => [
                'rowsread' => 1,
                'validrows' => 1,
                'invalidrows' => 0,
                'newusersdetected' => 1,
                'existingusersdetected' => 0,
            ],
        ];
        $importresult = [
            'rows' => [[
                'line' => 2,
                'blocking' => false,
                'messages' => [[
                    'fieldlabel' => 'row',
                    'text' => get_string('message_usercreated', 'tool_uploadusersplus'),
                    'level' => 'info',
                ], [
                    'fieldlabel' => 'row',
                    'text' => get_string('message_usercreateddone', 'tool_uploadusersplus'),
                    'level' => 'info',
                ]],
                'username' => 'dana',
                'status' => 'success',
                'action' => 'create',
                'statuslabel' => get_string('status_success', 'tool_uploadusersplus'),
                'actionlabel' => get_string('action_create', 'tool_uploadusersplus'),
                'prepared' => ['user' => ['firstname' => 'Dana', 'lastname' => 'West']],
                'existinguser' => (object)['id' => 31, 'firstname' => 'Dana', 'lastname' => 'West'],
            ]],
            'globalmessages' => [],
            'rolledback' => false,
        ];

        $report = $builder->build($validationresult, false, $importresult);

        $this->assertNotContains('row: ' . get_string('message_usercreated', 'tool_uploadusersplus'), $report['detailrows'][0]['details']);
        $this->assertContains('row: ' . get_string('message_usercreateddone', 'tool_uploadusersplus'), $report['detailrows'][0]['details']);
        $emailtext = $builder->build_email_text($report);
        $this->assertStringNotContainsString(get_string('message_usercreated', 'tool_uploadusersplus'), $emailtext);
        $this->assertStringContainsString(get_string('message_usercreateddone', 'tool_uploadusersplus'), $emailtext);
    }
}
