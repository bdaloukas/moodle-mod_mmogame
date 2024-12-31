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

namespace mmogametype_quiz\external;

use coding_exception;
use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_multiple_structure;
use core_external\external_single_structure;
use core_external\external_value;

use invalid_parameter_exception;
use mod_mmogame\local\database\mmogame_database_moodle;
use mod_mmogame\local\mmogame;

/**
 * External function for saving the answer of each question.
 *
 * @package   mmogametype_quiz
 * @copyright 2024 Vasilis Daloukas
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class set_answer extends external_api {

    /**
     * Returns description of method parameters
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'mmogameid' => new external_value(PARAM_INT, 'The ID of the mmogame'),
            'kinduser' => new external_value(PARAM_ALPHA, 'The kind of user'),
            'user' => new external_value(PARAM_ALPHANUM, 'The user data'),
            'answer' => new external_value(PARAM_RAW, 'The answer'),
            'answerid' => new external_value(PARAM_INT, 'The id of the answer'),
        ]);
    }

    /**
     * Implements the service logic.
     *
     * @param int $mmogameid
     * @param string $kinduser
     * @param string $user
     * @param int $attempt
     * @param string $answer
     * @param int|null $answerid
     * @param string $subcommand
     * @return array
     * @throws coding_exception
     * @throws invalid_parameter_exception
     */
    public static function execute(int $mmogameid, string $kinduser, string $user, int $attempt, string $answer,
                                   ?int $answerid, string $subcommand): array {
        // Validate the parameters.
        self::validate_parameters(self::execute_parameters(), [
            'mmogameid' => $mmogameid,
            'kinduser' => $kinduser,
            'user' => $user,
            'answer' => $answer ?? null,
            'answerid' => $avatarid ?? null,
        ]);

        $ret = [];

        $mmogame = mmogame::create( new mmogame_database_moodle(), $mmogameid);
        $auserid = mmogame::get_asuerid( $mmogame->get_db(), $kinduser, $user);

        $mmogame->login_user( $auserid);

        $mmogame->set_answer_model( $ret, $attempt, $answer, $answerid, $subcommand);

        $formattedret = [];
        foreach ($ret as $key => $value) {
            $formattedret[] = [
                'key' => $key,
                'value' => $value,
            ];
        }

        return ['ret' => $formattedret];
    }

    /**
     * Describe the return types.
     *
     * @return external_single_structure
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'ret' => new external_multiple_structure(
                new external_single_structure([
                    'key' => new external_value(PARAM_TEXT, 'The key of the entry'),
                    'value' => new external_value(PARAM_RAW, 'The value of the entry'),
                ]),
                'The list of key-value pairs'
            ),
        ]);
    }
}
