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
 * mmogamekind_quiz class
 *
 * @package    mmogametype_quiz
 * @copyright  2024 Vasilis Daloukas
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

define( 'ERRORCODE_NO_QUERIES', 'no_questions');
define( 'ERRORCODE_ADUEL_NO_RIVALS', 'aduel_no_rivals');

require_once(dirname(__FILE__) . '/lib.php');
require_once(dirname(__FILE__) . '/../../locallib.php');

/**
 * The class mmogame_quiz play the game Quiz.
 */
class mmogame_quiz extends mmogame {
    /** @var stopatend: stops at the end of this game. */
    protected $stopatend = false;
    /** @var callupdategrades: call updategrades. */
    protected $callupdategrades = true;

    /**
     * Constructor.
     *
     * @param object $db (the database)
     * @param object $rgame (a record from table mmogame)
     * @param object $rinstance (a record from table mmogame_aa_instances)
     */
    public function __construct($db, $rgame, $rinstance) {
        parent::__construct( $db, $rgame, $rinstance);

        if (isset( $rinstance->typeparams)) {
            $params = json_decode( $rinstance->typeparams);
            if (isset( $params->useshortanswer)) {
                $rgame->useshortanswer = $params->useshortanswer;
            }
        }
    }

    /**
     * Constructor.
     *
     * @param object $db (the database)
     * @param object $rgame (a record from table mmogame)
     * @param object $rinstance (a record from table mmogame_aa_instances)
     */
    public static function get_new($db, $rgame, $rinstance) {
        $function = 'mmogame_quiz_'.($rinstance->model == '' ? 'alone' : $rinstance->model);

        return new $function( $db, $rgame, $rinstance, false);
    }

    /**
     * return the name of attempts table.
     */
    public static function get_table_attempts() {
        return 'mmogame_quiz_attempts';
    }

    /**
     * Creates a new attempt
     *
     * @param int $queryid
     * @param int $timelimit
     * @param int $numattempt
     * @param int $timeclose
     * @return object (the new attempt or false if no attempt)
     */
    protected function get_attempt_new_internal($queryid, $timelimit, $numattempt, $timeclose) {
        $table = 'mmogame_quiz_attempts';
        if ($queryid === null || $queryid === false) {
            $query = false;
        } else {
            $query = $this->qbank->load( $queryid, true);
        }

        $a = $this->qbank->get_attempt_new( $this->get_auserid(), 1, $this->stopatend, true, $query);
        if ($a === false) {
            $this->set_errorcode( ERRORCODE_NO_QUERIES);
            return false;
        }

        $a['numattempt'] = $numattempt == 0 ? $this->compute_next_numattempt() : $numattempt;
        $a['timestart'] = time();
        if ($timeclose != 0) {
            $a['timeclose'] = $timeclose;
        } else if ($timelimit != 0) {
            $a['timelimit'] = $timelimit;
            $a['timestart'] = $a['timestart'] + $timelimit;
        }
        $id = $this->db->insert_record( $table, $a);

        $attempt = $this->db->get_record_select( $table, 'id=?', [$id]);

        return $attempt;
    }

    /**
     * Saves to array $ret informations about the $attempt.
     *
     * @param array &$ret (returns info about the current attempt)
     * @param object $attempt
     * @param object $data
     * @return object (the query of attempt)
     */
    public function append_json(&$ret, $attempt, $data) {
        $rinstance = $this->get_rinstance();

        $auserid = $this->get_auserid();

        $info = $this->get_avatar_info( $auserid);
        $ret['auserid'] = $info->auserid;
        $ret['avatar'] = $info->avatar;
        $ret['nickname'] = $info->nickname;
        $ret['colors'] = implode( ',', $info->colors);
        $ret['usercode'] = $info->usercode;

        $ret['fastjson'] = $rinstance->fastjson;
        $ret['name'] = $rinstance->name;
        $ret['state'] = $this->rstate->state;
        $ret['rank'] = $this->get_rank_alone( $auserid, 'sumscore');
        $ret['sumscore'] = $info->sumscore;
        $ret['timefastjson'] = $rinstance->timefastjson;

        $ret['percentcompleted'] = $info->percentcompleted;
        $ret['completedrank'] = $this->get_rank_alone( $auserid, 'percentcompleted');

        $maxwidth = isset( $data->maxwidth) ? $data->maxwidth : 0;
        $maxheight = isset( $data->maxheight) ? $data->maxheight : 0;
        if ($attempt === false) {
            $attempt = new stdClass();
            $attempt->id = 0;
            $attempt->timestart = 0;
            $attempt->timeclose = 0;
            $attempt->queryid = 0;
            $attempt->useranswer = '';
        }
        $ret['attempt'] = $attempt->id;

        $recquery = false;

        if ($attempt->queryid != 0) {
            $recquery = $this->get_qbank()->load_json( $this, $ret, '', $attempt->queryid, $attempt->layout,
            $files, false, $filewidth, $fileheight, $maxwidth, $maxheight);
        }
        $ret['timestart'] = $attempt->timestart;
        $ret['timeclose'] = $attempt->timeclose;

        return $recquery;
    }

    /**
     * Return the score with negative values. If "n" is the number of answer, if it correct returns (n-1) else returns (-1)
     *
     * @param boolean iscorrect
     * @param object $query
     * @return int
     */
    protected function get_score_query_negative($iscorrect, $query) {
        if (!$this->qbank->is_multichoice( $query)) {
            return $iscorrect ? 1 : 0;
        }

        return $iscorrect ? count( $query->answers) - 1 : -1;
    }
}
