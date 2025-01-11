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
 * This is the controller for report overview
 *
 * @package    mmogametype_quiz
 * @copyright  2024 Vasilis Daloukas
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mmogametype_quiz\output\overview;

use context_system;
use core\exception\coding_exception;
use core\exception\moodle_exception;
use dml_exception;
use moodle_url;

/**
 * Controller class to manage the display of the quiz attempts report.
 */
class overview_controller {
    /**
     * Displays or exports the data
     *
     * @return void
     * @throws \coding_exception
     * @throws coding_exception
     * @throws moodle_exception
     * @throws dml_exception
     */
    public function display(): void {
        global $DB, $PAGE, $OUTPUT;

        $id = required_param('id', PARAM_INT);
        $numgame = optional_param('numgame', null, PARAM_INT);
        $auserid = optional_param('auserid', null, PARAM_INT);
        $queryid = optional_param('queryid', null, PARAM_INT);
        $cvs = optional_param('cvs', null, PARAM_RAW);

        if (! $cm = get_coursemodule_from_id('mmogame', $id)) {
            throw new moodle_exception('invalidcoursemoduleid', 'error', '', null, $id);
        }
        if (! $rgame = $DB->get_record('mmogame', ['id' => $cm->instance])) {
            throw new moodle_exception('invalidcoursemoduleid', 'error', '', $cm->instance);
        }

        // Set up the page.
        $PAGE->set_url(new moodle_url('/mod/mmogame/report.php', ['id' => $id, 'mode' => 'overview']));
        $PAGE->set_context(context_system::instance());
        $PAGE->set_title(get_string('report_overview', 'mmogametype_quiz'));
        $PAGE->set_heading('Heading');

        // Create renderable object.
        $renderable = new overview_renderable($rgame, $id, $numgame, $auserid, $queryid, $cvs);

        $render = $PAGE->get_renderer('mmogametype_quiz', 'overview\overview');

        // Output the page.
        if ($cvs === null) {
            echo $OUTPUT->header();
        }
        echo $render->render($renderable);
        echo $OUTPUT->footer();
    }
}
