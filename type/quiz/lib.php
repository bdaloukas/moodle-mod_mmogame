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
 * Library of functions and constants for submodule Quiz
 *
 * @package    mmogametype_quiz
 * @copyright  2024 Vasilis Daloukas
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use mod_mmogame\local\mmogame;

/**
 * Given an ID of an instance of this module, this function will permanently delete the instance and any data that depends on it.
 *
 * @param int $mmogameid (id of the module instance)
 * @throws dml_exception
 */
function mmogametype_quiz_delete_instance(int $mmogameid): void {
    global $DB;

    $a = ['mmogame_quiz_attempts'];
    foreach ($a as $table) {
        $DB->delete_records_select( $table, 'mmogameid=?', [$mmogameid]);
    }
}

/**
 * Actual implementation of the reset course functionality, delete all the game responses for course $data->courseid.
 *
 * @param object $data (the data submitted from the reset course).
 * @param string $ids (id of all mmogame that have to be deleted).
 * @throws coding_exception
 * @throws dml_exception
 */
function mmogametype_quiz_reset_userdata(object $data, string $ids): void {
    global $DB;

    $a = ['mmogame_quiz_attempts', 'mmogame_aa_grades'];

    [$insql, $inparams] = $DB->get_in_or_equal( explode(',', $ids));

    if (!empty($data->reset_mmogame_all)) {
        foreach ($a as $table) {
            $DB->delete_records_select($table, "mmogameid $insql", $inparams);
        }
    }

    if (!empty($data->reset_mmogame_deleted_course)) {
        foreach ($a as $table) {
            $sql = "DELETE FROM {".$table."} t WHERE NOT EXISTS ( SELECT * FROM {mmogame} g WHERE t.mmogameid=g.id)";
            $DB->execute($sql);
        }
    }
}

/**
 * Return the models that this sub-plugin implements.
 *
 * @return array (model => name)
 * @throws coding_exception
 */
function mmogametype_quiz_get_models(): array {
    return [
        'alone' => get_string( 'model_alone', 'mmogametype_quiz'),
        'aduel' => get_string( 'model_aduel', 'mmogametype_quiz'),
        'split' => get_string( 'model_split', 'mmogametype_quiz'),
    ];
}

/**
 * Add an "IRT" link to the Course navigation (left drawer) from the subplugin.
 *
 * @param navigation_node $parentnode Root node for the current course navigation.
 * @param stdClass $course Course record.
 * @param context_course $context Course context.
 * @throws \core\exception\moodle_exception
 * @throws coding_exception
 */
function mmogametype_quiz_extend_navigation_course( navigation_node $parentnode,  stdClass $course,
                                                    context_course $context): void {
    /* (1) Capability gate (προσαρμόσ’ το αν θέλεις να το βλέπουν και άλλοι ρόλοι)
    if (!has_capability('mmogametype_quiz:viewirt', $context)) {
           return;
    }*/

    // Destination : page IRT of subplugin (course-level).
    $url   = new moodle_url('/mod/mmogame/type/quiz/irt/index.php', ['courseid' => $course->id]);
    $label = get_string('menulabel_irt', 'mmogametype_quiz');
    $icon  = new pix_icon('i/report', '');

    // Make node.
    $node = navigation_node::create(
        $label,
        $url,
        navigation_node::TYPE_CUSTOM,
        null,
        'mmogametype_quiz_irt',
        $icon
    );
    // Helpful for some themes.
    $node->showinflatnavigation = true;
    $parentnode->add_node($node);
}

/**
 * New menu entry called IRT
 *
 * @param settings_navigation $settings
 * @param navigation_node $mmogamenode
 * @return void
 * @throws \core\exception\moodle_exception
 * @throws coding_exception
 * @throws dml_exception
 */
function mmogametype_quiz_extend_settings_navigation(settings_navigation $settings, navigation_node $mmogamenode): void {
    $reportlist = mmogame_report_list($settings->get_page()->cm->context);
    $url = new moodle_url('/mod/mmogame/irt.php',
        ['id' => $settings->get_page()->cm->id, 'mode' => reset($reportlist), 'compute' => 1]);
    $mmogamenode->add_node(navigation_node::create("IRT", $url,
        navigation_node::TYPE_SETTING,
        null, 'mmogame_irt', new pix_icon('i/report', '')));
}

/**
 * Reads data from Database.
 *
 * @param mmogame $mmogame
 * @param context $context
 * @param ?array $responses
 * @param ?array $mapqueries
 * @param ?array $mapusers
 * @return array
 * @throws coding_exception
 * @throws dml_exception
 */
function mmogametype_quiz_irt_read(mmogame $mmogame, context $context, ?array &$responses,
                                   ?array &$mapqueries, ?array &$mapusers): array {
    global $DB;

    $responses = [];
    $where = [];
    $where[] = " a.mmogameid=".$mmogame->get_id()." AND a.numgame=".$mmogame->get_numgame();
    $where = count( $where) == 0 ? '' : " AND (".implode( " OR ", $where) . ")";
    $sql = "SELECT a.id, a.mmogameid, a.numgame, a.auserid, a.queryid, a.numquery,
        a.score, a.iscorrect, i.difficulty, g.theta
        FROM {question_bank_entries} qbe, {question_versions} qv, {mmogame_quiz_attempts} a
        LEFT JOIN {mmogame_am_aduel_pairs} AS ad ON a.numteam = ad.id
        LEFT JOIN {mmogame_aa_irt} i ON i.mmogameid=a.mmogameid AND i.numgame=a.numgame AND i.queryid=a.queryid
        LEFT JOIN {mmogame_aa_grades} g ON g.mmogameid=a.mmogameid AND g.numgame=a.numgame AND g.auserid=a.auserid
        LEFT JOIN {mmogame_aa_stats} s ON s.mmogameid=a.mmogameid AND s.numgame=a.numgame
            AND s.queryid=a.queryid AND s.auserid=a.auserid
        WHERE qbe.id=qv.questionbankentryid AND qbe.id= a.queryid
        AND a.timeanswer > 0
        AND qv.version = (
                SELECT MAX(subqv.version)
                FROM {question_versions} subqv
                WHERE subqv.questionbankentryid = qv.questionbankentryid
            ) $where
        ORDER BY a.timeanswer, a.id";
    $recs = $DB->get_records_sql($sql);
    $mapqueries = [];
    $mapusers = [];
    foreach ($recs as $rec) {
        if (!array_key_exists( $rec->queryid, $mapqueries)) {
            $question = $DB->get_record_select('question', 'id=?', [$rec->queryid]);

            $infoq = new stdClass;
            $position = $infoq->position = count($mapqueries);
            $infoq->queryid = $rec->queryid;
            $infoq->name = $question->name;
            $infoq->querytext = format_text($question->questiontext ?? '',
                $question->questiontextformat ?? FORMAT_HTML, ['context' => $context]);
            $infoq->questiontextformat = $question->questiontextformat;
            $infoq->b_online = $rec->difficulty;
            $mapqueries[$rec->queryid] = $infoq;
        } else {
            $position = $mapqueries[$rec->queryid]->position;
        }

        $key = "{$rec->mmogameid}_{$rec->numgame}_{$rec->auserid}";
        if (array_key_exists($key, $mapusers)) {
            $info = $mapusers[$key];
        } else {
            $info = new stdClass();
            $info->mmogameid = $rec->mmogameid;
            $info->numgame = $rec->numgame;
            $info->auserid = $rec->auserid;
            $info->theta_online = $rec->theta;
            $info->corrects = $info->wrongs = $info->count = 0;
            $info->first = [];
            $mapusers[$key] = $info;
        }
        if ($rec->iscorrect == 0) {
            $info->wrongs++;
            $info->count++;
        } else if ($rec->iscorrect == 1) {
            $info->count++;
            $info->corrects++;
        }

        if (!array_key_exists( $position, $info->first)) {
            $info->first[$position] = $rec->iscorrect;
        }
    }

    $numitems = count( $mapqueries);
    $empty = [];
    for ($i = 0 ; $i < $numitems; $i++) {
        $empty[] = null;
    }
    foreach ($mapusers as $info) {
        $a = $empty;
        foreach ($info->first as $position => $value) {
            $a[$position] = $value;
        }
        $responses[] = $a;
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
