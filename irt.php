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
    $numitems = 36;

    $responses = $mapqueries = $mapusers = [];
    $function = 'mmogametype_'.$mmogame->get_type().'_irt_read';
    if (function_exists($function)) {
        $function($mmogame, $numitems, $responses, $mapqueries, $mapusers, $kind);
    } else {
        die("function does not exist ".$function."()");
    }

    $ret = mmogame_irt_1pl::compute($responses, $numitems);

    $download = optional_param('download', null, PARAM_ALPHA);

    if ($download === null) {
        mmogame_irt_begin_page(get_string('pluginname', 'mod_mmogame') ?? 'IRT Dashboard');
        echo html_writer::tag('h3', 'Items (b)');
    }
    mmogame_irt_print_b_moodle($mapqueries, $ret, $context, $download);

    if ($download === null) {
        echo html_writer::tag('h3', 'Persons (θ)');
    }
    mmogame_irt_print_theta_moodle($mapusers, $ret);

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
 * @param array $mapqueries objects with  ->questiontext, ->difficulty (real time b)
 * @param array $data ['b','se_b','infit','std_infit','outfit','std_outfit','freq','percent']
 * @param stdClass $context
 * @param ?string $download
 * @throws coding_exception
 */
function mmogame_irt_print_b_moodle(array $mapqueries, array $data, stdClass $context, ?string $download): void {
    global $PAGE;

    $table = new flexible_table('mmogame-b-table');
    $table->define_baseurl($PAGE->url);

    $columns = ['num','questiontext','b','b_rt','se','wms','std_wms','ums','std_ums','freq','percent'];
    $headers = ['#','Question','b','Real time (b)','SE','WMS','Std.WMS','UMS','Std.UMS','Correct/Error/Null','%'];

    $table->define_columns($columns);
    $table->define_headers($headers);

    $table->sortable(true, 'num');
    $table->pageable(true);
    $table->is_downloadable(true);
    $table->show_download_buttons_at([TABLE_P_TOP, TABLE_P_BOTTOM]);
    $table->text_sorting('questiontext'); // σωστή ταξινόμηση για text
    $table->setup();

    $table->is_downloading($download, 'items_b', 'Items (b)');

    foreach ($mapqueries as $i => $info) {
        $b         = $data['b'][$i]            ?? null;
        $se        = $data['se_b'][$i]         ?? null;
        $infit     = $data['infit'][$i]        ?? null;
        $std_infit = $data['std_infit'][$i]    ?? null;
        $outfit    = $data['outfit'][$i]       ?? null;
        $std_outfit= $data['std_outfit'][$i]   ?? null;
        $freq      = $data['freq'][$i]         ?? '';
        $percent   = isset( $data['percent'][$i]) ? (round($data['percent'][$i])      ?? '') : '';

        // Format για HTML/Download: το tablelib χειρίζεται σωστά και για αρχεία.
        $table->add_data([
            $i+1,
            format_text($info->questiontext ?? '', $info->questionformat ?? FORMAT_HTML, ['context' => $context]),
//            format_string($info->questiontext ?? ''),
            is_null($b) ? '' : format_float($b, 2),
            isset($info->difficulty) ? format_float($info->difficulty, 2) : '',
            is_null($se) ? '' : format_float($se, 2),
            is_null($infit) ? '' : format_float($infit, 2),
            is_null($std_infit) ? '' : format_float($std_infit, 2),
            is_null($outfit) ? '' : format_float($outfit, 2),
            is_null($std_outfit) ? '' : format_float($std_outfit, 2),
            $freq,
            $percent
        ]);
    }

    // Render HTML or stream data to a file (as specified by ?download=csv|xlsx|...)
    $table->finish_output();
}

/**
 * Shows theta
 *
 * @param array $mapusers objects with ->mmogameid, ->numgame, ->auserid, ->theta (rt),
 *                        ->first (array), ->count, ->corrects, ->wrongs
 * @param array $data ['theta' => [...]] (estimation Rasch)
 * @throws coding_exception
 */
function mmogame_irt_print_theta_moodle(array $mapusers, array $data): void {
    global $PAGE;

    $table = new flexible_table('mmogame-theta-table');
    $table->define_baseurl($PAGE->url);

    $columns = ['num','mmogameid','numgame','auserid','theta','theta_rt','difcount','questions','corrects','wrongs','percent'];
    $headers = ['#','mmogameid','numgame','auserid','θ','θ (real time)','Dif count','Questions','Corrects','Wrongs','%'];

    $table->define_columns($columns);
    $table->define_headers($headers);

    $table->sortable(true, 'num');
    $table->pageable(true);
    $table->is_downloadable(true);
    $table->show_download_buttons_at([TABLE_P_TOP, TABLE_P_BOTTOM]);
    $table->setup();
    $table->is_downloading(optional_param('download', null, PARAM_ALPHA), 'persons_theta', 'Persons (θ)');

    foreach ($mapusers as $i => $u) {
        $theta_est = $data['theta'][$i] ?? null;
        $theta_rt  = $u->theta ?? null;

        $difcount = 0;
        if (!empty($u->first) && is_array($u->first)) {
            $difcount = array_sum(array_map('intval', $u->first));
        }

        $questions = $u->count   ?? null;
        $corrects  = $u->corrects?? null;
        $wrongs    = $u->wrongs  ?? null;

        // Αν δεν έχεις ήδη %, υπολόγισέ το πρόχειρα:
        $percent = '';
        if (is_numeric($corrects) && is_numeric($questions) && $questions > 0) {
            $percent = round(($corrects / $questions) * 100) . '%';
        }

        $table->add_data([
            $i,
            s($u->mmogameid ?? ''),
            s($u->numgame   ?? ''),
            s($u->auserid   ?? ''),
            is_null($theta_est) ? '' : format_float($theta_est, 2),
            is_null($theta_rt)  ? '' : format_float($theta_rt,  2),
            $difcount,
            $questions ?? '',
            $corrects  ?? '',
            $wrongs    ?? '',
            $percent
        ]);
    }

    $table->finish_output();
}