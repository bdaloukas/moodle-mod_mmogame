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
    public static function setadmin(object $data, mmogame $game): void {
        if (isset( $data->numgame) && $data->numgame > 0) {
            $game->get_rstate()->state = 0;
            $game->get_db()->update_record( 'mmogame', ['id' => $game->get_id(), 'numgame' => $data->numgame]);
            $game->update_state( $game->get_rstate()->state);
            $game->set_state( $game->get_rstate()->state);
            $game->get_rgame()->numgame = $data->numgame;
        } else if (isset( $data->state)) {
            if ($data->state >= 0 && $data->state <= 1) {
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
     * @param array|null $auserids
     * @param bool $isaduel
     * @return ?stdClass
     */
    public static function get_aduel(mmogame $mmogame, int $maxalone, bool &$newplayer1, bool &$newplayer2, ?array $auserids,
            bool $isaduel): ?stdClass {
        $newplayer1 = $newplayer2 = false;
        $auserid = $mmogame->get_auserid();
        $db = $mmogame->get_db();

        // Returns one that is started and not finished.
        $select = 'mmogameid=? AND numgame=? AND '.
            '(auserid1=? AND timestart1 <> 0 AND isclosed1 = 0 OR auserid2=? AND timestart2 <> 0 AND isclosed2 = 0)';
        $params = [$mmogame->get_id(), $mmogame->get_numgame(), $auserid, $auserid];
        if ($auserids !== null && count($auserids)) {
            [$insql, $inparams] = $mmogame->get_db()->get_in_or_equal( $auserids);
            $select .= " AND auserid1 $insql";
            $params = array_merge($params, $inparams);
        }
        $recs = $db->get_records_select( 'mmogame_am_aduel_pairs',
            $select, $params, 'id', '*', 0, 1);
        if (count( $recs) > 0) {
            return reset( $recs);
        }

        // Computes the theta of current user.
        $grade = $db->get_record_select( 'mmogame_aa_grades', 'mmogameid=? AND numgame=? AND auserid=?',
            [$mmogame->get_id(), $mmogame->get_numgame(), $auserid]);
        $theta = $grade != null ? $grade->theta : 0;

        if ($isaduel && $grade->countalone > 0) {
            $select = 'mmogameid=? AND numgame=? AND auserid1 <> ? AND isclosed1 = 1 AND isclosed2 = 0 AND auserid2 IS NULL';
            $params = [$mmogame->get_id(), $mmogame->get_numgame(), $auserid];
            if (count($auserids)) {
                [$insql, $inparams] = $mmogame->get_db()->get_in_or_equal($auserids);
                $select .= " AND auserid1 $insql";
                $params = array_merge($params, $inparams);
            }
            $pairs = $db->get_records_select('mmogame_am_aduel_pairs',
                $select, $params, 'id', '*', 0, 5);
        } else {
            $pairs = [];
        }
        if (count( $pairs ) == 0) {
            // There are no open aduel. Create a new one.
            $count = $db->count_records_select( 'mmogame_am_aduel_pairs',
                'mmogameid=? AND numgame=? AND auserid1 = ? AND auserid2 IS NULL',
                [$mmogame->get_id(), $mmogame->get_numgame(), $auserid]);
            if ($count > $maxalone) {
                return null;   // Wait an opponent.
            }
            $newplayer1 = true;
            return self::get_aduel_new( $mmogame);
        }

        $map = [];
        foreach ($pairs as $pair) {
            $key = round(1000000 * abs($pair->score - $theta));
            $map[$key] = $pair;
        }
        ksort( $map);

        $pair = reset( $map);

        // Check if it has a game without opponent.
        $pair->auserid2 = $auserid;
        $pair->timestart2 = time();
        $db->update_record( 'mmogame_am_aduel_pairs', ['id' => $pair->id, 'auserid2' => $auserid, 'timestart2' => time()]);

        $db->update_record( 'mmogame_aa_grades', ['id' => $grade->id, 'countalone' => $grade->countalone - 1]);

        $newplayer2 = true;

        return $pair;
    }

    /**
     * Return the new aduel record for current $mmogame
     *
     * @param mmogame $mmogame
     * @return ?stdClass
     */
    public static function get_aduel_new(mmogame $mmogame): ?stdClass {
        $db = $mmogame->get_db();

        $a = ['mmogameid' => $mmogame->get_id(), 'numgame' => $mmogame->get_numgame(), 'auserid1' => $mmogame->get_auserid(),
            'timestart1' => time(), 'timelimit' => $mmogame->get_timelimit(), 'isclosed1' => 0, 'isclosed2' => 0, ];
        $id = $db->insert_record( 'mmogame_am_aduel_pairs', $a);

        return $db->get_record_select( 'mmogame_am_aduel_pairs', 'id=?', [$id]);
    }

    /**
     * Return an attempt record of the game
     *
     * @param mmogame $mmogame
     * @param stdClass $aduel
     * @return ?stdClass (the attempt record)
     */
    public static function get_attempt(mmogame $mmogame, stdClass $aduel): ?stdClass {

        $table = $mmogame->get_table_attempts();
        $db = $mmogame->get_db();

        $recs = $mmogame->get_db()->get_records_select( $table, "auserid=? AND numgame=? AND numteam=? AND timeanswer=0",
            [$mmogame->get_auserid(), $mmogame->get_numgame(), $aduel->id], 'numattempt');
        $time = time();

        // Select the first that is no started.
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
        return null;
    }

    /**
     * Return an attempt record of the game
     *
     * @param mmogame $mmogame
     * @param stdClass $aduel
     * @param bool $all
     * @return array (array of attempts record)
     */
    public static function get_attempts(mmogame $mmogame, stdClass $aduel, bool $all = false): array {

        $table = $mmogame->get_table_attempts();

        return $mmogame->get_db()->get_records_select( $table,
            "auserid=? AND numgame=? AND numteam=? ".($all ? "" : " AND timeanswer=0"),
            [$mmogame->get_auserid(), $mmogame->get_numgame(), $aduel->id],
            'numattempt');
    }

    /**
     * Deletes all pairs (table mmogame_am_aduel_pairs)
     *
     * @param mmogame $mmogame
     */
    public static function delete(mmogame $mmogame): void {
        $mmogame->get_db()->delete_records_select( 'mmogame_am_aduel_pairs', 'mmogameid=?', [$mmogame->get_id()]);
    }

    /**
     * Closes the pair.
     *
     * @param mmogame $mmogame
     * @param $aduelid
     * @param $score
     */
    public static function close_user1(mmogame $mmogame, $aduelid, $score) {
        $params = ['id' => $aduelid, 'isclosed1' => 1, 'score' => $score];

        $mmogame->get_db()->update_record( 'mmogame_am_aduel_pairs', $params);
    }
}
