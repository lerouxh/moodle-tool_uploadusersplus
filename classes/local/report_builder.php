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
 * Report builder for tool_uploadusersplus.
 *
 * @package    tool_uploadusersplus
 * @copyright  2026 eLearn Solutions
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace tool_uploadusersplus\local;

defined('MOODLE_INTERNAL') || die();

/**
 * Converts validation/import data into a report-friendly structure.
 *
 * @package    tool_uploadusersplus
 * @copyright  2026 eLearn Solutions
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class report_builder {
    /**
     * Build a page report array.
     *
     * @param array $validationresult
     * @param bool $dryrun
     * @param array|null $importresult
     * @return array
     */
    public function build(array $validationresult, bool $dryrun, ?array $importresult = null): array {
        $settings = helper::get_admin_settings();
        $summary = [
            'rowsread' => $validationresult['summary']['rowsread'],
            'validrows' => $validationresult['summary']['validrows'],
            'invalidrows' => $validationresult['summary']['invalidrows'],
            'newusersdetected' => $validationresult['summary']['newusersdetected'],
            'existingusersdetected' => $validationresult['summary']['existingusersdetected'],
            'showdetectedcounts' => !$validationresult['hasblockingerrors'],
            'dryrun' => $dryrun,
            'heading' => $dryrun ? get_string('dryrunresults', 'tool_uploadusersplus') : get_string(
                'uploadresultsheading',
                'tool_uploadusersplus'
            ),
        ];

        $details = $validationresult['rows'];
        if (!empty($importresult['rows'])) {
            $details = $importresult['rows'];
        }
        $detailcolumns = $this->get_detail_columns($settings);
        $detailrows = $this->build_detail_rows($details, $settings, $dryrun);

        $rowmessages = [];
        foreach ($details as $row) {
            if (empty($row['blocking'])) {
                continue;
            }

            foreach ($row['messages'] as $message) {
                $rowmessages[] = get_string('rowvalidationissue', 'tool_uploadusersplus', (object)[
                    'line' => $row['line'],
                    'field' => $message['fieldlabel'],
                    'message' => $message['text'],
                ]);
            }
        }

        $showsuccessmessage = false;
        if (!$dryrun && !$validationresult['hasblockingerrors'] && empty($importresult['rolledback'])) {
            foreach ($details as $row) {
                $action = $row['action'] ?? '';
                $status = $row['status'] ?? '';
                if (($action === 'create' || $action === 'update') && $status === 'success') {
                    $showsuccessmessage = true;
                    break;
                }
            }
        }

        return [
            'summary' => $summary,
            'processinglimited' => !empty($validationresult['processinglimited']),
            'processinglimit' => (int)($validationresult['processinglimit'] ?? helper::get_free_row_processing_limit()),
            'globalmessages' => array_merge(
                $validationresult['globalmessages'] ?? [],
                $validationresult['globalerrors'],
                $importresult['globalmessages'] ?? []
            ),
            'rowmessages' => $rowmessages,
            'details' => $details,
            'detailcolumns' => $detailcolumns,
            'detailrows' => $detailrows,
            'hasblockingerrors' => $validationresult['hasblockingerrors'] || !empty($importresult['rolledback']),
            'showblockingmessage' => !$dryrun && ($validationresult['hasblockingerrors'] || !empty($importresult['rolledback'])),
            'blockingmessage' => get_string('blockingmessage', 'tool_uploadusersplus'),
            'showdryrunnotice' => $dryrun,
            'dryrunnotice' => get_string('dryrunwarning', 'tool_uploadusersplus'),
            'showsuccessmessage' => $showsuccessmessage,
            'successmessage' => get_string('uploadsuccessnotice', 'tool_uploadusersplus'),
            'emailsent' => !empty($importresult['emailsent']),
            'emailerror' => $importresult['emailerror'] ?? '',
        ];
    }

    /**
     * Build a plain-text version of the detailed report for email.
     *
     * @param array $report
     * @return string
     */
    public function build_email_text(array $report): string {
        $lines = [];
        $lines[] = get_string('email_details_heading', 'tool_uploadusersplus');
        $lines[] = implode(' | ', array_column($report['detailcolumns'], 'label'));

        foreach ($report['detailrows'] as $row) {
            $values = [];
            foreach ($report['detailcolumns'] as $column) {
                $value = $row[$column['key']];
                if (is_array($value)) {
                    $value = implode(' ; ', $value);
                }
                $values[] = $value;
            }
            $lines[] = implode(' | ', $values);
        }

        return implode("\n", $lines) . "\n";
    }

    /**
     * Get the column definitions for detailed reporting.
     *
     * @param \stdClass $settings
     * @return array
     */
    protected function get_detail_columns(\stdClass $settings): array {
        $columns = [
            ['key' => 'line', 'label' => get_string('csvline', 'tool_uploadusersplus')],
            [
                'key' => 'identity',
                'label' => !empty($settings->displayuseridindetailedreports)
                    ? get_string('moodleuserid', 'tool_uploadusersplus')
                    : get_string('username'),
            ],
        ];

        if (empty($settings->hidefirstnamelastnameindetailedreports)) {
            $columns[] = ['key' => 'firstname', 'label' => get_string('firstname')];
            $columns[] = ['key' => 'lastname', 'label' => get_string('lastname')];
        }

        $columns[] = ['key' => 'status', 'label' => get_string('status')];
        $columns[] = ['key' => 'action', 'label' => get_string('action')];
        $columns[] = ['key' => 'details', 'label' => get_string('details', 'tool_uploadusersplus')];

        return $columns;
    }

    /**
     * Build detailed report rows using the active visibility settings.
     *
     * @param array $details
     * @param \stdClass $settings
     * @param bool $dryrun
     * @return array
     */
    protected function build_detail_rows(array $details, \stdClass $settings, bool $dryrun): array {
        $rows = [];

        foreach ($details as $row) {
            $detailrow = [
                'line' => (string)$row['line'],
                'identity' => $this->get_detail_identity_value($row, $settings),
            ];

            if (empty($settings->hidefirstnamelastnameindetailedreports)) {
                $detailrow['firstname'] = $this->get_row_name_value($row, 'firstname');
                $detailrow['lastname'] = $this->get_row_name_value($row, 'lastname');
            }

            $detailrow['status'] = (string)$row['statuslabel'];
            $detailrow['action'] = (string)$row['actionlabel'];
            $detailrow['details'] = $this->format_detail_messages($row['messages'] ?? [], $dryrun);
            $rows[] = $detailrow;
        }

        return $rows;
    }

    /**
     * Resolve the configured identity value for a detailed-results row.
     *
     * When the detailed report is configured to display Moodle user IDs, a dry-run
     * create row will not yet have a real user ID. In that case we display '-'.
     *
     * @param array $row
     * @param \stdClass $settings
     * @return string
     */
    protected function get_detail_identity_value(array $row, \stdClass $settings): string {
        if (!empty($settings->displayuseridindetailedreports)) {
            if (!empty($row['existinguser']) && !empty($row['existinguser']->id)) {
                return (string)$row['existinguser']->id;
            }

            return '-';
        }

        if (isset($row['username']) && $row['username'] !== '') {
            return (string)$row['username'];
        }

        return (string)($row['prepared']['user']['username'] ?? '');
    }

    /**
     * Resolve the displayed first or last name for a detailed-results row.
     *
     * @param array $row
     * @param string $field
     * @return string
     */
    protected function get_row_name_value(array $row, string $field): string {
        if (!empty($row['prepared']['user'][$field])) {
            return (string)$row['prepared']['user'][$field];
        }

        if (!empty($row['existinguser']) && isset($row['existinguser']->{$field})) {
            return (string)$row['existinguser']->{$field};
        }

        return '';
    }

    /**
     * Format detailed row messages for screen and email display.
     *
     * @param array $messages
     * @param bool $dryrun
     * @return array
     */
    protected function format_detail_messages(array $messages, bool $dryrun): array {
        $formatted = [];
        $skipmessages = [];

        if (!$dryrun) {
            $skipmessages = [
                get_string('message_usercreated', 'tool_uploadusersplus'),
                get_string('message_userupdated', 'tool_uploadusersplus'),
            ];
        }

        foreach ($messages as $message) {
            if (!$dryrun && in_array($message['text'], $skipmessages, true)) {
                continue;
            }
            $formatted[] = $message['fieldlabel'] . ': ' . $message['text'];
        }

        return $formatted;
    }
}
