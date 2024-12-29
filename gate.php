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

// No login check is expected here because users can play users as guest and as normal users
// If are normal users include later require_login.
// @codingStandardsIgnoreLine
require( "../../config.php");

global $CFG, $DB, $USER, $OUTPUT, $PAGE;

$PAGE->set_url(new moodle_url('/mod/mmogame/gate2.php'));
$PAGE->set_pagelayout('embedded'); // Layout χωρίς header και footer.
$PAGE->set_context(context_system::instance());

// Load the JavaScript module.
$mmogameid = required_param('id', PARAM_INT);
$pin = required_param('pin', PARAM_INT);

$color = $DB->get_record_select( 'mmogame_aa_colorpalettes', 'id=?', [2]);
$colors = '['.$color->color1.', '.$color->color2.', '.$color->color3.', '.$color->color4.', '.$color->color5.']';

$rgame = $DB->get_record_select( 'mmogame', 'id=?', [$mmogameid, $pin]);
if ($rgame === false) {
    $data = new stdClass();
    $data->mmogameid = $mmogameid;
    $data->pin = $pin;
    echo get_string( 'ivalid_mmogame_or_pin', 'mmogame', $data);
    die;
}

if ($rgame->kinduser == 'moodle' ) {
    require_login();
}
$PAGE->requires->js('/mod/mmogame/type/quiz/amd/src/mmogamequiz.js');

$PAGE->requires->strings_for_js(
    ['js_avatars', 'js_code', 'js_help', 'js_name', 'js_palette', 'js_grade',
        'js_grade_last_question', 'js_grade_opponent', 'js_opponent', 'js_palette', 'js_percent',
        'js_question_time',
        'js_ranking_grade', 'js_ranking_percent', 'js_sound'],
    'mmogame');

$PAGE->requires->js_init_code("
    require(['mmogametype_quiz/mmogamequiz'], function(mmoGameQuiz) {
        var obj = new mmoGameQuiz()
        obj.repairColors( $colors)
        obj.gateOpen( $rgame->id,$rgame->pin, '$rgame->kinduser', '$USER->id');
        console.log(obj); // Μπορείς να κάνεις οποιαδήποτε ενέργεια με το αντικείμενο εδώ
    });
");

// Output the page.
echo $OUTPUT->header();
echo $OUTPUT->footer();
