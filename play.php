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
 * This file contains the definition for the class mmogame
 *
 * This class provides all the functionality for the new mmogame module.
 *
 * @package    mod_mmogame
 * @copyright  2019 Vasilis Daloukas
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(dirname(__FILE__) . '/../../config.php');
require_once( 'database/moodle.php');
require_once(dirname(__FILE__) . '/mmogame.php');

$db = new mmogame_database_moodle();

$guid = required_param('guid', PARAM_TEXT);
$pin = required_param('pin', PARAM_INT);
$usercode = optional_param('usercode', 0, PARAM_INT);

$game = mmogame_abstract::getgame( $db, $guid, $pin);
$type = $game->get_type();
$class = 'mmogame_js_'.$type;
require_once( "type/{$type}/play.php");
$class::init( $game, $usercode);
