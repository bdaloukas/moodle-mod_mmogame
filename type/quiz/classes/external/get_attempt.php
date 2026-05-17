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
use Random\RandomException;
use required_capability_exception;

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
            'sessionkey' => new external_value(PARAM_ALPHANUMEXT, 'The session of the user'),
            'nickname' => new external_value(PARAM_TEXT, 'The nickname of the user', VALUE_DEFAULT, '', true),
            'avatarid' => new external_value(PARAM_INT, 'The ID of the avatar', VALUE_DEFAULT, 0, true),
            'colorpaletteid' => new external_value(PARAM_INT, 'The ID of the color palette', VALUE_DEFAULT, 0, true),
            'subcommand' => new external_value(PARAM_ALPHANUMEXT, 'Subcommands like tool1, tool2, tool3', VALUE_DEFAULT, '', false),
        ]);
    }

    /**
     * Implements the service logic.
     *
     * @param string $sessionkey
     * @param string|null $nickname
     * @param int|null $avatarid
     * @param int|null $colorpaletteid
     * @param string $subcommand
     * @return string
     * @throws RandomException
     * @throws coding_exception
     * @throws invalid_parameter_exception
     * @throws required_capability_exception
     * @throws restricted_context_exception
     * @throws \dml_exception
     */
    public static function execute(
        string $sessionkey,
        ?string $nickname = null,
        ?int $avatarid = null,
        ?int $colorpaletteid = null,
        string $subcommand = ''
    ): string {
        $nickname = $nickname ?? '';
        $avatarid = $avatarid ?? 0;
        $colorpaletteid = $colorpaletteid ?? 0;
        $subcommand = $subcommand ?? '';

        // Validate the parameters.
        self::validate_parameters(self::execute_parameters(), [
            'sessionkey' => $sessionkey,
            'nickname' => $nickname,
            'avatarid' => $avatarid,
            'colorpaletteid' => $colorpaletteid,
            'subcommand' => $subcommand,
        ]);

        $sessionkey = trim($sessionkey);
        if (!preg_match('/^[a-f0-9]{64}$/', $sessionkey)) {
            return self::error('invalid_sessionkey');
        }

        $allowedsubcommands = ['', 'answer', 'tool', 'clear', 'tool1', 'tool2', 'tool3'];

        if (!in_array($subcommand, $allowedsubcommands, true)) {
            return self::error('bad_subcommand');
        }

        if (null !== $avatarid && $avatarid < 0) {
            return self::error('invalid_avatarid');
        }

        if (null !== $colorpaletteid && $colorpaletteid < 0) {
            return self::error('invalid_colorpaletteid');
        }

        $ret = [];

        $db = new mmogame_database_moodle();
        $auser = mmogame::get_auser_from_sessionkey($db, $sessionkey);
        if ($auser === null) {
            return self::error('no_user');
        }
        $mmogameid = $auser->mmogameid;

        // From this point on, mmogameid/auserid are trusted only from the validated session.
        $mmogame = mmogame::create($db, $mmogameid);
        $mmogame->login_user($auser->id);

        $rgame = $mmogame->get_rgame();

        // No selection of avatar and colorpalettes yet.
        $grade = $db->get_record_select(
            'mmogame_aa_grades',
            'mmogameid=? AND numgame=? AND auserid=?',
            [$mmogameid, $rgame->numgame, $auser->id]
        );

        if ($grade === null) {
            return self::error('no_user');
        }

        if ($nickname !== '' && $avatarid > 0 && $colorpaletteid > 0) {
            $nickname = mb_substr($nickname, 0, 50);
            $info = $mmogame->get_avatar_info($auser->id);
            $mmogame->get_db()->update_record(
                'mmogame_aa_grades',
                ['id' => $info->id, 'nickname' => $nickname, 'avatarid' => $avatarid, 'colorpaletteid' => $colorpaletteid]
            );
        }
        if ($mmogame->get_state() != 0) {
            $attempt = $mmogame->get_attempt();
        } else {
            $attempt = false;
        }

        $mmogame->append_json($ret, $attempt !== false ? $attempt : null, $subcommand);
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
