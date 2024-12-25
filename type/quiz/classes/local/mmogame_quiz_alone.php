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

namespace mmogametype_quiz\local;

use mod_mmogame\local\database\mmogame_database;
use stdClass;

/**
 * The class mmogame_quiz_alone play the game Quiz (Alone).
 */
class mmogame_quiz_alone extends mmogame_quiz {
    /** @var bool $callupdategrades: true if it can call function updategrades(). */
    protected bool $callupdategrades;

    /**
     * Constructor.
     *
     * @param mmogame_database $db (the database)
     * @param object $rgame (a record from table mmogame)
     */
    public function __construct(mmogame_database $db, object $rgame) {
        $this->callupdategrades = true;
        parent::__construct($db, $rgame);
    }

    /**
     * Tries to find an attempt of open games, otherwise creates a new attempt.
     *
     * @return false|object (a new attempt of false if no attempt)
     */
    public function get_attempt() {
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
     * Set the state of the current game.
     *
     * @param int $state
     */
    public function set_state(int $state): void {
        $timefastjson = round( 10 * microtime( true));

        $statecontents = $state . "-" . $timefastjson;
        $filecontents = '';

        $this->save_state($state, $statecontents, $filecontents, $timefastjson);
    }

    /**
     * Saves information about the user's answer.
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
                $a['useranswerid'] = $attempt->useranswerid = $useranswer;
            }
            $a['useranswer'] = $attempt->useranswer = $useranswer;
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
                    " FROM {mmogame_aa_stats} ".
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
     * @return int (now uses negative grading, in the future user will change it)
     */
    protected function get_score_query(bool $iscorrect, object $query): int {
        return $this->get_score_query_negative( $iscorrect, $query);
    }

    /**
     * Fill the array $ret with information about high scores.
     *
     * @param int $count
     * @param array $ret
     */
    public function get_highscore(int $count, array &$ret): void {

        // Ensure the count is positive.
        if ($count <= 0) {
            $count = 1;
        }

        // Initialize the map for processing results.
        $map = [];

        // Analyzes data for users with the highest sumscore.
        $this->get_highscore_analyze('sumscore', 'rank1', $count, $map);

        // Analyzes data for users with the highest percentcompleted.
        $this->get_highscore_analyze('percentcompleted', 'rank2', $count, $map);

        // Merge the two rankings into a unified map.
        $map2 = [];
        foreach ($map as $auserid => $data) {
            $key = sprintf("%10d %10d", min($data->rank1, $data->rank2), $auserid);
            $map2[$key] = $data;
        }
        ksort($map2);

        // Prepare the final output.
        $output = [];
        foreach ($map2 as $data) {
            if ($data->rank1 != 0 && $data->rank1 < $data->rank2) {
                $kind = 1;
                $rank = $data->rank1;
                $score = $data->score1;
            } else if ($data->rank2 != 0 && $data->rank2 < $data->rank1) {
                $kind = 2;
                $rank = $data->rank2;
                $score = $data->score2 . ' %';
            } else if ($data->rank1 != 0 && $data->rank2 != 0 && $data->rank1 == $data->rank2) {
                $kind = 12;
                $rank = $data->rank1;
                $score = $data->sumscore . ' - ' . round($data->percentcompleted / 100) . ' %';
            } else {
                continue;
            }

            $output[] = [
                'kind' => $kind,
                'rank' => $rank,
                'score' => $score,
                'name' => $data->nickname,
                'avatar' => $data->avatar,
            ];
        }

        // Return results.
        $ret['count'] = count($output);
        $ret['results'] = json_encode($output);
    }

    /**
     * Analyzes data based on $score_key and $rank_key
     * *
     * @param string $scorekey
     * @param string $rankkey
     * @param int $count
     * @param array $map
     * @return void
     */
    private function get_highscore_analyze(string $scorekey, string $rankkey, int $count, array &$map): void {
        // Fetch records from the database based on the given order criteria.
        $sql = "SELECT ag.*, av.directory, av.filename
            FROM {mmogame_aa_grades} ag
            LEFT JOIN {mmogame_aa_avatars} av ON av.id=ag.avatarid
            WHERE mmogameid=? AND numgame=? AND sumscore > 0
            ORDER BY {$scorekey} DESC";
        $recs = $this->db->get_records_sql($sql, [$this->rgame->id, $this->rgame->numgame], 0, $count);

        // Process rankings for the given records and update the map.
        $score = -1;
        $rank = 0;

        foreach ($recs as $rec) {
            // Check if the user already exists in the map.
            if (array_key_exists($rec->auserid, $map)) {
                $data = $map[$rec->auserid];
            } else {
                $data = new stdClass();
                $data->auserid = $rec->auserid;
                $data->nickname = $rec->nickname;
                $data->avatar = $rec->directory . '/' . $rec->filename;
                $data->rank1 = $data->rank2 = 0;
                $data->sumscore = $data->percentcopleted = 0;
            }

            // Calculate the score and cap it to the maximum score if necessary.
            $data->$scorekey = $rec->$scorekey;
            $data->$rankkey = ++$rank;

            // Handle tied scores by reusing the previous rank.
            if ($data->$scorekey == $score) {
                $data->$rankkey = $rank;
            } else {
                $score = $data->$rankkey;
                $rank = $data->$rankkey;
            }

            // Update the map with the new data.
            $map[$rec->auserid] = $data;
        }
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
        $ret['rank'] = $this->get_rank( $this->auserid, 'sumscore');

        $ret['percentcompleted'] = $info->percentcompleted;
        $ret['completedrank'] = $this->get_rank( $this->auserid, 'percentcompleted');

        return $attempt;
    }
}
