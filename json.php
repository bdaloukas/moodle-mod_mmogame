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
 * This file contains the JSON protocol
 *
 * @package    mod_mmogame
 * @copyright  2024 Vasilis Daloukas
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define( 'AJAX_SCRIPT', 1);
define('NO_MOODLE_COOKIES', true);

require( "../../config.php");
require_once( 'database/moodle.php');
require_once(dirname(__FILE__) . '/mmogame.php');

$db = new mmogame_database_moodle();

$data = get_data();
if (!isset( $data->command)) {
    die("NO data->command");
}
$ret = [];

$game = mmogame::getgame( $db, $data->mmogameid);

switch( $data->command) {
    case 'getavatars':
        dogetavatars( $game, $data, $ret);
        die( json_encode( $ret));
    case 'setavatar':
        dosetavatar( $game, $data, $ret);
        die( json_encode( $ret));
    case 'getcolorpalettes':
        dogetcolorpalettes( $game, $data, $ret);
        die( json_encode( $ret));
    case 'getcolorsavatars':
        dogetcolorpalettes( $game, $data, $ret);
        dogetavatars( $game, $data, $ret);
        die( json_encode( $ret));
    case 'setcolorpalette':
        dosetcolorpalette( $game, $data, $ret);
        die( json_encode( $ret));
}

$function = 'mmogame_json_'.$game->get_type().'_'.$data->command;

require_once( "type/".$game->get_type()."/json.php");
$function( $data, $game, $ret);

$rec = $DB->get_record_select( 'question', 'id=12');

$ret['time'] = round( 1000 * microtime( true));

$errorcode = $game->get_errorcode();
if ($errorcode != '') {
    $ret['errorcode'] = $errorcode;
}

$ret2 = $ret;
unset( $ret2['filec1']);

die( json_encode( $ret));

/**
 * Convert php://input to Json.
 *
 * @return object.
 */
function get_data(): object {
    $s = urldecode( file_get_contents("php://input"));

    return json_decode($s, false);
}

/**
 * Returns an array of existing avatars to select.
 *
 * @param object $mmogame
 * @param object $data
 * @param array $ret (an array of string with exists avatars)
 */
function dogetavatars( $mmogame, $data, &$ret) {
    if ($data->countavatars == 0) {
        $ret['countavatars'] = 0;
        return;
    }
    $auserid = mmogame::get_asuerid_from_object( $mmogame->get_db(), $data);

    $info = $mmogame->get_avatar_info( $auserid);
    $ret['nickname'] = $info->nickname;

    $avatars = $mmogame->get_avatars( $auserid);

    $n = 0;
    if ($info->avatarid != 0 && array_key_exists( $info->avatarid, $avatars)) {
        $ret['avatar1'] = $avatars[$info->avatarid];
        $ret['avatarid1'] = $info->avatarid;
        unset( $avatars[$info->avatarid]);
        $n++;
    }
    if ($data->countavatars > count( $avatars)) {
        $data->countavatars = count( $avatars);
    }
    $a = array_rand( $avatars, $data->countavatars - $n);

    shuffle( $a);
    foreach ($a as $key) {
        $ret['avatar'.(++$n)] = $avatars[$key];
        $ret['avatarid'.$n] = $key;
    }
    $ret['countavatars'] = $n;
}

/**
 * Sets an avatar to a coresponding user.
 *
 * @param object $mmogame
 * @param object $data
 * @param array $ret (an array containing the avatar and nickname)
 */
function dosetavatar( $mmogame, $data, &$ret) {
    $auserid = mmogame::get_asuerid_from_object( $mmogame->get_db(), $data);

    $mmogame->set_avatar( $auserid, $data->nickname, $data->avatarid);

    $info = $mmogame->get_avatar_info( $auserid);
    $ret['avatar'] = $info->avatar;
    $ret['nickname'] = $info->nickname;
}

/**
 * Compares the Hue of two colors.
 *
 * @param array $a (the first color)
 * @param array $b (the second color)
 * @return int (-1, 0 or 1)
 */
function usort_mmogame_palettes( $a, $b) {
    return mmogame::calcualteHue( $a[0]) <=> mmogame::calcualteHue( $b[0]);
}

/**
 * Returns an array of existing color palettes to select.
 *
 * @param object $mmogame
 * @param object $data
 * @param array $ret (an array of string with exists color palettes)
 */
function dogetcolorpalettes( $mmogame, $data, &$ret) {
    if ($data->countcolors == 0) {
        $ret['countcolors'] = 0;
        return;
    }

    $auserid = mmogame::get_asuerid_from_object( $mmogame->get_db(), $data);

    $pals = $mmogame->get_palettes( $auserid);

    while (count( $pals) > $data->countcolors) {
        $pos = array_rand( $pals);
        unset( $pals[$pos]);
    }

    $n = 0;
    foreach ($pals as $key => $colors) {
        $ret['palette'.(++$n)] = $colors;
        $ret['paletteid'.$n] = $key;
    }
    $ret['countcolors'] = $n;
}

/**
 * Sets a color palette to a coresponding user.
 *
 * @param object $mmogame
 * @param object $data
 * @param array $ret (the value of key "colors" containg the 5 colors of palette)
 */
function dosetcolorpalette( $mmogame, $data, &$ret) {
    $auserid = mmogame::get_asuerid_from_object( $mmogame->get_db(), $data);

    $mmogame->set_colorpalette( $auserid, $data->id);

    $info = $mmogame->get_avatar_info( $auserid);
    $ret['colors'] = implode( ',', $info->colors);
}
