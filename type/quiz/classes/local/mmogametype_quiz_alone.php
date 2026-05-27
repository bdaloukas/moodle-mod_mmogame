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
 * Alone quiz game type implementation for MMOGame.
 *
 * @package    mmogametype_quiz
 * @copyright  2024 Vasilis Daloukas
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mmogametype_quiz\local;

use coding_exception;
use dml_exception;
use stdClass;

/**
 * Handles single-player quiz attempts, scoring and high score data.
 */
class mmogametype_quiz_alone extends mmogametype_quiz {
    /**
     * Tries to find an attempt of open games, otherwise creates a new attempt.
     *
     * @return ?stdClass (a new attempt of false if no attempt)
     * @throws coding_exception
     * @throws dml_exception
     */
    public function get_attempt(): ?stdClass {
        if ($this->rstate->state != MMOGAME_QUIZ_STATE_PLAY) {
            return null;
        }

        $attempts = $this->db->get_records_select(
            'mmogame_quiz_attempts',
            'mmogameid=? AND numgame=? AND auserid=? AND timeanswer=0',
            [$this->rgame->id, $this->rgame->numgame, $this->get_auserid()],
            'timestart DESC',
            '*',
            0,
            1
        );

        if (count($attempts) > 0) {
            $attempt = reset($attempts);
            if ($attempt->timestart == 0) {
                // Not started, so update timestart.
                $attempt->timestart = time();
                $this->db->update_record(
                    'mmogame_quiz_attempts',
                    ['id' => $attempt->id, 'timestart' => $attempt->timestart]
                );
            }
            return $attempt;
        }

        $a = $this->qbank->get_attempt_new(
            1,
            $this->compute_next_numattempt('mmogame_quiz_attempts', $this->get_auserid())
        );
        if ($a === null) {
            $this->set_errorcode('no_queries');
            return null;
        }
        $queryid = $a['queries'][0];
        unset($a['queries']);
        $a['queryid'] = $queryid;
        $a['layout'] = $this->qbank->get_layout_queryid($queryid);
        $a['timestart'] = time();
        $a['attemptkey'] = $this->createkey();

        // Insert data to mmogame_quiz_attempts table.
        $id = $this->db->insert_record('mmogame_quiz_attempts', $a);
        return $this->db->get_record_select('mmogame_quiz_attempts', 'id=?', [$id]);
    }

    /**
     * Processes the user's answer for a quiz question, with optional auto-grading and statistics updates.
     *
     * @param stdClass $attempt
     * @param stdClass $query
     * @param ?string $useranswer
     * @param array $ret (will contain all information)
     */
    public function set_answer(
        stdClass $attempt,
        stdClass $query,
        ?string $useranswer,
        array &$ret
    ): void {
        // Compute iscorrect.
        $fraction = 0.0;
        $attempt->iscorrect = $this->qbank->is_correct($query, $useranswer, $this, $fraction) ? 1 : 0;
        $time = time();
        $istimeout = ($attempt->timeclose > 0 && $time > $attempt->timeclose + 1);

        $a = ['id' => $attempt->id];

        if (!$istimeout) {
            if ($this->qbank->is_multichoice($query)) {
                // Handle multiple-choice answers.
                $a['useranswer'] = null;
                $a['useranswerid'] = $attempt->useranswerid = intval($useranswer);
            } else {
                // Handle other question types.
                $a['useranswer'] = $attempt->useranswer = $useranswer;
                $a['useranswerid'] = null;
            }
        }

        $attempt->timeanswer = $time;
        $a['timeanswer'] = $attempt->timeanswer;
        // Update the score based on the correctness of the answer.
        $a['grade'] = $attempt->grade = $this->get_score_query($attempt->iscorrect, $query);

        // Update the database record for the attempt (IRT only).
        $theta = $difficulty = null;
        $this->get_selection()->update($attempt->queryid, $attempt->iscorrect, $theta, $difficulty);
        $a['theta'] = $theta;
        $a['difficulty'] = $difficulty;

        // Update the database record for the attempt.
        $this->db->update_record('mmogame_quiz_attempts', $a);

        // Update statistics for the user and the question.
        $ret['addgrade'] = $attempt->grade >= 0 ? '+' . $attempt->grade : $attempt->grade;

        $addcountmastered = 0;
        $this->qbank->update_stats(
            null,
            $attempt->queryid,
            $attempt->iscorrect,
            0,
            $attempt->numattempt,
            $addcountmastered
        );

        $rgrade = $this->qbank->update_grades($attempt->auserid, $attempt->grade, $addcountmastered);
        $ret['grade'] = $rgrade->grade;
        $ret['countmastered'] = $rgrade->countmastered;
    }

    /**
     * Return the score of user's answer.
     *
     * @param bool $iscorrect
     * @param object $query
     * @return int (now uses negative grading, in the future user will change it)
     */
    protected function get_score_query(bool $iscorrect, object $query): int {
        return $this->get_score_query_negative($iscorrect, $query);
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

        // Analyzes data for users with the highest grade.
        $this->get_highscore_analyze('grade', 'rank1', $count, $map);

        // Analyzes data for users with the highest percent.
        $this->get_highscore_analyze('countmastered', 'rank2', $count, $map);

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
                $grade = $data->grade;
            } else if ($data->rank2 != 0 && $data->rank2 < $data->rank1) {
                $kind = 2;
                $rank = $data->rank2;
                $grade = $data->countmastered;
            } else if ($data->rank1 != 0 && $data->rank2 != 0 && $data->rank1 == $data->rank2) {
                $kind = 12;
                $rank = $data->rank1;
                $grade = $data->grade;
            } else {
                continue;
            }

            $output[] = [
                'kind' => $kind,
                'rank' => $rank,
                'grade' => $grade,
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
            WHERE mmogameid=? AND numgame=? AND grade > 0
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
                $data->grade = $data->countmastered = 0;
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
     * Do nothing on this mode.
     *
     * @param stdClass $attempt
     */
    public function set_attempt(stdClass $attempt): void {
    }

    /**
     * Updates the database and array $ret about the correctness of user's answer
     *
     * @param array $ret
     * @param ?string $attemptkey
     * @param ?string $answer
     * @param string $subcommand
     * @return ?stdClass: the attempt
     */
    public function set_answer_mode(array &$ret, ?string $attemptkey, ?string $answer, string $subcommand = ''): ?stdClass {
        // The session key is part of the lookup condition so answers can only be
        // saved for attempts owned by the current anonymous/user session.
        $attempt = $this->db->get_record_select(
            'mmogame_quiz_attempts',
            'mmogameid=? AND auserid=? AND attemptkey=?',
            [$this->get_id(), $this->auserid, $attemptkey]
        );
        if ($attempt === null) {
            // Invalid or expired attempt session.
            return null;
        }

        if (
            $attempt->auserid != $this->auserid || $attempt->mmogameid != $this->rgame->id
            || $attempt->numgame != $this->rgame->numgame
        ) {
            return null;
        }
        $this->set_attempt($attempt);

        $query = $this->qbank->load($attempt->queryid);
        if (isset($subcommand) && $subcommand == 'tool2') {
            $ret['tool2'] = 1;
        }
        $this->set_answer($attempt, $query, $answer, $ret);

        $ret['iscorrect'] = $attempt->iscorrect ? 1 : 0;
        $ret['correct'] = $query->concept;
        $ret['attempt'] = $attempt->id;
        $ret['atemptkey'] = $attempt->attemptkey;

        $info = $this->get_avatar_info($this->auserid);
        $ret['grade'] = $info->grade;
        $ret['rank'] = $this->get_rank($info->grade, 'grade');

        $ret['countmastered'] = $info->countmastered;
        $ret['rankmastered'] = $this->get_rank($info->countmastered, 'countmastered');
        $ret['countqueries'] = $this->get_rstate()->countqueries;

        return $attempt;
    }

    /**
     * Returns question types that uses (multichoice).
     * @return string[]
     */
    public function get_qtypes(): array {
        return ['multichoice'];
    }
}
