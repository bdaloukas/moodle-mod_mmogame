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
 * @throws dml_exception
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
 * @param int $mmogameid id of the module instance
 * @return boolean Success/Failure
 * @throws dml_exception
 */
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
 * @param stdClass $mmogame
 */
function mmogame_before_add_or_update_question( stdClass $mmogame): void {
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
 * @throws dml_exception
 */
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

/**
 * Returns the same as mmogame_num_attempt_summary() but wrapped in a link
 * to the mmogame reports.
 *
 * @param stdClass $mmogame the mmogame object. Only $mmogame->id is used at the moment.
 * @param stdClass $cm the cm object. Only $cm->course, $cm->groupmode and
 *      $cm->groupingid fields are used at the moment.
 * @param stdClass $context the mmogame context.
 * @param bool $returnzero if false (default), when no attempts have been made
 *      '' is returned instead of 'Attempts: 0'.
 * @param int $currentgroup if there is a concept of current group where this method is being called
 *         (e.g., a report) pass it in here. Default 0 which means no current group.
 * @return string HTML fragment for the link.
 */
function mmogame_attempt_summary_link_to_reports(stdClass $mmogame, stdClass $cm, stdClass $context,
                                                 bool $returnzero = false, int $currentgroup = 0): string {
    global $PAGE;

    return $PAGE->get_renderer('mod_mmogame')->mmogame_attempt_summary_link_to_reports(
        $mmogame, $cm, $context, $returnzero, $currentgroup);
}

/**
 * Return a textual summary of the number of attempts that have been made at a particular mmogame,
 * returns '' if no attempts have been made yet, unless $returnzero is passed as true.
 *
 * @param stdClass $mmogame the mmogame object. Only $mmogame->id is used at the moment.
 * @param stdClass $cm the cm object. Only $cm->course, $cm->groupmode and
 *      $cm->groupingid fields are used at the moment.
 * @param bool $returnzero if false (default), when no attempts have been
 *      made '' is returned instead of 'Attempts: 0'.
 * @param int $currentgroup if there is a concept of current group where this method is being called
 *         (e.g., a report) pass it in here. Default 0 which means no current group.
 * @return string a string like "Attempts: 123", "Attemtps 123 (45 from your groups)" or
 *          "Attemtps 123 (45 from this group)".
 * @throws coding_exception
 * @throws dml_exception
 */
function mmogame_num_attempt_summary(stdClass $mmogame, stdClass $cm, bool $returnzero = false, int $currentgroup = 0): string {
    global $DB, $USER;
    $numattempts = $DB->count_records('mmogame_quiz_attempts', ['mmogameid' => $mmogame->id]);
    if ($numattempts || $returnzero) {
        if (groups_get_activity_groupmode($cm)) {
            $a = new stdClass();
            $a->total = $numattempts;
            if ($currentgroup) {
                $a->group = $DB->count_records_sql('SELECT COUNT(DISTINCT qa.id) FROM ' .
                    '{mmogame_quiz_attempts} qa JOIN ' .
                    '{groups_members} gm ON qa.userid = gm.userid ' .
                    'WHERE mmogameid = ? AND groupid = ?',
                    [$mmogame->id, $currentgroup]);
                return get_string('attemptsnumthisgroup', 'mmogameid', $a);
            } else if ($groups = groups_get_all_groups($cm->course, $USER->id, $cm->groupingid)) {
                list($usql, $params) = $DB->get_in_or_equal(array_keys($groups));
                $a->group = $DB->count_records_sql('SELECT COUNT(DISTINCT qa.id) FROM ' .
                    '{mmogame_quiz_attempts} qa JOIN ' .
                    '{groups_members} gm ON qa.userid = gm.userid ' .
                    'WHERE mmogameid = ? AND ' .
                    "groupid $usql", array_merge([$mmogame->id], $params));
                return get_string('attemptsnumyourgroups', 'mmogame', $a);
            }
        }
        return get_string('attemptsnum', 'mmogame', $numattempts);
    }
    return '';
}

/**
 * This function extends the settings navigation block for the site.
 *
 * It is safe to rely on PAGE here as we will only ever be within the module
 * context when this is called
 *
 * @param settings_navigation $settings
 * @param navigation_node $mmogamenode
 * @return void
 * @throws \core\exception\moodle_exception
 * @throws coding_exception
 */
function mmogame_extend_settings_navigation(settings_navigation $settings, navigation_node $mmogamenode) {
    global $CFG;

    $model = 'quiz';

    // Included here as we only ever want to include this file if we really need to.
    require_once($CFG->libdir . '/questionlib.php');

    // We want to add these new nodes after the Edit settings node, and before the
    // Locally assigned roles' node. Of course, both of those are controlled by capabilities.
    $keys = $mmogamenode->get_children_key_list();
    $beforekey = null;
    $i = array_search('modedit', $keys);
    if ($i === false && array_key_exists(0, $keys)) {
        $beforekey = $keys[0];
    } else if (array_key_exists($i + 1, $keys)) {
        $beforekey = $keys[$i + 1];
    }

    question_extend_settings_navigation($mmogamenode, $settings->get_page()->cm->context)->trim_if_empty();

    if (has_any_capability(['mod/quiz:viewreports', 'mod/quiz:grade'], $settings->get_page()->cm->context)) {
        require_once($CFG->dirroot . '/mod/mmogame/report/reportlib.php');
        $reportlist = mmogame_report_list($settings->get_page()->cm->context);

        $url = new moodle_url('/mod/mmogame/report.php',
            ['id' => $settings->get_page()->cm->id, 'mode' => reset($reportlist)]);
        $reportnode = $mmogamenode->add_node(navigation_node::create(get_string('results', 'quiz'), $url,
            navigation_node::TYPE_SETTING,
            null, 'mmogame_report', new pix_icon('i/report', '')));

        foreach ($reportlist as $report) {
            $url = new moodle_url('/mod/mmogame/report.php', ['id' => $settings->get_page()->cm->id, 'mode' => $report]);
            $reportnode->add_node(navigation_node::create(get_string('report_'.$report, 'mmogametype_'.$model), $url,
                navigation_node::TYPE_SETTING,
                null, 'mmogame_report_' . $report, new pix_icon('i/item', '')));
        }
    }
}
