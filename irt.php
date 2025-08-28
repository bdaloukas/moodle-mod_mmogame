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

use core\notification;
use mod_mmogame\local\database\mmogame_database_moodle;
use mod_mmogame\local\irt\mmogame_irt_1pl;
use mod_mmogame\local\mmogame;

$mmogame = mmogame::create( new mmogame_database_moodle(), $cm->instance);
$model = $mmogame->get_model();

$context = context_module::instance( $cm->id);
require_capability('mod/mmogame:view', $context);

// Initialize $PAGE, compute blocks.
$PAGE->set_url('/mod/mmogame/irt.php', ['id' => $cm->id]);

mmogame_irt( $mmogame, $context);

/**
 * Computes IRT.
 *
 * @param mmogame $mmogame
 * @param context $context
 * @return void
 * @throws \core\exception\coding_exception
 * @throws coding_exception
 * @throws dml_exception
 * @throws moodle_exception
 */
function mmogame_irt(mmogame $mmogame, context $context): void {
    $recompute = optional_param('recompute', 0, PARAM_INT);
    $wheresnippet = optional_param('wheresnippet', '', PARAM_RAW_TRIMMED);

    $keyid = mmogame_irt_1pl::keyid( $mmogame);

    if ($recompute) {
        require_sesskey();

        try {
            $filterparams = [];
            $safewhere = mmogame_compile_where_snippet($wheresnippet, $filterparams);

            $responses = $mapqueries = $mapusers = $positionsq = $positionsu = [];
            $function = 'mmogametype_'.$mmogame->get_type().'_irt_read';
            if (function_exists($function)) {
                $function($mmogame, $context, $responses, $mapqueries, $mapusers, $safewhere, $filterparams);
            } else {
                die("function does not exist ".$function."()");
            }

            $irtq = $irtu = [];
            mmogame_irt_1pl::compute($responses, count($mapqueries), $irtq, $irtu);
            $keyid = mmogame_irt_1pl::keyid($mmogame);
            mmogame_irt_1pl::save($keyid, $wheresnippet, $irtq, $irtu, $mapqueries, $mapusers);

            notification::success('recomputed');
        } catch (Exception $e) {
            notification::error(get_string('irt_wheresnippet_invalid', 'mod_mmogame'));
        }
        global $PAGE;
        redirect($PAGE->url); // No resubmit.
    }

    $download = optional_param('download', null, PARAM_ALPHA);

    if ($download === null) {
        mmogame_irt_begin_page(get_string('pluginname', 'mod_mmogame') ?? 'IRT Dashboard', $mmogame, $context);
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
 * @param mmogame $mmogame
 * @param context $context
 * @return void
 * @throws \core\exception\coding_exception
 * @throws coding_exception
 */
function mmogame_irt_begin_page(string $pagetitle, mmogame $mmogame, context $context): void {
    global $PAGE, $OUTPUT;
    $PAGE->set_pagelayout('report');
    $PAGE->set_title($pagetitle);
    $PAGE->set_heading($pagetitle);
    echo $OUTPUT->header();

    if (has_capability('mod/mmogame:manage', $context)) {
        echo html_writer::tag('button', 'recompute', [
            'type'  => 'button',
            'class' => 'btn btn-primary',
            'id'    => 'toggle-recompute',
        ]);

    }

    // Hidden inline form (shows on button click).
    echo html_writer::start_div('irt-inline-form mt-3', ['id' => 'irt-recompute-form', 'style' => 'display:none;']);
    echo html_writer::start_tag('form', ['method' => 'post', 'action' => $PAGE->url]);

    echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'sesskey', 'value' => sesskey()]);
    echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'recompute', 'value' => 1]);

    echo html_writer::start_div('form-group mb-2');
    echo html_writer::tag('label', 'irt_wheresnippet', ['for' => 'id_wheresnippet']);
    echo html_writer::tag('textarea', '',
        ['id' => 'id_wheresnippet', 'name' => 'wheresnippet', 'rows' => 3, 'class' => 'form-control',
            'placeholder' => 'a.mmogameid = '.$mmogame->get_id().' AND a.numgame='.$mmogame->get_numgame()]);
    echo html_writer::tag('small', 'irt_wheresnippet_help', ['class' => 'form-text text-muted']);
    echo html_writer::end_div();

    echo html_writer::start_div('mt-2');
    echo html_writer::tag('button', 'recompute', ['type' => 'submit', 'class' => 'btn btn-primary']);
    echo html_writer::tag('button', get_string('cancel'),
        ['type' => 'button', 'class' => 'btn btn-secondary', 'id' => 'cancel-recompute']);
    echo html_writer::end_div();

    echo html_writer::end_tag('form');
    echo html_writer::end_div();
?>
<script>
    (function() {
        var toggleBtn = document.getElementById('toggle-recompute');
        var panel     = document.getElementById('irt-recompute-form');
        var cancelBtn = document.getElementById('cancel-recompute');

        if (!toggleBtn || !panel) return;

        function show()  { panel.style.display = 'block'; }
        function hide()  { panel.style.display = 'none'; }
        function toggle(){ panel.style.display = (panel.style.display === 'none' || panel.style.display === '') ?
            'block' : 'none'; }

        toggleBtn.addEventListener('click', toggle);
        if (cancelBtn) cancelBtn.addEventListener('click', hide);
    })();
</script>
    <?php
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
            round($rec->percent) ?? '',
        ]);
    }

    $table->finish_output();
}

/**
 * Compile a restricted WHERE snippet into safe SQL with placeholders and params.
 * Allowed fields: a.auserid, a.mmogameid, a.numgame
 * Allowed ops: =, !=, <>, <, <=, >, >=
 * Allowed logic: AND, OR, parentheses
 * Integers only for values.
 *
 * @param string $snippet
 * @param array  $outparams Filled with integer params for the ? placeholders
 * @return string Safe SQL fragment (without leading WHERE)
 * @throws \moodle_exception
 */
function mmogame_compile_where_snippet(string $snippet, array &$outparams): string {
    $s = trim($snippet);
    $outparams = [];

    if ($s === '') {
        return '';
    }

    // Disallow quotes, semicolons and SQL comments.
    if (preg_match('/[;\'"]|--|\/\*/u', $s)) {
        throw new \moodle_exception('invalidfilter', 'mod_mmogame');
    }

    $len = strlen($s);
    $i = 0;
    $parts = [];
    $parens = 0;

    // Added: IN and comma (,) as tokens.
    $re = '/\G\s*(?:'
        .'(?<field>a\.(?:auserid|mmogameid|numgame))'
        .'|(?<op><=|>=|<>|!=|=|<|>)'
        .'|(?<in>IN)'
        .'|(?<lp>\()'
        .'|(?<rp>\))'
        .'|(?<comma>,)'
        .'|(?<logic>AND|OR)'
        .'|(?<num>-?\d+)'
        .')\s*/Ai';

    // State machine values.
    $expectfieldorlp = 0;
    $expectop = 1;
    $expectnum = 2;
    $expectlogicorrp = 3;

    // Extra states for IN(...).
    $expectlpforin = 4;
    $expectnumin = 5;
    $expectcommanorclosein = 6;

    $state = $expectfieldorlp;

    // Temporary buffer for '?' placeholders inside IN.
    $inqmarks = null; // The null => not inside IN, array => collecting placeholders.

    while ($i < $len) {
        if (!preg_match($re, $s, $m, 0, $i)) {
            throw new \moodle_exception('invalidfilter', 'mod_mmogame');
        }

        if (!empty($m['field'])) {
            if ($state !== $expectfieldorlp) {
                throw new \moodle_exception('invalidfilter', 'mod_mmogame');
            }
            $parts[] = strtolower($m['field']);
            $state = $expectop;

        } else if (!empty($m['op'])) {
            if ($state !== $expectop) {
                throw new \moodle_exception('invalidfilter', 'mod_mmogame');
            }
            $op = $m['op'];
            if ($op === '!=') {
                $op = '<>'; // Canonicalize.
            }
            $parts[] = $op;
            $state = $expectnum;

        } else if (!empty($m['in'])) {
            if ($state !== $expectop) {
                throw new \moodle_exception('invalidfilter', 'mod_mmogame');
            }
            // Enter IN(...) mode.
            $inqmarks = [];
            $state = $expectlpforin;

        } else if (!empty($m['num'])) {
            if ($state === $expectnum) {
                // Normal comparison with a single number.
                $parts[] = '?';
                $outparams[] = (int)$m['num'];
                $state = $expectlogicorrp;

            } else if ($state === $expectnumin) {
                // Number inside IN(...).
                $outparams[] = (int)$m['num'];
                $inqmarks[] = '?';
                $state = $expectcommanorclosein;

            } else {
                throw new \moodle_exception('invalidfilter', 'mod_mmogame');
            }

        } else if (!empty($m['lp'])) {
            if ($state === $expectfieldorlp) {
                // Outer grouping parenthesis.
                $parts[] = '(';
                $parens++;
                $state = $expectfieldorlp;

            } else if ($state === $expectlpforin) {
                // Opening parenthesis of IN(...).
                // Does not affect $parens (only grouping parentheses count).
                $state = $expectnumin;

            } else {
                throw new \moodle_exception('invalidfilter', 'mod_mmogame');
            }

        } else if (!empty($m['comma'])) {
            if ($state !== $expectcommanorclosein) {
                throw new \moodle_exception('invalidfilter', 'mod_mmogame');
            }
            // Expect another number in IN(...).
            $state = $expectnumin;

        } else if (!empty($m['rp'])) {
            if ($state === $expectlogicorrp && $parens > 0) {
                // Closing outer grouping parenthesis.
                $parts[] = ')';
                $parens--;
                $state = $expectlogicorrp;

            } else if ($state === $expectcommanorclosein && is_array($inqmarks)) {
                // Closing IN(...) list.
                if (count($inqmarks) === 0) {
                    // Disallow empty IN().
                    throw new \moodle_exception('invalidfilter', 'mod_mmogame');
                }
                $parts[] = 'IN (' . implode(',', $inqmarks) . ')';
                $inqmarks = null;
                $state = $expectlogicorrp;

            } else {
                throw new \moodle_exception('invalidfilter', 'mod_mmogame');
            }

        } else if (!empty($m['logic'])) {
            if ($state !== $expectlogicorrp) {
                throw new \moodle_exception('invalidfilter', 'mod_mmogame');
            }
            $parts[] = strtoupper($m['logic']);
            $state = $expectfieldorlp;
        }

        $i += strlen($m[0]);
    }

    // Final checks.
    if ($parens !== 0 || $state !== $expectlogicorrp || $inqmarks !== null) {
        throw new \moodle_exception('invalidfilter', 'mod_mmogame');
    }

    return implode(' ', $parts);
}
