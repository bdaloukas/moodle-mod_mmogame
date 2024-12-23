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
 * JSON file
 *
 * @package    mmogametype_quiz
 * @copyright  2024 Vasilis Daloukas
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use mod_mmogame\local\mmogame;

/**
 * Fill the array $ret with info about the current attempt.
 *
 * @param object $data
 * @param mmogame $mmogame
 * @param array $ret
 */
function mmogame_json_quiz_getattempt(object $data, mmogame $mmogame, array &$ret) {
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
 * Update the database about the answer of user and returns to variable $ret information.
 *
 * @param object $data
 * @param object $mmogame
 * @param array $ret
 * @return mixed
 */
function mmogame_json_quiz_answer(object $data, object $mmogame, array &$ret) {
    $auserid = mmogame::get_asuerid_from_object( $mmogame->get_db(), $data);
    $mmogame->login_user( $auserid);

    return $mmogame->set_answer_model( $data, $ret);
}

/**
 * Fills the variable $ret with information about highscore.
 *
 * @param object $data
 * @param object $mmogame
 * @param array $ret
 */
function mmogame_json_quiz_gethighscore(object $data, object $mmogame, array &$ret) {
    $auserid = mmogame::get_asuerid_from_object( $mmogame->get_db(), $data);
    $mmogame->login_user( $auserid);
    $mmogame->get_highscore( $data->count, $ret);
}
