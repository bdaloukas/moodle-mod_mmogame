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
 * State
 *
 * @package    mod_mmogame
 * @copyright  2024 Vasilis Daloukas
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('NO_DEBUG_DISPLAY', true);
define('ABORT_AFTER_CONFIG', true);
define('CACHE_DISABLE_ALL', true);
define('NO_MOODLE_COOKIES', true);
define('NO_SESSION', true);
define('AJAX_SCRIPT', true);
define('NO_OUTPUT_BUFFERING', true);
define('NO_CONFIG_CHECK', true);
require('../../config.php');

$fastjson = \core\param::from_type( \core\param::INT->value)->required_param( 'fastjson');
$type = \core\param::from_type( \core\param::ALPHA->value)->required_param( 'type');

$ret = '';
$filemain = $CFG->dataroot. '/temp/mmogame/states/'.substr( $fastjson, -2) ."/{$fastjson}";
if (!file_exists( $filemain.'.txt')) {
    return;
}

$ret = file_get_contents( $filemain.'.txt');
die( $ret);
