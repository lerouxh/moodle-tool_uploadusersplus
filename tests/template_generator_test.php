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
 * Tests for the template generator.
 *
 * @package    tool_uploadusersplus
 * @category   test
 * @copyright  2026 eLearn Solutions
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Template generator tests.
 *
 * @package    tool_uploadusersplus
 * @category   test
 * @copyright  2026 eLearn Solutions
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class tool_uploadusersplus_template_generator_test extends advanced_testcase {
    /**
     * Test header generation order.
     *
     * @return void
     */
    public function test_build_headers_in_expected_order(): void {
        $this->resetAfterTest();
        $generator = $this->getDataGenerator();
        $generator->create_custom_profile_field([
            'datatype' => 'text',
            'shortname' => 'employeeid',
            'name' => 'Employee ID',
        ]);
        $generator->create_custom_profile_field([
            'datatype' => 'menu',
            'shortname' => 'region',
            'name' => 'Region',
            'param1' => "North\nSouth",
        ]);

        $templategenerator = new \tool_uploadusersplus\local\template_generator();
        $headers = $templategenerator->build_headers(true, true, 2, 2, true, true);

        $this->assertSame('username', $headers[0]);
        $this->assertSame('email', $headers[4]);
        $this->assertContains('profile_field_employeeid', $headers);
        $this->assertContains('profile_field_region', $headers);
        $this->assertContains('institution', $headers);
        $this->assertSame(['course1', 'group1', 'course2', 'group2', 'cohort1', 'cohort2'], array_slice($headers, -6));
    }

    /**
     * Test password header inclusion can be disabled for template generation.
     *
     * @return void
     */
    public function test_build_headers_excludes_password_when_setting_disabled(): void {
        $this->resetAfterTest();
        set_config('includepasswordfield', 0, 'tool_uploadusersplus');

        $templategenerator = new \tool_uploadusersplus\local\template_generator();
        $headers = $templategenerator->build_headers(false, false, 1, 1, false, false);

        $this->assertSame(['username', 'firstname', 'lastname', 'email'], $headers);
    }

    /**
     * Test only configured custom profile fields are included in generated templates.
     *
     * @return void
     */
    public function test_build_headers_uses_selected_custom_profile_fields_only(): void {
        $this->resetAfterTest();
        $generator = $this->getDataGenerator();
        $generator->create_custom_profile_field([
            'datatype' => 'text',
            'shortname' => 'employeeid',
            'name' => 'Employee ID',
        ]);
        $generator->create_custom_profile_field([
            'datatype' => 'menu',
            'shortname' => 'region',
            'name' => 'Region',
            'param1' => "North\nSouth",
        ]);
        set_config('customprofilefieldstoinclude', 'employeeid', 'tool_uploadusersplus');

        $templategenerator = new \tool_uploadusersplus\local\template_generator();
        $headers = $templategenerator->build_headers(true, false, 1, 1, false, false);

        $this->assertContains('profile_field_employeeid', $headers);
        $this->assertNotContains('profile_field_region', $headers);
    }
}
