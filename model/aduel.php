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
define( 'ERROR_ADUEL_USER2_NULL', 'aduel_user2_null');

/**
 * The class mmogameModel_aduel has the code for model ADuel
 *
 * @package    mod_mmogame
 * @copyright  2024 Vasilis Daloukas
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mmogameModel_aduel {

    /**
     * Return info for administrator
     *
     * @param object $data (not used)
     * @param object $mmogame
     * @param array $ret
     */
    public static function json_getadmin($data, $mmogame, &$ret) {
        $state = $ret['state'] = $mmogame->get_rstate()->state;

        $params = [$mmogame->get_id(), $mmogame->get_numgame];
        $ret['stats_users'] = $mmogame->get_db()->count_records_select( 'mmogame_aa_grades', 'mmogameid=? AND numgame=?', $params);
        $ret['stats_answers'] = $mmogame->get_db()->count_records_select( 'mmogame_quiz_attempts',
            'mmogameid=? AND numgame=? AND timeanswer <> 0', $params);
    }

    /**
     * Administrator can change numgame or state
     *
     * @param object $data
     * @param object $mmogame
     */
    public static function json_setadmin($data, $mmogame) {
        $ret = [];
        if (isset( $data->numgame) && $data->numgame > 0) {
            $mmogame->get_rstate()->state = 0;
            $mmogame->get_db()->update_record( 'mmogame', ['id' => $mmogame->get_id(), 'numgame' => $data->numgame]);
            $mmogame->update_state( $mmogame->get_rstate()->state);
            $mmogame->set_state_json( $mmogame->get_rstate()->state, $ret);
        } else if (isset( $data->state)) {
            if ($data->state >= 0 && $data->state <= MMOGAME_ADUEL_STATE_LAST) {
                $mmogame->update_state( $data->state);
                $mmogame->set_state_json( $data->state, $ret);
            }
        }
    }

    /**
     * Return the aduel record for current $mmogame record
     *
     * @param object $mmogame
     * @param int $newplayer1
     * @param int $newplayer2
     */
    public static function get_aduel($mmogame, &$newplayer1, &$newplayer2) {
        $newplayer1 = $newplayer2 = false;
        $auserid = $mmogame->get_auserid();
        $db = $mmogame->get_db();

        $stat = $db->get_record_select( 'mmogame_aa_stats', 'mmogameid=? AND numgame=? AND auserid=? AND queryid IS NULL',
            [$mmogame->get_id(), $mmogame->get_numgame(), $auserid]);
        if ($stat === false) {
            $stat = new stdClass();
            $stat->percent = $stat->id = $stat->count1 = $stat->count2 = 0;
        };

        // Returns one that is started and not finished.
        $recs = $db->get_records_select( 'mmogame_am_aduel_pairs',
            'mmogameid=? AND numgame=? AND '.
            '(auserid1=? AND timestart1 <> 0 AND isclosed1 = 0 OR auserid2=? AND timestart2 <> 0 AND isclosed2 = 0)',
            [$mmogame->get_id(), $mmogame->get_numgame(), $auserid, $auserid], 'id', '*', 0, 1);
        foreach ($recs as $rec) {
            return $rec;
        }

        // Count1=count alone, Count2=count with oposite.
        if ($stat->count1 <= $stat->count2) {
            return self::get_aduel_new( $mmogame, $newplayer1, $stat);
        }

        // Count1 is bigger than count2.

        // Selects one of the games without oponent.
        $sql = "SELECT a.*, s.percent".
            " FROM {$db->prefix}mmogame_am_aduel_pairs a ".
            " LEFT JOIN {$db->prefix}mmogame_aa_stats s ON ".
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

            if ($count > $mmogame->get_maxalone()) {
                return false;   // Wait an oponent.
            }
            return self::get_aduel_new( $mmogame, $newplayer1, $stat);
        }

        // There are many alone games.
        // Find a game with percent near my percent.
        $map = [];    // The map1 contains games with lower grade and map2 with upper grade.
        foreach ($recs as $rec) {
            $step = $rec->percent <= $stat->percent ? 1 : 2; // 1 means lower than my percent.
            // Try to find the bigger of smaller or small of biggers percent.
            $key = $step.sprintf( '%10.6f %10d', abs( $rec->percent - $stat->percent), $rec->id);
            $map[$key] = $rec;
        }
        ksort( $map);

        foreach ($map as $rec) {
            break;
        }

        // Check if it has a game without oponent.
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
     * @param object $mmogame
     * @param int $newplayer1
     * @param object $stat (the record of table mmogame_aa_stats)
     */
    public static function get_aduel_new($mmogame, &$newplayer1, $stat) {
        $db = $mmogame->get_db();

        $a = ['mmogameid' => $mmogame->get_id(), 'numgame' => $mmogame->get_numgame(), 'auserid1' => $mmogame->get_auserid(),
            'timestart1' => time(), 'timelimit' => $mmogame->get_timelimit(), 'isclosed1' => 0, 'isclosed2' => 0, ];
        $id = $db->insert_record( 'mmogame_am_aduel_pairs', $a);

        $newplayer1 = true;
        if ($stat->id == 0) {
            $mmogame->get_qbank()->update_stats( $mmogame->get_auserid(), null, null, 0, 0, 0, ['count1' => 1]);
        } else {
            $db->update_record( 'mmogame_aa_stats', ['id' => $stat->id, 'count1' => $stat->count1 + 1]);
        }

        return $db->get_record_select( 'mmogame_am_aduel_pairs', 'id=?', [$id]);
    }

    /**
     * Finishes the attempt for a duel game.
     *
     * @param object $mmogame The game instance that contains methods for updating grades and records.
     * @param object $aduel The duel object containing user IDs and other related information.
     * @param boolean $iscorrect1 Indicates if the first player's attempt is correct.
     * @param boolean $iscorrect2 Indicates if the second player's attempt is correct.
     * @param boolean $isclosed1 Indicates if the first player's duel is closed.
     * @param boolean $isclosed2 Indicates if the second player's duel is closed.
     * @param int $scorewin The score awarded if the first player wins.
     * @param int $scorelose The score awarded if the first player loses.
     * @param int $scoredraw The score awarded if the duel ends in a draw.
     * @param int $adueladdscore The score to be added for the duel, passed by reference.
     * @return int The score of the current user based on the outcome of the attempt.
     */
    public static function finish_attempt($mmogame, $aduel, $iscorrect1, $iscorrect2,
    $isclosed1, $isclosed2, $scorewin, $scorelose, $scoredraw, &$adueladdscore) {
        $score1 = $iscorrect1 == $iscorrect2 ? ($iscorrect1 ? $scoredraw : $scorelose) : ($iscorrect1 ? $scorewin : $scorelose);
        $mmogame->get_qbank()->update_grades( $aduel->auserid1, $score1, 0, 1);

        $score2 = $iscorrect1 == $iscorrect2 ? ($iscorrect1 ? $scoredraw : $scorelose) : ($iscorrect2 ? $scorewin : $scorelose);
        $mmogame->get_qbank()->update_grades( $aduel->auserid2, $score2, 0, 1);

        $mmogame->get_db()->update_record( 'mmogame_am_aduel_pairs',
            ['id' => $aduel->id, 'isclosed1' => $isclosed1, 'isclosed2' => $isclosed2]);

        $adueladdscore = $mmogame->get_auserid() == $aduel->auserid1 ? $score2 : $score1;

        return $mmogame->get_auserid() == $aduel->auserid1 ? $score1 : $score2;
    }

    /**
     * Return an attempt record of the game
     *
     * @param object $mmogame
     * @param object $aduel
     * @return object (the attempt record)
     */
    public static function get_attempt($mmogame, $aduel) {

        $table = $mmogame->get_table_attempts();
        $db = $mmogame->get_db();
        $aduel = $mmogame->get_aduel();

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

        $a = ['id' => $mmogame->get_aduel()->id];
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
     * @param object $mmogame
     */
    public static function delete($mmogame) {
        $mmogame->get_db()->delete_records_select( 'mmogame_am_aduel_pairs', 'id=?', [$mmogame->get_aduel()->id]);
    }
}
