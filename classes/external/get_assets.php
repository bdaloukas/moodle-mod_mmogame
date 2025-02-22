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

use invalid_parameter_exception;
use mod_mmogame\local\database\mmogame_database_moodle;
use mod_mmogame\local\mmogame;

/**
 * External function to get the list of avatars and color palettes.
 *
 * @package    mod_mmogame
 * @copyright 2024 Vasilis Daloukas
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class get_assets extends external_api {

    /**
     * Returns description of method parameters
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'mmogameid' => new external_value(PARAM_INT, 'The ID of the mmogame'),
            'kinduser' => new external_value(PARAM_ALPHA, 'The kind of user'),
            'user' => new external_value(PARAM_ALPHANUMEXT, 'The user data'),
            'avatars' => new external_value(PARAM_INT, 'The count of avatars to return'),
            'colorpalettes' => new external_value(PARAM_INT, 'The count of colorpalettes to return'),
        ]);
    }

    /**
     * Implements the service logic.
     *
     * @param int $mmogameid
     * @param string $kinduser
     * @param string $user
     * @param int $avatars
     * @param int $colorpalettes
     * @return array
     * @throws coding_exception
     * @throws invalid_parameter_exception
     */
    public static function execute(int $mmogameid, string $kinduser, string $user, int $avatars = 0,
                                   int $colorpalettes = 0): array {
        // Validate the parameters.
        self::validate_parameters(self::execute_parameters(), [
            'mmogameid' => $mmogameid,
            'kinduser' => $kinduser,
            'user' => $user,
            'avatars' => $avatars ?? 0,
            'colorpalettes' => $colorpalettes ?? 0,
        ]);

        // Perform security checks.
        $cm = get_coursemodule_from_instance('mmogame', $mmogameid);
        $context = module::instance($cm->id);
        self::validate_context($context);
        require_capability('mod/mmogame:play', $context);

        $result = [];

        $mmogame = mmogame::create( new mmogame_database_moodle(), $mmogameid);
        $auserid = mmogame::get_asuerid( $mmogame->get_db(), $kinduser, $user, true);

        // Generate avatars array if avatars > 0.
        if ($avatars > 0) {
            self::compute_avatars($mmogame, $auserid, $avatars, $result);
        }

        // Generate colorpalettes array if colorpalettes > 0.
        if ($colorpalettes > 0) {
            self::compute_colorpalettes($mmogame, $colorpalettes, $result);
        }

        return $result;
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
        ]);
    }

    /**
     * Returns a list of avatars and corresponding id
     *
     * @param mmogame $mmogame
     * @param int $auserid
     * @param int $count
     * @param array $result
     * @return void
     */
    private static function compute_avatars(mmogame $mmogame, int $auserid, int $count, array &$result): void {
        $info = $mmogame->get_avatar_info( $auserid);

        $a = $mmogame->get_avatars( $auserid);

        $avatars = $ids = [];

        if ($info->avatarid != 0 && array_key_exists( $info->avatarid, $avatars)) {
            $avatars[] = $avatars[$info->avatarid];
            $ids[] = $info->avatarid;
            unset( $avatars[$info->avatarid]);
            $count--;
        }
        if ($count == 1) {
            $ids[] = $id = array_rand( $a, min($count, count($a)));
            $avatars[] = $a[$id];
        } else if ($count > 1) {
            $keys = array_rand( $a, min($count, count($a)));
            shuffle( $keys);
            foreach ($keys as $key) {
                $ids[] = $key;
                $avatars[] = $a[$key];
            }
        }

        $result['avatars'] = $avatars;
        $result['avatarids'] = $ids;
    }

    /**
     * Returns a list of color palettes and corresponding id
     *
     * @param mmogame $mmogame
     * @param int $count
     * @param array $result
     * @return void
     */
    private static function compute_colorpalettes(mmogame $mmogame, int $count, array &$result): void {
        $pals = $mmogame->get_palettes();

        while (count( $pals) > $count) {
            $id = array_rand( $pals);
            unset( $pals[$id]);
        }

        $colorpalettes = [];
        foreach ($pals as $pal) {
            $colorpalettes[] = implode( ',', $pal);
        }

        $result['colorpaletteids'] = array_keys( $pals);
        $result['colorpalettes'] = $colorpalettes;
    }
}
