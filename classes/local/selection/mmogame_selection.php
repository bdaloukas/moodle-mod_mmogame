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

namespace mod_mmogame\local\selection;

use mod_mmogame\local\mmogame;
use stdClass;

/**
 * The class mmogame_selection is an abstract class for selecting questions
 */
abstract class mmogame_selection {
    /** @var mmogame $mmogame: mmogame to be used. */
    protected mmogame $mmogame;

    /**
     * Constructors that saves $mmogame to variable
     *
     * @param stdClass $mmogame
     */
    public function __construct(stdClass $mmogame) {
        $this->mmogame = $mmogame;
    }

    /**
     * Returns the name of fields that used for comparing two students
     *
     * @return string
     */
    public function get_field_rankvalue1(): string {
        return 'countmastered';
    }

    /**
     * Return $mmogame (local variable)
     *
     * @return mmogame
     */
    protected function get_mmogame(): mmogame {
        return $this->mmogame;
    }

    /**
     * Select $count queries using selection algorithm.
     *
     * @param array $ids
     * @param int $count
     * @param int $numattempt
     * @return array
     */
    abstract public function get_queries(array $ids, int $count, int $numattempt): array;

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
        $mmogame = $this->mmogame;
        $numgame = $mmogame->get_numgame();
        $auserid = $mmogame->get_auserid();
        $db = $mmogame->get_db();

        $mapids = [];
        foreach ($ids as $queryid => $categoryid) {
            $mapids[$queryid] = $queryid;
        }
        $countqueries = count($mapids);

        $stats = $db->get_records_select(
            'mmogame_aa_stats',
            'mmogameid=? AND numgame=? AND auserid = ?',
            [$mmogame->get_id(), $numgame, $mmogame->get_auserid()],
            '',
            'id,queryid'
        );
        // 1. Deletes records from mmogame_aa_stats belonging to the invalid queries.
        foreach ($stats as $stat) {
            $queryid = $stat->queryid;
            if (!array_key_exists($queryid, $mapids)) {
                if ($stat->isvalid !== 0) {
                    $db->update_record('mmogame_aa_stats', ['id' => $stat->id, 'isvalid' => 0]);
                }
                continue;
            }

            unset($mapids[$queryid]);
            if ($stat->isvalid === 0) {
                $db->update_record('mmogame_aa_stats', ['id' => $stat->id, 'isvalid' => 1]);
            }
        }

        // 2. Insert new records mmogame_aa_stats.
        foreach ($mapids as $queryid) {
            $db->insert_record(
                'mmogame_aa_stats',
                [
                    'mmogameid' => $mmogame->get_id(),
                    'numgame' => $numgame,
                    'queryid' => $queryid,
                    'auserid' => $auserid,
                    'isvalid' => 1,
                    'counterror' => 0,
                    'timeerror' => 0,
                    'countcorrect' => 0,
                    'countused' => 0,
                    'islastcorrect' => 0,
                    'nextquery' => 0,
                    'nextattempt' => 0,
                    'randkey' => mt_rand(0, PHP_INT_MAX),
                ]
            );
        }

        return $countqueries;
    }

    /**
     * Checks if is needed to recompute stats (after changing selection algorithm)
     *
     * @param stdClass $auser
     * @param array $ids
     * @return void
     */
    protected function check_stats(stdClass $auser, array $ids): void {
        $mmogame = $this->mmogame;
        $db = $this->mmogame->get_db();
        $hashname = 'h' . implode(',', $ids);
        if (md5($hashname) !== $auser->hashcompute) {
            $countqueries = $this->repair_stats($ids);
            $db->update_record(
                'mmogame_aa_users',
                ['id' => $auser->id, 'hashcompute' => md5($hashname)]
            );
            if ($mmogame->get_rstate()->countqueries != $countqueries) {
                $db->update_record(
                    'mmogame_aa_states',
                    ['id' => $mmogame->get_rstate()->id, 'countqueries' => $countqueries]
                );
            }
        }
    }

    /**
     * Balance selected query IDs across categories.
     *
     * @param array $found Array with queryid as key and categoryid as value.
     * @param int $count Number of query IDs to return.
     * @return array Selected query IDs.
     */
    protected static function balance_categories(array $found, int $count): array {
        if ($count <= 0 || empty($found)) {
            return [];
        }

        $bycategory = [];

        foreach ($found as $queryid => $categoryid) {
            $bycategory[$categoryid][] = (int) $queryid;
        }

        foreach ($bycategory as &$queryids) {
            shuffle($queryids);
        }
        unset($queryids);

        $selected = [];

        while (count($selected) < $count && !empty($bycategory)) {
            foreach (array_keys($bycategory) as $categoryid) {
                if (empty($bycategory[$categoryid])) {
                    unset($bycategory[$categoryid]);
                    continue;
                }

                $selected[] = array_shift($bycategory[$categoryid]);

                if (count($selected) >= $count) {
                    break;
                }
            }
        }

        return $selected;
    }

    /**
     * Checks if you have to update mmogame_as_heuristic
     *
     * @return true (by default)
     */
    public function can_update_heuristic(): bool {
        return true;
    }

    /**
     * Used for IRT updating
     *
     * @param int $queryid
     * @param bool $iscorrect
     * @param float|null $theta
     * @param float|null $difficulty
     * @return void
     */
    public function update(int $queryid, bool $iscorrect, ?float &$theta, ?float &$difficulty): void {
    }
}
