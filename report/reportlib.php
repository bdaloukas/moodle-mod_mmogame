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
 * Helper functions for the quiz reports.
 *
 * @package   mod_mmogame
 * @copyright 2024 Vasilis Daloukas
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/mod/mmogame/lib.php');

/**
 * Returns an array of reports to which the current user has access to.
 * @param stdClass $context
 * @return array reports are ordered as they should be for display in tabs.
 * @throws coding_exception
 * @throws dml_exception
 */
function mmogame_report_list(stdClass $context): array {

    require_capability('mod/mmogame:viewreports', $context);

    return ['overview'];
}
