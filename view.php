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
 * This file is the entry point to the mmogame module. All pages are rendered from here
 *
 * @package    mod_mmogame
 * @copyright  2024 Vasilis Daloukas
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use mod_mmogame\local;

require_once(dirname(__FILE__) . '/../../config.php');

global $CFG, $DB;

require_once($CFG->libdir.'/gradelib.php');

$id = required_param('id', PARAM_INT); // Is mmoGame Module ID.

if (! $cm = get_coursemodule_from_id('mmogame', $id)) {
    throw new moodle_exception('invalidcoursemoduleid', 'error', '', null, $id);
}
if (! $course = $DB->get_record('course', ['id' => $cm->course])) {
    throw new moodle_exception('coursemisconf', 'error', '', $cm->course);
}
if (! $mmogame = $DB->get_record('mmogame', ['id' => $cm->instance])) {
    throw new moodle_exception('invalidcoursemoduleid', 'error', '', $cm->instance);
}

// Check login and get context.
require_login($course->id, false, $cm);

$mmogame = local\mmogame::getgame( new local\database\mmogame_database_moodle(), $cm->instance);

if ($mmogame !== false) {
    require_once($CFG->dirroot.'/mod/mmogame/type/'.$mmogame->get_type().'/view.php');
}
