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
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * This is the renderer for report overview
 *
 * @package    mmogametype_quiz
 * @copyright  2024 Vasilis Daloukas
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


namespace mmogametype_quiz\output\overview;

use core\exception\moodle_exception;
use core\output\html_writer;
use html_table;
use moodle_url;
use plugin_renderer_base;

/**
 * Renderer class to show or export data of the quiz attempts report.
 */
class overview_renderer extends plugin_renderer_base {
    /**
     * Renders
     *
     * @param overview_renderable $report
     * @return string|void
     * @throws \moodle_exception
     * @throws moodle_exception
     */
    public function render_overview(overview_renderable $report) {

        // Form.
        $form = html_writer::start_tag('form', [
            'method' => 'get',
            'action' => new moodle_url('/mod/mmogame/report.php', ['id' => $report->id, 'mode' => 'overview']),
        ]);

        // Add numgame.
        $form .= html_writer::label(get_string('numgame', 'mod_mmogame').': ', 'numgame', false);
        $form .= html_writer::select($report->get_numgame_options(), 'numgame', $report->numgame, false,
                ['class' => 'me-2 mb-2']);

        // Add auserid.
        $auseridoptions = $report->get_auserid_options();
        $form .= html_writer::label(get_string('user', 'mod_mmogame').': ', 'auserid', false);
        $form .= html_writer::select($auseridoptions, 'auserid', $report->auserid, false,
                ['class' => 'me-2 mb-2']);

        // Add queries.
        $queryoptions = $report->get_queries_options();
        $form .= html_writer::label(get_string('question').':', 'queries', false);
        $form .= html_writer::select($queryoptions, 'queries', $report->numgame, false,
                ['class' => 'me-2 mb-2']);

        $form .= html_writer::empty_tag('input', [
            'type' => 'submit',
            'value' => 'submit',
            'class' => 'me-2 mb-2',
        ]);

        $form .= html_writer::empty_tag('input', [
            'type' => 'submit',
            'name' => 'cvs',
            'value' => 'cvs',
        ]);

        $form .= html_writer::empty_tag('input', [
            'type' => 'hidden',
            'name' => 'id',
            'value' => $report->id,
        ]);

        $form .= html_writer::empty_tag('input', [
            'type' => 'hidden',
            'name' => 'mode',
            'value' => 'overview',
        ]);

        $form .= html_writer::end_tag('form');

        // Show results.
        $output = $form;
        $records = $report->get_data();
        if (!empty($records)) {
            $strftimedaydate = get_string("strftimedaydate");
            $table = new html_table();
            $table->head = [
                get_string('numgame', 'mod_mmogame'),
                get_string('user', 'mod_mmogame'),
                get_string('numattempt', 'mod_mmogame'),
                get_string('question'),
                get_string('answer'),
                get_string('iscorrect', 'mod_mmogame'),
                get_string('grade', 'mod_mmogame'),
                get_string('timeanswer', 'mod_mmogame'),
            ];
            $answers = [];
            foreach ($records as $record) {
                $iscorrect = '';
                if ($record->iscorrect === -1) {
                    continue;
                } else if ($record->iscorrect == 1) {
                    $iscorrect = get_string('yes');
                }
                $table->data[] = [
                    $record->numgame,
                    array_key_exists( $record->auserid, $auseridoptions) ? $auseridoptions[$record->auserid] : '',
                    $record->numattempt,
                    array_key_exists( $record->queryid, $queryoptions) ? $queryoptions[$record->queryid] : '',
                    $report->get_answer( $record, $answers),
                    $iscorrect,
                    $record->score,
                    $record->timeanswer != 0 ? userdate($record->timeanswer, $strftimedaydate) : '',
                ];
            }
            if ($report->export == 'cvs') {
                $this->export_table_to_csv( $table);
                exit;
            }
            $output .= html_writer::table($table);
        } else if (!is_null($report->auserid)) {
            $output .= html_writer::div( 'norecords', 'alert alert-warning' );
        }

        return $output;
    }

    /**
     * Exports data to cvs format
     *
     * @param html_table $table
     *
     * @throws moodle_exception
     * @throws \moodle_exception
     */
    public function export_table_to_csv(html_table $table): void {
        // Check if the table has data.
        if (empty($table->data)) {
            throw new \moodle_exception('No data available to export');
        }
        // Set HTTP headers for CSV export.
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment;filename="export.csv"');

        // Open output stream for CSV writing.
        $output = fopen('php://output', 'w');

        // Write the table headers, if available.
        if (!empty($table->head)) {
            fputcsv($output, $table->head);
        }

        // Write the table data.
        foreach ($table->data as $row) {
            // Convert each row to plain text (remove HTML).
            $plainrow = array_map(function ($cell) {
                return strip_tags(is_object($cell) ? $cell->text : $cell);
            }, $row);

            fputcsv($output, $plainrow);
        }

        // Close the output stream.
        fclose($output);
        exit; // Stop further execution.
    }
}
