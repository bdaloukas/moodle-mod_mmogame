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
 * This file is the entry point to the mmogame module. All pages are rendered from here
 *
 * @package    mmogametype_quiz
 * @copyright  2024 Vasilis Daloukas
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use mod_mmogame\event\course_module_viewed;
use mod_mmogame\local\irt\mmogame_irt_1pl;
use mod_mmogame\local\mmogame;

defined('MOODLE_INTERNAL') || die();

global $course, $cm, $id, $mmogame, $CFG, $PAGE;

// Check login and get context.
require_login($course->id, false, $cm);

$model = $mmogame->get_model();

$context = context_module::instance( $cm->id);
require_capability('mod/mmogame:view', $context);

// Initialize $PAGE, compute blocks.
$PAGE->set_url('/mod/mmogame/view.php', ['id' => $cm->id]);

$url = $CFG->wwwroot.'/mod/mmogame/gate.php?id='.$id.'&pin='.$mmogame->get_rgame()->pin;

$context = context_module::instance( $cm->id);
require_capability('mod/mmogame:view', $context);

if (has_capability('mod/mmogame:manage', $context)) {
    course_module_viewed::viewed($mmogame->get_rgame(), $context)->trigger();
    mmogame_quiz_manage( $id, $mmogame, $url);
} else {
    redirect( $url);
}

/**
 * Creates the admin form.
 *
 * @param int $id
 * @param mmogame $mmogame
 * @param string $url (url for playing the game)
 * @throws coding_exception|moodle_exception
 */
function mmogame_quiz_manage(int $id, mmogame $mmogame, string $url): void {
    global $CFG, $OUTPUT;

    if (count( $_POST) > 0) {
        if (!isset( $_POST['irt'])) {
            mmogame_quiz_manage_submit($mmogame);
            redirect($CFG->wwwroot . '/mod/mmogame/view.php?id=' . $id);
        }
    }

    // Create form.
    $classname = 'mmogametype_quiz\local\mmogametype_quiz_' . $mmogame->get_model().'_admin';
    $mform = new $classname( $id, $mmogame);

    echo $OUTPUT->header();

    $mform->display();

    echo '<br>'.get_string( 'url_for_playing', 'mmogame', ": <a href=\"$url\" target=\"_blank\">$url</a><br>");

    if (isset( $_POST['irt'])) {
        mmogame_quiz_manage_irt( $mmogame, 'first');
    }

    echo $OUTPUT->footer();
}

/**
 * Computes IRT.
 *
 * @param mmogame $mmogame
 * @param string $kind
 * @return void
 * @throws dml_exception
 */
function mmogame_quiz_manage_irt(mmogame $mmogame, string $kind): void {
    $numitems = 36;

    mmogame_quiz_manage_irt_read($mmogame, $numitems, $responses, $mapqueries, $mapusers, $kind);

    $ret = mmogame_irt_1pl::compute($responses, $numitems);

    mmogame_quiz_manage_irt_print_b($mapqueries, $ret);
    echo '<br>';
    mmogame_quiz_manage_irt_print_theta($mapusers, $ret);
}

/**
 * Prints data.
 *
 * @param $mapqueries
 * @param array $data
 * @return void
 */
function mmogame_quiz_manage_irt_print_b($mapqueries, array $data): void {
    $b = $data['b'];
    $seb = $data['se_b'];
    $infit = $data['infit'];
    $outfit = $data['outfit'];
    $stdinfit = $data['std_infit'];
    $stdoutfit = $data['std_outfit'];
    $freq = $data['freq'];
    $percent = $data['percent'];

    echo '<table border="1" cellspacing="0" cellpadding="4">';
    echo '<thead>';
    echo '<tr>';
    echo '<th>Num</th>';
    echo '<th>Question Text</th>';
    echo '<th>Difficulty (b)</th>';
    echo '<th>Real time (b)</th>';
    echo '<th>Standard Error (SE)</th>';
    echo '<th>WMS</th>';
    echo '<th>Std. WMS</th>';
    echo '<th>UMS</th>';
    echo '<th>Std. UMS</th>';
    echo '<th>Correct / Error / Null</th>';
    echo '<th>Percent</th>';
    echo '</tr>';
    echo '</thead>';

    echo '<tbody>';
    $lines = ['num;questiontext;b;se_b;wms;std_wms;ums;std_ums'];
    foreach ($mapqueries as $i => $info) {
        echo '<tr>';
        echo '<td>'.($i + 1) . '</td>';
        echo '<td>'.$info->questiontext. '</td>';
        echo '<td>'.round($b[$i], 2) . '</td>';
        echo '<td>'.round($info->difficulty, 2).'</td>';
        echo '<td>'.($seb[$i] !== null ? round($seb[$i], 2) : '') . '</td>';
        $b1 = $infit[$i] > 1.5 || $infit[$i] < 0.5 ? '<b>' : '';
        $b2 = $infit[$i] > 1.5 || $infit[$i] < 0.5 ? '</b>' : '';
        echo '<td>'.$b1.($infit[$i] !== null ? round($infit[$i], 2) : '').$b2.'</td>';
        echo '<td>'.($stdinfit[$i] !== null ? round($stdinfit[$i], 2) : '').'</td>';
        $b1 = $outfit[$i] > 1.5 || $outfit[$i] < 0.5 ? '<b>' : '';
        $b2 = $outfit[$i] > 1.5 || $outfit[$i] < 0.5 ? '</b>' : '';
        echo '<td>' . $b1.($outfit[$i] !== null ? round($outfit[$i], 2) : '').$b2.'</td>';
        echo '<td>'.($stdoutfit[$i] !== null ? round($stdoutfit[$i], 2) : '').'</td>';
        echo '<td>'.$freq[$i].'</td>';
        echo '<td>'.($percent[$i] !== null ? round($percent[$i]) : '').'%</td>';
        echo '</tr>';
        $lines[] = ($i + 1).';"'.htmlspecialchars($info->questiontext).
            '";'.$b[$i].';'.$seb[$i].';'.$infit[$i].';'.$stdinfit[$i].';'.
            $outfit[$i].';'.$stdoutfit[$i];
    }
    echo '</tbody>';
    echo '</table>';

    file_put_contents( "b.csv", implode("\n", $lines) );
}

/**
 * Prints theta.
 *
 * @param $mapusers
 * @param array $data
 * @return void
 */
function mmogame_quiz_manage_irt_print_theta($mapusers, array $data): void {
    $theta = $data['theta'];

    echo '<table border="1" cellspacing="0" cellpadding="4">';
    echo '<thead>';
    echo '<tr>';
    echo '<th>Num</th>';
    echo '<th>mmogameid</th>';
    echo '<th>numgame</th>';
    echo '<th>auserid</th>';
    echo '<th>theta</th>';
    echo '<th>theta (real time)</th>';
    echo '<th>Dif count</th>';
    echo '<th>Questions</th>';
    echo '<th>Corrects</th>';
    echo '<th>Wrongs</th>';
    echo '<th>Percent</th>';
    echo '</tr>';
    echo '</thead>';

    $lines = ['num;mmogameid;numgame;auserid;theta'];
    echo '<tbody>';
    $i = 0;
    foreach ($mapusers as $info) {
        echo '<tr>';
        echo '<td>' . ($i + 1) . '</td>';
        echo '<td>' . $info->mmogameid. '</td>';
        echo '<td>' . $info->numgame. '</td>';
        echo '<td>' . $info->auserid . '</td>';
        echo '<td>' . round($theta[$i], 2) . '</td>';
        echo '<td>' . round($info->theta, 2) . '</td>';
        echo '<td>' . array_sum($info->first) . '</td>';
        echo '<td>' . $info->count . '</td>';
        echo '<td>' . $info->corrects . '</td>';
        echo '<td>' . $info->wrongs . '</td>';
        echo '<td>' . round(100 * $info->corrects / ($info->corrects + $info->wrongs)) . '%</td>';
        echo '</tr>';
        $lines[] = ($i + 1).";$info->mmogameid;$info->numgame;$info->auserid;".$theta[$i];
        $i++;
    }
    echo '</tbody>';
    echo '</table>';

    file_put_contents( "theta.csv", implode("\n", $lines) );
}

/**
 * Reads data from SPSS.
 *
 * @param mmogame $mmogame
 * @param int $numitems
 * @param ?array $responses
 * @param ?array $mapqueries
 * @param ?array $mapusers
 * @param string $kind
 * @return array
 * @throws dml_exception
 */
function mmogame_quiz_manage_irt_read(mmogame $mmogame, int $numitems, ?array &$responses,
                ?array &$mapqueries, ?array &$mapusers, string $kind): array {
    global $DB;

    $responses = [];
    $where = [];
    $where[] = " a.mmogameid=".$mmogame->get_id()." AND a.numgame=".$mmogame->get_numgame();
    $where = count( $where) == 0 ? '' : " AND (".implode( " OR ", $where) . ")";
    $sql = "SELECT a.id, a.mmogameid, a.numgame, a.auserid, a.queryid, a.numquery, q.id as questionid,q.name,
        a.score, a.iscorrect, i.difficulty, g.theta
        FROM {question_bank_entries} qbe, {question} q, {question_versions} qv, {mmogame_quiz_attempts} a
        LEFT JOIN {mmogame_am_aduel_pairs} AS ad ON a.numteam = ad.id
        LEFT JOIN {mmogame_aa_irt} i ON i.mmogameid=a.mmogameid AND i.numgame=a.numgame AND i.queryid=a.queryid
        LEFT JOIN {mmogame_aa_grades} g ON g.mmogameid=a.mmogameid AND g.numgame=a.numgame AND g.auserid=a.auserid
        LEFT JOIN {mmogame_aa_stats} s ON s.mmogameid=a.mmogameid AND s.numgame=a.numgame
            AND s.queryid=a.queryid AND s.auserid=a.auserid
        WHERE qbe.id=qv.questionbankentryid AND qv.questionid=q.id AND qbe.id= a.queryid
        AND a.timeanswer > 0
        AND qv.version = (
                SELECT MAX(subqv.version)
                FROM {question_versions} subqv
                WHERE subqv.questionbankentryid = qv.questionbankentryid
            ) $where
        ORDER BY a.mmogameid, a.numgame, a.auserid, a.queryid, a.id";
    $recs = $DB->get_records_sql($sql);
    $mapfirst = [];
    $mapqueries = [];
    $mapusers = [];
    foreach ($recs as $rec) {
        if ($rec->name === null) {
            continue;
        }

        if (!array_key_exists( $rec->queryid, $mapqueries)) {
            $question = $DB->get_record_select('question', 'id=?', [$rec->questionid]);

            $infoq = new stdClass;
            $infoq->queryid = $rec->queryid;
            $infoq->questiontext = $question->questiontext;
            $infoq->difficulty = $rec->difficulty;
            $mapqueries[$rec->queryid] = $infoq;
        }

        $key = "{$rec->mmogameid}_{$rec->numgame}_{$rec->auserid}";
        if (array_key_exists($key, $mapusers)) {
            $info = &$mapusers[$key];
        } else {
            $info = new stdClass();
            $info->mmogameid = $rec->mmogameid;
            $info->numgame = $rec->numgame;
            $info->auserid = $rec->auserid;
            $info->theta = $rec->theta;
            $info->corrects = $info->wrongs = $info->count = 0;
            $mapusers[$key] = $info;
            $info->first = [];
            for ($i = 0; $i < $numitems; $i++) {
                $info->first[] = null;
                $info->last[] = null;
            }
        }
        if ($rec->iscorrect == 0) {
            $info->wrongs++;
            $info->count++;
        } else if ($rec->iscorrect == 1) {
            $info->count++;
            $info->corrects++;
        }

        $info->last[$rec->queryid] = $rec->iscorrect;

        $key = "{$rec->mmogameid}_{$rec->numgame}_{$rec->auserid}_{$rec->queryid}";
        if (!array_key_exists( $key, $mapfirst)) {
            $mapfirst[$key] = 1;
            $info->first[$rec->queryid] = $rec->iscorrect;
        }
    }

    foreach ($mapusers as $info) {
        $responses[] = $info->first;
    }

    $lines = $header = [];
    for ($i = 1; $i <= $numitems; $i++) {
        $header[] = 'query'.$i;
    }
    $lines[] = implode( ';', $header);
    foreach ($responses as $response) {
        $lines[] = implode( ';', $response);
    }
    file_put_contents( "data.csv", implode( "\n", $lines));
    return $mapusers;
}

/**
 * For admin form has the code for prev/next numgame and prev/next state.
 *
 * @param mmogame $mmogame
 **/
function mmogame_quiz_manage_submit(mmogame $mmogame): void {
    $state = $mmogame->get_state();
    $numgame = $mmogame->get_numgame();

    $changestate = $changenumgame = false;
    if (array_key_exists( 'prevstate', $_POST) && $state > 0) {
        $state--;
        $changestate = true;
    } else if (array_key_exists( 'nextstate', $_POST) && $state < 1) {
        $state++;
        $changestate = true;
    }

    if (array_key_exists( 'prevnumgame', $_POST) && $numgame > 0) {
        $numgame--;
        $changenumgame = true;
    } else if (array_key_exists( 'nextnumgame', $_POST)) {
        $numgame++;
        $changenumgame = true;
    }

    $data = new stdClass();
    if ($changestate) {
        $data->state = $state;
    }
    if ($changenumgame) {
        $data->numgame = $numgame;
    }
    $model = $mmogame->get_model();
    if (substr( $model, '-5') == 'split' && $model != 'split') {
        $model = substr( $model, 0, strlen($model) - 5);
    }
    if ($model == 'split') {
        $model = 'alone';
    }
    $class = "mod_mmogame\local\model\mmogame_model_".$model;

    $class::setadmin( $data, $mmogame);
}
