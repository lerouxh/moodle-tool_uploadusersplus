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
 * CSV parser.
 *
 * @package    tool_uploadusersplus
 * @copyright  2026 eLearn Solutions
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace tool_uploadusersplus\local;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/csvlib.class.php');

/**
 * Parser for comma- and semicolon-separated CSV files.
 *
 * @package    tool_uploadusersplus
 * @copyright  2026 eLearn Solutions
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class csv_parser {
    /**
     * Parse CSV content.
     *
     * @param string $content
     * @param int|null $datarowlimit
     * @return array
     */
    public function parse(string $content, ?int $datarowlimit = null): array {
        $content = $this->normalise_content($content);
        if ($content === '') {
            return [
                'delimitername' => 'comma',
                'headers' => [],
                'rows' => [],
                'errors' => [get_string('error_emptycsv', 'tool_uploadusersplus')],
                'rowlimit' => $datarowlimit,
                'rowlimitexceeded' => false,
            ];
        }

        $delimitername = $this->detect_delimiter($content);
        $delimiter = \csv_import_reader::get_delimiter($delimitername);
        $readlimit = $datarowlimit === null ? null : $datarowlimit + 2;
        $rows = $this->read_rows($content, $delimiter, $readlimit);

        if (empty($rows)) {
            return [
                'delimitername' => $delimitername,
                'headers' => [],
                'rows' => [],
                'errors' => [get_string('error_emptycsv', 'tool_uploadusersplus')],
                'rowlimit' => $datarowlimit,
                'rowlimitexceeded' => false,
            ];
        }

        $headerrow = array_shift($rows);
        $headers = array_map(static function(string $header): string {
            return trim($header);
        }, $headerrow['values']);
        $rowlimitexceeded = $datarowlimit !== null && count($rows) > $datarowlimit;
        if ($rowlimitexceeded) {
            $rows = array_slice($rows, 0, $datarowlimit);
        }

        return [
            'delimitername' => $delimitername,
            'headers' => $headers,
            'rows' => $rows,
            'errors' => [],
            'rowlimit' => $datarowlimit,
            'rowlimitexceeded' => $rowlimitexceeded,
        ];
    }

    /**
     * Normalise content for consistent parsing.
     *
     * @param string $content
     * @return string
     */
    protected function normalise_content(string $content): string {
        $content = \core_text::trim_utf8_bom($content);
        $content = preg_replace('!\r\n?!', "\n", $content);
        return trim($content);
    }

    /**
     * Detect whether comma or semicolon is the actual delimiter.
     *
     * @param string $content
     * @return string
     */
    public function detect_delimiter(string $content): string {
        $commascore = $this->score_delimiter($content, ',');
        $semicolonscore = $this->score_delimiter($content, ';');

        if ($semicolonscore > $commascore) {
            return 'semicolon';
        }

        return 'comma';
    }

    /**
     * Score a delimiter candidate based on consistent column counts.
     *
     * @param string $content
     * @param string $delimiter
     * @return int
     */
    protected function score_delimiter(string $content, string $delimiter): int {
        $rows = $this->read_rows($content, $delimiter, 10);
        if (empty($rows)) {
            return 0;
        }

        $headercount = count($rows[0]['values']);
        if ($headercount <= 1) {
            return 0;
        }

        $score = $headercount * 10;
        foreach ($rows as $row) {
            if (count($row['values']) === $headercount) {
                $score += 5;
            }
        }

        return $score;
    }

    /**
     * Read CSV rows.
     *
     * @param string $content
     * @param string $delimiter
     * @param int|null $limit
     * @return array
     */
    protected function read_rows(string $content, string $delimiter, ?int $limit = null): array {
        $rows = [];
        $stream = fopen('php://temp', 'w+');
        fwrite($stream, $content);
        rewind($stream);

        $csvline = 1;
        while (($values = fgetcsv($stream, 0, $delimiter, '"', '\\')) !== false) {
            if (count($values) === 1 && $values[0] === null) {
                $csvline++;
                continue;
            }

            if ($this->row_is_empty($values)) {
                $csvline++;
                continue;
            }

            $rows[] = [
                'line' => $csvline,
                'values' => array_map(static function($value): string {
                    return is_string($value) ? $value : '';
                }, $values),
            ];

            if ($limit !== null && count($rows) >= $limit) {
                break;
            }

            $csvline++;
        }

        fclose($stream);

        return $rows;
    }

    /**
     * Determine whether a parsed CSV row contains only blank cells.
     *
     * @param array $values
     * @return bool
     */
    protected function row_is_empty(array $values): bool {
        foreach ($values as $value) {
            if (trim((string)$value) !== '') {
                return false;
            }
        }

        return true;
    }
}
