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

/**
 * This file contains the model ADuel
 *
 * @package    mod_mmogame
 * @copyright  2024 Vasilis Daloukas
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_mmogame\local\model;

use mod_mmogame\local\mmogame;
use stdClass;

/**
 * The class mmogame_model_aduel has the code for model ADuel
 *
 * @package    mod_mmogame
 * @copyright  2024 Vasilis Daloukas
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mmogame_model_aduel {

    /**
     * Administrator can change numgame or state
     *
     * @param object $data
     * @param mmogame $game
     */
    public static function json_setadmin(object $data, mmogame $game) {
        if (isset( $data->numgame) && $data->numgame > 0) {
            $game->get_rstate()->state = 0;
            $game->get_db()->update_record( 'mmogame', ['id' => $game->get_id(), 'numgame' => $data->numgame]);
            $game->update_state( $game->get_rstate()->state);
            $game->set_state( $game->get_rstate()->state);
        } else if (isset( $data->state)) {
            if ($data->state >= 0 && $data->state <= STATE_LAST) {
                $game->update_state( $data->state);
                $game->set_state( $data->state);
            }
        }
    }

    /**
     * Return the aduel record for current $mmogame record
     *
     * @param mmogame $mmogame
     * @param int $maxalone
     * @param bool $newplayer1
     * @param bool $newplayer2
     * @return false|mixed
     */
    public static function get_aduel(mmogame $mmogame, int $maxalone, bool &$newplayer1, bool &$newplayer2) {
        $newplayer1 = $newplayer2 = false;
        $auserid = $mmogame->get_auserid();
        $db = $mmogame->get_db();

        $stat = $db->get_record_select( 'mmogame_aa_stats', 'mmogameid=? AND numgame=? AND auserid=? AND queryid IS NULL',
            [$mmogame->get_id(), $mmogame->get_numgame(), $auserid]);
        if ($stat === false) {
            $stat = new stdClass();
            $stat->percent = $stat->id = $stat->count1 = $stat->count2 = 0;
        }

        // Returns one that is started and not finished.
        $recs = $db->get_records_select( 'mmogame_am_aduel_pairs',
            'mmogameid=? AND numgame=? AND '.
            '(auserid1=? AND timestart1 <> 0 AND isclosed1 = 0 OR auserid2=? AND timestart2 <> 0 AND isclosed2 = 0)',
            [$mmogame->get_id(), $mmogame->get_numgame(), $auserid, $auserid], 'id', '*', 0, 1);
        $rec = false;
        foreach ($recs as $rec) {
            return $rec;
        }
        // Count1=count alone, Count2=count with an opposite.
        if ($stat->count1 <= $stat->count2) {
            return self::get_aduel_new( $mmogame, $newplayer1, $stat);
        }

        // Count1 is bigger than count2.

        // Selects one of the games without opponent.
        $sql = "SELECT a.*, s.percent".
            " FROM {mmogame_am_aduel_pairs} a ".
            " LEFT JOIN {mmogame_aa_stats} s ON ".
            "a.mmogameid=s.mmogameid AND a.numgame=s.numgame AND a.auserid1=s.auserid AND s.queryid IS NULL AND numteam IS NULL".
            " WHERE a.auserid2 IS NULL AND a.mmogameid=? AND a.numgame=? AND a.auserid1<>? AND a.isclosed1 = 1";
        $recs = $db->get_records_sql( $sql, [$mmogame->get_id(), $mmogame->get_numgame(), $auserid]);

        if ($stat->count1 - $stat->count2 == 1) {
            if (count( $recs) < 3) {
                return self::get_aduel_new( $mmogame, $newplayer1, $stat);
            }
        }

        if (count( $recs) == 0) {
            $count = $db->count_records_select( 'mmogame_am_aduel_pairs',
                'mmogameid=? AND numgame=? AND auserid1 = ? AND auserid2 IS NULL',
                [$mmogame->get_id(), $mmogame->get_numgame(), $auserid]);
            if ($count > $maxalone) {
                return false;   // Wait an opponent.
            }
            return self::get_aduel_new( $mmogame, $newplayer1, $stat);
        }

        // There are many "alone" games.
        // Find a game with percent near my percent.
        $map = [];    // The map1 contains games with lower grade and map2 with upper grade.
        foreach ($recs as $rec) {
            $step = $rec->percent <= $stat->percent ? 1 : 2; // 1 mean lower than my percent.
            // Try to find the bigger of smaller or small of bigger percent.
            $key = $step.sprintf( '%10.6f %10d', abs( $rec->percent - $stat->percent), $rec->id);
            $map[$key] = $rec;
        }
        ksort( $map);

        foreach ($map as $rec) {
            break;
        }

        // Check if it has a game without opponent.
        $rec->auserid2 = $auserid;
        $rec->timestart2 = time();
        $db->update_record( 'mmogame_am_aduel_pairs', ['id' => $rec->id, 'auserid2' => $auserid, 'timestart2' => time()]);
        $newplayer2 = true;
        if ($stat->id == 0) {
            $mmogame->get_qbank()->update_stats( $mmogame->get_auserid(), null, null, 0, 0, 0, ['count2' => 1]);
        } else {
            $db->update_record( 'mmogame_aa_stats', ['id' => $stat->id, 'count2' => $stat->count2 + 1]);
        }
        return $rec;
    }

    /**
     * Return the new aduel record for current $mmogame
     *
     * @param mmogame $mmogame
     * @param bool $newplayer1
     * @param object $stat (the record of table mmogame_aa_stats)
     * @return mixed
     */
    public static function get_aduel_new(mmogame $mmogame, bool &$newplayer1, object $stat) {
        $db = $mmogame->get_db();

        $a = ['mmogameid' => $mmogame->get_id(), 'numgame' => $mmogame->get_numgame(), 'auserid1' => $mmogame->get_auserid(),
            'timestart1' => time(), 'timelimit' => $mmogame->get_timelimit(), 'isclosed1' => 0, 'isclosed2' => 0, ];
        $id = $db->insert_record( 'mmogame_am_aduel_pairs', $a);

        $newplayer1 = true;
        if ($stat->id == 0) {
            $mmogame->get_qbank()->update_stats( $mmogame->get_auserid(), 0, 0, 0, 0, 0, ['count1' => 1]);
        } else {
            $db->update_record( 'mmogame_aa_stats', ['id' => $stat->id, 'count1' => $stat->count1 + 1]);
        }

        return $db->get_record_select( 'mmogame_am_aduel_pairs', 'id=?', [$id]);
    }

    /**
     * Return an attempt record of the game
     *
     * @param mmogame $mmogame
     * @param object $aduel
     * @return false|object (the attempt record)
     */
    public static function get_attempt(mmogame $mmogame, object $aduel) {

        $table = $mmogame->get_table_attempts();
        $db = $mmogame->get_db();

        $recs = $mmogame->get_db()->get_records_select( $table, "auserid=? AND numgame=? AND numteam=? AND timeanswer=0",
            [$mmogame->get_auserid(), $mmogame->get_numgame(), $aduel->id], 'numattempt');
        $time = time();

        foreach ($recs as $rec) {
            if ($rec->timeclose > $time || $rec->timeclose == 0) {
                if ($rec->timestart == 0) {
                    $rec->timestart = time();
                    $rec->timeclose = $rec->timestart + $aduel->timelimit;
                    $db->update_record( $table, ['id' => $rec->id, 'timestart' => $rec->timestart, 'timeclose' => $rec->timeclose]);
                }
                return $rec;
            }
            if ($rec->timestart == 0) {
                $rec->timestart = $time;
                $rec->timeclose = $time + $aduel->timelimit;
                $db->update_record( $table, ['id' => $rec->id, 'timestart' => $rec->timestart, 'timeclose' => $rec->timeclose]);
                return $rec;
            }
        }

        $a = ['id' => $aduel->id];
        if ($mmogame->get_auserid() == $aduel->auserid1) {
            $a['isclosed1'] = 1;
        } else {
            $a['isclosed2'] = 1;
        }
        $mmogame->get_db()->update_record( 'mmogame_am_aduel_pairs', $a);

        return false;
    }

    /**
     * Deletes all pairs (table mmogame_am_aduel_pairs)
     *
     * @param mmogame $mmogame
     */
    public static function delete(mmogame $mmogame) {
        $mmogame->get_db()->delete_records_select( 'mmogame_am_aduel_pairs', 'mmogameid=?', [$mmogame->get_id()]);
    }
}
