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
 * This file contains the gate code that's clear saved user info.
 *
 *
 * @package    mod_mmogame
 * @copyright  2024 Vasilis Daloukas
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// No login check is expected here because users can play users as guest and as normal users
define('NO_MOODLE_COOKIES', true);
require( "../../config.php");

global $CFG, $DB, $USER, $OUTPUT, $PAGE;

$mmogameid = required_param('id', PARAM_INT);
$pin = required_param('pin', PARAM_INT);

$PAGE->set_url(new moodle_url('/mod/mmogame/gate2.php'));
$PAGE->set_pagelayout('embedded'); // Layout χωρίς header και footer.
$PAGE->set_context(context_system::instance());

$PAGE->requires->js_call_amd('mod_mmogame/mmogame');

$url = "$CFG->wwwroot/mod/mmogame/gate.php?id=$mmogameid&pin=$pin";
$PAGE->requires->js_init_code("
    require(['mod_mmogame/mmogame'], function(mmogame) {
        var obj = new mmogame();
        obj.clearDB('" . $url."');
    });
");

// Output the page.
echo $OUTPUT->header();
echo $OUTPUT->footer();
