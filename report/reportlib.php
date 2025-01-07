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
 * Get the default report for the current user.
 * @param stdClass $context the quiz context.
 */
function mmogame_report_default_report($context) {
    $reports = mmogame_report_list($context);
    return reset($reports);
}


/**
 * Returns an array of reports to which the current user has access to.
 * @param $context
 * @return array reports are ordered as they should be for display in tabs.
 * @throws coding_exception
 * @throws dml_exception
 */
function mmogame_report_list($context) {
    global $DB;
    static $reportlist = null;
    if (!is_null($reportlist)) {
        return $reportlist;
    }

    $reports = $DB->get_records('mmogame_reports', null, 'displayorder DESC', 'name, capability');
    $reportdirs = core_component::get_plugin_list('mmogame');

    // Order the reports tab in descending order of displayorder.
    $reportcaps = [];
    foreach ($reports as $key => $report) {
        if (array_key_exists($report->name, $reportdirs)) {
            $reportcaps[$report->name] = $report->capability;
        }
    }

    // Add any other reports, which are on disc but not in the DB, on the end.
    foreach ($reportdirs as $reportname => $notused) {
        if (!isset($reportcaps[$reportname])) {
            $reportcaps[$reportname] = null;
        }
    }
    $reportlist = [];
    foreach ($reportcaps as $name => $capability) {
        if (empty($capability)) {
            $capability = 'mod/mmogame:viewreports';
        }
        if (has_capability($capability, $context)) {
            $reportlist[] = $name;
        }
    }
    return $reportlist;
}
