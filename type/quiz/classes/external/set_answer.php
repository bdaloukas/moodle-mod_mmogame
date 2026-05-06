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
use core\context\module;
use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_value;
use core_external\restricted_context_exception;
use invalid_parameter_exception;
use mod_mmogame\local\database\mmogame_database_moodle;
use mod_mmogame\local\mmogame;
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
            'mmogameid' => new external_value(PARAM_INT, 'The ID of the mmogame'),
            'kinduser' => new external_value(PARAM_ALPHA, 'The kind of user'),
            'user' => new external_value(PARAM_ALPHANUMEXT, 'The user data'),
            'attempt' => new external_value(PARAM_INT, 'The id of the attempt'),
            'sessionkey' => new external_value(PARAM_ALPHANUM, 'The sessionkey of the attempt'),
            'answer' => new external_value(PARAM_TEXT, 'The answer', VALUE_DEFAULT, ''),
            'answerid' => new external_value(PARAM_INT, 'The id of the answer'),
            'subcommand' => new external_value(PARAM_ALPHANUM, 'Subcommand'),
        ]);
    }

    /**
     * Implements the service logic.
     *
     * @param int $mmogameid
     * @param string $kinduser
     * @param string $user
     * @param int $attempt
     * @param string $sessionkey
     * @param ?string $answer
     * @param int|null $answerid
     * @param string $subcommand
     * @return string
     * @throws coding_exception
     * @throws invalid_parameter_exception
     * @throws required_capability_exception
     * @throws restricted_context_exception
     */
    public static function execute(
        int $mmogameid,
        string $kinduser,
        string $user,
        int $attempt,
        string $sessionkey,
        ?string $answer,
        ?int $answerid,
        string $subcommand
    ): string {
        // Validate the parameters.
        self::validate_parameters(self::execute_parameters(), [
            'mmogameid' => $mmogameid,
            'kinduser' => $kinduser,
            'user' => $user,
            'attempt' => $attempt,
            'sessionkey' => $sessionkey,
            'answer' => $answer,
            'answerid' => $answerid ?? null,
            'subcommand' => $subcommand,
        ]);
        $answer = trim((string)$answer);

        if ($mmogameid <= 0) {
            return self::error('invalid_mmogameid');
        }

        $user = trim($user );

        if (!preg_match( '/^[A-Za-z0-9_-]{1,100}$/', $user)) {
            return self::error('invalid_user');
        }

        $allowedkindusers = [ 'moodle', 'wordpress', 'guid' ];

        if (!in_array( $kinduser, $allowedkindusers, true)) {
            return self::error('invalid_kinduser');
        }

        // Perform security checks.
        if ($kinduser === 'moodle') {
            $cm = get_coursemodule_from_instance('mmogame', $mmogameid);
            $context = module::instance($cm->id);
            self::validate_context($context);
            require_capability('mod/mmogame:play', $context);
        }

        $ret = [];

        $mmogame = mmogame::create(new mmogame_database_moodle(), $mmogameid);
        $auserid = mmogame::get_asuerid($mmogame->get_db(), $kinduser, $user, false, 0);
        if ($auserid == null) {
            return self::error('no_user');
        }

        if ($attempt <= 0) {
            return self::error('invalid_attempt');
        }

        if (strlen( $answer ) > 1000) {
            return self::error('answer_too_long');
        }

        if ( null !== $answerid && $answerid < 0 ) {
            return self::error('invalid_answerid');
        }

        $allowedsubcommands = ['', 'answer', 'tool', 'clear'];

        if (!in_array($subcommand, $allowedsubcommands, true)) {
            return self::error('bad_subcommand');
        }

        $mmogame->login_user($auserid);

        $mmogame->set_answer_mode($ret, $attempt, $sessionkey, $answer, $answerid, $subcommand, $sessionkey);

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
