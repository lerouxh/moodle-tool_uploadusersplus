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
 * Tests for validator behaviour.
 *
 * @package    tool_uploadusersplus
 * @category   test
 * @copyright  2026 eLearn Solutions
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Validator tests.
 *
 * @package    tool_uploadusersplus
 * @category   test
 * @copyright  2026 eLearn Solutions
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class tool_uploadusersplus_validator_test extends advanced_testcase {
    /**
     * Test a missing password header is blocking when the selected password mode requires it.
     *
     * @return void
     */
    public function test_missing_password_header_is_blocking_when_passwords_required(): void {
        $this->resetAfterTest();

        $formdata = new stdClass();
        $formdata->uploadtype = \tool_uploadusersplus\local\helper::UPLOADTYPE_ADDNEW;
        $formdata->newpasswords = \tool_uploadusersplus\local\helper::NEWPASSWORDS_FILE;
        $formdata->existingpasswords = \tool_uploadusersplus\local\helper::EXISTINGPASSWORDS_NOCHANGES;
        $formdata->dryrun = 1;

        $content = "username,firstname,lastname,email\nalice,Alice,Smith,alice@example.com\n";

        $validator = new \tool_uploadusersplus\local\validator();
        $result = $validator->validate($content, $formdata);

        $this->assertTrue($result['hasblockingerrors']);
        $this->assertContains(
            get_string('error_missingpasswordheaderrequiredmode', 'tool_uploadusersplus'),
            $result['globalerrors']
        );
    }

    /**
     * Test missing required custom profile fields remain blocking by default.
     *
     * @return void
     */
    public function test_missing_required_custom_profile_field_blocks_by_default(): void {
        $this->resetAfterTest();
        $generator = $this->getDataGenerator();
        $generator->create_custom_profile_field([
            'datatype' => 'text',
            'shortname' => 'cell',
            'name' => 'Cell',
            'required' => 1,
        ]);

        $formdata = (object)[
            'uploadtype' => \tool_uploadusersplus\local\helper::UPLOADTYPE_ADDNEW,
            'newpasswords' => \tool_uploadusersplus\local\helper::NEWPASSWORDS_CREATE,
            'existingpasswords' => \tool_uploadusersplus\local\helper::EXISTINGPASSWORDS_NOCHANGES,
            'dryrun' => 1,
        ];

        $content = "username,firstname,lastname,email\nalice,Alice,Smith,alice@example.com\n";
        $validator = new \tool_uploadusersplus\local\validator();
        $result = $validator->validate($content, $formdata);

        $this->assertTrue($result['hasblockingerrors']);
        $this->assertStringContainsString('profile_field_cell', $result['rows'][0]['messages'][0]['text']);
    }

    /**
     * Test missing required custom profile fields can be skipped when configured.
     *
     * @return void
     */
    public function test_missing_required_custom_profile_field_can_be_skipped(): void {
        $this->resetAfterTest();
        $generator = $this->getDataGenerator();
        $generator->create_custom_profile_field([
            'datatype' => 'text',
            'shortname' => 'cell',
            'name' => 'Cell',
            'required' => 1,
        ]);
        set_config('ignorerequiredcustomprofilefieldsmissing', 1, 'tool_uploadusersplus');

        $formdata = (object)[
            'uploadtype' => \tool_uploadusersplus\local\helper::UPLOADTYPE_ADDNEW,
            'newpasswords' => \tool_uploadusersplus\local\helper::NEWPASSWORDS_CREATE,
            'existingpasswords' => \tool_uploadusersplus\local\helper::EXISTINGPASSWORDS_NOCHANGES,
            'dryrun' => 1,
        ];

        $content = "username,firstname,lastname,email\nalice,Alice,Smith,alice@example.com\n";
        $validator = new \tool_uploadusersplus\local\validator();
        $result = $validator->validate($content, $formdata);

        $this->assertFalse($result['hasblockingerrors']);
        $this->assertContains(
            get_string('missingrequiredcustomprofilefieldsnotice', 'tool_uploadusersplus'),
            $result['globalmessages']
        );
    }

    /**
     * Test a supplied required custom profile field still blocks when its value is invalid or blank.
     *
     * @return void
     */
    public function test_supplied_required_custom_profile_field_still_validates_normally(): void {
        $this->resetAfterTest();
        $generator = $this->getDataGenerator();
        $generator->create_custom_profile_field([
            'datatype' => 'text',
            'shortname' => 'cell',
            'name' => 'Cell',
            'required' => 1,
        ]);
        set_config('ignorerequiredcustomprofilefieldsmissing', 1, 'tool_uploadusersplus');

        $formdata = (object)[
            'uploadtype' => \tool_uploadusersplus\local\helper::UPLOADTYPE_ADDNEW,
            'newpasswords' => \tool_uploadusersplus\local\helper::NEWPASSWORDS_CREATE,
            'existingpasswords' => \tool_uploadusersplus\local\helper::EXISTINGPASSWORDS_NOCHANGES,
            'dryrun' => 1,
        ];

        $content = "username,firstname,lastname,email,profile_field_cell\nalice,Alice,Smith,alice@example.com,\n";
        $validator = new \tool_uploadusersplus\local\validator();
        $result = $validator->validate($content, $formdata);

        $this->assertTrue($result['hasblockingerrors']);
        $this->assertEmpty($result['globalmessages']);
        $this->assertSame(
            get_string('error_profilefieldrequired', 'tool_uploadusersplus', 'profile_field_cell'),
            $result['rows'][0]['messages'][0]['text']
        );
    }

    /**
     * Test unknown profile field datatypes block by default.
     *
     * @return void
     */
    public function test_unknown_profile_field_datatype_blocks_by_default(): void {
        $this->resetAfterTest();
        $generator = $this->getDataGenerator();
        $generator->create_custom_profile_field([
            'datatype' => 'social',
            'shortname' => 'socialfield',
            'name' => 'url',
            'param1' => 'url',
        ]);

        $formdata = (object)[
            'uploadtype' => \tool_uploadusersplus\local\helper::UPLOADTYPE_ADDNEW,
            'newpasswords' => \tool_uploadusersplus\local\helper::NEWPASSWORDS_CREATE,
            'existingpasswords' => \tool_uploadusersplus\local\helper::EXISTINGPASSWORDS_NOCHANGES,
            'dryrun' => 1,
        ];

        $content = "username,firstname,lastname,email,profile_field_socialfield\n"
            . "alice,Alice,Smith,alice@example.com,https://example.com\n";

        $validator = new \tool_uploadusersplus\local\validator();
        $result = $validator->validate($content, $formdata);

        $this->assertTrue($result['hasblockingerrors']);
        $this->assertSame(
            get_string('error_unsupportedprofilefielddatatype', 'tool_uploadusersplus', (object)[
                'fieldname' => get_string('webpage', 'profilefield_social'),
                'datatype' => 'social',
            ]),
            $result['rows'][0]['messages'][0]['text']
        );
    }

    /**
     * Test unknown profile field datatypes can be allowed with a warning.
     *
     * @return void
     */
    public function test_unknown_profile_field_datatype_can_skip_validation_with_warning(): void {
        $this->resetAfterTest();
        $generator = $this->getDataGenerator();
        $generator->create_custom_profile_field([
            'datatype' => 'social',
            'shortname' => 'socialfield',
            'name' => 'url',
            'param1' => 'url',
        ]);
        set_config(
            'unknownprofilefielddatatypes',
            \tool_uploadusersplus\local\helper::UNKNOWN_PROFILE_DATATYPE_ALLOWRAW,
            'tool_uploadusersplus'
        );

        $formdata = (object)[
            'uploadtype' => \tool_uploadusersplus\local\helper::UPLOADTYPE_ADDNEW,
            'newpasswords' => \tool_uploadusersplus\local\helper::NEWPASSWORDS_CREATE,
            'existingpasswords' => \tool_uploadusersplus\local\helper::EXISTINGPASSWORDS_NOCHANGES,
            'dryrun' => 1,
        ];

        $content = "username,firstname,lastname,email,profile_field_socialfield\n"
            . "alice,Alice,Smith,alice@example.com,not-a-url\n";

        $validator = new \tool_uploadusersplus\local\validator();
        $result = $validator->validate($content, $formdata);

        $this->assertFalse($result['hasblockingerrors']);
        $this->assertContains(
            get_string('warning_unknownprofiledatatypevalidationskipped', 'tool_uploadusersplus', (object)[
                'fieldname' => get_string('webpage', 'profilefield_social'),
                'datatype' => 'social',
            ]),
            $result['globalmessages']
        );
    }

    /**
     * Test the free version validates only the configured number of non-empty data rows.
     *
     * @return void
     */
    public function test_free_version_limits_validation_to_first_50_data_rows(): void {
        $this->resetAfterTest();
        $limit = \tool_uploadusersplus\local\helper::get_free_row_processing_limit();
        $formdata = (object)[
            'uploadtype' => \tool_uploadusersplus\local\helper::UPLOADTYPE_ADDNEW,
            'newpasswords' => \tool_uploadusersplus\local\helper::NEWPASSWORDS_CREATE,
            'existingpasswords' => \tool_uploadusersplus\local\helper::EXISTINGPASSWORDS_NOCHANGES,
            'dryrun' => 1,
        ];
        $rows = ["username,firstname,lastname,email"];
        for ($i = 1; $i <= $limit; $i++) {
            $rows[] = "user{$i},First{$i},Last{$i},user{$i}@example.com";
        }
        $rows[] = "user1,Duplicate,Ignored,duplicate@example.com";

        $validator = new \tool_uploadusersplus\local\validator();
        $result = $validator->validate(implode("\n", $rows), $formdata);

        $this->assertFalse($result['hasblockingerrors']);
        $this->assertTrue($result['processinglimited']);
        $this->assertSame($limit, $result['processinglimit']);
        $this->assertCount($limit, $result['rows']);
        $this->assertSame($limit, $result['summary']['rowsread']);
        $this->assertSame($limit, $result['summary']['validrows']);
        $this->assertSame('user' . $limit, $result['rows'][$limit - 1]['username']);
    }
}
