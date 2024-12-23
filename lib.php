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
 * lib
 *
 * @package    mod_mmogame
 * @copyright  2024 Vasilis Daloukas
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/** Identifier the Moodle Questions as a source of question bank. */
const MMOGAME_QBANK_MOODLEQUESTION = 'moodlequestion';
/** Identifier the Moodle Glossary as a source of question bank. */
const MMOGAME_QBANK_MOODLEGLOSSARY = 'moodleglossary';

/**
 * Given an object containing all the necessary data, will create a new instance and return the id number of the new instance.
 *
 * @param object $mform An object from the form in mod.html
 *
 * @return int The id of the newly inserted game record
 */
function mmogame_add_instance(object $mform): int {
    global $DB;

    mmogame_before_add_or_update( $mform);

    if (!isset( $mform->guid)) {
        $mform->guidgame = mmogame_guidv4();
    }
    if (!isset( $mform->fastjson)) {
        for (;;) {
            // Generate a random number with 10 digits.
            $mform->fastjson = random_int(1000000000, 9999999999); // Generates a 10-digit number.

            // Check if the number already exists in the database.
            if ($DB->get_record_select('mmogame', 'fastjson=?', [$mform->fastjson]) === false) {
                break;
            }
        }
    }

    if (!isset( $mform->numgame)) {
        $mform->numgame = 1;
    }
    if (!isset( $mform->numattempt)) {
        $mform->numattempt = 1;
    }
    if (!isset( $mform->timefastjson)) {
        $mform->timefastjson = 0;
    }
    $mform->id = $DB->insert_record("mmogame", $mform);

    return $mform->id;
}

/**
 * Given an ID of an instance of this module, this function will permanently delete the instance and any data that depends on it.
 *
 * @param int $mmogameid Id of the module instance
 * @return boolean Success/Failure
 **/
function mmogame_delete_instance(int $mmogameid): bool {
    global $CFG, $DB;

    $rgame = $DB->get_record_select( 'mmogame', 'id=?', [$mmogameid]);
    if ($rgame === false) {
        return true;
    }

    $function = 'mmogametype_'.$rgame->type.'_delete_instance';
    require_once( $CFG->dirroot.'/mod/mmogame/type/'.$rgame->type.'/lib.php');
    $function( $mmogameid);

    $DB->delete_records_select( 'mmogame', 'id=?', [$mmogameid]);
    $DB->delete_records_select( 'mmogame_aa_grades', 'mmogameid=?', [$mmogameid]);
    $DB->delete_records_select( 'mmogame_aa_stats', 'mmogameid=?', [$mmogameid]);
    $DB->delete_records_select( 'mmogame_am_aduel_pairs', 'mmogameid=?', [$mmogameid]);

    return true;
}

/**
 * Updates some fields before writing to the database.
 *
 * @param stdClass $mform
 */
function mmogame_before_add_or_update(stdClass $mform): void {
    if (!isset( $mform->qbank)) {
        return;
    }

    $a = explode( '-', $mform->typemodel);
    if (count( $a) == 2) {
        $mform->type = $a[0];
        $mform->model = $a[1];
    }

    $mform->timemodified = time();

    switch ($mform->qbank) {
        case MMOGAME_QBANK_MOODLEGLOSSARY:
            mmogame_before_add_or_update_glossary( $mform);
            break;
        case MMOGAME_QBANK_MOODLEQUESTION:
            mmogame_before_add_or_update_question( $mform);
            break;
    }
}

/**
 * Called before add or update for repairing glossary params.
 *
 * @param stdClass $mmogame
 */
function mmogame_before_add_or_update_glossary(stdClass $mmogame): void {
    if (!isset( $mmogame->glossaryid)) {
        $mmogame->glossaryid = 0;
    }
    if (!isset( $mmogame->glossarycategoryid)) {
        $mmogame->glossarycategoryid = 0;
    }

    $mmogame->qbankparams = $mmogame->glossaryid.','.$mmogame->glossarycategoryid;
}

/**
 * Called before add or update for repairing question params.
 *
 * @param mmogame $mmogame
 */
function mmogame_before_add_or_update_question( mmogame $mmogame): void {
    $a = [];

    // Iterate over all properties of the $mmogame object.
    foreach (get_object_vars($mmogame) as $name => $value) {
        // Check if the property name starts with 'categoryid' and has a non-zero value.
        if (strpos($name, 'categoryid') === 0 && $value != 0) {
            $a[] = $value;
        }
    }

    $mmogame->qbankparams = implode( ',', $a);
}

/**
 * Given an object containing all the necessary data, this function will update an existing instance with new data.
 *
 * @param object $mmogame An object from the form in mod.html
 * @return boolean Success/Fail
 **/
function mmogame_update_instance(object $mmogame): bool {
    global $DB;

    $mmogame->id = $mmogame->instance;

    mmogame_before_add_or_update( $mmogame);

    if (!$DB->update_record("mmogame", $mmogame)) {
        return false;
    }

    return true;
}

/**
 * Returns a unique guid string version 4.
 *
 * @param bool $trim
 * @return string (the guid)
 */
function mmogame_guidv4(bool $trim = true): string {
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
    return $lbrace.
              substr($charid,  0,  8).$hyphen.
              substr($charid,  8,  4).$hyphen.
              substr($charid, 12,  4).$hyphen.
              substr($charid, 16,  4).$hyphen.
              substr($charid, 20, 12).
              $rbrace;
}

/**
 * Returns a list of sub-plugins.
 *
 * @return array of strings
 */
function mmogame_get_types(): array {
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

/**
 * Return the list if Moodle features this module supports
 *
 * @param string $feature FEATURE_xx constant for requested feature
 * @return ?bool True if module supports feature, false if not, null if it doesn't know or string for the module purpose.
 */
function mmogame_supports(string $feature): ?bool {
    switch ($feature) {
        case FEATURE_BACKUP_MOODLE2:
            return true;
        default:
            return null;
    }
}
