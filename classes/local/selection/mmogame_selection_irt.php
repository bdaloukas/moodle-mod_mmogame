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
 * IRT 1PL question selection implementation for MMOGame.
 *
 * @package    mod_mmogame
 * @copyright  2024 Vasilis Daloukas
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_mmogame\local\selection;

use stdClass;

/**
 * Selects questions and updates parameters using an item response theory model.
 */
class mmogame_selection_irt extends mmogame_selection {
    /**
     * Get queries.
     *
     * @param array $ids
     * @param int $count
     * @param int $numattempt
     * @return array
     */
    public function get_queries(array $ids, int $count, int $numattempt): array {
        $mmogame = $this->get_mmogame();
        $db = $mmogame->get_db();
        $mmogameid = $mmogame->get_id();
        $auser = $mmogame->get_auser();
        $numgame = $mmogame->get_numgame();
        $this->check_stats($auser, $ids);

        $sortnum = 1;
        $sort1 = 'st.serialcorrects,st.counterror DESC,st.countused,ABS(irt.difficulty-?),st.randkey';
        $sort2 = 'st.serialcorrects,st.counterror DESC,irt.difficulty DESC,st.randkey';

        // Get player's skill rating (theta).
        $rgrade = $mmogame->get_rgrade($mmogame->get_auserid());
        $theta = $rgrade !== null ? $rgrade->theta : 0;

        // Retrieve all questions with player stats.
        $sql = "SELECT irt.queryid, irt.difficulty, st.counterror,
            st.countused, st.countcorrect, st.serialcorrects, st.nextattempt, ABS(irt.difficulty - ?) as dif
            FROM {mmogame_as_irt} irt
            LEFT JOIN {mmogame_aa_stats} st ON st.queryid = irt.queryid AND st.mmogameid=? AND st.numgame=? AND st.auserid = ?
            WHERE irt.mmogameid=? AND irt.numgame=?
            ORDER BY " . ($sortnum === 1 ? $sort1 : $sort2);
        $params = [$theta, $mmogameid, $mmogame->get_numgame(), $auser->id, $mmogameid, $numgame];
        if ($sortnum === 1) {
            $params[] = $theta;
        }
        $recs = $db->get_records_sql(
            $sql,
            $params,
            0,
            2 * $count
        );
        $found = [];
        foreach ($recs as $rec) {
            $found[$rec->queryid] = $rec->queryid;
        }

        return self::balance_categories($found, $count);
    }

    /**
     * Computes raschProbability.
     *
     * @param float $theta
     * @param float $difficulty
     * @return float
     */
    protected function rasch_probability(float $theta, float $difficulty): float {
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
    protected function update_parameters(
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
     * Updates tables mmogame_aa_grades and mmogame_as_irt.
     *
     * @param int $queryid
     * @param bool $iscorrect
     * @param ?float $theta
     * @param ?float $difficulty
     * @return void
     */
    public function update(int $queryid, bool $iscorrect, ?float &$theta, ?float &$difficulty): void {
        $mmogame = $this->mmogame;
        $db = $mmogame->get_db();

        // Read parameters from database.
        $recg = $mmogame->get_rgrade($mmogame->get_auserid());
        $theta = $recg !== null ? $recg->theta : 0;

        $reci = $db->get_record_select(
            'mmogame_as_irt',
            'mmogameid=? AND numgame=? AND queryid=?',
            [$mmogame->get_id(), $mmogame->get_numgame(), $queryid]
        );
        $difficulty = $reci !== null ? $reci->difficulty : 0;

        // Updates parameters.
        $this->update_parameters($theta, $difficulty, $iscorrect ? 1 : 0);

        // Saves new values.
        if ($recg !== null) {
            $db->update_record(
                'mmogame_aa_grades',
                ['id' => $recg->id, 'theta' => $theta, 'timemodified' => time()]
            );
        }
        if ($reci !== null) {
            $db->update_record(
                'mmogame_as_irt',
                ['id' => $reci->id, 'difficulty' => $difficulty, 'timemodified' => time()]
            );
        }
    }

    /**
     * Selects questions for revision
     * @return array
     */
    public function idea(): array {
        $mmogame = $this->mmogame;
        $db = $mmogame->get_db();
        $auser = $mmogame->get_auser();

        // Get player's skill rating (theta).
        $rec = $mmogame->get_rgrade($auser->id);
        $theta = $rec !== null ? $rec->theta : 0;

        // Retrieve all questions with player stats.
        $sql = "SELECT irt.queryid, irt.difficulty, st.counterror,
            st.countused, st.countcorrect, st.serialcorrects, st.nextquery
            FROM {mmogame_as_irt} irt
            LEFT JOIN {mmogame_aa_stats} st
                ON st.queryid = irt.queryid AND st.mmogameid=irt.mmogameid AND st.numgame=irt.numgame AND st.auserid = ?
            WHERE irt.mmogameid=? AND irt.numgame=?
            ORDER BY st.serialcorrects,st.counterror DESC,ABS(irt.difficulty - ?)";
        $questions = $db->get_records_sql($sql, [$auser->id, $mmogame->get_id(), $mmogame->get_numgame(), $theta], 0, 10);

        $ret = [];
        foreach ($questions as $question) {
            $ret[] = $question->queryid;
        }

        return $ret;
    }

    /**
     * Computes how to wait before reshowing one query
     *
     * @param int $queryid
     * @param int $iscorrect
     * @return int
     */
    public function compute_addnextattempt(int $queryid, int $iscorrect): int {
        $mmogame = $this->mmogame;
        $auserid = $mmogame->get_auser()->id;

        if ($iscorrect) {
            return 10;
        } else {
            $db = $this->mmogame->get_db();
            $irt = $db->get_record_select(
                'mmogame_as_irt',
                'mmogameid=? AND numgame=? AND queryid=?',
                [$mmogame->get_id(), $mmogame->get_numgame(), $queryid]
            );
            $rgrade = $mmogame->get_rgrade($auserid);
            if ($rgrade === null || $irt === null) {
                return 0;
            } else if ($irt->difficulty > $rgrade->theta) {
                // Incorrect & difficult query.
                return 5;
            } else {
                // Incorrect & easy query.
                return 7;
            }
        }
    }

    /**
     * Checks if you have to update mmogame_as_heuristic
     *
     * @return false
     */
    public function can_update_heuristic(): bool {
        return false;
    }

    /**
     * Returns the name of fields that used for comparing two students
     *
     * @return string
     */
    public function get_field_rankvalue1(): string {
        return 'theta';
    }

    /**
     * Computes the ranking of query $queryid
     *
     * @param int $queryid
     * @return ?int
     */
    public function get_rankquery(int $queryid): ?int {
        return $this->get_rankquery_table('mmogame_as_irt', 'difficulty', $queryid);
    }

    /**
     * Ensure that the in-memory questions list and the mmogame_aa_stats table are in sync with the defined set of query IDs ($ids).
     *
     * - Removes questions whose queryid is not present in $ids.
     * - Inserts missing rows into mmogame_aa_stats for queryids in $ids.
     *
     * @param array $ids Map: queryid => categoryid.
     * @return int
     */
    protected function repair_stats(array $ids): int {
        $ret = parent::repair_stats($ids);
        $mmogame = $this->mmogame;

        $rstate = $mmogame->get_rstate();
        $hashname = $mmogame->get_rgame()->selection . json_encode($ids, JSON_PRETTY_PRINT);
        if (md5($hashname) === $rstate->hashcompute) {
            return $ret;
        }

        $numgame = $mmogame->get_numgame();
        $db = $mmogame->get_db();

        $mapids = [];
        foreach ($ids as $queryid => $categoryid) {
            $mapids[$queryid] = $queryid;
        }

        $irts = $db->get_records_select(
            'mmogame_as_irt',
            'mmogameid=? AND numgame=?',
            [$mmogame->get_id(), $numgame],
            '',
            'id,queryid,isvalid'
        );
        // 1. Deletes records from mmogame_aa_stats belonging to the invalid queries.
        foreach ($irts as $irt) {
            $queryid = $irt->queryid;
            if (!array_key_exists($queryid, $mapids)) {
                if ($irt->isvalid !== 0) {
                    $db->update_record('mmogame_as_irt', ['id' => $irt->id, 'isvalid' => 0]);
                }
                continue;
            }

            unset($mapids[$queryid]);
            if ($irt->isvalid === 0) {
                $db->update_record('mmogame_as_irt', ['id' => $irt->id, 'isvalid' => 1]);
            }
        }

        // 2. Insert new records mmogame_aa_stats.
        foreach ($mapids as $queryid) {
            $db->insert_record(
                'mmogame_as_irt',
                [
                    'mmogameid' => $mmogame->get_id(),
                    'numgame' => $numgame,
                    'queryid' => $queryid,
                    'isvalid' => 1,
                    'difficulty' => 0,
                    'timemodified' => time(),
                ]
            );
        }

        $rstate->hashcompute = md5($hashname);
        $db->update_record(
            'mmogame_aa_states',
            [
                'id' => $rstate->id,
                'hashcompute' => $rstate->hashcompute,
            ]
        );

        return $ret;
    }
}
