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
 * @package    mod_mmogame
 * @copyright  2024 Vasilis Daloukas
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_mmogame\local\selection;

/**
 * The class mmogame_selection_heuristic selects queries using heuristic algorithm
 */
class mmogame_selection_heuristic extends mmogame_selection {
    /**
     * * Get queries.
     *
     * @param array $ids
     * @param int $count
     * @param int $numattempt
     * @return array
     */
    public function get_queries(array $ids, int $count, int $numattempt): array {
        $mmogame = $this->get_mmogame();
        $db = $this->mmogame->get_db();
        $mmogameid = $mmogame->get_id();
        $auser = $mmogame->get_auser();
        $this->check_stats($auser, $ids);

        $sort1 = 'serialcorrects, countused, countcorrect, timeerror, randkey';
        $sort2 = 'serialcorrects, countused, countcorrect, timeerror, nextattempt, randkey';
        $found = [];
        $recs = $db->get_records_select(
            'mmogame_aa_stats',
            'mmogameid=? AND numgame=? AND auserid=? AND nextattempt <= ?',
            [$mmogameid, $mmogame->get_numgame(), $auser->id, $numattempt],
            $sort1,
            'id,queryid',
            0,
            2 * $count
        );
        foreach ($recs as $rec) {
            $found[$rec->queryid] = $rec->queryid;
        }
        if (count($found) < $count) {
            $recs = $db->get_records_select(
                'mmogame_aa_stats',
                'mmogameid=? AND numgame=? AND auserid=?',
                [$mmogameid, $mmogame->get_numgame(), $auser->id, $numattempt],
                $sort2,
                'id,queryid',
                0,
                $count - count($found)
            );
            foreach ($recs as $rec) {
                $found[$rec->queryid] = $rec->queryid;
            }
        }

        return self::balance_categories($found, $count);
    }

    /**
     * Create missing records in mmogame_as_heuristic
     *
     * @param int $queryid
     * @param bool $iscorrect
     */
    public function update_stats(int $queryid, bool $iscorrect) {
        $mmogame = $this->mmogame;
        $db = $mmogame->get_db();
        $rgame = $mmogame->get_rgame();

        $rec = $db->get_record_select(
            'mmogame_as_heuristic',
            'mmogameid=? AND numgame=? AND queryid=?',
            [$rgame->id, $rgame->numgame, $queryid]
        );
        if ($rec !== null) {
            $a = ['id' => $rec->id, 'countused' => ++$rec->countused];
            if ($iscorrect) {
                $a['countcorrect'] = ++$rec->countcorrect;
            }
            $a['percent'] = $rec->countcorrect / $rec->countused;
            $db->update_record(
                'mmogame_as_heuristic',
                $a
            );
        } else {
            $a = [
                'mmogameid' => $rgame->id,
                'numgame' => $rgame->numgame,
                'queryid' => $queryid,
                'countcorrect' => $iscorrect ? 1 : 0,
                'countused' => 1,
                'percent' => $iscorrect ? 1 : 0,
            ];
            $db->insert_record('mmogame_as_heuristic', $a);
        }
    }

    /**
     * Computes how to wait before reshowing one query
     *
     * @param int $queryid
     * @param int $iscorrect
     * @return int
     */
    public function compute_addnextattempt(int $queryid, int $iscorrect): int {
        return $iscorrect ? 10 : 5;
    }
}
