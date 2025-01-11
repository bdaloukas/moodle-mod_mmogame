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
 * Index file for the mmogame module.
 *
 * @package    mod_mmogame
 * * @copyright  2024 Vasilis Daloukas
 * * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');

$id = required_param('id', PARAM_INT); // Course ID.

// Verify the course exists.
if (!$course = $DB->get_record('course', ['id' => $id])) {
    throw new moodle_exception('invalidcourseid', 'error');
}

// Require login to the course.
require_login($course);

$context = context_course::instance($course->id);
require_capability('mod/mmogame:view', $context);

// Set up the page.
$PAGE->set_url('/mod/mmogame/index.php', ['id' => $id]);
$PAGE->set_title(get_string('modulenameplural', 'mod_mmogame'));
$PAGE->set_heading($course->fullname);

// Get all instances of the module in the course.
$instances = get_all_instances_in_course('mmogame', $course);

echo $OUTPUT->header();

// Display the module list.
if (!$instances) {
    echo $OUTPUT->notification(get_string('noinstances', 'mod_mmogame'));
} else {
    $table = new html_table();
    $table->head = [
        get_string('name'),
        get_string('introduction', 'mod_mmogame'),
    ];

    foreach ($instances as $instance) {
        $link = html_writer::link(
            new moodle_url('/mod/mmogame/view.php', ['id' => $instance->coursemodule]),
            format_string($instance->name)
        );

        $intro = format_module_intro('mmogame', $instance, $instance->coursemodule);

        $table->data[] = [$link, $intro];
    }

    echo html_writer::table($table);
}

echo $OUTPUT->footer();
