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

namespace mod_mmogame\external;

use coding_exception;
use core\context\module;
use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_multiple_structure;
use core_external\external_single_structure;
use core_external\external_value;

use core_external\restricted_context_exception;
use invalid_parameter_exception as invalid_parameter_exceptionAlias;
use mod_mmogame\local\database\mmogame_database_moodle;
use mod_mmogame\local\mmogame;
use required_capability_exception;

/**
 * External function to get the list of avatars and color palettes.
 *
 * @package    mmogame
 * @copyright 2025 Vasilis Daloukas
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class get_assets_split extends external_api {

    /**
     * Returns description of method parameters
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'mmogameid' => new external_value(PARAM_INT, 'The ID of the mmogame'),
            'kinduser' => new external_value(PARAM_ALPHA, 'The kind of user'),
            'user' => new external_value(PARAM_ALPHANUMEXT, 'The user data'),
            'countsplit' => new external_value(PARAM_INT, 'The number of splits'),
            'countpalettes' => new external_value(PARAM_INT, 'The count of colorpalettes to return'),
            'countavatars' => new external_value(PARAM_INT, 'The count of avatars to return'),
        ]);
    }

    /**
     * Implements the service logic.
     *
     * @param int $mmogameid
     * @param string $kinduser
     * @param string $user
     * @param int $countsplit
     * @param int $countpalettes
     * @param int $countavatars
     * @return array
     * @throws coding_exception
     * @throws invalid_parameter_exceptionAlias
     * @throws required_capability_exception
     * @throws restricted_context_exception
     */
    public static function execute(int $mmogameid, string $kinduser, string $user, int $countsplit,
                                   int $countpalettes = 0, int $countavatars = 0): array {
        // Validate the parameters.
        self::validate_parameters(self::execute_parameters(), [
            'mmogameid' => $mmogameid,
            'kinduser' => $kinduser,
            'user' => $user,
            'countsplit' => $countsplit,
            'countpalettes' => $colorpalettes ?? 0,
            'countavatars' => $numavatars ?? 0,
        ]);
        // Perform security checks.
        $cm = get_coursemodule_from_instance('mmogame', $mmogameid);
        if ($kinduser == 'moodle') {
            $context = module::instance($cm->id);
            self::validate_context($context);
            require_capability('mod/mmogame:play', $context);
        }

        $mmogame = mmogame::create( new mmogame_database_moodle(), $mmogameid);
        $retpalettes = $retavatars = [];
        $maxavatars = 0;
        $mmogame->get_assets_split($countsplit, $countpalettes, $countavatars, $retpalettes,
            $retavatars, $maxavatars, $kinduser, $user);
        $avatarids = $avatars = $paletteids = $palettes = [];
        foreach ($retpalettes as $key => $value) {
            $paletteids[] = $key;
            $palettes[] = implode( ',', $value);
        }
        foreach ($retavatars as $map) {
            foreach ($map as $key => $value) {
                $avatarids[] = $key;
                $avatars[] = $value;
            }
        }
        return ['avatars' => $avatars, 'avatarids' => $avatarids,
            'colorpalettes' => $palettes, 'colorpaletteids' => $paletteids,
            'numavatars' => min( $maxavatars, $countavatars)];
    }

    /**
     * Describe the return types.
     *
     * @return external_single_structure
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'avatars' => new external_multiple_structure(
                new external_value(PARAM_TEXT, 'An avatar filename'),
                'The list of avatar filenames',
                VALUE_OPTIONAL
            ),
            'avatarids' => new external_multiple_structure(
                new external_value(PARAM_INT, 'An avatar ID'),
                'The list of avatar IDs',
                VALUE_OPTIONAL
            ),
            'colorpalettes' => new external_multiple_structure(
                new external_value(PARAM_RAW, 'A color palette'),
                'The list of color palettes',
                VALUE_OPTIONAL
            ),
            'colorpaletteids' => new external_multiple_structure(
                new external_value(PARAM_INT, 'A color palette ID'),
                'The list of color palette IDs',
                VALUE_OPTIONAL
            ),
            'numavatars' => new external_value(PARAM_INT, 'The number of avatars'),
        ]);
    }
}
