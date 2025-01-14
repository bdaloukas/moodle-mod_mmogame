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

namespace mod_mmogame\local\model;

use mod_mmogame\local\mmogame;
use stdClass;

define('STATE_LAST', 1);

/**
 * The class mmogame_model_alone has the code for model Alone
 *
 * @package    mod_mmogame
 * @copyright  2024 Vasilis Daloukas
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mmogame_model_alone {
    /**
     * Administrator can change numgame or state
     *
     * @param stdClass $data
     * @param mmogame $mmogame
     */
    public static function json_setadmin(stdClass $data, mmogame $mmogame): void {
        $rgame = $mmogame->get_rgame();

        if (isset( $data->numgame) && $data->numgame > 0) {
            $rgame->numgame = $data->numgame;
            $mmogame->get_db()->update_record( 'mmogame',
                ['id' => $rgame->id, 'numgame' => $rgame->numgame]);
            $mmogame->update_state( $mmogame->get_rstate()->state);
            $mmogame->set_state( $mmogame->get_rstate()->state);
        } else if (isset( $data->state)) {
            if ($data->state >= 0 && $data->state <= STATE_LAST) {
                $mmogame->update_state( $data->state);
                $mmogame->set_state( $data->state);
            }
        }
    }
}
