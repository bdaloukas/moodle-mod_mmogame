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

use invalid_parameter_exception;
use mod_mmogame\local\database\mmogame_database_moodle;
use mod_mmogame\local\mmogame;

/**
 * External function for starting a new attempt or continuing the last attempt.
 *
 * @package   mmogametype_quiz
 * @copyright 2024 Vasilis Daloukas
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class get_attempt extends external_api {
    /**
     * Returns description of method parameters
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'mmogameid' => new external_value(PARAM_INT, 'The ID of the mmogame'),
            'kinduser' => new external_value(PARAM_ALPHA, 'The kind of user'),
            'user' => new external_value(PARAM_RAW, 'The user data'),
            'nickname' => new external_value(PARAM_TEXT, 'The nickname of the user'),
            'avatarid' => new external_value(PARAM_INT, 'The ID of the avatar'),
            'colorpaletteid' => new external_value(PARAM_INT, 'The ID of the color palette'),
        ]);
    }

    /**
     * Implements the service logic.
     *
     * @param int $mmogameid
     * @param string $kinduser
     * @param string $user
     * @param string|null $nickname
     * @param int|null $avatarid
     * @param int|null $colorpaletteid
     * @return string
     * @throws coding_exception
     * @throws invalid_parameter_exception
     */
    public static function execute(int $mmogameid, string $kinduser, string $user,
                                   ?string $nickname = null, ?int $avatarid = null, ?int $colorpaletteid = null): string {
        // Validate the parameters.
        self::validate_parameters(self::execute_parameters(), [
            'mmogameid' => $mmogameid,
            'kinduser' => $kinduser,
            'user' => $user,
            'nickname' => $nickname,
            'avatarid' => $avatarid,
            'colorpaletteid' => $colorpaletteid,
        ]);

        $ret = [];

        $mmogame = mmogame::create(new mmogame_database_moodle(), $mmogameid);
        $auserid = mmogame::get_asuerid($mmogame->get_db(), $kinduser, $user);

        $mmogame->login_user($auserid);

        $ret['type'] = $mmogame->get_type();
        $ret['model'] = $mmogame->get_model();

        if (isset($nickname) && isset($avatarid) && isset($colorpaletteid)) {
            $info = $mmogame->get_avatar_info($auserid);
            $mmogame->get_db()->update_record('mmogame_aa_grades',
                ['id' => $info->id, 'nickname' => $nickname, 'avatarid' => $avatarid,  'colorpaletteid' => $colorpaletteid]);
        }

        if ($mmogame->get_state() != 0) {
            $attempt = $mmogame->get_attempt();
        } else {
            $attempt = false;
        }

        $mmogame->append_json($ret, $attempt);

        return json_encode( $ret);
    }

    /**
     * Describe the return types.
     *
     * @return external_value
     */
    public static function execute_returns(): external_value {
        return new external_value(PARAM_RAW, 'A JSON-encoded object with dynamic keys and values');
    }
}
