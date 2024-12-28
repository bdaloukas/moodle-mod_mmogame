<?php
require_once(__DIR__ . '/../../config.php');

global $PAGE;

$PAGE->set_url(new moodle_url('/mod/mmogame/gate2.php'));
$PAGE->set_pagelayout('embedded'); // Layout χωρίς header και footer.
$PAGE->set_context(context_system::instance());

global $CFG, $DB, $USER, $OUTPUT;

// Load the JavaScript module.
$mmogameid = required_param('id', PARAM_INT);
$pin = required_param('pin', PARAM_INT);

$rgame = $DB->get_record_select( 'mmogame', 'id=?', [$mmogameid, $pin]);
if ($rgame === false) {
    $data = new stdClass();
    $data->mmogameid = $mmogameid;
    $data->pin = $pin;
    echo get_string( 'ivalid_mmogame_or_pin', 'mmogame', $data);
    die;
}
//$PAGE->requires->js_call_amd('mod_mmogame/helloworld', 'mmogame_gate', [1,927568,'moodle',$USER->id]);
//$PAGE->requires->js_call_amd('mod_mmogame/helloworld', 'mmogame_gate');
//$PAGE->requires->js(new moodle_url('/mod/mmogame/amd/src/helloworld.js?mmogameid=1&pin=2&kinduser=moodle&user=3'));

//$PAGE->requires->js('/mod/mmogame/type/quiz/amd/src/mmogamequizalone.js');
$PAGE->requires->js('/mod/mmogame/type/quiz/amd/src/mmogamequiz.js');

/*
$PAGE->requires->js_init_code("
    require(['../../type/quiz/amd/mmogamequizalone'], function(mmoGameQuizAlone) {
        // Δημιουργία αντικειμένου μέσω της init με παραμέτρους
        var obj = mmoGameQuizAlone.init($rgame->id,$rgame->pin, '$rgame->kinduser', '$USER->id');
        console.log(obj); // Μπορείς να κάνεις οποιαδήποτε ενέργεια με το αντικείμενο εδώ
    });
");
*/
$PAGE->requires->js_init_code("
    require(['mmogametype_quiz/mmogamequiz'], function(mmoGameQuiz) {
        var obj = new mmoGameQuiz($rgame->id,$rgame->pin, '$rgame->kinduser', '$USER->id');
        console.log(obj); // Μπορείς να κάνεις οποιαδήποτε ενέργεια με το αντικείμενο εδώ
    });
");
//obj.init($rgame->id,$rgame->pin, '$rgame->kinduser', '$USER->id');

$PAGE->requires->strings_for_js( ['js_name', 'js_question_time', 'js_sound', 'js_help'], 'mmogame');


// Output the page.
echo $OUTPUT->header();
echo $OUTPUT->footer();

die;


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

require( "../../config.php");

global $CFG, $DB, $USER;

$mmogameid = required_param('id', PARAM_INT);
$pin = required_param('pin', PARAM_INT);

$rgame = $DB->get_record_select( 'mmogame', 'id=?', [$mmogameid, $pin]);
if ($rgame === false) {
    $data = new stdClass();
    $data->mmogameid = $mmogameid;
    $data->pin = $pin;
    echo get_string( 'ivalid_mmogame_or_pin', 'mmogame', $data);
    die;
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="description" content="MMOGAME Gate.">
<title><?php echo $rgame->name; ?></title>
<style>
<?php echo file_get_contents( dirname(__FILE__)."/styles.css"); ?>
</style>
<body>

<?php

$color = $DB->get_record_select( 'mmogame_aa_colorpalettes', 'id=?', [2]);
$colors = '['.$color->color1.', '.$color->color2.', '.$color->color3.', '.$color->color4.', '.$color->color5.']';
if ($rgame->kinduser == 'moodle') {
    $sql = "SELECT cm.* FROM {course_modules} cm, {modules} m ".
        " WHERE cm.module=m.id AND cm.course=? AND cm.instance=? AND m.name=? ";
    $cm = $DB->get_record_sql( $sql, [$rgame->course, $rgame->id, 'mmogame']);
    require_login($rgame->course, false, $cm);
    $usercode = $USER->id;
} else {
    $usercode = '0';
}

global $PAGE;

//$PAGE->requires->require_js('/mod/mmogame/amd/src/helloworld.js');


/*
$PAGE->requires->js_call_amd('mod_mmogame/mmogame_gate', 'init', [
    'mmogameid' => $rgame->id,
    'pin' => $rgame->pin,
    'kinduser' => $rgame->kinduser,
    'user' => $usercode
]);
*/
$PAGE->requires->js('../../type/quiz/amd/mmogamequizalone');
$PAGE->requires->js_init_code("
    require(['../../type/quiz/amd/mmogamequizalone'], function(mmoGameQuizAlone) {
        // Δημιουργία αντικειμένου μέσω της init με παραμέτρους
        var obj = mmoGameQuizAlone.init($rgame->id,$rgame->pin, '$rgame->kinduser', '$usercode');
        console.log(obj); // Μπορείς να κάνεις οποιαδήποτε ενέργεια με το αντικείμενο εδώ
    });
");

echo 'ok';