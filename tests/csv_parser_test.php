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
 * Tests for the CSV parser.
 *
 * @package    tool_uploadusersplus
 * @category   test
 * @copyright  2026 eLearn Solutions
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * CSV parser tests.
 *
 * @package    tool_uploadusersplus
 * @category   test
 * @copyright  2026 eLearn Solutions
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class tool_uploadusersplus_csv_parser_test extends advanced_testcase {
    /**
     * Test comma-separated parsing.
     *
     * @return void
     */
    public function test_parse_comma_delimited_content(): void {
        $parser = new \tool_uploadusersplus\local\csv_parser();
        $content = "username,password,firstname,lastname,email\nalpha,Secret123!,Alice,Example,alice@example.com\n";
        $result = $parser->parse($content);

        $this->assertSame('comma', $result['delimitername']);
        $this->assertSame(['username', 'password', 'firstname', 'lastname', 'email'], $result['headers']);
        $this->assertCount(1, $result['rows']);
        $this->assertSame('alpha', $result['rows'][0]['values'][0]);
    }

    /**
     * Test semicolon-separated parsing with quoted commas.
     *
     * @return void
     */
    public function test_parse_semicolon_delimited_content(): void {
        $parser = new \tool_uploadusersplus\local\csv_parser();
        $content = "username;firstname;lastname;email;description\n"
            . "beta;Bob;Example;bob@example.com;\"Hello, world\"\n";
        $result = $parser->parse($content);

        $this->assertSame('semicolon', $result['delimitername']);
        $this->assertSame('Hello, world', $result['rows'][0]['values'][4]);
    }

    /**
     * Test empty rows containing only delimiters or blank cells are ignored.
     *
     * @return void
     */
    public function test_parse_ignores_empty_rows(): void {
        $parser = new \tool_uploadusersplus\local\csv_parser();
        $content = "username;firstname;lastname;email\n"
            . ";;;\n"
            . " ; ; ; \n"
            . "alpha;Alice;Example;alice@example.com\n";
        $result = $parser->parse($content);

        $this->assertCount(1, $result['rows']);
        $this->assertSame('alpha', $result['rows'][0]['values'][0]);
    }

    /**
     * Test row limit truncates processed rows and reports when more rows exist.
     *
     * @return void
     */
    public function test_parse_applies_data_row_limit(): void {
        $parser = new \tool_uploadusersplus\local\csv_parser();
        $limit = \tool_uploadusersplus\local\helper::get_free_row_processing_limit();
        $rows = ["username,firstname,lastname,email"];
        for ($i = 1; $i <= $limit + 1; $i++) {
            $rows[] = "user{$i},First{$i},Last{$i},user{$i}@example.com";
        }

        $result = $parser->parse(implode("\n", $rows), $limit);

        $this->assertCount($limit, $result['rows']);
        $this->assertTrue($result['rowlimitexceeded']);
        $this->assertSame('user' . $limit, $result['rows'][$limit - 1]['values'][0]);
    }

    /**
     * Test row limit is not exceeded when the file has exactly the limit.
     *
     * @return void
     */
    public function test_parse_row_limit_allows_exact_limit(): void {
        $parser = new \tool_uploadusersplus\local\csv_parser();
        $limit = \tool_uploadusersplus\local\helper::get_free_row_processing_limit();
        $rows = ["username,firstname,lastname,email"];
        for ($i = 1; $i <= $limit; $i++) {
            $rows[] = "user{$i},First{$i},Last{$i},user{$i}@example.com";
        }

        $result = $parser->parse(implode("\n", $rows), $limit);

        $this->assertCount($limit, $result['rows']);
        $this->assertFalse($result['rowlimitexceeded']);
    }
}
