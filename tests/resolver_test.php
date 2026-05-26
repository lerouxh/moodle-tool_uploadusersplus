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
 * Tests for resolver behaviour.
 *
 * @package    tool_uploadusersplus
 * @category   test
 * @copyright  2026 eLearn Solutions
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Resolver tests.
 *
 * @package    tool_uploadusersplus
 * @category   test
 * @copyright  2026 eLearn Solutions
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class tool_uploadusersplus_resolver_test extends advanced_testcase {
    /**
     * Test course and cohort ambiguity handling.
     *
     * @return void
     */
    public function test_resolver_returns_ambiguity_errors(): void {
        $this->resetAfterTest();
        $generator = $this->getDataGenerator();

        $generator->create_course(['shortname' => 'COURSE_A', 'fullname' => 'Shared name']);
        $generator->create_course(['shortname' => 'COURSE_B', 'fullname' => 'Shared name']);
        $generator->create_cohort(['idnumber' => 'COHORT_A', 'name' => 'Shared cohort']);
        $generator->create_cohort(['idnumber' => 'COHORT_B', 'name' => 'Shared cohort']);

        $resolver = new \tool_uploadusersplus\local\resolver();
        $courseresult = $resolver->resolve_course('Shared name');
        $cohortresult = $resolver->resolve_cohort('Shared cohort');

        $this->assertArrayHasKey('error', $courseresult);
        $this->assertArrayHasKey('error', $cohortresult);
    }

    /**
     * Test group resolution by exact name inside a course.
     *
     * @return void
     */
    public function test_resolver_finds_group_in_course(): void {
        $this->resetAfterTest();
        $generator = $this->getDataGenerator();
        $course = $generator->create_course(['shortname' => 'GROUPTEST']);
        $group = $generator->create_group(['courseid' => $course->id, 'name' => 'Blue team']);

        $resolver = new \tool_uploadusersplus\local\resolver();
        $groupresult = $resolver->resolve_group($course->id, 'Blue team');

        $this->assertArrayHasKey('record', $groupresult);
        $this->assertSame($group->id, $groupresult['record']->id);
    }
}

