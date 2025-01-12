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
 * This script controls the display of the quiz reports.
 *
 * @package   mod_mmogame
 * @copyright 2024 Vasilis Daloukas
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use mmogametype_quiz\output\overview\overview_controller;

define('NO_OUTPUT_BUFFERING', true);

require_once(__DIR__ . '/../../config.php');

require_login();

$id = optional_param('id', 0, PARAM_INT);
$mode = required_param('mode', PARAM_ALPHANUM);


$cm = $DB->get_record('course_modules', ['id' => $id], '*', MUST_EXIST);
$rgame = $DB->get_record('mmogame', ['id' => $cm->instance], '*', MUST_EXIST);

$controller = new overview_controller();
$controller->display();
