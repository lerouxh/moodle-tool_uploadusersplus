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
 * Renderer for tool_uploadusersplus.
 *
 * @package    tool_uploadusersplus
 * @copyright  2026 eLearn Solutions
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace tool_uploadusersplus\output;

defined('MOODLE_INTERNAL') || die();

/**
 * Renderer for the Upload users PLUS page.
 *
 * @package    tool_uploadusersplus
 * @copyright  2026 eLearn Solutions
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class renderer extends \plugin_renderer_base {
    /**
     * Render the reports button shown on the main page.
     *
     * @param bool $enabled
     * @return string
     */
    public function render_reports_button(bool $enabled = true): string {
        if ($enabled) {
            $url = new \moodle_url('/admin/tool/uploadusersplus/reports.php');
            return \html_writer::div(
                $this->single_button($url, get_string('reportsbutton', 'tool_uploadusersplus'), 'get'),
                'mb-3'
            );
        }

        $button = \html_writer::tag('button', get_string('reportsbutton', 'tool_uploadusersplus'), [
            'type' => 'button',
            'class' => 'btn btn-secondary',
            'data-action' => 'tool-uploadusersplus-report',
            'disabled' => 'disabled',
            'aria-disabled' => 'true',
        ]);

        return \html_writer::div($button, 'mb-3');
    }

    /**
     * Render a back button to the main upload page.
     *
     * @return string
     */
    public function render_back_to_upload_button(): string {
        $url = new \moodle_url('/admin/tool/uploadusersplus/index.php');
        return \html_writer::div(
            $this->single_button($url, get_string('backtouploadusersplus', 'tool_uploadusersplus'), 'get'),
            'mb-3'
        );
    }

    /**
     * Render the plugin logo or a clear fallback message.
     *
     * @return string
     */
    public function render_logo(): string {
        global $CFG;

        $logopath = $CFG->dirroot . '/admin/tool/uploadusersplus/pix/uup_100x100.png';
        if (!is_readable($logopath)) {
            return $this->notification(get_string('logomissingnotice', 'tool_uploadusersplus'), 'warning');
        }

        $image = $this->image_url('uup_100x100', 'tool_uploadusersplus');
        return \html_writer::div(
            \html_writer::empty_tag('img', [
                'src' => $image,
                'alt' => get_string('logoalt', 'tool_uploadusersplus'),
                'class' => 'img-fluid',
                'width' => 100,
                'height' => 100,
            ]),
            'tool-uploadusersplus-logo mb-3'
        );
    }

    /**
     * Render the report block.
     *
     * @param array $report
     * @param bool $showdetails
     * @return string
     */
    public function render_report(array $report, bool $showdetails): string {
        $output = [];
        $output[] = $this->render_summary($report);

        if (!empty($report['showdryrunnotice'])) {
            $output[] = \html_writer::div(
                s($report['dryrunnotice']),
                'alert alert-warning alert-block fade in alert-dismissible'
            );
        }

        if (!empty($report['showsuccessmessage'])) {
            $output[] = $this->notification($report['successmessage'], 'success');
        }

        if ($report['showblockingmessage']) {
            $output[] = $this->notification($report['blockingmessage'], 'warning');
        }

        if ($showdetails) {
            $output[] = $this->render_details($report['detailcolumns'], $report['detailrows']);
        }

        if (!empty($report['emailsent'])) {
            $output[] = $this->notification(get_string('detailsemailsent', 'tool_uploadusersplus'), 'success');
        }

        if (!empty($report['emailerror'])) {
            $output[] = $this->notification($report['emailerror'], 'warning');
        }

        return implode("\n", $output);
    }

    /**
     * Render summary counts and global messages.
     *
     * @param array $report
     * @return string
     */
    protected function render_summary(array $report): string {
        $items = [];
        $summary = $report['summary'];

        $items[] = \html_writer::tag('li', s(get_string('summary_rowsread', 'tool_uploadusersplus', $summary['rowsread'])));
        $items[] = \html_writer::tag('li', s(get_string('summary_validrows', 'tool_uploadusersplus', $summary['validrows'])));
        $items[] = \html_writer::tag(
            'li',
            s(get_string('summary_invalidrows', 'tool_uploadusersplus', $summary['invalidrows'])),
            ['class' => $summary['invalidrows'] !== 0 ? 'text-danger' : '']
        );

        if ($summary['showdetectedcounts']) {
            $items[] = \html_writer::tag(
                'li',
                s(get_string('summary_newusersdetected', 'tool_uploadusersplus', $summary['newusersdetected'])),
                ['class' => $summary['newusersdetected'] !== 0 ? 'text-success' : '']
            );
            $items[] = \html_writer::tag(
                'li',
                s(get_string('summary_existingusersdetected', 'tool_uploadusersplus', $summary['existingusersdetected'])),
                ['class' => $summary['existingusersdetected'] !== 0 ? 'text-success' : '']
            );
        }

        $messages = '';
        if (!empty($report['globalmessages'])) {
            $messageitems = [];
            foreach ($report['globalmessages'] as $message) {
                $messageitems[] = \html_writer::tag('li', s($message));
            }
            $messages = \html_writer::tag('h4', get_string('globalissues', 'tool_uploadusersplus'))
                . \html_writer::tag('ul', implode('', $messageitems));
        }

        $processinglimitnotice = '';
        if (!empty($report['processinglimited'])) {
            $processinglimitnotice = \html_writer::div(
                get_string('processinglimitnotice', 'tool_uploadusersplus', (object)[
                    'limit' => $report['processinglimit'],
                    'prolink' => \tool_uploadusersplus\local\helper::get_pro_purchase_link(
                        get_string('proversion', 'tool_uploadusersplus')
                    ),
                ]),
                'alert alert-info mt-3'
            );
        }

        $rowmessages = '';
        if (!empty($report['rowmessages'])) {
            $messageitems = [];
            foreach ($report['rowmessages'] as $message) {
                $messageitems[] = \html_writer::tag('li', s($message));
            }
            $rowmessages = \html_writer::tag('h4', get_string('rowvalidationissues', 'tool_uploadusersplus'))
                . \html_writer::tag('ul', implode('', $messageitems));
        }

        $output = $this->box(
            \html_writer::tag('h3', s($summary['heading']))
            . \html_writer::tag('ul', implode('', $items))
            . $processinglimitnotice
            . $messages,
            'generalbox'
        );

        if ($rowmessages !== '') {
            $output .= $this->box($rowmessages, 'generalbox');
        }

        return $output;
    }

    /**
     * Render detailed per-row results.
     *
     * @param array $columns
     * @param array $rows
     * @return string
     */
    protected function render_details(array $columns, array $rows): string {
        $table = new \html_table();
        $table->head = array_map(static function(array $column): string {
            return $column['label'];
        }, $columns);
        $table->data = [];

        foreach ($rows as $row) {
            $cells = [];
            foreach ($columns as $column) {
                $value = $row[$column['key']];
                if (is_array($value)) {
                    $value = implode('<br>', array_map('s', $value));
                } else {
                    $value = s((string)$value);
                }
                $cells[] = $value;
            }

            $table->data[] = $cells;
        }

        return \html_writer::tag('h3', get_string('detailedresults', 'tool_uploadusersplus'))
            . \html_writer::div(\html_writer::table($table), 'flexible-wrap');
    }

    /**
     * Render a clean empty-state message for the reports page.
     *
     * @return string
     */
    public function render_empty_reports_state(): string {
        return $this->notification(get_string('noreportsyet', 'tool_uploadusersplus'), 'info');
    }
}
