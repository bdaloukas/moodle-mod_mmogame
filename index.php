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
 * MMOGame class.
 *
 * @package    mod_mmogame
 * @copyright  2024 Vasilis Daloukas
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use mod_mmogame\event\course_module_instance_list_viewed;

require_once("../../config.php");
require_once("lib.php");

$id = required_param('id', PARAM_INT);

$PAGE->set_url('/mod/mmogame/index.php', ['id' => $id]);
$course = get_course($id);
$coursecontext = context_course::instance($id);
require_login($course);
$PAGE->set_pagelayout('incourse');

$params = ['context' => $coursecontext];
$event = course_module_instance_list_viewed::create($params);
$event->trigger();

// Print the header.
$strmmogames = get_string("modulenameplural", "mmogame");
$PAGE->navbar->add($strmmogames);
$PAGE->set_title($strmmogames);
$PAGE->set_heading($course->fullname);
/** @var renderer $output */
$output = $PAGE->get_renderer('mod_mmogame');
echo $output->header();
echo $output->heading($strmmogames, 2);

// Get all the appropriate data.
if (!$mmogames = get_all_instances_in_course("mmogame", $course)) {
    notice(get_string('thereareno', 'moodle', $strmmogames), "../../course/view.php?id=$course->id");
    die;
}

// Configure table for displaying the list of instances.
$headings = [get_string('name')];
$align = ['left', 'left'];

if (course_format_uses_sections($course->format)) {
    array_unshift($headings, get_string('sectionname', 'format_'.$course->format));
} else {
    array_unshift($headings, '');
}
array_unshift($align, 'center');

$showing = '';

if (has_capability('mod/mmogame:viewreports', $coursecontext)) {
    array_push($headings, get_string('attempts', 'mmogame'));
    array_push($align, 'left');
    $showing = 'stats';
}

$table = new html_table();
$table->head = $headings;
$table->align = $align;

// Populate the table with the list of instances.
$currentsection = '';

foreach ($mmogames as $mmogame) {
    $cm = get_coursemodule_from_instance('mmogame', $mmogame->id);
    $context = context_module::instance($cm->id);
    $data = [];

    // Section number if necessary.
    $strsection = '';
    if ($mmogame->section != $currentsection) {
        if ($mmogame->section) {
            $strsection = $mmogame->section;
            $strsection = get_section_name($course, $mmogame->section);
        }
        if ($currentsection !== "") {
            $table->data[] = 'hr';
        }
        $currentsection = $mmogame->section;
    }
    $data[] = $strsection;

    // Link to the instance.
    $class = '';
    if (!$mmogame->visible) {
        $class = ' class="dimmed"';
    }
    $data[] = "<a$class href=\"view.php?id=$mmogame->coursemodule\">" .
        format_string($mmogame->name, true) . '</a>';

    if ($showing == 'stats') {
        // The $mmogame objects returned by get_all_instances_in_course have the necessary $cm
        // fields set to make the following call work.
        $data[] = \mmogame_attempt_summary_link_to_reports($mmogame, $cm, $context);
    }

    $table->data[] = $data;
} // End of loop over mmogame instances.

// Display the table.
echo html_writer::table($table);

// Finish the page.
echo $output->footer();
