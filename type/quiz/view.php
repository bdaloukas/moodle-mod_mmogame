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
 * @package    mmogametype_quiz
 * @copyright  2024 Vasilis Daloukas
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// Check login and get context.
require_login($course->id, false, $cm);

$model = $mmogame->get_model();
require( "admin_{$model}.php");

$context = mmogame_get_context_module_instance( $cm->id);
require_capability('mod/mmogame:view', $context);

// Initialize $PAGE, compute blocks.
$PAGE->set_url('/mod/mmogame/view.php', ['id' => $cm->id]);

require_once($CFG->dirroot."/mod/mmogame/model/{$model}.php");

if (has_capability('mod/mmogame:manage', $context)) {
    $url = $CFG->wwwroot.'/mod/mmogame/gate.php?id='.$mmogame->get_id().'&pin='.$mmogame->get_rinstance()->pin;
    mmogame_quiz_manage( $id, $mmogame, $url);
} else {
    redirect( $url);
}

/**
 * Creates the admin form.
 *
 * @param int $id
 * @param object $mmogame
 * @param string $url (url for playing the game)
 **/
function mmogame_quiz_manage($id, $mmogame, $url) {
    global $OUTPUT;

    if (count( $_POST) > 0) {
        mmogame_quiz_manage_submit( $mmogame);
    }

    // Create form.
    $function = "mmogame_quiz_".$mmogame->get_model()."_admin";
    $mform = new $function( $id, $mmogame);

    echo $OUTPUT->header();

    $mform->display();

    echo '<br>'.get_string( 'url_for_playing', 'mmogame', ": <a href=\"$url\" target=\"_blank\">$url</a>");
    echo $OUTPUT->footer();
}

/**
 * For admin form has the code for prev/next numgame and prev/next state.
 *
 * @param object $mmogame
 **/
function mmogame_quiz_manage_submit($mmogame) {
    global $DB;

    $state = $mmogame->get_state();
    $numgame = $mmogame->get_numgame();

    $changestate = $changenumgame = false;
    if (array_key_exists( 'prevstate', $_POST) && $state > 0) {
        $state--;
        $changestate = true;
    } else if (array_key_exists( 'nextstate', $_POST) && $state < 1) {
        $state++;
        $changestate = true;
    }

    if (array_key_exists( 'prevnumgame', $_POST) && $numgame > 0) {
        $numgame--;
        $changenumgame = true;
    } else if (array_key_exists( 'nextnumgame', $_POST)) {
        $numgame++;
        $changenumgame = true;
    }

    $data = new stdClass();
    if ($changestate) {
        $data->state = $state;
    }
    if ($changenumgame) {
        $data->numgame = $numgame;
    }
    $class = "mmogameModel_".$mmogame->get_model();
    $class::json_setadmin( $data, $mmogame);
}
