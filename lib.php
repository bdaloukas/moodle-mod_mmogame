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

/**
 * Returns the context instance of a Module. Is the same for all version of Moodle.
 *
 * This is used to find out if scale used anywhere
 *
 * @param int $moduleid
 * @return stdClass context
 */
function mmogame_get_context_module_instance( $moduleid) {
    if (class_exists( 'context_module')) {
        return context_module::instance( $moduleid);
    }

    return get_context_instance( CONTEXT_MODULE, $moduleid);
}

/**
 * use events?
 */
function mmogame_use_events() {
    $version = mmogame_get_moodle_version();

    return( $version >= '02.07');
}

/**
 * Returns the version of Moodle.
 *
 * @return string version
 */
function mmogame_get_moodle_version() {
    global $DB;

    static $smoodleversion = null;

    if ($smoodleversion != null) {
        return $smoodleversion;
    }

    $rec = $DB->get_record_select( 'config', "name='release'");
    if ($rec == false) {
        return $smoodleversion = '';
    } else {
        $a = explode( '.', $rec->value);
        return $smoodleversion = sprintf( '%02u.%02u', $a[0], $a[1]);
    }
}

/**
 * Given an object containing all the necessary data, will create a new instance and return the id number of the new instance.
 *
 * @param object $mform An object from the form in mod.html
 *
 * @return int The id of the newly inserted game record
 **/
function mmogame_add_instance( $mform) {
    global $DB;

    mmogame_before_add_or_update( $mform);

    if (!isset( $mform->guid)) {
        $mform->guidgame = mmogame_guidv4();
    }

    $mform->id = $DB->insert_record("mmogame", $mform);

    mmogame_after_add_or_update( $mform);

    return $mform->id;
}

/**
 * Given an ID of an instance of this module, this function will permanently delete the instance and any data that depends on it.
 *
 * @param int $mmogameid Id of the module instance
 * @return boolean Success/Failure
 **/
function mmogame_delete_instance( $mmogameid) {
    global $CFG, $DB;

    $mmogame = $DB->get_record_select( 'mmogame', 'id=?', [$mmogameid]);
    if ($mmogame === false) {
        return true;
    }

    $instances = $DB->get_records_select( 'mmogame_aa_instances', 'mmogameid=?', [$mmogameid]);
    foreach ($instances as $instance) {
        $function = 'mmogametype_'.$instance->type.'_delete_instance';
        require_once( $CFG->dirroot.'/mod/mmogame/type/'.$instance->type.'/lib.php');
        $function( $mmogameid, $instance->id);
    }

    $DB->delete_records_select( 'mmogame', 'id=?', [$mmogameid]);

    return true;
}

/**
 * Updates some fields before writing to database.
 *
 * @param stdClass $mform
 */
function mmogame_before_add_or_update( &$mform) {
    global $DB;

    if (!isset( $mform->qbank)) {
        return;
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
 * Updates some fields after writing to database.
 *
 * @param stdClass $mform
 */
function mmogame_after_add_or_update( &$mform) {
    global $DB;

    $recs = $DB->get_records_select( 'mmogame_aa_instances', 'mmogameid=?',
        [$mform->id], 'id', '*', 0, 1);
    if (count( $recs) == 0) {
        global $USER;

        $newrec = new stdClass();
        $newrec->mmogameid = $mform->id;
        $newrec->numgame = 1;
        $newrec->pin = $mform->pin;
        $newrec->enabled = 1;
        $newrec->numattempt = 1;
        $newrec->auserid = mmogame::get_auserid_from_moodle( new mmogame_database_moodle, $USER->id, true);
        $DB->insert_record( 'mmogame_aa_instances', $newrec);
        $recs = $DB->get_records_select( 'mmogame_aa_instances', 'mmogameid=?', [$mform->id], 'id', '*', 0, 1);
    }

    $instance = reset( $recs);
    $updrec = new stdClass();
    $updrec->id = $instance->id;
    $updrec->pin = $mform->pin;
    $updrec->kinduser = $mform->kinduser;
    $a = explode( '-', $mform->typemodel);
    if (count( $a) == 2) {
        $updrec->type = $a[0];
        $updrec->model = $a[1];
    }

    $DB->update_record( 'mmogame_aa_instances', $updrec);
}

/**
 * Called before add or update for repairing glossary params.
 *
 * @param stdClass $mmogame
 */
function mmogame_before_add_or_update_glossary( &$mmogame) {
    if (!isset( $mmogame->glossaryid)) {
        $mmogame->glossaryid = 0;
    }
    if (!isset( $mmogame->glossarycategoryid)) {
        $mmogame->glossarycategoryid = 0;
    }

    $mmogame->qbankparams = "{$mmogame->glossaryid},{$mmogame->glossarycategoryid}";
}

/**
 * Called before add or update for repairing question params.
 *
 * @param stdClass $mmogame
 */
function mmogame_before_add_or_update_question( &$mmogame) {

    $a = [];

    for ($i = 1; $i <= MMOGAME_QBANK_NUM_CATEGORIES; $i++) {
        $name = 'categoryid'.$i;

        if (isset( $mmogame->$name) && ($mmogame->$name != 0)) {
            $a[] = $mmogame->$name;
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
function mmogame_update_instance( $mmogame) {
    global $DB;

    $mmogame->id = $mmogame->instance;

    mmogame_before_add_or_update( $mmogame);

    if (!$DB->update_record("mmogame", $mmogame)) {
        return false;
    }

    mmogame_after_add_or_update( $mmogame);

    return true;
}
