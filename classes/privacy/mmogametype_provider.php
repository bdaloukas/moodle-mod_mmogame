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
 * This file contains the mmogametype_provider interface.
 *
 * mmoGame Sub plugins should implement this if they store personal information.
 *
 * @package    mod_mmogame
 * @copyright  2024 Vasilis Daloukas
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace mod_mmogame\privacy;

use context;
use core_privacy\local\request\plugin\subplugin_provider;

/**
 * Privacy class for requesting user data.
 *
 * @package    mod_mmogame
 * @copyright  2024 Vasilis Daloukas
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
interface mmogametype_provider extends subplugin_provider {
    /**
     * This method is used to export any user data this sub-plugin has using the mmogame_plugin_request_data object to get the
     * context and userid.
     *
     * @param  context $context
     * @param int $mmogameid
     * @param string $model
     * @param int $auserid
     * @param int $numgame
     * @param array $path
     */
    public static function export_type_user_data(context $context, int $mmogameid, string $model, int $auserid, int $numgame, array $path);
}
