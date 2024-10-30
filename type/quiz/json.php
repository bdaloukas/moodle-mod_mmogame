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
 * Json file
 *
 * @package    mmogametype_quiz
 * @copyright  2024 Vasilis Daloukas
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(dirname(__FILE__) . '/../../model/aduel.php');

/**
 * Fill the array $ret with info about the current attempt.
 *
 * @param object $data
 * @param object $game
 * @param array $ret
 */
function mmogame_json_quiz_getattempt($data, $mmogame, &$ret) {
    global $CFG;

    $auserid = mmogame::get_asuerid_from_object( $mmogame->get_db(), $data);
    if ($auserid === false) {
        $ret['errorcode'] = 'invalidauser';
        return;
    }
    $mmogame->login_user( $auserid);

    $ret['type'] = $mmogame->get_type();
    $ret['model'] = $mmogame->get_model();

    if (isset( $data->nickname) && isset( $data->avatarid) && isset( $data->paletteid)) {
        $info = $mmogame->get_avatar_info( $auserid);
        $mmogame->get_db()->update_record( 'mmogame_aa_grades',
            ['id' => $info->id, 'nickname' => $data->nickname, 'avatarid' => $data->avatarid,
            'colorpaletteid' => $data->paletteid, ]);
    }

    if ($mmogame->get_state() != 0) {
        $attempt = $mmogame->get_attempt();
    } else {
        $attempt = false;
    }

    $mmogame->append_json( $ret, $attempt, $data);
}

/**
 * Update the database about the answer of user and returns to variable $ret informations.
 *
 * @param object $data
 * @param object $game
 * @param array $ret
 */
function mmogame_json_quiz_answer($data, $game, &$ret) {
    $auserid = mmogame::get_asuerid_from_object( $game->get_db(), $data);
    $game->login_user( $auserid);

    return $game->set_answer_model( $data, $ret);
}

/**
 * Fills the variable $ret with information about highscore.
 *
 * @param object $data
 * @param object $game
 * @param array $ret
 */
function mmogame_json_quiz_gethighscore($data, $mmogame, &$ret) {
    $auserid = mmogame::get_asuerid_from_object( $mmogame->get_db(), $data);
    $mmogame->login_user( $auserid);
    $instance = $mmogame->get_rinstance();
    $mmogame->get_highscore( $data->count, $ret);
}
