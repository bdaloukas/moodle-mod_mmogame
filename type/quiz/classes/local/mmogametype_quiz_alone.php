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
class mmogametype_quiz_alone extends mmogametype_quiz {
    /** @var bool $callupdategrades: true if it can call function updategrades(). */
    protected bool $callupdategrades;

    /**
     * Constructor.
     *
     * @param mmogame_database $db (the database)
     * @param stdClass $rgame (a record from table mmogame)
     */
    public function __construct(mmogame_database $db, stdClass $rgame) {
        $this->callupdategrades = true;
        parent::__construct($db, $rgame);
    }

    /**
     * Tries to find an attempt of open games, otherwise creates a new attempt.
     *
     * @return ?stdClass (a new attempt of false if no attempt)
     */
    public function get_attempt(): ?stdClass {
        $attempt = $this->db->get_record_select( 'mmogame_quiz_attempts',
            'mmogameid=? AND numgame=? AND auserid=? AND timeanswer=0',
            [$this->rgame->id, $this->rgame->numgame, $this->get_auserid()]);

        if ($attempt !== null) {
            if ($attempt->timestart == 0) {
                $attempt->timestart = time();
                $this->db->update_record( 'mmogame_quiz_attempts',
                    ['id' => $attempt->id, 'timestart' => $attempt->timestart]);
            }
            return $attempt;
        }

        $countquestions = $corrects = 0;
        $a = $this->qbank->get_attempt_new( 1, true, $countquestions, $corrects);
        if ($a === null) {
            $this->set_errorcode( ERRORCODE_NO_QUERIES);
            return null;
        }

        // Update field countquestions in table mmogame_aa_grades.
        $grade = $this->db->get_record_select( 'mmogame_aa_grades',
            'mmogameid=? AND numgame=? AND auserid=?',
            [$this->rgame->id, $this->rgame->numgame, $this->get_auserid()]);
        if ($grade !== null) {
            $this->db->update_record( 'mmogame_aa_grades',
                ['id' => $grade->id, 'countquestions' => $countquestions, 'percent' => $corrects / $countquestions]);
        }

        $a['numattempt'] = $this->compute_next_numattempt();
        $a['timestart'] = time();

        // Insert data to mmogame_quiz_attempts table.
        $id = $this->db->insert_record( 'mmogame_quiz_attempts', $a);
        return $this->db->get_record_select( 'mmogame_quiz_attempts', 'id=?', [$id]);
    }

    /**
     * Processes the user's answer for a quiz question, with optional auto-grading and statistics updates.
     *
     * @param stdClass $attempt
     * @param stdClass $query
     * @param ?string $useranswer
     * @param ?int $useranswerid
     * @param bool $autograde
     * @param array $ret (will contain all information)
     */
    public function set_answer(stdClass $attempt, stdClass $query, ?string $useranswer, ?int $useranswerid,
                               bool $autograde, array &$ret): void {
        // If auto-grading is enabled, check if the answer is correct and set iscorrect.
        if ($autograde) {
            $fraction = 0.0;
            $attempt->iscorrect = $this->qbank->is_correct( $query, $useranswer, $useranswerid, $this, $fraction);
            $attempt->iscorrect = $attempt->iscorrect ? 1 : 0;
        }

        $time = time();
        $istimeout = ($attempt->timeclose > 0 && $time > $attempt->timeclose + 1);

        $a = ['id' => $attempt->id];

        if (!$istimeout) {
            if ($this->qbank->is_multichoice( $query)) {
                // Handle multiple-choice answers.
                $a['useranswerid'] = $attempt->useranswerid = $useranswerid;
                $a['useranswer'] = null;
            } else {
                // Handle other question types.
                $a['useranswer'] = $attempt->useranswer = $useranswer;
                $a['useranswerid'] = null;
            }
        }

        $attempt->timeanswer = $time;
        $a['timeanswer'] = $attempt->timeanswer;

        if ($autograde) {
            if ($this->callupdategrades) {
                // Update the score based on the correctness of the answer.
                $a['score'] = $attempt->score = $this->get_score_query( $attempt->iscorrect, $query);

                // Updates the percent of completed questions.
                $sql = "SELECT s.islastcorrect, g.countquestions, g.percent,g.id as gradeid, s.id as statid
                    FROM {mmogame_aa_grades} g, {mmogame_aa_stats} s
                    WHERE g.mmogameid=? AND g.numgame = ? AND g.auserid=? AND s.queryid=?
                        AND s.mmogameid=g.mmogameid AND s.numgame = g.numgame AND s.auserid=g.auserid";
                $stat = $this->db->get_record_sql( $sql,
                    [$attempt->mmogameid, $attempt->numgame, $attempt->auserid, $attempt->queryid]);
                if ($stat->countquestions > 0) {
                    $mul = 0;
                    if ($attempt->iscorrect && $stat->islastcorrect == 0) {
                        $mul = 1;
                    } else if ($attempt->iscorrect == 0 && $stat->islastcorrect) {
                        $mul = -1;
                    }
                    if ($mul !== 0) {
                        $this->db->update_record( 'mmogame_aa_grades',
                            ['id' => $stat->gradeid, 'percent' => $stat->percent + $mul / $stat->countquestions]);
                    }
                }

                // Update statistics for the user and the question.
                $this->qbank->update_grades( $attempt->auserid, $attempt->score, 0, 1);
                $ret['addscore'] = $attempt->score >= 0 ? '+'.$attempt->score : $attempt->score;

                $this->qbank->update_stats( $attempt->auserid, null, $attempt->queryid, 0,
                    $attempt->iscorrect == 1 ? 1 : 0, $attempt->iscorrect == 0 ? 1 : 0);

                $this->qbank->update_stats( null, null, $attempt->queryid, 0,
                    $attempt->iscorrect == 1 ? 1 : 0, $attempt->iscorrect == 0 ? 1 : 0);
            }
        }

        // Update the database record for the attempt.
        if ($autograde) {
            $a['iscorrect'] = $attempt->iscorrect;
            $this->db->update_record( 'mmogame_quiz_attempts', $a);
        }
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

        // Analyzes data for users with the highest percent.
        $this->get_highscore_analyze('percent', 'rank2', $count, $map);

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
                $score = $data->sumscore;
            } else if ($data->rank2 != 0 && $data->rank2 < $data->rank1) {
                $kind = 2;
                $rank = $data->rank2;
                $score = $data->percentcompleted . ' %';
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
            ORDER BY $scorekey DESC";
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
                $data->sumscore = $data->percentcompleted = 0;
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
     * @param stdClass $attempt
     */
    public function set_attempt(stdClass $attempt): void {

    }

    /**
     * Updates the database and array $ret about the correctness of user's answer
     *
     * @param array $ret
     * @param ?int $attemptid
     * @param ?string $answer
     * @param ?int $answerid
     * @param string $subcommand
     * @return ?stdClass: the attempt
     */
    public function set_answer_model(array &$ret, ?int $attemptid, ?string $answer, ?int $answerid = null,
                                     string $subcommand = ''): ?stdClass {
        if ($attemptid === null) {
            return null;
        }

        $attempt = $this->db->get_record_select( 'mmogame_quiz_attempts', 'mmogameid=? AND auserid=? AND id=?',
            [$this->get_id(), $this->auserid, $attemptid]);
        if ($attempt === null) {
            return null;
        }

        if ($attempt->auserid != $this->auserid || $attempt->mmogameid != $this->rgame->id
        || $attempt->numgame != $this->rgame->numgame) {
            return null;
        }
        $this->set_attempt( $attempt);

        $autograde = true;
        $query = $this->qbank->load( $attempt->queryid);
        if (isset( $subcommand) && $subcommand == 'tool2') {
            $autograde = false;
            $ret['tool2'] = 1;
        }
        $this->set_answer( $attempt, $query, $answer, $answerid, $autograde, $ret);

        $ret['iscorrect'] = $attempt->iscorrect ? 1 : 0;
        $ret['correct'] = $query->concept;
        $ret['attempt'] = $attempt->id;

        $info = $this->get_avatar_info( $this->auserid);
        $ret['sumscore'] = $info->sumscore;
        $ret['rank'] = $this->get_rank( $info->sumscore, 'sumscore');

        $ret['percent'] = $info->percent;
        $ret['percentrank'] = $this->get_rank( $info->percent, 'percent');

        return $attempt;
    }
}
