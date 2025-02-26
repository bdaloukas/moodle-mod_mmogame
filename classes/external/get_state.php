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
use core_external\external_value;

use core_external\restricted_context_exception;
use invalid_parameter_exception as invalid_parameter_exceptionAlias;
use mod_mmogame\local\database\mmogame_database_moodle;
use mod_mmogame\local\mmogame;
use required_capability_exception;

/**
 * External function to get the list of avatars and color palettes.
 *
 * @package    mod_mmogame
 * @copyright 2024 Vasilis Daloukas
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class get_state extends external_api {

    /**
     * Returns description of method parameters
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'mmogameid' => new external_value(PARAM_INT, 'The ID of the mmogame'),
        ]);
    }

    /**
     * Implements the service logic.
     *
     * @param int $mmogameid
     * @return string
     * @throws coding_exception
     * @throws invalid_parameter_exceptionAlias
     * @throws required_capability_exception
     * @throws restricted_context_exception
     */
    public static function execute(int $mmogameid): string {
        // Validate the parameters.
        self::validate_parameters(self::execute_parameters(), [
            'mmogameid' => $mmogameid,
        ]);
        // Perform security checks.
        $cm = get_coursemodule_from_instance('mmogame', $mmogameid);
        $context = module::instance($cm->id);
        self::validate_context($context);
        require_capability('mod/mmogame:play', $context);

        $mmogame = mmogame::create( new mmogame_database_moodle(), $mmogameid);
        return $mmogame->set_state( $mmogame->get_state());
    }

    /**
     * Describe the return types.
     *
     * @return external_value
     */
    public static function execute_returns(): external_value {
        return new external_value(PARAM_TEXT, 'The serialized state of the mmogame');
    }

}
