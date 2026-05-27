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
 * Returns high score data for MMOGame quiz mode through the REST external API.
 *
 * @package   mmogametype_quiz
 * @copyright 2024 Vasilis Daloukas
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
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
 * External API endpoint for retrieving quiz high score data.
 */
class get_highscore extends external_api {
    /**
     * Returns description of method parameters
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'sessionkey' => new external_value(PARAM_ALPHANUM, 'Session key'),
            'count' => new external_value(PARAM_INT, 'How many users'),
        ]);
    }

    /**
     * Implements the service logic.
     *
     * @param string $sessionkey
     * @param int $count
     * @return string
     * @throws coding_exception
     * @throws invalid_parameter_exception
     * @throws required_capability_exception
     * @throws restricted_context_exception
     */
    public static function execute(string $sessionkey, int $count): string {
        // Validate the parameters.
        self::validate_parameters(self::execute_parameters(), [
            'sessionkey' => $sessionkey,
            'count' => $count,
        ]);

        if ($count <= 0 || $count > 50) {
            return self::error('invalid_count');
        }

        $sessionkey = trim($sessionkey);

        if (!preg_match('/^[a-f0-9]{64}$/', $sessionkey)) {
            return self::error('invalid_sessionkey');
        }

        $db = new mmogame_database_moodle();
        $auser = mmogame::get_auser_from_sessionkey($db, $sessionkey);
        if ($auser === null) {
            return self::error('no_user');
        }

        if ($auser->kind === 'moodle') {
            // Perform security checks.
            $cm = get_coursemodule_from_instance('mmogame', $auser->mmogameid);
            $context = module::instance($cm->id);
            self::validate_context($context);
            require_capability('mod/mmogame:play', $context);
        }

        $ret = [];

        $mmogame = mmogame::create($db, (int)$auser->mmogameid);

        $mmogame->login_user($auser);

        $mmogame->get_highscore($count, $ret);

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
