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
 * @license    http://www.gnu.onpx browserslist@latest --update-dbrg/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mmogametype_quiz\local;

use mod_mmogame\local\database\mmogame_database;
use mod_mmogame\local\mmogame;
use mod_mmogame\local\model\mmogame_model_aduel;
use stdClass;

defined('MOODLE_INTERNAL') || die();

require_once(dirname(__FILE__) . '/../../lib.php');
require_once( 'mmogametype_quiz_algorithm_irt.php');

define( 'ERRORCODE_NO_QUERIES', 'no_questions');
define( 'ERRORCODE_ADUEL_NO_RIVALS', 'aduel_no_rivals');

/**
 * mmogametype_quiz_split is responsible for managing and facilitating quiz gameplay
 * within the mmogame system, including handling attempts, scoring,
 * and maintaining related user data.
 */
class mmogametype_quiz_split extends mmogame {
    /** @var int $numquestions: number of questions that contain one group of questions. */
    protected int $numquestions;

    /** @var int $maxalone: maximum number of questions that a user can play withoyt an oponent. */
    protected int $maxalone = 200;

    /**
     * Constructor.
     *
     * @param mmogame_database $db (the database)
     * @param stdClass $rgame (a record from table mmogame)
     */
    public function __construct(mmogame_database $db, stdClass $rgame) {
        $rgame->usemultichoice = true;

        parent::__construct( $db, $rgame);

        $this->numquestions = 4;
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
     * @param array $auserids
     * @param bool $isaduel
     * @param array $adueluserids
     * @return array|null
     */
    public function get_attempts(array $auserids, bool $isaduel, array $adueluserids): ?array {
        $ids = $qs0 = null;

        $ret = $ignore = [];
        foreach ($auserids as $auserid) {
            $this->login_user( $auserid);
            $newplayer1 = $newplayer2 = false;

            $aduel = mmogame_model_aduel::get_aduel( $this, $this->maxalone,  $newplayer1, $newplayer2, $adueluserids, $isaduel);
            if ($aduel === null) {
                $this->set_errorcode( ERRORCODE_ADUEL_NO_RIVALS);
                return null;
            }
            if (!$newplayer1 && !$newplayer2) {
                $recs = mmogame_model_aduel::get_attempts( $this, $aduel);
                if (count($recs) > 0) {
                    $ret[] = $recs;
                    continue;
                } else {
                    // Have to repair.
                    $temps = mmogame_model_aduel::get_attempts( $this, $aduel, true);
                    if (count($temps) == 0) {
                        $newplayer1 = true;
                    } else {
                        // Append new questions.
                        $newplayer1 = true;
                    }
                }
            }

            if ($ids === null) {
                // Get the ids of all the queries.
                $ids = $this->qbank->get_queries_ids();
                if ($ids === null || count($ids) == 0) {
                    return null;
                }
                // Initializes data.
                $qs = [];
                foreach ($ids as $id) {
                    $q = new stdClass();
                    $q->id = $id;
                    $q->qpercent = $q->qcountused = $q->ucountused = $q->utimeerror = $q->uscore = $q->upercent = 0;

                    $qs[$id] = $q;
                }
                $qs0 = $qs;
            } else {
                $qs = $qs0;
            }

            if ($newplayer1) {
                $ret[] = $this->get_attempts_new1($ids, $qs, $aduel, $ignore);
            } else {
                $ret[] = $this->get_attempts_new2($aduel);
            }
        }

        return $ret;
    }

    /**
     * Creates a new attempt for the second player.
     *
     * @param stdClass $aduel
     * @return array (a new attempt of false if no attempt)
     */
    protected function get_attempts_new2($aduel): array {
        $recs = $this->db->get_records_select( 'mmogame_quiz_attempts',
            'mmogameid=? AND numgame=? AND auserid=?',
            [$this->rgame->id, $this->rgame->numgame, $this->auserid],
            'numquery DESC', 'numquery', 0, 1);
        if (count($recs) === 0) {
            $numquery = 1;
        } else {
            $rec = reset( $recs);
            $numquery = $rec->numquery + 1;
        }

        $recs = $this->db->get_records_select( 'mmogame_quiz_attempts',
            'mmogameid=? AND numgame=? AND numteam=? AND auserid=?',
            [$this->rgame->id, $this->rgame->numgame, $aduel->id, $aduel->auserid1], 'numattempt');
        $ids = [];
        foreach ($recs as $rec) {
            $a = ['mmogameid' => $this->get_id(),
                'auserid' => $aduel->auserid2, 'queryid' => $rec->queryid, 'numgame' => $rec->numgame,
                'timestart' => 0, 'numteam' => $rec->numteam, 'numquery' => $numquery++,
                'numattempt' => $rec->numattempt, 'layout' => $rec->layout, 'timeanswer' => 0, ];
            $a['timeclose'] = 0;
            $id = $this->db->insert_record( 'mmogame_quiz_attempts', $a);
            $ids[] = $id;
        }
        [$insql, $inparams] = $this->db->get_in_or_equal($ids);
        $sql = "SELECT * FROM {mmogame_quiz_attempts} WHERE id $insql ORDER BY id";
        return $this->db->get_records_sql($sql, $inparams);
    }

    /**
     * Creates a new attempt for the first player. Also select which question will be contained in the attempt.
     *
     * @param array $ids
     * @param array $qs
     * @param stdClass $aduel
     * @param array $ignore
     * @return ?array (a new attempt of false if no attempt)
     */
    protected function get_attempts_new1(array $ids, array $qs, stdClass $aduel, array &$ignore): ?array {
        $recs = $this->db->get_records_select( 'mmogame_quiz_attempts',
            'mmogameid=? AND numgame=? AND auserid=?',
            [$this->rgame->id, $this->rgame->numgame, $this->auserid],
            'numquery DESC', 'numquery',    0, 1);
        if (count($recs) === 0) {
            $numquery = 1;
        } else {
            $rec = reset( $recs);
            $numquery = $rec->numquery + 1;
        }

        $queries = mmogametype_quiz_algorithm_irt::get_queries(
            $this->get_db(), $this->rgame->id, $this->rgame->numgame,
            $this->auserid, $ids, 4, $numquery, $ignore);
        if ($queries === null) {
            return null;
        }
        $num = 0;
        $a = $ids = [];
        foreach ($queries as $queryid) {
            $a['mmogameid'] = $this->rgame->id;
            $a['numgame'] = $this->rgame->numgame;
            $a['numteam'] = $aduel->id;
            $a['auserid'] = $this->auserid;
            $a['numattempt'] = ++$num;
            $a['queryid'] = $queryid;
            $a['timestart'] = count($ids) == 0 ? time() : 0;  // Only the first starts.
            $a['timeanswer'] = 0;
            $a['timeclose'] = 0;
            $a['numquery'] = $numquery++;
            $ids[] = $this->db->insert_record( 'mmogame_quiz_attempts', $a);

            $ignore[$queryid] = $queryid;
        }

        [$insql, $inparams] = $this->db->get_in_or_equal($ids);
        $sql = "SELECT * FROM {mmogame_quiz_attempts} WHERE id $insql ORDER BY id";
        $ret = $this->db->get_records_sql($sql, $inparams);

        $sql = 'UPDATE {mmogame_aa_grades} SET countalone=countalone + 1 WHERE
                mmogameid=? AND numgame=? AND auserid=?';
        $this->db->execute( $sql, [$this->rgame->id, $this->rgame->numgame, $this->auserid]);

        return $ret;
    }

    /**
     * Return the queries for ADuel.
     *
     * @param array $ids
     * @param array $qs
     * @param int $count (how many)
     * @return ?array or false
     */
    public function get_queries_aduel(array $ids, array $qs, int $count): ?array {

        $grade = $this->get_grade( $this->auserid);

        // Computes statistics per question.
        [$insql, $inparams] = $this->db->get_in_or_equal( $ids);
        $recs = $this->db->get_records_select( 'mmogame_aa_stats',
            "mmogameid=? AND numgame=? AND auserid IS NULL AND queryid $insql",
            array_merge( [$this->rgame->id, $this->rgame->numgame], $inparams),
            '', 'id,queryid,percent,countused');
        foreach ($recs as $rec) {
            $q = $qs[$rec->queryid];
            $q->qpercent = $rec->percent;
            $q->qcountused = $rec->countused;
            $qs[$rec->queryid] = $q;
        }

        // Computes statistics per question and user.
        $recs = $this->db->get_records_select( 'mmogame_aa_stats',
            "mmogameid=? AND numgame=? AND auserid = ? AND queryid $insql",
            array_merge([$this->rgame->id, $this->rgame->numgame, $this->auserid], $inparams),
            '', 'queryid,countused,countcorrect,counterror,timeerror,percent,islastcorrect');

        $corrects = 0;
        foreach ($recs as $rec) {
            $q = $qs[$rec->queryid];
            $q->utimeerror = $rec->timeerror;
            $q->ucountused = $rec->countused;
            $q->upercent = $rec->percent;
            $q->uscore = $rec->countcorrect - 2 * $rec->counterror;
            $qs[$rec->queryid] = $q;

            if ($rec->islastcorrect) {
                $corrects++;
            }
        }
        $map = [];
        $min = 0;
        foreach ($qs as $q) {
            if ($q->uscore < $min) {
                $min = $q->uscore;
            }
        }

        $percent = $grade !== null ? $grade->percent : 0;
        foreach ($qs as $q) {
            $key = sprintf( "%10d %10d %10d %5d %10d %5d %10d",
                // If it has big negative score give priority to them.
                $q->uscore < 0 ? -$min + $q->uscore : 999999999,
                // If it has negative score more priority has the older question.
                $q->uscore < 0 ? $q->utimeerror : 0,
                // Fewer times used by user higher priority has.
                $q->ucountused,
                // Sorts by distance.
                $q->qpercent < $percent ? abs( round( 100 * $percent - 100 * $q->qpercent)) : 0,
                // Prioritizes question that fewer times by everyone.
                round( 100 * $q->qcountused),
                rand(1, 9999),
                $q->id
            );
            $map[$key] = $q;
        }
        ksort( $map);

        $map2 = [];
        // Selects 3 * count of needed.
        foreach ($map as $q) {
            if (count( $map2) >= 3 * $count) {
                break;
            }
            $key2 = sprintf( "%10.6f %10d", $q->qpercent, $q->id);
            $map2[$key2] = $q;
        }
        // Remove items so to remain $count.
        while (count( $map2) > $count) {
            $key = array_rand( $map2);
            unset( $map2[$key]);
        }

        ksort( $map2);
        $ret = [];
        foreach ($map2 as $q) {
            $q2 = $this->qbank->load( $q->id);
            $ret[] = $q2;
        }

        // Update statistics.
        foreach ($ret as $q) {
            $this->qbank->update_stats($this->auserid, null, $q->id, 1, 0, 0);
            $this->qbank->update_stats(null, null, $q->id, 1, 0, 0);
        }
        $this->db->update_record( 'mmogame_aa_grades',
            ['id' => $grade->id, 'countquestions' => count( $ids), 'percent' => $corrects / count($ids)]);

        return count( $ret) ? $ret : null;
    }

    /**
     * return the name of tabler attempts.
     */
    public static function get_table_attempts(): string {
        return 'mmogame_quiz_attempts';
    }

    /**
     * Returns question types that uses (multichoice).
     * @return string[]
     */
    public function get_qtypes() {
        return ['multichoice'];
    }

    /**
     * Set answer model.
     *
     * @param array $ret
     * @param int|null $attemptid
     * @param string|null $answer
     * @param int $timestart
     * @param int $timeanswer
     * @param int|null $answerid
     * @param string $subcommand
     * @return stdClass|null
     */
    public function set_answer_model(array  &$ret, ?int $attemptid, ?string $answer, int $timestart, int $timeanswer,
                                     ?int $answerid = null, string $subcommand = ''): ?stdClass {
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
        $aduel = $this->db->get_record_select( 'mmogame_am_aduel_pairs', 'id=?', [$attempt->numteam]);

        $autograde = true;
        $query = $this->qbank->load( $attempt->queryid);
        if (isset( $subcommand) && $subcommand == 'tool2') {
            $autograde = false;
            $ret['tool2'] = 1;
        }
        $this->set_answer( $attempt, $query, $answer, $timestart, $timeanswer, $answerid, $autograde, $ret, $aduel);

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
     * @param bool $autograde
     * @param array $ret (will contain all information)
     */
    public function set_answer_alone(stdClass $attempt, stdClass $query, ?string $useranswer,
                                     int $timestart, int $timeanswer, ?int $useranswerid,
                                     bool $autograde, array &$ret): void {
        // If auto-grading is enabled, check if the answer is correct and set iscorrect.
        if ($autograde) {
            $fraction = 0.0;
            $attempt->iscorrect = $this->qbank->is_correct( $query, $useranswer, $useranswerid, $this, $fraction);
            $attempt->iscorrect = $attempt->iscorrect ? 1 : 0;
        }
        $istimeout = false;

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

        $a['timestart'] = $timestart;
        $a['timeanswer'] = $attempt->timeanswer = $timeanswer;

        if ($autograde) {
            // Update the score based on the correctness of the answer.
            $a['score'] = $attempt->score = $this->get_score_query( $attempt->iscorrect, $query);

            // Update statistics for the user and the question.
            $this->qbank->update_grades( $attempt->auserid, $attempt->score, 0, 1);
            $ret['addscore'] = $attempt->score >= 0 ? '+'.$attempt->score : $attempt->score;

            if ($attempt->iscorrect) {
                $nextquery = $attempt->numquery + 10;
            } else {
                $irt = $this->db->get_record_select( 'mmogame_aa_irt',
                    'mmogameid=? AND numgame=? AND queryid=?',
                    [$attempt->mmogameid, $attempt->numgame, $attempt->queryid]);
                $grade = $this->db->get_record_select( 'mmogame_aa_grades',
                    'mmogameid=? AND numgame=? AND auserid=?',
                    [$attempt->mmogameid, $attempt->numgame, $attempt->auserid]);
                if ($grade === null || $irt === null) {
                    $nextquery = 0;
                } else if ($irt->difficulty > $grade->theta) {
                    // Incorrect & difficult query.
                    $nextquery = $attempt->numquery + 5;
                } else {
                    // Incorrect & easy query.
                    $nextquery = $attempt->numquery + 7;
                }
            }
            $this->qbank->update_stats( $attempt->auserid, null, $attempt->queryid, 1,
                $attempt->iscorrect == 1 ? 1 : 0, $attempt->iscorrect == 0 ? 1 : 0, $nextquery, null);

            $this->qbank->update_stats( null, null, $attempt->queryid, 1,
                $attempt->iscorrect == 1 ? 1 : 0, $attempt->iscorrect == 0 ? 1 : 0, 0, null);

            mmogametype_quiz_algorithm_irt::update(
                $this->get_db(),
                $this->rgame->id,
                $this->rgame->numgame,
                $this->auserid,
                $attempt->queryid,
                $attempt->iscorrect
            );
        }

        // Update the database record for the attempt.
        if ($autograde) {
            $a['iscorrect'] = $attempt->iscorrect;
            $this->db->update_record( 'mmogame_quiz_attempts', $a);
        }
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
     * @param bool $autograde Whether autograding is enabled.
     * @param array $ret Output array for additional information.
     * @param stdClass $aduel Output array for additional information.
     */
    public function set_answer(stdClass $attempt, stdClass $query, ?string $useranswer,
                               int $timestart, int $timeanswer, ?int $useranswerid,
                               bool $autograde, array &$ret, stdClass $aduel): void {
        $this->set_answer_alone( $attempt, $query, $useranswer, $timestart, $timeanswer, $useranswerid, $autograde, $ret);
        $ret['iscorrect'] = $attempt->iscorrect;
        if ($this->auserid == $aduel->auserid1) {
            $recs = $this->db->get_records_select( 'mmogame_quiz_attempts',
                'mmogameid=? AND numgame=? AND numteam=? AND timeanswer = 0 AND auserid=?',
                [$attempt->mmogameid, $attempt->numgame, $aduel->id, $this->auserid], 'id',
            '*', 0, 1);
            $rec = reset($recs);
            if ($rec === false) {
                // We finished.
                $grade = $this->db->get_record_select('mmogame_aa_grades',
                    'mmogameid=? AND numgame=? AND auserid=?',
                    [$attempt->mmogameid, $attempt->numgame, $this->auserid]);
                $score = $grade !== null ? $grade->theta : 0;
                mmogame_model_aduel::close_user1($this, $aduel->id, $score);
            }

            return;
        }
        // Adjust the attempt score if negative.
        if ($attempt->score < 0) {
            $attempt->score = 0;
        }
        $opposite = $this->db->get_record_select( 'mmogame_quiz_attempts',
            'mmogameid=? AND numgame=? AND numteam=? AND numattempt=? AND auserid = ?',
            [$attempt->mmogameid, $attempt->numgame, $aduel->id, $attempt->numattempt, $aduel->auserid1]
        );
        if ($opposite !== false) {
            if ($attempt->iscorrect == 1) {
                // Check the answer of opposite. If is wrong duplicate my points.
                if ($opposite->iscorrect == 0) {
                    $attempt->score *= 2;
                    if ($aduel->tool3numattempt2 == $attempt->numattempt) {
                        // The wizard tool.
                        $attempt->score -= 2;
                    }
                    $ret['addscore'] = '+'.$attempt->score;
                    $this->db->update_record( 'mmogame_quiz_attempts',
                        ['id' => $attempt->id, 'score' => $attempt->score]);
                    $this->qbank->update_grades( $attempt->auserid, $attempt->score, 0, 0);
                }
            } else if ($attempt->iscorrect == 0) {
                // Check the answer of opposite. If is right duplicate other points.
                if ($opposite->iscorrect) {
                    $this->db->update_record( 'mmogame_quiz_attempts',
                        ['id' => $opposite->id, 'score' => 2 * $opposite->score]);
                    $this->qbank->update_grades( $opposite->auserid, $opposite->score, 0, 0);
                }
            }
        } else if ($aduel->tool3numattempt2 == $attempt->numattempt) {
            // The wizard tool.
            $attempt->score -= 1;
            $ret['addscore'] = '+'.$attempt->score;
            $this->db->update_record( 'mmogame_quiz_attempts',
                ['id' => $attempt->id, 'score' => $attempt->score]);
            $this->qbank->update_grades( $attempt->auserid, -1, 0, 0);
        }

        $recs = $this->db->get_records_select( 'mmogame_quiz_attempts',
            'mmogameid =? AND numgame=? AND numteam=? AND timeanswer = 0 AND auserid=?',
            [$attempt->mmogameid, $attempt->numgame, $aduel->id, $aduel->auserid2], 'id', 'id', 0, 1);
        if ($recs === null || count($recs) == 0) {
            // We finished.
            $this->db->update_record( 'mmogame_am_aduel_pairs', ['id' => $aduel->id, 'isclosed2' => 1]);
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
     * Return the score with negative values. If "n" is the number of answers, if it corrects returns (n-1) else returns (-1)
     *
     * @param bool $iscorrect
     * @param stdClass $query
     * @return int
     */
    protected function get_score_query_negative(bool $iscorrect, stdClass $query): int {
        if (!$this->qbank->is_multichoice( $query)) {
            return $iscorrect ? 1 : 0;
        }

        return $iscorrect ? count( $query->answers) - 1 : -1;
    }

    /**
     * Set the state of the current game.
     *
     * @param int $state
     * @return string
     */
    public function set_state(int $state): string {
        $timefastjson = round( 10 * microtime( true));

        $statecontents = $state . "-" . $timefastjson;
        $filecontents = '';

        $this->save_state($state, $statecontents, $filecontents, $timefastjson);

        return $statecontents;
    }
}
