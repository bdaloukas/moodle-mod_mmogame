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
 * mmogametype_quiz_split class
 *
 * @package    mmogametype_quiz
 * @copyright  2025 Vasilis Daloukas
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mmogametype_quiz\local;

use coding_exception;
use dml_exception;
use mod_mmogame\local\database\mmogame_database;
use mod_mmogame\local\mmogame;
use mod_mmogame\local\mode\mmogame_mode_aduel;
use stdClass;

/**
 * mmogametype_quiz_split is responsible for managing and facilitating quiz gameplay
 * within the mmogame system, including handling attempts, scoring,
 * and maintaining related user data.
 */
class mmogametype_quiz_split extends mmogame {
    /** @var int $numqueries: number of queries that contain one group of queries. */
    protected int $numqueries = 4;

    /** @var int $maxalone: maximum number of questions that a user can play withoyt an oponent. */
    protected int $maxalone = -1;

    /**
     * Constructor.
     *
     * @param mmogame_database $db (the database)
     * @param stdClass $rgame (a record from table mmogame)
     */
    public function __construct(mmogame_database $db, stdClass $rgame) {
        $rgame->usemultichoice = true;

        parent::__construct($db, $rgame);

        $this->timelimit = 300;
    }

    /**
     * Saves to array $ret information about the $attempt.
     *
     * @param array $ret (returns info about the current attempt)
     * @param ?stdClass $attempt
     * @param string $subcommand
     * @return ?stdClass
     */
    public function append_json(array &$ret, ?stdClass $attempt, string $subcommand = ''): ?stdClass {
        return null;
    }

    /**
     * Returns the attempts.
     *
     * @param array $ausers
     * @return ?array  [array of attempts, array of countmastered]
     * @throws coding_exception
     * @throws dml_exception
     */
    public function get_attempts(array $ausers): ?array {
        $ids = null;

        $ret = $queryranks = $countmastered = [];
        foreach ($ausers as $auser) {
            $this->login_user($auser);
            $newplayer1 = $newplayer2 = false;

            $aduel = mmogame_mode_aduel::get_aduel($this, $this->maxalone, $newplayer1, $newplayer2, false);
            if ($aduel === null) {
                $this->set_errorcode('no_rivals');
                return null;
            }

            $rgrade = $this->get_rgrade($auser->id);
            $countmastered[] = $rgrade->countmastered !== null ? $rgrade->countmastered : 0;

            if (!$newplayer1) {
                // Continue previous attempt.
                // Reads attempts from database.
                $recs = mmogame_mode_aduel::get_attempts($this, $aduel);
                if (count($recs) > 0) {
                    $ret[] = $recs;
                    $this->compute_queryranks($recs, $queryranks);
                    continue;
                } else {
                    // Have to repair.
                    mmogame_mode_aduel::close_user1($this, $aduel->id, $rgrade);
                    $newplayer1 = true;
                }
            }

            if ($ids === null) {
                // Get the ids of all the queries.
                $ids = $this->qbank->get_queries_ids();
                if ($ids === null || count($ids) == 0) {
                    return null;
                }
            }

            if ($newplayer1) {
                $ret[] = $this->get_attempts_new1($ids, $aduel);
            }
        }
        return [$ret, $countmastered];
    }

    /**
     * Deletes an attempt from database.
     *
     * @param int $attemptid
     */
    public function delete_attempt(int $attemptid): void {
        $this->db->delete_records_select('mmogame_quiz_attempts', 'id=?', [$attemptid]);
    }

    /**
     * Creates a new attempt for the first player. Also select which question will be contained in the attempt.
     *
     * @param array $ids
     * @param stdClass $aduel
     * @return ?array (a new attempt of false if no attempt)
     */
    protected function get_attempts_new1(array $ids, stdClass $aduel): ?array {
        $numattempt = $this->compute_next_numattempt('mmogame_quiz_attempts', $aduel->auserid1);

        $queries = $this->selection->get_queries($ids, $this->numqueries, $numattempt);

        if (count($queries) === 0) {
            return null;
        }
        $a = $ids = [];
        foreach ($queries as $queryid) {
            $a['mmogameid'] = $this->rgame->id;
            $a['numgame'] = $this->rgame->numgame;
            $a['numteam'] = $aduel->id;
            $a['auserid'] = $this->auserid;
            $a['numattempt'] = $numattempt++;
            $a['queryid'] = $queryid;
            $a['timestart'] = count($ids) == 0 ? time() : 0;  // Only the first starts.
            $a['timeanswer'] = 0;
            $a['timeclose'] = 0;
            $a['attemptkey'] = mmogame::createkey();
            $ids[] = $this->db->insert_record('mmogame_quiz_attempts', $a);
        }

        [$insql, $inparams] = $this->db->get_in_or_equal($ids);
        $sql = "SELECT * FROM {mmogame_quiz_attempts} WHERE id $insql ORDER BY id";
        return $this->db->get_records_sql($sql, $inparams);
    }

    /**
     * Returns question types that uses (multichoice).
     * @return string[]
     */
    public function get_qtypes(): array {
        return ['multichoice'];
    }

    /**
     * Set answer mode.
     *
     * @param array $ret
     * @param ?string $attemptkey
     * @param ?string $answer
     * @param int $timestart
     * @param int $timeanswer
     * @param ?int $answerid
     * @param ?string $subcommand
     * @return ?stdClass
     */
    public function set_answer_mode(
        array &$ret,
        ?string $attemptkey,
        ?string $answer,
        int $timestart,
        int $timeanswer,
        ?int $answerid = null,
        ?string $subcommand = ''
    ): ?stdClass {

        // The session key is part of the lookup condition so answers can only be
        // saved for attempts owned by the current anonymous/user session.
        $attempt = $this->db->get_record_select(
            'mmogame_quiz_attempts',
            'mmogameid=? AND auserid=? AND attemptkey=?',
            [$this->get_id(), $this->auserid, $attemptkey]
        );
        if ($attempt === null) {
            // Invalid or expired game session.
            return null;
        }

        if (
            $attempt->auserid != $this->auserid || $attempt->mmogameid != $this->rgame->id
            || $attempt->numgame != $this->rgame->numgame
        ) {
            return null;
        }
        $aduel = $this->db->get_record_select('mmogame_am_aduel_pairs', 'id=?', [$attempt->numteam]);

        $query = $this->qbank->load($attempt->queryid);
        if (isset($subcommand) && $subcommand == 'tool2') {
            $ret['tool2'] = 1;
        }

        $this->set_answer(
            $attempt,
            $query,
            $answer,
            $timestart,
            $timeanswer,
            $answerid,
            0,
            $ret,
            $aduel
        );

        $ret['iscorrect'] = $attempt->iscorrect ? 1 : 0;

        return $attempt;
    }

    /**
     * Processes the user's answer for a quiz question, with optional auto-grading and statistics updates.
     *
     * @param stdClass $attempt
     * @param stdClass $query
     * @param ?string $useranswer
     * @param int $timestart
     * @param int $timeanswer
     * @param ?int $useranswerid
     * @param int $tools
     * @param array $ret (will contain all information)
     */
    public function set_answer_alone(
        stdClass $attempt,
        stdClass $query,
        ?string $useranswer,
        int $timestart,
        int $timeanswer,
        ?int $useranswerid,
        int $tools,
        array &$ret
    ): void {
        // Check if the answer is correct and set iscorrect.
        $fraction = 0.0;
        $attempt->iscorrect = $this->qbank->is_correct($query, $useranswer, $this, $fraction) ? 1 : 0;

        $a = ['id' => $attempt->id];

        if ($this->qbank->is_multichoice($query)) {
            // Handle multiple-choice answers.
            $a['useranswerid'] = $attempt->useranswerid = $useranswerid;
            $a['useranswer'] = null;
        } else {
            // Handle other question types.
            $a['useranswer'] = $attempt->useranswer = $useranswer;
            $a['useranswerid'] = null;
        }

        $a['timestart'] = $timestart;
        $a['timeanswer'] = $attempt->timeanswer = $timeanswer;

        // Update the score based on the correctness of the answer.
        $a['grade'] = $attempt->grade = $this->get_grade_query($attempt->iscorrect, $query);

        // Update statistics for the user and the question.
        $addnextattempt = $this->selection->compute_addnextattempt($attempt->queryid, $attempt->iscorrect);
        $addcountmastered = 0;
        $this->qbank->update_stats(
            null,
            $attempt->queryid,
            $attempt->iscorrect == 1 ? 1 : 0,
            $attempt->iscorrect == 0 ? 1 : 0,
            $attempt->numattempt + $addnextattempt,
            $addcountmastered
        );

        $ret['addgrade'] = $attempt->grade >= 0 ? '+' . $attempt->grade : $attempt->grade;

        $a['iscorrect'] = $attempt->iscorrect;
        $a['tools'] = $tools;

        // Update the database record for the attempt (IRT only).
        $theta = $difficulty = null;
        $this->get_selection()->update($attempt->queryid, $attempt->iscorrect, $theta, $difficulty);
        $a['theta'] = $theta;
        $a['difficulty'] = $difficulty;

        $this->db->update_record('mmogame_quiz_attempts', $a);

        $this->qbank->update_grades($attempt->auserid, $attempt->grade, $addcountmastered);
    }

    /**
     * Saves information about the user's answer.
     *
     * @param stdClass $attempt The quiz attempt object.
     * @param stdClass $query The query object related to the quiz.
     * @param ?string $useranswer The user's answer as a string.
     * @param int $timestart
     * @param int $timeanswer
     * @param ?int $useranswerid Optional user answer ID.
     * @param int $tools
     * @param array $ret Output array for additional information.
     * @param stdClass $aduel Output array for additional information.
     */
    public function set_answer(
        stdClass $attempt,
        stdClass $query,
        ?string $useranswer,
        int $timestart,
        int $timeanswer,
        ?int $useranswerid,
        int $tools,
        array &$ret,
        stdClass $aduel
    ): void {
        $this->set_answer_alone($attempt, $query, $useranswer, $timestart, $timeanswer, $useranswerid, $tools, $ret);

        $ret['iscorrect'] = $attempt->iscorrect;
        if ($this->auserid == $aduel->auserid1) {
            $recs = $this->db->get_records_select(
                'mmogame_quiz_attempts',
                'mmogameid=? AND numgame=? AND numteam=? AND timeanswer = 0 AND auserid=?',
                [$attempt->mmogameid, $attempt->numgame, $aduel->id, $this->auserid],
                'id',
                '*',
                0,
                1
            );
            $rec = reset($recs);
            if ($rec === false) {
                // We finished.
                mmogame_mode_aduel::close_user1(
                    $this,
                    $aduel->id,
                    $this->get_rgrade($this->auserid)
                );
            }

            return;
        }
        // Adjust the attempt score if negative.
        if ($attempt->score < 0) {
            $attempt->score = 0;
        }
        $opposite = $this->db->get_record_select(
            'mmogame_quiz_attempts',
            'mmogameid=? AND numgame=? AND numteam=? AND numattempt=? AND auserid = ?',
            [$attempt->mmogameid, $attempt->numgame, $aduel->id, $attempt->numattempt, $aduel->auserid1]
        );
        if ($opposite !== false) {
            if ($attempt->iscorrect == 1) {
                // Check the answer of opposite. If is wrong duplicate my points.
                if ($opposite->iscorrect == 0) {
                    $attempt->grade *= 2;
                    if ($aduel->tool3numattempt2 == $attempt->numattempt) {
                        // The wizard tool.
                        $attempt->grade -= 2;
                    }
                    $ret['addgrade'] = '+' . $attempt->score;
                    $this->db->update_record(
                        'mmogame_quiz_attempts',
                        ['id' => $attempt->id, 'grade' => $attempt->grade]
                    );
                    $this->qbank->update_grades($attempt->auserid, $attempt->grade, 0);
                }
            } else if ($attempt->iscorrect == 0) {
                // Check the answer of opposite. If is right duplicate other points.
                if ($opposite->iscorrect) {
                    $this->db->update_record(
                        'mmogame_quiz_attempts',
                        ['id' => $opposite->id, 'grade' => 2 * $opposite->grade]
                    );
                    $this->qbank->update_grades($opposite->auserid, $opposite->grade, 0);
                }
            }
        } else if ($aduel->tool3numattempt2 == $attempt->numattempt) {
            // The wizard tool.
            $attempt->score -= 1;
            $ret['addgrade'] = '+' . $attempt->grade;
            $this->db->update_record(
                'mmogame_quiz_attempts',
                ['id' => $attempt->id, 'grade' => $attempt->grade]
            );
            $this->qbank->update_grades($attempt->auserid, -1, 0);
        }

        $recs = $this->db->get_records_select(
            'mmogame_quiz_attempts',
            'mmogameid =? AND numgame=? AND numteam=? AND timeanswer = 0 AND auserid=?',
            [$attempt->mmogameid, $attempt->numgame, $aduel->id, $aduel->auserid2],
            'id',
            'id',
            0,
            1
        );
        if (count($recs) == 0) {
            // We finished.
            $this->db->update_record('mmogame_am_aduel_pairs', ['id' => $aduel->id, 'isclosed2' => 1]);
        }
    }

    /**
     * Return the score of user's answer.
     *
     * @param bool $iscorrect
     * @param object $query
     * @return int (now uses negative grading, in the future user will change it)
     */
    protected function get_grade_query(bool $iscorrect, object $query): int {
        return $this->get_grade_query_negative($iscorrect, $query);
    }

    /**
     * Return the score with negative values. If "n" is the number of answers, if it corrects returns (n-1) else returns (-1)
     *
     * @param bool $iscorrect
     * @param stdClass $query
     * @return int
     */
    protected function get_grade_query_negative(bool $iscorrect, stdClass $query): int {
        if (!$this->qbank->is_multichoice($query)) {
            return $iscorrect ? 1 : 0;
        }

        return $iscorrect ? count($query->answers) - 1 : -1;
    }

    /**
     * Computes queryranks (how many queries are with smaller difficulty plus one).
     *
     * @param array $recs
     * @param array $queryranks
     * @return void
     */
    private function compute_queryranks(array $recs, array &$queryranks): void {
        $queryids = [];

        foreach ($recs as $rec) {
            $queryids[] = $rec->queryid;
        }
        [$insql, $inparams] = $this->db->get_in_or_equal($queryids);

        $sql = "SELECT queryid,
            1 + (SELECT COUNT(*)
                FROM {mmogame_as_irt} irt2
                WHERE irt2.mmogameid=irt.mmogameid AND irt.numgame=irt2.numgame
                    AND irt2.difficulty < irt.difficulty) as c
            FROM {mmogame_as_irt} irt
            WHERE mmogameid=? AND numgame=? AND queryid $insql";
        $recranks = $this->db->get_records_sql(
            $sql,
            array_merge([$this->get_id(), $this->get_numgame()], $inparams)
        );
        $map = [];
        foreach ($recranks as $recrank) {
            $map[$recrank->queryid] = (int)$recrank->c;
        }

        foreach ($recs as $rec) {
            if (isset($map[$rec->queryid])) {
                $queryranks[] = $map[$rec->queryid];
            } else {
                $queryranks[] = null;
            }
        }
    }

    /**
     * return the name of table attempts.
     */
    public static function get_table_attempts(): string {
        return 'mmogame_quiz_attempts';
    }
}
