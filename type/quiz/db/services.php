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
//
// Sometimes, changes between versions involve
// alterations to database structures and other
// major things that may break installations.
//
// The upgrade function in this file will attempt
// to perform all the necessary actions to upgrade
// your older installation to the current version.
//
// If there's something it cannot do itself, it
// will tell you what you need to do.
//
// The commands in here will all be database-neutral,
// using the methods of database_manager class.

/**
 * Web service for mmogametype_quiz
 *
 * @package    mmogametype_quiz
 * @subpackage db
 * @copyright  2024 Vasilis Daloukas
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$functions = [
    'mmogametype_quiz_get_attempt' => [
        'classname'     => 'mmogametype_quiz\external\get_attempt',
        'description'   => 'Starts a new attempt or continues the last attempt.',
        'type'          => 'write',
        'ajax'          => true,
        'loginrequired' => false,
    ],
    'mmogametype_quiz_set_answer' => [
        'classname'     => 'mmogametype_quiz\external\set_answer',
        'description'   => 'Save the answer to the question.',
        'type'          => 'write',
        'ajax'          => true,
        'loginrequired' => false,
    ],
    'mmogametype_quiz_get_highscore' => [
        'classname'     => 'mmogametype_quiz\external\get_highscore',
        'description'   => 'Gets the high scores for the game.',
        'type'          => 'write',
        'ajax'          => true,
        'loginrequired' => false,
    ],
];
