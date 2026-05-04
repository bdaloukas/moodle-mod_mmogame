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
 * mmogametype_quiz_algorithm_irt class
 *
 * @package    mmogametype_quiz
 * @copyright  2024 Vasilis Daloukas
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mmogametype_quiz\local;

use mod_mmogame\local\database\mmogame_database;
use stdClass;

/**
 * mmogame_quiz is responsible for managing and facilitating quiz gameplay
 * within the mmogame system, including handling attempts, scoring,
 * and maintaining related user data.
 */

// TO_DO remove tables mmogame_aa_stats with auserid IS NULL (use IRT instead).

/**
 * The class mmogametype_quiz_algorithm_irt computes next qustions with IRT-1PL.
 */
class mmogametype_quiz_algorithm_irt {
    /**
     * Ensure that the in-memory questions list and the IRT table are in sync with the defined set of query IDs ($ids).
     *
     * - Removes questions whose queryid is not present in $ids.
     * - Inserts missing rows into mmogame_aa_irt for queryids in $ids.
     * - Adds default question objects for those missing queryids into $questions.
     *
     * @param mmogame_database $db
     * @param int $mmogameid
     * @param int $numgame
     * @param array &$questions List of question objects (by reference).
     * @param array $ids Map: queryid => categoryid.
     */
    protected static function repair_stats(
        mmogame_database $db,
        int $mmogameid,
        int $numgame,
        array &$questions,
        array $ids
    ): void {
        // Map of questions to delete (indexes in $questions) and existing queryids.
        $mapdelete = $mapq = [];

        // 1. Identify questions that no longer belong to the valid query set ($ids).
        foreach ($questions as $id => $question) {
            // Defensive check: if queryid is missing or not found in $ids, mark for deletion.
            if (!array_key_exists($question->queryid, $ids)) {
                // Does not exist.
                $mapdelete[] = $id;
            } else {
                // Store that this queryid is present in $questions.
                $mapq[$question->queryid] = 1;
            }
        }
        // 2. Remove all invalid questions from the list.
        foreach ($mapdelete as $id) {
            $queryid = $questions[$id]->queryid;
            $db->delete_records_select(
                'mmogame_aa_irt',
                'mmogameid=? AND numgame=? AND queryid=?',
                [$mmogameid, $numgame, $queryid]
            );
            unset($questions[$id]);
        }

        // 3. Add missing IRT entries and create default question objects for queryids not in $questions.
        foreach ($ids as $queryid => $categoryid) {
            if (!array_key_exists($queryid, $mapq)) {
                // Insert a new IRT record into the database.
                $a = ['mmogameid' => $mmogameid, 'numgame' => $numgame, 'queryid' => $queryid, 'timemodified' => time()];
                $db->insert_record('mmogame_aa_irt', $a);

                // Create a default question object so the algorithm can use it.
                $question = new stdClass();
                $question->queryid = $queryid;
                $question->difficulty = 0;
                $question->countused = 0;
                $question->countcorrect = 0;
                $question->serialcorrects = 0;
                $question->nextquery = 0;
                $question->counterror = 0;
                $question->dif = 0;

                // Append to the question list.
                $questions[] = $question;
            }
        }
    }

    /**
     * * Get queries.
     *
     * @param mmogame_database $db
     * @param int $mmogameid
     * @param int $numgame
     * @param int $auserid
     * @param array $ids
     * @param int $algorithm
     * @param int $count
     * @param int $numquery
     * @param array $ignore
     * @param int $countquestions
     * @param int $corrects
     * @param array $islastcorrect
     * @param array $rank
     * @return array
     */
    public static function get_queries(
        mmogame_database $db,
        int $mmogameid,
        int $numgame,
        int $auserid,
        array $ids,
        int $algorithm,
        int $count,
        int $numquery,
        array $ignore,
        int &$countquestions,
        int &$corrects,
        array &$islastcorrect,
        array &$rank
    ): array {
        return self::get_queries_improvement(
            $db,
            $mmogameid,
            $numgame,
            $auserid,
            $ids,
            $count,
            $numquery,
            $ignore,
            $countquestions,
            $corrects,
            $islastcorrect,
            $rank
        );
    }

    /**
     * * Get queries.
     *
     * @param mmogame_database $db
     * @param int $mmogameid
     * @param int $numgame
     * @param int $auserid
     * @param array $ids
     * @param int $count
     * @param int $numquery
     * @param array $ignore is a map queryid => queryid
     * @param int $countquestions
     * @param int $corrects
     * @param array $islastcorrect
     * @param array $ranks
     * @return array
     */
    public static function get_queries_improvement(
        mmogame_database $db,
        int $mmogameid,
        int $numgame,
        int $auserid,
        array $ids,
        int $count,
        int $numquery,
        array $ignore,
        int &$countquestions,
        int &$corrects,
        array &$islastcorrect,
        array &$ranks
    ): array {
        // Get player's skill rating (theta).
        $rec = $db->get_record_select(
            'mmogame_aa_grades',
            'mmogameid=? AND numgame=? AND auserid=?',
            [$mmogameid, $numgame, $auserid]
        );
        $theta = $rec !== null ? $rec->theta : 0;

        $islastcorrect = [];
        $maplastcorrect = [];

        // Retrieve all questions with player stats.
        $sql = "SELECT irt.queryid, irt.difficulty, st.counterror,
            st.countused, st.countcorrect, st.serialcorrects, st.nextquery, ABS(irt.difficulty - ?) as dif
            FROM {mmogame_aa_irt} irt
            LEFT JOIN {mmogame_aa_stats} st ON st.queryid = irt.queryid AND st.mmogameid=? AND st.numgame=? AND st.auserid = ?
            WHERE irt.mmogameid=? AND irt.numgame=?";
        $questions = $db->get_records_sql($sql, [$theta, $mmogameid, $numgame, $auserid, $mmogameid, $numgame]);
        self::repair_stats($db, $mmogameid, $numgame, $questions, $ids);

        //self::sort_questions($questions);
        self::sort_questions_difficulty($questions);

        $countquestions = count($questions);
        $corrects = 0;
        $mapdifficulty  = [];
        foreach ($questions as $question) {
            $mapdifficulty[$question->queryid] = $question->difficulty;
            if ($question->serialcorrects > 0) {
                $corrects++;
            }
        }
        asort($mapdifficulty);

        $categories = [];
        $countsearch = 2 * $count;

        // First step uses nextquery and ignore.
        // Second step selects random.
        for ($step = 1; $step <= 2 && $countsearch > 0 && count($questions) > 0; $step++) {
            while ($countsearch > 0 && count($questions) > 0) {
                $change = false;
                foreach ($questions as $id => $q) {
                    if ($step == 1) {
                        if ($q->nextquery !== null && $numquery < $q->nextquery) {
                            // In the first step delay re-displaying questions.
                            continue;
                        }
                        if (array_key_exists($q->queryid, $ignore)) {
                            // It is used to nearby split.
                            continue;
                        }
                    }

                    if (!isset($ids[$q->queryid])) {
                        unset($questions[$id]);
                        continue;
                    }
                    $categoryid = $ids[$q->queryid];
                    $categories[$categoryid][] = $q->queryid;
                    $maplastcorrect[$q->queryid] = $q->serialcorrects == 0 ? 0 : 1;
                    unset($questions[$id]);
                    $change = true;
                    if (--$countsearch === 0) {
                        break;
                    }
                }
                if ($change === false) {
                    break;
                }
            }
        }

        while ($countsearch > 0 && count($questions) > 0) {
            $id = array_rand($questions);
            $queryid = $questions[$id]->queryid;
            if (!isset($ids[$queryid])) {
                unset($questions[$id]);
                continue;
            }
            $categoryid = $ids[$queryid];
            $categories[$categoryid][] = $queryid;
            $maplastcorrect[$queryid] = $questions[$id]->serialcorrects == 0 ? 0 : 1;

            unset($questions[$id]);
            $countsearch--;
        }

        // Balance in each category.
        $ret = [];
        while (count($ret) < $count) {
            $found = false;
            foreach ($categories as &$codes) {
                if (!empty($codes) && count($ret) < $count) {
                    $queryid = array_shift($codes);
                    $islastcorrect[] = $maplastcorrect[$queryid];
                    $ret[] = (int)$queryid;
                    $found = true;
                }
            }
            if (!$found) {
                break;  // No other questions.
            }
        }
        $keys = array_keys($mapdifficulty);
        $map = [];
        foreach ($ret as $queryid) {
            $rank = 1 + array_search($queryid, $keys, true);
            $ranks[] = $rank;
            $map[$queryid] = $rank;
        }
        asort($map);

        return array_keys($map);
    }

    /**
     * Computes raschProbability.
     *
     * @param float $theta
     * @param float $difficulty
     * @return float
     */
    protected static function rasch_probability(float $theta, float $difficulty): float {
        return 1 / (1 + exp(-($theta - $difficulty)));
    }

    /**
     * Updates probability and error.
     * @param float $theta
     * @param float $difficulty
     * @param float $response
     * @param float $learningrate
     * @return void
     */
    protected static function update_parameters(
        float &$theta,
        float &$difficulty,
        float $response,
        float $learningrate = 0.05
    ): void {
        // Computes probability.
        $prob = self::rasch_probability($theta, $difficulty);
        $error = $response - $prob;

        // Gradient update.
        $theta += $learningrate * $error;
        $difficulty -= $learningrate * $error; // Note: difficulty decreases if answered correctly.
    }

    /**
     * Updates tables mmogame_aa_grades and mmogame_aa_irt.
     *
     * @param mmogame_database $db
     * @param int $mmogameid
     * @param int $numgame
     * @param int $auserid
     * @param int $queryid
     * @param bool $iscorrect
     * @param ?float $theta
     * @param ?float $difficulty
     * @return void
     */
    public static function update(
        mmogame_database $db,
        int $mmogameid,
        int $numgame,
        int $auserid,
        int $queryid,
        bool $iscorrect,
        ?float &$theta,
        ?float &$difficulty
    ): void {
        // Read parameters from database.
        $recg = $db->get_record_select(
            'mmogame_aa_grades',
            'mmogameid=? AND numgame=? AND auserid=?',
            [$mmogameid, $numgame, $auserid]
        );
        $theta = $recg !== null ? $recg->theta : 0;

        $reci = $db->get_record_select(
            'mmogame_aa_irt',
            'mmogameid=? AND numgame=? AND queryid=?',
            [$mmogameid, $numgame, $queryid]
        );
        $difficulty = $reci !== null ? $reci->difficulty : 0;

        // Updates parameters.
        self::update_parameters($theta, $difficulty, $iscorrect ? 1 : 0);

        // Saves new values.
        if ($recg !== null) {
            $sql = "UPDATE {mmogame_aa_grades} SET theta=?,timemodified=? WHERE id=?";
            $db->execute($sql, [$theta, time(), $recg->id]);
        }
        if ($reci !== null) {
            $sql = "UPDATE {mmogame_aa_irt} SET difficulty=?, timemodified=? WHERE id=?";
            $db->execute($sql, [$difficulty, time(), $reci->id]);
        }
    }

    /**
     * Saves logs to table mmogame_aa_irt_log
     *
     * @param mmogame_database $db
     * @param int $mmogameid
     * @param int $numgame
     * @param int $auserid
     * @param mixed $theta
     * @param int $queryid
     * @param float $difficulty
     * @param ?int $serialcorrects
     * @param ?int $nextquery
     * @param int $step
     * @param int $numquery
     * @param float $bestscore
     * @param string $info
     * @return void
     */
    private static function log(
        mmogame_database $db,
        int $mmogameid,
        int $numgame,
        int $auserid,
        mixed $theta,
        int $queryid,
        float $difficulty,
        ?int $serialcorrects,
        ?int $nextquery,
        int $step,
        int $numquery,
        float $bestscore,
        string $info
    ): void {
        $db->insert_record(
            'mmogame_aa_irt_log',
            [
                'mmogameid' => $mmogameid,
                'numgame' => $numgame,
                'auserid' => $auserid,
                'theta' => $theta,
                'queryid' => $queryid,
                'difficulty' => $difficulty,
                'serialcorrects' => $serialcorrects === null ? 0 : $serialcorrects,
                'nextquery' => $nextquery === null ? 0 : $nextquery,
                'step' => $step,
                'timecreated' => time(),
                'numquery' => $numquery,
                'bestscore' => $bestscore,
                'info' => $info,
                ]
        );
    }


    /**
     *
     * Sort questions
     *
     * // SORT serialcorrects ASC, counterror DESC, countused ASC, difficulty difference ASC
     * @param array $questions
     */
    private static function sort_questions(array &$questions): void {
        foreach ($questions as $qid => $q) {
            // Random μόνο για tie-break.
            $q->rand = mt_rand() / mt_getrandmax();
            $questions[$qid] = $q;
        }

        uasort($questions, static function ($a, $b) {
            // 1) serialcorrects ASC.
            if ($a->serialcorrects !== $b->serialcorrects) {
                return $a->serialcorrects <=> $b->serialcorrects;
            }

            // 2) counterror DESC.
            if ($a->counterror !== $b->counterror) {
                return $b->counterror <=> $a->counterror;
            }

            // 3) countused ASC.
            if ($a->countused !== $b->countused) {
                return $a->countused <=> $b->countused;
            }

            // 4) difficulty difference ASC.
            if ($a->dif !== $b->dif) {
                return $a->dif <=> $b->dif;
            }

            // 5) tie-break: random.
            return $a->rand <=> $b->rand;
        });
    }

    /**
     * Sort questions
     *
     * // SORT serialcorrects ASC, counterror DESC, countused ASC, difficulty difference ASC
     * @param array $questions
     */
    private static function sort_questions_difficulty(array &$questions): void {
        foreach ($questions as $qid => $q) {
            // Random μόνο για tie-break.
            $q->rand = mt_rand() / mt_getrandmax();
            $questions[$qid] = $q;
        }

        uasort($questions, static function ($a, $b) {
            // 1) serialcorrects ASC.
            if ($a->serialcorrects !== $b->serialcorrects) {
                return $a->serialcorrects <=> $b->serialcorrects;
            }

            // 2) counterror DESC.
            if ($a->counterror !== $b->counterror) {
                return $b->counterror <=> $a->counterror;
            }

            // 3) countused ASC.
            //if ($a->countused !== $b->countused) {
            //    return $a->countused <=> $b->countused;
            //}
            // 4) difficulty difference ASC.
            if ($a->difficulty !== $b->difficulty) {
                return $b->difficulty <=> $a->difficulty;
            }

            // 5) tie-break: random.
            return $a->rand <=> $b->rand;
        });
    }

    /**
     * Selects questions for revision
     *
     * @param mmogame_database $db
     * @param int $mmogameid
     * @param int $numgame
     * @param int $auserid
     * @return array
     */
    public static function idea(mmogame_database $db, int $mmogameid, int $numgame, int $auserid): array {
        // Get player's skill rating (theta).
        $rec = $db->get_record_select(
            'mmogame_aa_grades',
            'mmogameid=? AND numgame=? AND auserid=?',
            [$mmogameid, $numgame, $auserid]
        );
        $theta = $rec !== null ? $rec->theta : 0;
        // Retrieve all questions with player stats.
        $sql = "SELECT irt.queryid, irt.difficulty, st.counterror,
            st.countused, st.countcorrect, st.serialcorrects, st.nextquery
            FROM {mmogame_aa_irt} irt
            LEFT JOIN {mmogame_aa_stats} st
                ON st.queryid = irt.queryid AND st.mmogameid=irt.mmogameid AND st.numgame=irt.numgame AND st.auserid = ?
            WHERE irt.mmogameid=? AND irt.numgame=?
            ORDER BY st.serialcorrects,st.counterror DESC,ABS(irt.difficulty - ?)";
        $questions = $db->get_records_sql($sql, [$auserid, $mmogameid, $numgame, $theta], 0, 10);

        $ret = [];
        foreach ($questions as $question) {
            $ret[] = $question->queryid;
        }

        return $ret;
    }
}
