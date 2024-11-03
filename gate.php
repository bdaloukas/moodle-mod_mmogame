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

define('NO_MOODLE_COOKIES', true);
require( "../../config.php");

require_once( 'database/moodle.php');
require_once(dirname(__FILE__) . '/mmogame.php');

$db = new mmogame_database_moodle();
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
<html>
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="description" content="MMOGAME Gate.">
<title><?php echo $rgame->name; ?></title>
<style type="text/css">
<?php echo file_get_contents( dirname(__FILE__)."/styles.css"); ?>
</style>
<body onload="runmmogame()">

<?php

$game = mmogame::getgame( $db, $rgame->id, $pin);
$type = $game->get_type();
$color = $DB->get_record_select( 'mmogame_aa_colorpalettes', 'id=?', [2]);
$colors = "[$color->color1, $color->color2, $color->color3, $color->color4, $color->color5]";

$usercode = $rgame->kinduser == 'moodle' ? $USER->id : '0';

echo "<script>\n";
mmogame_change_javascript( $rgame->type, 'js/mmogame.js');
$class = 'mmogame'.ucfirst($rgame->type).ucfirst($rgame->model);
mmogame_change_javascript( $rgame->type, 'js/gate.js', '[CLASS]', $class);
mmogame_change_javascript( $rgame->type, "type/{$rgame->type}/js/{$rgame->type}.js");
mmogame_change_javascript( $rgame->type, "type/{$rgame->type}/js/{$rgame->type}_{$rgame->model}.js");

?>
    function runmmogame() {
        let game = new mmogameGate();
        game.repairColors( <?php echo $colors;?>)
        game.open(<?php echo "\"{$CFG->wwwroot}/mod/mmogame/json.php\",{$rgame->id},
            {$rgame->pin},{$usercode},\"{$rgame->kinduser}\"" ?>);
    }
</script>

<?php
/**
 * Reads a javascript file and changes some string for translation replacing [LANG_...], [LANGM_...], [CLASS].
 *
 * @param string $type (type is a sub-plugin name e.g. quiz).'
 * @param string $file
 * @param string $search
 * @param string $replace
 *
 * @package    mod_mmogame
 * @copyright  2024 Vasilis Daloukas
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 **/
function mmogame_change_javascript( $type, $file, $search = '', $replace = '') {
    if (!file_exists( dirname(__FILE__).'/'.$file)) {
        return;
    }

    $s = file_get_contents( dirname(__FILE__).'/'.$file);

    // Remove multi-line comments (/* ... */).
    $s = preg_replace('/\/\*.*?\*\//s', '', $s);

    $s = preg_replace('/\n\s*\n/', "\n", $s); // Remove empty lines.

    // Changes variables.
    $source = $dest = [];
    if ($search != '') {
        $source[] = $search;
        $dest[] = $replace;
    }

    // Start from [LANG and finish with ].
    preg_match_all('/\[LANG_[A-Z0-9_]+\]/', $s, $matches);

    foreach ($matches as $stringm) {
        foreach ($stringm as $string) {
            $source[] = $string;
            $name = 'js_'.strtolower( substr( trim($string, '[]'), 5));
            $dest[] = get_string( $name, 'mmogametype_'.$type);
        }
    }

    // Start from [LANGM (for whole module) and finish with ].
    preg_match_all('/\[LANGM_[A-Z0-9_]+\]/', $s, $matches);
    foreach ($matches as $stringm) {
        foreach ($stringm as $string) {
            $source[] = $string;
            $name = 'js_'.strtolower( substr( trim($string, '[]'), 6));
            $dest[] = get_string( $name, 'mmogame');
        }
    }

    echo str_replace( $source, $dest, $s);
}
