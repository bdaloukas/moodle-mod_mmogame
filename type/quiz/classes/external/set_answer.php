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
use core_external\external_value;
use core_external\restricted_context_exception;
use invalid_parameter_exception;
use mod_mmogame\local\database\mmogame_database_moodle;
use mod_mmogame\local\mmogame;
use Random\RandomException;
use required_capability_exception;

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
            'sessionkey' => new external_value(PARAM_ALPHANUM, 'The sessionkey of the attempt'),
            'attemptkey' => new external_value(PARAM_ALPHANUM, 'The attemptkey of the attempt'),
            'answer' => new external_value(PARAM_TEXT, 'The answer', VALUE_DEFAULT, ''),
            'subcommand' => new external_value(PARAM_ALPHANUM, 'Subcommand'),
        ]);
    }

    /**
     * Implements the service logic.
     *
     * @param string $sessionkey
     * @param string $attemptkey
     * @param ?string $answer
     * @param string $subcommand
     * @return string
     * @throws RandomException
     * @throws coding_exception
     * @throws invalid_parameter_exception
     * @throws required_capability_exception
     * @throws restricted_context_exception
     */
    public static function execute(
        string $sessionkey,
        string $attemptkey,
        ?string $answer,
        string $subcommand
    ): string {
        // Validate the parameters.
        self::validate_parameters(self::execute_parameters(), [
            'sessionkey' => $sessionkey,
            'attemptkey' => $attemptkey,
            'answer' => $answer,
            'subcommand' => $subcommand,
        ]);
        $answer = trim((string)$answer);

        $sessionkey = trim($sessionkey);
        if (!preg_match('/^[a-f0-9]{64}$/', $sessionkey)) {
            return self::error('invalid_sessionkey');
        }

        $db = new mmogame_database_moodle();
        $auser = mmogame::get_auser_from_sessionkey($db, $sessionkey);
        if ($auser === null) {
            return self::error('no_user');
        }
        $ret = [];

        $mmogame = mmogame::create($db, (int)$auser->mmogameid);

        if (strlen($answer) > 1000) {
            return self::error('answer_too_long');
        }

        $mmogame->login_user((int)$auser->id);

        // Checks also than attemptkey is valid for this mmogameid, auserid.
        $mmogame->set_answer_mode($ret, $attemptkey, $answer, $subcommand);

        return json_encode($ret);
    }

    /**
     * Describe the return types.
     *
     * @return external_value
     */
    public static function execute_returns(): external_value {
        return new external_value(PARAM_RAW, 'A JSON-encoded object with dynamic keys and values');
    }

    /**
     * Returns error code
     *
     * @param string $error
     *
     * @return string
     */
    private static function error(string $error): string {
        return json_encode(['errorcode' => $error]);
    }
}
