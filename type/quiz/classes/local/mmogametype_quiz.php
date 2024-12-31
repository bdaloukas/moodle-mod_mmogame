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

namespace mmogametype_quiz\local;

use mod_mmogame\local\database\mmogame_database;
use mod_mmogame\local\mmogame;

defined('MOODLE_INTERNAL') || die();

define( 'ERRORCODE_NO_QUERIES', 'no_questions');
define( 'ERRORCODE_ADUEL_NO_RIVALS', 'aduel_no_rivals');

require_once(dirname(__FILE__) . '/../../lib.php');

/**
 * mmogame_quiz is responsible for managing and facilitating quiz gameplay
 * within the mmogame system, including handling attempts, scoring,
 * and maintaining related user data.
 */
class mmogametype_quiz extends mmogame {
    /** @var bool $stopatend: stops at the end of this game. */
    protected bool $callupdategrades = true;

    /**
     * return the name of tabler attempts.
     */
    public static function get_table_attempts(): string {
        return 'mmogame_quiz_attempts';
    }

    /**
     * Creates a new attempt
     *
     * @param int $queryid
     * @param int $timelimit
     * @param int $numattempt
     * @param int $timeclose
     * @return false|object (the new attempt or false if no attempt)
     */
    protected function get_attempt_new_internal(int $queryid, int $timelimit, int $numattempt, int $timeclose) {
        $table = 'mmogame_quiz_attempts';
        if ($queryid === 0) {
            $query = null;
        } else {
            $query = $this->qbank->load( $queryid, true);
        }

        $a = $this->qbank->get_attempt_new( 1, true, $query);
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

        return $this->db->get_record_select( $table, 'id=?', [$id]);
    }

    /**
     * Saves to array $ret informations about the $attempt.
     *
     * @param array $ret (returns info about the current attempt)
     * @param bool|object $attempt
     * @param string $subcommand
     * @return false|object (the query of attempt)
     *
     */
    public function append_json(array &$ret, $attempt, string $subcommand = '') {
        $auserid = $this->get_auserid();

        $info = $this->get_avatar_info( $auserid);
        $ret['avatar'] = $info->avatar;
        $ret['nickname'] = $info->nickname;
        $ret['colors'] = implode( ',', $info->colors);
        $ret['fastjson'] = $this->rgame->fastjson;
        $ret['name'] = $this->rgame->name;
        $ret['state'] = $this->rstate->state;
        $ret['rank'] = $this->get_rank( $auserid, 'sumscore');
        $ret['sumscore'] = $info->sumscore;
        $ret['timefastjson'] = $this->rgame->timefastjson;
        $ret['percentcompleted'] = $info->percentcompleted;
        $ret['completedrank'] = $this->get_rank( $auserid, 'percentcompleted');

        if ($attempt === false) {
            $attempt = new \stdClass();
            $attempt->id = 0;
            $attempt->timestart = 0;
            $attempt->timeclose = 0;
            $attempt->queryid = 0;
            $attempt->useranswer = '';
        }
        $ret['attempt'] = $attempt->id;

        $recquery = false;

        if ($attempt->queryid != 0) {
            $recquery = $this->get_qbank()->load_json( $this, $ret, '', $attempt->queryid, $attempt->layout, false);
        }
        $ret['timestart'] = $attempt->timestart;
        $ret['timeclose'] = $attempt->timeclose;

        return $recquery;
    }

    /**
     * Return the score with negative values. If "n" is the number of answers, if it corrects returns (n-1) else returns (-1)
     *
     * @param bool $iscorrect
     * @param object $query
     * @return int
     */
    protected function get_score_query_negative(bool $iscorrect, object $query): int {
        if (!$this->qbank->is_multichoice( $query)) {
            return $iscorrect ? 1 : 0;
        }

        return $iscorrect ? count( $query->answers) - 1 : -1;
    }

    /**
     * Deletes info for a given mmogame and auser
     *
     * @param object $db
     * @param object $rgame
     * @param int $auserid
     */
    public static function delete_auser(object $db, object $rgame, int $auserid) {
        $db->delete_records_select( 'mmogame_quiz_attempts', 'mmogameid=? AND auserid=?', [$rgame->id, $auserid]);
    }
}
