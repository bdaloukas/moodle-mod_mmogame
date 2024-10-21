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
 * Strings for component 'game', language 'en', branch 'MOODLE_20_STABLE'
 *
 * @package    mmogame_quiz
 * @copyright  2024 Vasilis Daloukas
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['pluginname'] = 'MMOGame Quiz';
$string['algorithm'] = 'Alogorithm for selecting questions';
$string['algorithm_adaptive'] = 'Adaprive';
$string['algorithm_random'] = 'Random';

// Lib.php.
$string['model_alone'] = 'Quiz - Alone';
$string['model_aduel'] = 'Quiz - ADuel';

// Mod_form.php.
$string['showcorrect'] = 'Show correct answer';

// Play Alone.

// JS Aduel.
$string['waittostart'] = 'Waiting for game to start';
$string['js_aduel_cut'] = 'It only shows two answers to choose from.';
$string['js_aduel_skip'] = 'Skips the question.';
$string['js_aduel_wizard'] = 'It shows the correct answer but the player will earn 1 point less.';
$string['js_aduel_example1'] = 'The player answered incorrectly while his opponent answered correctly.';
$string['js_aduel_example2'] = 'The player has Rank 1 in the game with 14 points and a correct answer rate of 27%.';

$string['state0'] = 'Wait to start';
$string['alone_state1'] = 'Playing the game';
$string['aduel_state1'] = 'Playing the game';

// Java script.
$string['js_aduel_help'] = 'Each player initially answers 4 questions of increasing difficulty and earns 3 points if his answer is correct and loses 1 point for each wrong answer.<br>
Another player then answers the same questions.<br>
If only one of the two answered a question correctly, he gets an extra 3 points.';
$string['js_alone_help'] = 'Each player earns 3 points if his answer is correct and loses 1 point for each wrong answer.';
$string['js_help_5050'] = 'Shows only 2 possible answers';
$string['js_help_skip'] = 'Skip the question';
$string['js_wizard'] = 'Revealing the correct answer will cost you 1 mark';
$string['js_next_question'] = 'Next question';
$string['js_wait_oponent'] = 'Waiting to find an opponent.';
$string['js_correct_answer'] = 'Correct answer';
$string['js_wrong_answer'] = 'Wrong answer';
$string['js_grade'] = 'Grade';
$string['js_position_grade'] = 'Ranking order based on opponent rating';
$string['js_position_percent'] = "Ranking order based on opponent's percentage of correct answers";
$string['js_percent_oponent'] = "Opponent's percentage of correct answers";
$string['js_ranking_order'] = 'Ranking order';
