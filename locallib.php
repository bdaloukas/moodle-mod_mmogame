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
 * This file contains the definition for the class mmogame
 *
 * This class provides all the functionality for the new mmogame module.
 *
 * @package    mod_mmogame
 * @copyright  2024 Vasilis Daloukas
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define( 'MMOGAME_QBANK_MOODLEQUESTION', 'moodlequestion');
define( 'MMOGAME_QBANK_MOODLEGLOSSARY', 'moodleglossary');
define( 'MMOGAME_QBANK_NONE', 'none');
define( 'MMOGAME_QBANK_NUM_CATEGORIES', 3);

define( 'MMOGAME_DATABASE_MOODLE', 'moodle');

define( 'MMOGAME_USER_MOODLE', 'moodle');

/**
 * Returns a unique guid string version 4.
 *
 * @param boolean $trim
 * @return string (the guid)
 */
function mmogame_guidv4($trim = true) {
    // Windows.
    if (function_exists('com_create_guid') === true) {
        if ($trim) {
            return trim(com_create_guid(), '{}');
        } else {
            return com_create_guid();
        }
    }

    // OSX/Linux.
    if (function_exists('openssl_random_pseudo_bytes') === true) {
        $data = openssl_random_pseudo_bytes(16);
        $data[6] = chr(ord($data[6]) & 0x0f | 0x40);    // Set version to 0100.
        $data[8] = chr(ord($data[8]) & 0x3f | 0x80);    // Set bits 6-7 to 10.
        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }

    // Fallback (PHP 4.2+).
    mt_srand((double)microtime() * 10000);
    $charid = strtolower(md5(uniqid(rand(), true)));
    $hyphen = chr(45);                  // Is "-".
    $lbrace = $trim ? "" : chr(123);    // Is "{".
    $rbrace = $trim ? "" : chr(125);    // Is "}".
    $guidv4 = $lbrace.
              substr($charid,  0,  8).$hyphen.
              substr($charid,  8,  4).$hyphen.
              substr($charid, 12,  4).$hyphen.
              substr($charid, 16,  4).$hyphen.
              substr($charid, 20, 12).
              $rbrace;
    return $guidv4;
}

/**
 * Returns a list of sub-plugins.
 *
 * @return array of strings
 */
function mmogame_get_types() {
    $dir = __DIR__.'/type';
    $types = [];
    if (is_dir($dir)) {
        $files = scandir($dir);

        foreach ($files as $file) {
            if ($file != "." && $file != "..") {
                if (is_dir( $dir.'/'.$file)) {
                    $types[] = $file;
                }
            }
        }
    }

    return $types;
}
