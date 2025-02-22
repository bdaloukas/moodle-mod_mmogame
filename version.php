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
 * Version information
 *
 * @package mod_mmogame
 * @copyright 2024 Vasilis Daloukas
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$plugin->component = 'mod_mmogame';  // Full name of the plugin (used for diagnostics).
$plugin->version   = 2025022200;  // The current module version (Date: YYYYMMDDXX).
$plugin->requires  = 2022030300;  // Requires Moodle 4.0.
$plugin->cron      = 0;           // Period for cron to check this module (secs).
$plugin->release   = '2025-02-22';
$plugin->maturity = MATURITY_STABLE;
