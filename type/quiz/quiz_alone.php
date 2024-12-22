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
 * mmogame_quiz_alone class
 *
 * @package    mmogametype_quiz
 * @copyright  2024 Vasilis Daloukas
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once(dirname(__FILE__) . '/quiz.php');

/**
 * The class mmogame_quiz_alone play the game Quiz (Alone).
 */
class mmogame_quiz_alone extends mmogame_quiz {
    /** @var bool $callupdategrades: true if it can call function updategrades(). */
    protected bool $callupdategrades;

    /**
     * Constructor.
     *
     * @param object $db (the database)
     * @param object $rgame (a record from table mmogame)
     */
    public function __construct(object $db, object $rgame) {
        $this->callupdategrades = true;

        parent::__construct($db, $rgame);
    }

    /**
     * Tries to find an attempt of open games, otherwise creates a new attempt.
     *
     * @return object (a new attempt of false if no attempt)
     */
    public function get_attempt(): object {
        $attempt = $this->db->get_record_select( 'mmogame_quiz_attempts',
            'mmogameid=? AND numgame=? AND auserid=? AND timeanswer=0',
            [$this->rgame->id, $this->rgame->numgame, $this->get_auserid()]);

        if ($attempt !== false) {
            if ($attempt->timestart == 0) {
                $attempt->timestart = time();
                $this->db->update_record( 'mmogame_quiz_attempts',
                    ['id' => $attempt->id, 'timestart' => $attempt->timestart]);
            }
            return $attempt;
        }

        return $this->get_attempt_new_internal(0, 0, 0, 0);
    }

    /**
     * Set the state of current game.
     *
     * @param int $state
     */
    public function set_state_json(int $state): void {
        $timefastjson = round( 10 * microtime( true));

        $statecontents = $state . "-" . $timefastjson;
        $filecontents = '';

        $this->save_state($state, $statecontents, $filecontents, $timefastjson);
    }

    /**
     * Saves informations about the user's answer.
     *
     * @param object $attempt
     * @param object $query
     * @param string $useranswer
     * @param bool $autograde
     * @param bool $submit
     * @param array $ret (will contain all information)
     * @return bool (is correct or not)
     */
    public function set_answer(object $attempt, object $query, string $useranswer, bool $autograde, bool $submit,
        array &$ret): bool {

        if ($autograde) {
            $fraction = 0.0;
            $attempt->iscorrect = $this->qbank->is_correct( $query, $useranswer, $this, $fraction);
        }

        $time = time();
        $istimeout = ($attempt->timeclose > 0 && $time > $attempt->timeclose + 1);

        $a = ['id' => $attempt->id];

        if (!$istimeout) {
            if ($this->qbank->is_multichoice( $query)) {
                if ($useranswer == null) {
                    $useranswer = '';
                }
                if (strpos( $useranswer, ',') === false) {
                    $answerid = $useranswer;
                    $attempt->useranswerid = $answerid;
                    $a['useranswerid'] = $attempt->useranswerid;
                }
            }
            $attempt->useranswer = $useranswer;
            $a['useranswer'] = $useranswer;
        }

        if ($submit) {
            $attempt->timeanswer = $time;
            $a['timeanswer'] = $attempt->timeanswer;
        }

        if ($submit && $autograde) {
            if ($this->callupdategrades) {
                $a['score'] = $attempt->score = $this->get_score_query( $attempt->iscorrect, $query);

                $this->qbank->update_grades( $attempt->auserid, $attempt->score, 0, 1);
                $ret['addscore'] = $attempt->score >= 0 ? '+'.$attempt->score : $attempt->score;

                // Update 3 statistics.
                $this->qbank->update_stats( $attempt->auserid, 0, $attempt->queryid, 0,
                    $attempt->iscorrect == 1 ? 1 : 0, $attempt->iscorrect == 0 ? 1 : 0);

                $sql = "SELECT COUNT(*) AS c ".
                    " FROM ".$this->get_db()->prefix."mmogame_aa_stats ".
                    " WHERE mmogameid=? AND numgame=? AND auserid=? AND NOT queryid IS NULL ".
                    " AND countcorrect >= 2 * counterror AND countcorrect > 0";
                $stat = $this->get_db()->get_record_sql(
                    $sql, [$this->rgame->id, $this->rgame->numgame, $attempt->auserid]);
                $values = ['countcompleted' => $stat->c];
                $this->qbank->update_stats( $attempt->auserid, 0, 0, 0,
                    $attempt->iscorrect == 1 ? 1 : 0, $attempt->iscorrect == 0 ? 1 : 0, $values);

                $this->qbank->update_stats( 0, 0,  $attempt->queryid, 0,
                    $attempt->iscorrect == 1 ? 1 : 0, $attempt->iscorrect == 0 ? 1 : 0);
            }
        }

        if ($autograde) {
            $a['iscorrect'] = $attempt->iscorrect;
            $this->db->update_record( 'mmogame_quiz_attempts', $a);
        }

        return $attempt->iscorrect;
    }

    /**
     * Return the score of user's answer.
     *
     * @param bool $iscorrect
     * @param object $query
     * @return int (now uses negative grading, in the future user will can change it)
     */
    protected function get_score_query(bool $iscorrect, object $query): int {
        return $this->get_score_query_negative( $iscorrect, $query);
    }

    /**
     * Fill the array $ret wirh information about high scores.
     *
     * @param int $count
     * @param array $ret
     */
    public function get_highscore(int $count, array &$ret): void {
        $recs = $this->db->get_records_select( 'mmogame_aa_grades', 'mmogameid=? AND numgame=? AND sumscore > 0',
            [$this->rgame->id, $this->rgame->numgame], 'sumscore DESC', '*', 0, $count);
        $map = [];
        $rank = 0;
        $prevscore = $prevrank = -1;
        foreach ($recs as $rec) {
            $data = new stdClass();
            $data->auserid = $rec->auserid;
            $data->score1 = $rec->sumscore;
            $data->rank1 = ++$rank;
            $data->nickname = $rec->nickname;
            $data->avatarid = $rec->avatarid;
            if ($data->score1 == $prevscore && $prevrank >= 0) {
                $data->rank1 = $prevrank;
            } else {
                $prevscore = $data->score1;
            }

            $data->rank2 = $data->score2 = 0;

            $map[$rec->auserid] = $data;
        }
        $recs = $this->db->get_records_select( 'mmogame_aa_grades', 'mmogameid=? AND numgame=? AND percentcompleted > 0',
            [$this->rgame->id, $this->rgame->numgame], 'percentcompleted DESC', '*', 0, $count);
        $prevscore = -1;
        $rank = 0;
        foreach ($recs as $rec) {
            if (array_key_exists( $rec->auserid, $map)) {
                $data = $map[$rec->auserid];
            } else {
                $data = new stdClass();
                $data->auserid = $rec->auserid;
                $data->rank1 = $data->score1 = 0;
                $data->nickname = $rec->nickname;
                $data->avatarid = $rec->avatarid;
            }
            $data->score2 = round( 100 * $rec->percentcompleted);
            if ($data->score2 > 100) {
                $data->score2 = 100;
            }
            $data->rank2 = ++$rank;
            if ($data->score2 == $prevscore && $prevrank >= 0) {
                $data->rank2 = $prevrank;
            } else {
                $prevscore = $data->score2;
            }

            $map[$rec->auserid] = $data;
        }
        $map2 = [];
        foreach ($map as $auserid => $data) {
            $key = sprintf( "%10d %10d", min($data->rank1, $data->rank2), $auserid);
            $map2[$key] = $data;
        }

        $ranks = $names = $avatars = [];
        foreach ($map2 as $key => $data) {
            if ($data->rank1 != 0 && $data->rank1 < $data->rank2) {
                $kinds[] = 1;
                $ranks[] = $data->rank1;
                $scores[] = $data->score1;
            } else if ($data->rank2 != 0 && $data->rank2 < $data->rank1) {
                $kinds[] = 2;
                $ranks[] = $data->rank2;
                $scores[] = $data->score2.' %';
            } else if ($data->rank1 != 0 && $data->rank2 != 0 && $data->rank1 == $data->rank2) {
                $kinds[] = 12;
                $ranks[] = $data->rank1;
                $scores[] = $data->score1.' - '.$data->score2.' %';
            } else {
                continue;
            }
            $names[] = $data->nickname;
            $rec = $this->db->get_record_select( 'mmogame_aa_avatars', 'id=?', [$data->avatarid]);
            $avatars[] = $rec !== false ? $rec->directory.'/'.$rec->filename : '';
        }
        $ret['count'] = count( $ranks);
        $ret['ranks'] = implode( '#', $ranks);
        $ret['names'] = implode( '#', $names);
        $ret['scores'] = implode( '#', $scores);
        $ret['kinds'] = implode( '#', $kinds);
        $ret['avatars'] = implode( '#', $avatars);
    }

    /**
     * Do nothing on this model.
     *
     * @param object $attempt
     */
    public function set_attempt(object $attempt) {

    }

    /**
     * Updates the database and array $ret about the correctness of user's answer
     *
     * @param object $data
     * @param array $ret
     * @return false|object: the attempt
     */
    public function set_answer_model(object $data, array &$ret) {
        if (!isset( $data->attempt) || $data->attempt == 0) {
            return false;
        }

        $attempt = $this->db->get_record_select( 'mmogame_quiz_attempts', 'mmogameid=? AND auserid=? AND id=?',
            [$this->get_id(), $this->auserid, $data->attempt]);
        if ($attempt === false) {
            return false;
        }

        if ($attempt->auserid != $this->auserid || $attempt->mmogameid != $this->rgame->id
        || $attempt->numgame != $this->rgame->numgame) {
            return false;
        }
        $this->set_attempt( $attempt);

        $autograde = true;
        $query = $this->qbank->load( $attempt->queryid);
        if (isset( $data->subcommand) && $data->subcommand == 'tool2') {
            $autograde = false;
            $ret['tool2'] = 1;
        }
        $iscorrect = $this->set_answer( $attempt, $query, $data->answer, $autograde, $data->submit != 0, $ret);

        $ret['iscorrect'] = $iscorrect ? 1 : 0;
        $ret['correct'] = $query->concept;
        $ret['submit'] = $data->submit;
        $ret['attempt'] = $attempt->id;

        $info = $this->get_avatar_info( $this->auserid);
        $ret['sumscore'] = $info->sumscore;
        $ret['nickname'] = $info->nickname;
        $ret['rank'] = $this->get_rank_alone( $this->auserid, 'sumscore');

        $ret['percentcompleted'] = $info->percentcompleted;
        $ret['completedrank'] = $this->get_rank_alone( $this->auserid, 'percentcompleted');

        return $attempt;
    }
}
