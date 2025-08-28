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
 * This file is the IRT analysis to the mmogame module.
 *
 * @package    mod_mmogame
 * @copyright  2024 Vasilis Daloukas
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');

global $CFG, $DB, $PAGE;

$id = required_param('id', PARAM_INT); // Is mmoGame Module ID.

if (! $cm = get_coursemodule_from_id('mmogame', $id)) {
    throw new moodle_exception('invalidcoursemoduleid', 'error', '', null, $id);
}
if (! $course = $DB->get_record('course', ['id' => $cm->course])) {
    throw new moodle_exception('coursemisconf', 'error', '', $cm->course);
}
if (! $mmogame = $DB->get_record('mmogame', ['id' => $cm->instance])) {
    throw new moodle_exception('invalidcoursemoduleid', 'error', '', $cm->instance);
}

// Require login to the course.
require_course_login($course);

require_once($CFG->libdir.'/tablelib.php');

use mod_mmogame\local\database\mmogame_database_moodle;
use mod_mmogame\local\irt\mmogame_irt_1pl;
use mod_mmogame\local\mmogame;

$mmogame = mmogame::create( new mmogame_database_moodle(), $cm->instance);
$model = $mmogame->get_model();

$context = context_module::instance( $cm->id);
require_capability('mod/mmogame:view', $context);

// Initialize $PAGE, compute blocks.
$PAGE->set_url('/mod/mmogame/irt.php', ['id' => $cm->id]);

mmogame_irt( $mmogame, $context, 'first');

/**
 * Computes IRT.
 *
 * @param mmogame $mmogame
 * @param stdClass $context
 * @param string $kind
 * @return void
 * @throws dml_exception|coding_exception
 */
function mmogame_irt(mmogame $mmogame, stdClass $context, string $kind): void {
    $responses = $mapqueries = $mapusers = $positionsq = $positionsu = [];
    $function = 'mmogametype_'.$mmogame->get_type().'_irt_read';
    if (function_exists($function)) {
        $function($mmogame, $context, null, $responses, $mapqueries, $mapusers, $positionsq, $positionsu);
    } else {
        die("function does not exist ".$function."()");
    }

    $compute = optional_param('compute', 0, PARAM_INT);

    $keyid = mmogame_irt_1pl::keyid( $mmogame);
    if ($compute) {
        $irtq = $irtu = [];
        mmogame_irt_1pl::compute($responses, count($mapqueries), $irtq, $irtu);
        mmogame_irt_1pl::save($keyid, $irtq, $irtu, $mapqueries, $mapusers, $positionsq, $positionsu);
    }

    $download = optional_param('download', null, PARAM_ALPHA);

    if ($download === null) {
        mmogame_irt_begin_page(get_string('pluginname', 'mod_mmogame') ?? 'IRT Dashboard');
        echo html_writer::tag('h3', 'Items (b)');
    }
    mmogame_irt_print_b_moodle($keyid, $download);

    if ($download === null) {
        echo html_writer::tag('h3', 'Persons (θ)');
    }
    mmogame_irt_print_theta_moodle($keyid, $download);

    mmogame_irt_end_page();
}

/**
 * Begin page
 *
 * @param string $pagetitle
 * @return void
 * @throws \core\exception\coding_exception
 */
function mmogame_irt_begin_page(string $pagetitle): void {
    global $PAGE, $OUTPUT;
    $PAGE->set_pagelayout('report');
    $PAGE->set_title($pagetitle);
    $PAGE->set_heading($pagetitle);
    echo $OUTPUT->header();
}

/**
 * End page
 *
 * @return void
 */
function mmogame_irt_end_page(): void {
    global $OUTPUT;
    echo $OUTPUT->footer();
}

/**
 * Shows tables + exports Moodle-style
 *
 * @param int $keyid
 * @param ?string $download
 * @throws coding_exception
 * @throws dml_exception
 */
function mmogame_irt_print_b_moodle(int $keyid, ?string $download): void {
    global $DB, $PAGE;

    $table = new flexible_table('mmogame-b-table');
    $table->define_baseurl($PAGE->url);

    $sort = optional_param('sort', 'id', PARAM_ALPHA);

    $recs = $DB->get_records_select( 'mmogame_aa_irt_queries', 'keyid=?', [$keyid], $sort);
    $columns = ['num', 'name', 'questiontext', 'b', 'b_rt', 'se', 'infit', 'std_infit', 'outfit',
        'std_outfit', 'corrects', 'wrongs', 'queries', 'nulls', 'percent'];
    $headers = ['#', 'Name', 'Question', 'b', 'Online (b)', 'SE', 'Infit MNSQ', 'Std.Infit', 'Outfit MNSQ',
        'Std.Outfit', 'Corrects', 'Wrongs', 'Nulls', 'Queries', 'Percent %'];

    $table->define_columns($columns);
    $table->define_headers($headers);

    $table->sortable(true, 'num');
    $table->pageable(true);
    $table->is_downloadable(true);
    $table->show_download_buttons_at([TABLE_P_TOP, TABLE_P_BOTTOM]);
    $table->setup();

    $table->is_downloading($download, 'items_b', 'Items (b)');

    $i = 1;
    foreach ($recs as $rec) {
        $table->add_data([
            $i++,
            $rec->name,
            $rec->querytext,
            is_null($rec->b) ? '' : format_float($rec->b, 2),
            isset($rec->b_online) ? format_float($rec->b_online, 2) : '',
            is_null($rec->seb) ? '' : format_float($rec->seb, 2),
            is_null($rec->infit) ? '' : format_float($rec->infit, 2),
            is_null($rec->std_infit) ? '' : format_float($rec->std_infit, 2),
            is_null($rec->outfit) ? '' : format_float($rec->outfit, 2),
            is_null($rec->std_outfit) ? '' : format_float($rec->std_outfit, 2),
            $rec->corrects ?? '',
            $rec->wrongs ?? '',
            $rec->nulls ?? '',
            ($rec->corrects + $rec->wrongs) ?? '',
            $rec->percent ?? '',
        ]);
    }

    // Render HTML or stream data to a file (as specified by ?download=csv|xlsx|...).
    $table->finish_output();
}

/**
 * Shows theta
 *
 * @param int $keyid
 * @param string|null $download
 * @throws coding_exception
 * @throws dml_exception
 */
function mmogame_irt_print_theta_moodle(int $keyid, ?string $download): void {
    global $DB, $PAGE;

    $table = new flexible_table('mmogame-theta-table');
    $table->define_baseurl($PAGE->url);

    $columns = ['s_num', 's_mmogameid', 's_numgame', 's_auserid', 's_theta', 's_theta_rt',
        's_corrects', 's_wrongs', 's_nulls', 's_queries', 's_percent'];
    $headers = ['#', 'mmogameid', 'numgame', 'auserid', 'theta', 'Online theta',
        'Corrects', 'Wrongs', 'Nulls', 'Queries', 'Percent %'];

    $table->define_columns($columns);
    $table->define_headers($headers);

    $table->sortable(true, 'num');
    $table->pageable(true);
    $table->is_downloadable(true);
    $table->show_download_buttons_at([TABLE_P_TOP, TABLE_P_BOTTOM]);
    $table->setup();
    $table->is_downloading(optional_param('download', null, PARAM_ALPHA), 'persons_theta', 'Persons (θ)');

    $sort = 'id';
    $recs = $DB->get_records_select( 'mmogame_aa_irt_ausers', 'keyid=?', [$keyid], $sort);

    $i = 1;
    foreach ($recs as $rec) {
        $table->add_data([
            $i++,
            s($rec->mmogameid ?? ''),
            s($rec->numgame ?? ''),
            s($rec->auserid ?? ''),
            is_null($rec->theta) ? '' : format_float($rec->theta, 2),
            is_null($rec->theta_online) ? '' : format_float($rec->theta_online,  2),
            $rec->corrects ?? '',
            $rec->wrongs ?? '',
            $rec->nulls ?? '',
            $rec->queries ?? '',
            $rec->percent ?? '',
        ]);
    }

    $table->finish_output();
}
