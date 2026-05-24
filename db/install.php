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
//
// Sometimes, changes between versions involve
// alterations to database structures and other
// major things that may break installations.
//
// The upgrade function in this file will attempt
// to perform all the necessary actions to upgrade
// your older installation to the current version.
//
// If there's something it cannot do itself, it
// will tell you what you need to do.
//
// The commands in here will all be database-neutral,
// using the methods of database_manager class
//
// Please do not forget to use upgrade_set_timeout()
// before any action that may take longer time to finish.

/**
 * This code run after the installation of MMOGame module and fills tables avatars and colorpalettes.
 *
 * @package    mod_mmogame
 * @copyright  2024 Vasilis Daloukas
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Function to execute custom code after the module is installed.
 */
function xmldb_mmogame_install() {
    global $DB;

    // Check if it needed to insert data at table mmogame_aa_colorpalettes.
    $recs = $DB->get_records_select('mmogame_aa_colorpalettes', '', null, '', '*', 0, 1);
    if (count($recs) == 0) {
        xmldb_mmogame_install_import_colorpalettes();
    }

    $recs = $DB->get_records_select('mmogame_aa_avatars', '', null, '', '*', 0, 1);
    if (count($recs) == 0) {
        xmldb_mmogame_install_avatars();
    }

    return true;
}

/**
 * Creates default color palettes.
 *
 * @return bool
 * @throws dml_exception
 */
function xmldb_mmogame_install_import_colorpalettes(): bool {
    global $DB;

    $records = [
        (object) ['id' => 1, 'category' => 'Game Design', 'name' => 'Dragon Idle',
            'color1' => 10190553, 'color2' => 7992050, 'color3' => 6799194, 'color4' => 13693522, 'color5' => 12540705,
            'colorsort1' => 12540705, 'colorsort2' => 10190553, 'colorsort3' => 6799194, 'colorsort4' => 7992050,
            'colorsort5' => 13693522, 'hue' => 22.025316455696],
        (object) ['id' => 2, 'category' => 'Game Design', 'name' => 'Valheim / UI Redesign',
            'color1' => 4506098, 'color2' => 6202559, 'color3' => 3565683, 'color4' => 2310181, 'color5' => 6189872,
            'colorsort1' => 2310181, 'colorsort2' => 3565683, 'colorsort3' => 6189872, 'colorsort4' => 6202559,
            'colorsort5' => 4506098, 'hue' => 124.13793103448],
        (object) ['id' => 3, 'category' => 'Game Design', 'name' => 'Luscus art (2016-2018)',
            'color1' => 9473522, 'color2' => 3312601, 'color3' => 7520754, 'color4' => 10542066, 'color5' => 15891512,
            'colorsort1' => 3312601, 'colorsort2' => 15891512, 'colorsort3' => 9473522, 'colorsort4' => 7520754, 'colorsort5' => 10542066, 'hue' => 208.02395209581],
        (object) ['id' => 4, 'category' => 'Game Design', 'name' => 'Robin: Sherwood Marauders',
            'color1' => 14239842, 'color2' => 3426675, 'color3' => 15918928, 'color4' => 14254616, 'color5' => 15886409,
            'colorsort1' => 3426675, 'colorsort2' => 14239842, 'colorsort3' => 15886409, 'colorsort4' => 14254616, 'colorsort5' => 15918928, 'hue' => 220],
        (object) ['id' => 5, 'category' => 'Game Design', 'name' => 'Illustrations - Crusader Kings III',
            'color1' => 15331314, 'color2' => 4014123, 'color3' => 9208134, 'color4' => 12562531, 'color5' => 5848098,
            'colorsort1' => 4014123, 'colorsort2' => 5848098, 'colorsort3' => 9208134, 'colorsort4' => 12562531, 'colorsort5' => 15331314, 'hue' => 68.571428571429],
        (object) ['id' => 6, 'category' => 'Game Design', 'name' => 'Crash On The Run Concept Art',
            'color1' => 848411, 'color2' => 15918653, 'color3' => 15909685, 'color4' => 15878412, 'color5' => 7546399,
            'colorsort1' => 7546399, 'colorsort2' => 15878412, 'colorsort3' => 848411, 'colorsort4' => 15909685, 'colorsort5' => 15918653, 'hue' => 5],
        (object) ['id' => 7, 'category' => 'Game Design', 'name' => 'Village Square',
            'color1' => 14256587, 'color2' => 8706308, 'color3' => 9486122, 'color4' => 14256968, 'color5' => 12535082,
            'colorsort1' => 12535082, 'colorsort2' => 14256968, 'colorsort3' => 9486122, 'colorsort4' => 8706308, 'colorsort5' => 14256587, 'hue' => 10.872483221477],
        (object) ['id' => 8, 'category' => 'Fashion', 'name' => 'Liubov Pogorela',
            'color1' => 9409190, 'color2' => 4606809, 'color3' => 15915221, 'color4' => 14257035, 'color5' => 14268600,
            'colorsort1' => 4606809, 'colorsort2' => 9409190, 'colorsort3' => 14257035, 'colorsort4' => 14268600,
            'colorsort5' => 15915221, 'hue' => 224.21052631579],
        (object) ['id' => 9, 'category' => 'Fashion', 'name' => 'Zeynep Çelik',
            'color1' => 12538973, 'color2' => 15907739, 'color3' => 14259838, 'color4' => 4202778, 'color5' => 9195586,
            'colorsort1' => 4202778, 'colorsort2' => 9195586, 'colorsort3' => 12538973, 'colorsort4' => 14259838,
            'colorsort5' => 15907739, 'hue' => 11.052631578947],
        (object) ['id' => 10, 'category' => 'Fashion', 'name' => 'Edina Csoboth',
            'color1' => 2570329, 'color2' => 2064780, 'color3' => 14264203, 'color4' => 7555644,
            'color5' => 14251615, 'colorsort1' => 2570329, 'colorsort2' => 7555644, 'colorsort3' => 2064780, 'colorsort4' => 14251615, 'colorsort5' => 14264203, 'hue' => 219.6],
        (object) ['id' => 11, 'category' => 'Architecture', 'name' => 'IND architects',
            'color1' => 6130342, 'color2' => 3170419, 'color3' => 11587033, 'color4' => 3319462,
            'color5' => 10916402, 'colorsort1' => 3170419, 'colorsort2' => 6130342, 'colorsort3' => 3319462, 'colorsort4' => 10916402, 'colorsort5' => 11587033, 'hue' => 197.01492537313],
        (object) ['id' => 12, 'category' => 'Architecture', 'name' => 'HOLZBRÜCKE | FULL CGI',
            'color1' => 1329292, 'color2' => 2714790, 'color3' => 15912470, 'color4' => 15889413, 'color5' => 14226948,
            'colorsort1' => 1329292, 'colorsort2' => 14226948, 'colorsort3' => 2714790, 'colorsort4' => 15889413, 'colorsort5' => 15912470, 'hue' => 214],
        (object) ['id' => 13, 'category' => 'Travel', 'name' => 'Yakobchuk Olena',
            'color1' => 14145497, 'color2' => 1589337, 'color3' => 6130342, 'color4' => 12548927, 'color5' => 9187099,
            'colorsort1' => 1589337, 'colorsort2' => 9187099, 'colorsort3' => 6130342, 'colorsort4' => 12548927, 'colorsort5' => 14145497, 'hue' => 203.07692307692],
        (object) ['id' => 14, 'category' => 'Travel', 'name' => 'Tryfonov',
            'color1' => 4818572, 'color2' => 10541529, 'color3' => 14266726, 'color4' => 12556649, 'color5' => 10890541,
            'colorsort1' => 10890541, 'colorsort2' => 4818572, 'colorsort3' => 12556649, 'colorsort4' => 14266726, 'colorsort5' => 10541529, 'hue' => 0],
        (object) ['id' => 15, 'category' => 'Travel', 'name' => 'Tierney',
            'color1' => 5333132, 'color2' => 8820646, 'color3' => 12570073, 'color4' => 12552022, 'color5' => 9195307,
            'colorsort1' => 9195307, 'colorsort2' => 5333132, 'colorsort3' => 12552022, 'colorsort4' => 8820646, 'colorsort5' => 12570073, 'hue' => 22.268041237113],
        (object) ['id' => 16, 'category' => 'Illustration', 'name' => 'Graziela Andrade',
            'color1' => 15890819, 'color2' => 15897792, 'color3' => 15916706, 'color4' => 15903633, 'color5' => 7553334,
            'colorsort1' => 7553334, 'colorsort2' => 15890819, 'colorsort3' => 15897792, 'colorsort4' => 15903633, 'colorsort5' => 15916706, 'hue' => 10.819672131148],
        (object) ['id' => 17, 'category' => 'Flavour', 'name' => 'Aliaksandr Barouski',
            'color1' => 14273154, 'color2' => 15920095, 'color3' => 14267785, 'color4' => 10911581, 'color5' => 9198666,
            'colorsort1' => 9198666, 'colorsort2' => 10911581, 'colorsort3' => 14267785, 'colorsort4' => 14273154, 'colorsort5' => 15920095, 'hue' => 16.363636363636],
        (object) ['id' => 18, 'category' => 'Travel', 'name' => 'Simona',
            'color1' => 5148070, 'color2' => 15911685, 'color3' => 15900421, 'color4' => 15908753, 'color5' => 4205858,
            'colorsort1' => 4205858, 'colorsort2' => 5148070, 'colorsort3' => 15900421, 'colorsort4' => 15911685, 'colorsort5' => 15908753, 'hue' => 22],
        (object) ['id' => 19, 'category' => 'Travel', 'name' => 'RightFramePhotoVideo',
            'color1' => 2899801, 'color2' => 12238630, 'color3' => 14014822, 'color4' => 9203789, 'color5' => 15894603,
            'colorsort1' => 2899801, 'colorsort2' => 9203789, 'colorsort3' => 15894603, 'colorsort4' => 12238630, 'colorsort5' => 14014822, 'hue' => 214.66666666667],
        (object) ['id' => 20, 'category' => 'Travel', 'name' => 'Mrton',
            'color1' => 6202559, 'color2' => 4020527, 'color3' => 7317048, 'color4' => 6655038, 'color5' => 15921906,
            'colorsort1' => 4020527, 'colorsort2' => 6655038, 'colorsort3' => 7317048, 'colorsort4' => 6202559, 'colorsort5' => 15921906, 'hue' => 100],
        (object) ['id' => 21, 'category' => 'Travel', 'name' => 'Biletskiy Evgeniy',
            'color1' => 14240356, 'color2' => 7540265, 'color3' => 1534579, 'color4' => 6004390, 'color5' => 7550761,
            'colorsort1' => 7540265, 'colorsort2' => 7550761, 'colorsort3' => 1534579, 'colorsort4' => 14240356, 'colorsort5' => 6004390, 'hue' => 343.9603960396],
        (object) ['id' => 22, 'category' => 'Fashion', 'name' => 'Alena Saz',
            'color1' => 15902281, 'color2' => 14263183, 'color3' => 15911869, 'color4' => 9193786, 'color5' => 14232363,
            'colorsort1' => 9193786, 'colorsort2' => 14232363, 'colorsort3' => 14263183, 'colorsort4' => 15902281, 'colorsort5' => 15911869, 'hue' => 10.975609756098],
        (object) ['id' => 23, 'category' => 'Flavour', 'name' => 'baibaz',
            'color1' => 10908312, 'color2' => 11124602, 'color3' => 14259308, 'color4' => 12545616, 'color5' => 15921906,
            'colorsort1' => 12545616, 'colorsort2' => 10908312, 'colorsort3' => 14259308, 'colorsort4' => 11124602, 'colorsort5' => 15921906, 'hue' => 16.216216216216],
        (object) ['id' => 24, 'category' => 'Flavour', 'name' => 'Tatyana Sidyukova',
            'color1' => 12518438, 'color2' => 11124602, 'color3' => 14240423, 'color4' => 239270, 'color5' => 15903527,
            'colorsort1' => 12518438, 'colorsort2' => 239270, 'colorsort3' => 14240423, 'colorsort4' => 11124602,
            'colorsort5' => 15903527, 'hue' => 349.09090909091],
        (object) ['id' => 25, 'category' => 'Travel', 'name' => 'Tryfonov',
            'color1' => 12525356, 'color2' => 8169106, 'color3' => 15266512, 'color4' => 14267652, 'color5' => 9198914,
            'colorsort1' => 12525356, 'colorsort2' => 9198914, 'colorsort3' => 8169106, 'colorsort4' => 14267652, 'colorsort5' => 15266512, 'hue' => 355.125],
        (object) ['id' => 26, 'category' => 'Travel', 'name' => 'Jag_cz',
            'color1' => 306623, 'color2' => 311231, 'color3' => 153945, 'color4' => 10528259, 'color5' => 15917257,
            'colorsort1' => 153945, 'colorsort2' => 306623, 'colorsort3' => 311231, 'colorsort4' => 10528259, 'colorsort5' => 15917257, 'hue' => 180],
        (object) ['id' => 28, 'category' => 'Travel', 'name' => 'Sondem',
            'color1' => 2244641, 'color2' => 5862221, 'color3' => 3489837, 'color4' => 7048265, 'color5' => 5844002,
            'colorsort1' => 2244641, 'colorsort2' => 5844002, 'colorsort3' => 3489837, 'colorsort4' => 5862221, 'colorsort5' => 7048265, 'hue' => 118.06451612903],
        (object) ['id' => 29, 'category' => 'Game Design', 'name' => 'Carlos Cavalcante',
            'color1' => 947049, 'color2' => 887912, 'color3' => 6012716, 'color4' => 15897924, 'color5' => 14254175,
            'colorsort1' => 947049, 'colorsort2' => 887912, 'colorsort3' => 6012716, 'colorsort4' => 14254175, 'colorsort5' => 15897924, 'hue' => 174.05940594059],
        (object) ['id' => 30, 'category' => 'Fashion', 'name' => 'Macarena Ibsen',
            'color1' => 7537175, 'color2' => 5846311, 'color3' => 12550763, 'color4' => 15909294, 'color5' => 12539478,
            'colorsort1' => 7537175, 'colorsort2' => 5846311, 'colorsort3' => 12539478, 'colorsort4' => 12550763, 'colorsort5' => 15909294, 'hue' => 348.84955752212],
        (object) ['id' => 31, 'category' => 'Wilderness', 'name' => 'Asian tropical rainforest',
            'color1' => 1517057, 'color2' => 2899969, 'color3' => 8955420, 'color4' => 6255382, 'color5' => 14014839,
            'colorsort1' => 1517057, 'colorsort2' => 2899969, 'colorsort3' => 6255382, 'colorsort4' => 8955420, 'colorsort5' => 14014839, 'hue' => 84.324324324324],
        (object) ['id' => 32, 'category' => 'Graphic Design', 'name' => 'Rebranding Casa dos Rapazes',
            'color1' => 215718, 'color2' => 219276, 'color3' => 239164, 'color4' => 15903056, 'color5' => 15886409,
            'colorsort1' => 215718, 'colorsort2' => 219276, 'colorsort3' => 239164, 'colorsort4' => 15886409, 'colorsort5' => 15903056, 'hue' => 213.86503067485],
        (object) ['id' => 33, 'category' => 'Game Design', 'name' => 'Merge Game - Objects & Props - Part 3',
            'color1' => 14769906, 'color2' => 8664998, 'color3' => 5327858, 'color4' => 8627768, 'color5' => 13955686,
            'colorsort1' => 8664998, 'colorsort2' => 5327858, 'colorsort3' => 8627768, 'colorsort4' => 14769906, 'colorsort5' => 13955686, 'hue' => 281.62162162162],
        (object) ['id' => 34, 'category' => 'Game Design', 'name' => 'Isometric Game Design for Sunny House',
            'color1' => 5451174, 'color2' => 296921, 'color3' => 6320925, 'color4' => 15900733, 'color5' => 15891253,
            'colorsort1' => 5451174, 'colorsort2' => 6320925, 'colorsort3' => 296921, 'colorsort4' => 15891253, 'colorsort5' => 15900733, 'hue' => 258.84297520661],
    ];

    foreach ($records as $record) {
        $DB->insert_record('mmogame_aa_colorpalettes', $record, false);
    }

    return true;
}
/**
 * Reads the avatars in the assets/avatars director and inserts a corresponging record to table aa_mmogame_avatars.
 */
function xmldb_mmogame_install_avatars() {
    global $DB;

    $dir = dirname(__FILE__) . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'assets' . DIRECTORY_SEPARATOR . 'avatars';
    $d = dir($dir);

    while (false !== ($entry = $d->read())) {
        if (substr($entry, 0, 1) != '.') {
            $d2 = dir($dir . DIRECTORY_SEPARATOR . DIRECTORY_SEPARATOR . $entry);
            while (false !== ($entry2 = $d2->read())) {
                if (substr($entry2, 0, 1) == '.') {
                    continue;
                }
                $rec = $DB->get_record_select(
                    'mmogame_aa_avatars',
                    'directory=? AND filename=?',
                    [$entry, $entry2]
                );
                if ($rec != false) {
                    continue;
                }
                $rec = new stdClass();
                $rec->directory = $entry;
                $rec->filename = $entry2;
                $rec->numused = 0;
                $rec->randomkey = mt_rand();
                $DB->insert_record('mmogame_aa_avatars', $rec);
            }
            $d2->close();
        }
    }
    $d->close();
}
