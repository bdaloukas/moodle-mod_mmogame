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
use stdClass;

defined('MOODLE_INTERNAL') || die();

require_once(dirname(__FILE__) . '/../../lib.php');

/**
 * mmogame_quiz is responsible for managing and facilitating quiz gameplay
 * within the mmogame system, including handling attempts, scoring,
 * and maintaining related user data.
 */
//TO_DO remove tables mmogame_aa_stats with auserid IS NULL (use IRT instead)
/**
 * The class mmogametype_quiz_algorithm_irt computes next qustions with IRT-1PL.
 */
class mmogametype_quiz_algorithm_irt {
    /**
     * Repairs stats.
     *
     * @param $db
     * @param int $mmogameid
     * @param int $numgame
     * @param array $questions
     * @param array $ids
     */
    protected static function repair_stats($db, int $mmogameid, int $numgame, array &$questions, array $ids) {
        $mapd = $mapq = [];
        foreach ($questions as $id => $question) {
            if (!in_array($question->queryid, $ids)) {
                // Does not exist.
                $mapd[] = $id;
            } else {
                $mapq[$question->queryid] = 1;
            }
        }
        foreach ($mapd as $id) {
            unset( $questions[$id]);
        }

        foreach ($ids as $queryid) {
            if (!array_key_exists( $queryid, $mapq)) {
                $a = [ 'mmogameid' => $mmogameid, 'numgame' => $numgame, 'queryid' => $queryid, 'timemodified' => time()];
                $db->insert_record('mmogame_aa_irt', $a);
                $question = new stdClass();
                $question->queryid = $queryid;
                $question->difficulty = 0;
                $question->countused = 0;
                $question->countcorrect = 0;
                $question->serialcorrects = 0;
                $question->nextquery = 0;
                $questions[] = $question;
            }
        }
    }

    /**
     * * Get queries.
     *
     * @param $db
     * @param int $mmogameid
     * @param int $numgame
     * @param int $auserid
     * @param array $ids
     * @param $count
     * @param $numquery
     * @param array $ignore
     * @return array
     */
    public static function get_queries($db, int $mmogameid, int $numgame, int $auserid, array $ids,
                                       $count, $numquery, array $ignore): array {

        $start = microtime(true);

        // Get player's skill rating (theta).
        $rec = $db->get_record_select('mmogame_aa_grades',
            'mmogameid=? AND numgame=? AND auserid=?',
            [$mmogameid, $numgame, $auserid]);
        $theta = $rec !== null ? $rec->theta : 0;

        // Retrieve all questions with player stats.
        $sql = "SELECT irt.queryid, irt.difficulty,
            st.countused, st.countcorrect, st.serialcorrects, st.nextquery
            FROM {mmogame_aa_irt} irt
            LEFT JOIN {mmogame_aa_stats} st ON st.queryid = irt.queryid AND st.mmogameid=? AND st.numgame=? AND st.auserid = ?
            WHERE irt.mmogameid=? AND irt.numgame=?
            ORDER BY difficulty";
        $questions = $db->get_records_sql( $sql, [$mmogameid, $numgame, $auserid, $mmogameid, $numgame]);

        self::repair_stats($db, $mmogameid, $numgame, $questions, $ids);

        $info = '';
        $ret = [];
        $min = 2;
        $minrand = 1000000;
        $maxrand = 10 * $minrand - 1;
        // First step uses nextquery and serialcorrects.
        // Second step uses only serialcorrects.
        // Last step selects random.
        for ($step = 1; $step <= 3; $step++) {
            while ($count > 0) {
                if (count($questions) == 0) {
                    break;
                }
                $bestscore = 0;
                $first = true;
                $bestquery = null;
                foreach ($questions as $id => $q) {
                    if ($step < 3 && $q->serialcorrects >= $min) {
                        continue;
                    }
                    if ($step == 1 && $numquery < $q->nextquery) {
                        continue;
                    }
                    if ($step == 1 && array_key_exists($q->queryid, $ignore)) {
                        // It is used to nearby split.
                        continue;
                    }
                    $score = abs($theta - $q->difficulty);
                    $score += 1.0 / rand($minrand, $maxrand);
                    if ($first || $score < $bestscore) {
                        $bestscore = $score;
                        $bestquery = $id;
                        $first = false;
                    }
                }
                if ($bestquery === null) {
                    break;
                }
                $ret[] = $questions[$bestquery]->queryid;
                $q = $questions[$bestquery];
                self::log($db, $mmogameid, $numgame, $auserid, $theta, $q->queryid, $q->difficulty,
                    $q->serialcorrects, $q->nextquery, $step, $numquery, $bestscore, $info);
                unset( $questions[$bestquery]);
                $count--;
                $numquery++;
            }
            if ($count == 0 || count($questions) == 0) {
                break;
            }
            $min = 0;
        }

        while ($count < 0 && count($questions) > 0) {
            $id = array_rand( $questions);
            $ret[] = $questions[$id]->queryid;
            unset( $questions[$id]);
        }

        return $ret;
    }

    /**
     * Computes raschProbability.
     *
     * @param float $theta
     * @param float $difficulty
     * @return float|int
     */
    protected static function rasch_probability(float $theta, float $difficulty) {
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
    protected static function update_parameters(float &$theta, float &$difficulty, float $response, float $learningrate = 0.05) {
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
     * @param $db
     * @param int $mmogameid
     * @param int $numgame
     * @param int $auserid
     * @param int $queryid
     * @param bool $iscorrect
     * @return void
     */
    public static function update($db, int $mmogameid, int $numgame, int $auserid, int $queryid, bool $iscorrect) {
        // Read parameters from database.
        $recg = $db->get_record_select( 'mmogame_aa_grades',
            'mmogameid=? AND numgame=? AND auserid=?',
            [$mmogameid, $numgame, $auserid]);
        $theta = $recg !== null ? $recg->theta : 0;

        $reci = $db->get_record_select( 'mmogame_aa_irt',
            'mmogameid=? AND numgame=? AND queryid=?',
            [$mmogameid, $numgame, $queryid]);
        $difficulty = $reci !== null ? $reci->difficulty : 0;

        // Updates parameters.
        self::update_parameters($theta, $difficulty, $iscorrect ? 1 : 0);
        // Saves new values.
        if ($recg !== null) {
            $sql = "UPDATE {mmogame_aa_grades} SET theta=?,timemodified=? WHERE id=?";
            $db->execute( $sql, [$theta, time(), $recg->id]);
        }
        if ($reci !== null) {
            $sql = "UPDATE {mmogame_aa_irt} SET difficulty=?, timemodified=? WHERE id=?";
            $db->execute( $sql, [$difficulty, time(), $reci->id]);
        }
    }

    /**
     * Saves logs to table mmogame_aa_irt_log
     *
     * @param $db
     * @param $mmogameid
     * @param $numgame
     * @param $auserid
     * @param mixed $theta
     * @param $queryid
     * @param $difficulty
     * @param $serialcorrects
     * @param $nextquery
     * @param int $step
     * @param int $numquery
     * @param $bestscore
     * @param string $info
     * @return void
     */
    private static function log($db, $mmogameid, $numgame, $auserid, mixed $theta, $queryid,
            $difficulty, $serialcorrects, $nextquery, int $step, int $numquery, $bestscore, string $info) {
        $db->insert_record( 'mmogame_aa_irt_log',
            ['mmogameid' => $mmogameid,
                'numgame' => $numgame,
                'auserid' => $auserid,
                'theta' => $theta,
                'queryid' => $queryid,
                'difficulty' => $difficulty,
                'serialcorrects' => $serialcorrects == null ? 0 : $serialcorrects,
                'nextquery' => $nextquery == null ? 0 : $nextquery,
                'step' => $step,
                'timecreated' => time(),
                'numquery' => $numquery,
                'bestscore' => $bestscore,
                'info' => $info,
                ]);
    }
}
