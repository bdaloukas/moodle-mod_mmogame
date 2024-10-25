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
 * This file contains the model Alone
 *
 * @package    mod_mmogame
 * @copyright  2024 Vasilis Daloukas
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * The class mmogameModel_alone has the code for model Alone
 *
 * @package    mod_mmogame
 * @copyright  2024 Vasilis Daloukas
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mmogameModel_alone {
    /**
     * Administrator can change numgame or state
     *
     * @param object $data
     * @param object $game
     */
    public static function json_setadmin($data, $game) {
        $instance = $game->get_rinstance();

        $ret = [];
        if (isset( $data->numgame) && $data->numgame > 0) {
            $instance->numgame = $data->numgame;
            $game->get_db()->update_record( 'mmogame_aa_instances',
                ['id' => $instance->id, 'numgame' => $instance->numgame]);
            $game->update_state( $game->get_rstate()->state);
            $game->set_state_json( $game->get_rstate()->state, $ret);
        } else if (isset( $data->state)) {
            if ($data->state >= 0 && $data->state <= MMOGAME_ALONE_STATE_LAST) {
                $game->update_state( $data->state);
                $game->set_state_json( $data->state, $ret);
            }
        }
    }

    /**
     * Return info for administrator
     *
     * @param object $data (not used)
     * @param object $game
     * @param array $ret
     */
    public static function json_getadmin($data, $game, &$ret) {
        $instance = $game->get_rinstance();

        $state = $ret['state'] = $game->get_rstate()->state;

        $ret['stats_users'] = $game->get_db()->count_records_select( 'mmogame_aa_grades', 'ginstanceid=? AND numgame=?',
            [$instance->id, $instance->numgame]);
        $ret['stats_answers'] = $game->get_db()->count_records_select( 'mmogame_quiz_attempts',
            'ginstanceid=? AND numgame=?',
            [$instance->id, $instance->numgame]);
    }
}
