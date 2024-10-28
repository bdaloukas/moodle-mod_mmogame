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
 * Defines the mmogame module Settings form.
 *
 * @package    mod_mmogame
 * @copyright  2024 Vasilis Daloukas
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

define( 'MMOGAME_PIN_DIGITS', 6);

define( 'MMOGAME_KINDUSER_GUID', 'guid');
define( 'MMOGAME_KINDUSER_MOODLE', 'moodle');

require_once($CFG->dirroot . '/course/moodleform_mod.php');
require_once($CFG->dirroot . '/mod/mmogame/locallib.php');

require_once($CFG->dirroot . '/mod/mmogame/database/moodle.php');
require_once($CFG->dirroot . '/mod/mmogame/mmogame.php');

/**
 * class mod_mmogame_mod_form extends class moodleform_mod
 *
 * @package    mod_mmogame
 * @copyright  2024 Vasilis Daloukas
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_mmogame_mod_form extends moodleform_mod {
    /** @var array options to be used with date_time_selector fields in the mmogame. */
    public static $datefieldoptions = ['optional' => true];

    /** @var int the max number of attempts allowed in any user or group override on this mmogame. */
    protected $maxattemptsanyoverride = null;

    /**
     * Return the id.
     */
    public function get_id() {
        return $this->_instance;
    }

    /**
     * Definition of form.
     */
    protected function definition() {
        global $COURSE, $CFG, $DB, $PAGE;
        $mform = $this->_form;

        $id = $this->_instance;

        // -------------------------------------------------------------------------------
        $mform->addElement('header', 'general', get_string('general', 'form'));

        // Name.
        $mform->addElement('text', 'name', get_string('name'), ['size' => '64']);
        if (!empty($CFG->formatstringstriptags)) {
            $mform->setType('name', PARAM_TEXT);
        } else {
            $mform->setType('name', PARAM_CLEANHTML);
        }
        $mform->addRule('name', null, 'required', null, 'client');
        $mform->addRule('name', get_string('maximumchars', '', 255), 'maxlength', 255, 'client');

        if (!isset( $g)) {
            $mform->setDefault('name', get_string( 'pluginname', 'mmogame'));
        }

        // Introduction.
        $this->standard_intro_elements(get_string('introduction', 'mmogame'));

        $qbankoptions = [];
        $qbankoptions[MMOGAME_QBANK_MOODLEQUESTION] = get_string('sourcemodule_question', 'mmogame');

        $mform->addElement('select', 'qbank', get_string('sourcemodule', 'mmogame'), $qbankoptions);

        $this->definition_glossary( $mform);
        $this->definition_question( $mform);

        $usersoptions = [];
        $usersoptions[MMOGAME_KINDUSER_GUID] = get_string('kinduser_guid', 'mmogame');
        $usersoptions[MMOGAME_KINDUSER_MOODLE] = get_string('kinduser_moodle', 'mmogame');
        $mform->addElement('select', 'kinduser', get_string('kinduser', 'mmogame'), $usersoptions);

        $this->definition_models( $mform);

        $this->standard_coursemodule_elements();

        $this->add_action_buttons();
    }

    /**
     * Definition for params abouts models
     *
     * @param object $mform
     */
    protected function definition_models($mform) {
        global $CFG;

        $types = mmogame_get_types();
        $dir = __DIR__.'/type';
        $models = [];
        foreach( $types as $type) {
            require_once( $dir.'/'.$type.'/lib.php');
            $function = 'mmogametype_'.$type.'_get_models';
            $map = $function();
            foreach ($map as $model => $value) {
                $models[$type.'-'.$model] = $value;
            }
        }

        $options = [];
        foreach ($models as $model => $title) {
            $options[$model] = $title;
        }

        $mform->addElement('select', 'typemodel', get_string('type', 'mmogame'), $options);

        // Pin.
        $mform->addElement('text', 'pin', "PIN", ['size' => '10']);
        $mform->setType('pin', PARAM_INT);
        $mform->hideIf('pin', 'user', 'neq', MMOGAME_KINDUSER_GUID);

        // Enabled.
        $mform->addElement('advcheckbox', 'enabled', get_string( 'enabled', 'mmogame'),
            get_string('yesno', 'mmogame'), ['group' => 1], [0, 1]);

        // Strip tags.
        $mform->addElement('advcheckbox', 'striptags', get_string( 'striptags', 'mmogame'),
            get_string('yesno', 'mmogame'), ['group' => 1], [0, 1]);
    }

    /**
     * data_preprocessing
     *
     * @param stdClass $toform
     */
    public function data_preprocessing(&$toform) {
        if (isset($toform['grade'])) {
            // Convert to a real number, so we don't get 0.0000.
            $toform['grade'] = $toform['grade'] + 0;
        }

        // Completion settings check.
        if (empty($toform['completionusegrade'])) {
            $toform['completionpass'] = 0; // Forced unchecked.
        }
    }

    /**
     * validation
     *
     * @param stdClass $data
     * @param array $files
     *
     * @return moodle_url
     */
    public function validation($data, $files) {

        $errors = parent::validation($data, $files);

        if ($data['qbank'] == 'glossary') {
            if (!array_key_exists( 'glossaryid', $data) || $data['glossaryid'] == 0) {
                $errors['glossaryid'] = get_string( 'sourcemodule_glossary', 'mmogame');
            }
        } else if ($data['qbank'] == 'question') {
            if (!array_key_exists( 'questioncategoryid', $data) || $data['questioncategoryid'] == 0) {
                $errors['questioncategoryid'] = get_string( 'sourcemodule_questioncategory', 'game');
            }
        }

        if ($data['kinduser'] == MMOGAME_KINDUSER_GUID) {
            if (intval( $data['pin']) == 0) {
                $errors['pin'] = get_string( 'missing_pin', 'mmogame');
            }
        }

        if (array_key_exists( 'glossarycategoryid', $data)) {
            if ($data['glossarycategoryid'] != 0) {
                $sql = "SELECT glossaryid FROM {$CFG->prefix}glossary_categories ".
                " WHERE id=".$data['glossarycategoryid'];
                $rec = $DB->get_record_sql( $sql);
                if ($rec != false) {
                    if ($data['glossaryid'] != $rec->glossaryid) {
                        $s = get_string( 'different_glossary_category', 'game');
                        $errors['glossaryid'] = $s;
                        $errors['glossarycategoryid'] = $s;
                    }
                }
            }
        }

        return $errors;
    }

    /**
     * Display module-specific activity completion rules.
     * Part of the API defined by moodleform_mod
     * @return array Array of string IDs of added items, empty array if none
     */
    public function add_completion_rules() {
        $mform = $this->_form;
        $items = [];

        return $items;
    }

    /**
     * Called during validation. Indicates whether a module-specific completion rule is selected.
     *
     * @param array $data Input data (not yet validated)
     * @return bool True if one or more rules is enabled, false if none are.
     */
    public function completion_rule_enabled($data) {
        return !empty($data['completionattemptsexhausted']) || !empty($data['completionpass']);
    }

    /**
     * Computes the categories of all question of the current course;
     *
     * @return array of question categories
     */
    public function get_array_question_categories() {
        global $CFG, $DB, $COURSE;

        $courseid = $COURSE->id;
        $context = context_course::instance( $courseid);

        $qtypes = '';
        $qtypes .= ($qtypes != '' ? ' OR ' : '').'qtype = "shortanswer"';
        $qtypes .= ($qtypes != '' ? ' OR ' : '').'qtype = "multichoice"';

        if ($qtypes == '') {
            return;
        }

        $a = [];
        $table = "{$CFG->prefix}question q, {$CFG->prefix}qtype_multichoice_options qmo";
        $select = " AND q.qtype='multichoice' AND qmo.single = 1 AND qmo.questionid=q.id";
        $sql2 = "SELECT COUNT(DISTINCT questionbankentryid) FROM $table,{$CFG->prefix}question_bank_entries qbe,".
            " {$CFG->prefix}question_versions qv ".
            " WHERE qbe.questioncategoryid = qc.id AND qbe.id=qv.questionbankentryid AND q.id=qv.questionid $select";
        $sql = "SELECT id,name,($sql2) as c FROM {$CFG->prefix}question_categories qc WHERE contextid = $context->id";
        if ($recs = $DB->get_records_sql( $sql)) {
            foreach ($recs as $rec) {
                $a[$rec->id] = $rec->name.' ('.$rec->c.')';
            }
        }

        return $a;
    }

    /**
     * Set data
     *
     * @param array $defaultvalues
     */
    public function set_data($defaultvalues) {
        global $DB;

        $mmogameid = isset( $defaultvalues->id) ? $defaultvalues->id : 0;

        if ($mmogameid != 0) {
            $recs = $DB->get_records_select( 'mmogame_aa_instances', 'mmogameid=?',
                [$mmogameid], 'id', '*', 0, 1);
            if (count( $recs)) {
                $instance = reset( $recs);
                $defaultvalues->pin = $instance->pin;
                $defaultvalues->enabled = $instance->enabled;
                $defaultvalues->striptags = $instance->striptags;
            }
        }

        if (!isset( $defaultvalues->pin) || $defaultvalues->pin == 0) {
            $db = new mmogame_database_moodle();
            $defaultvalues->pin = mmogame::get_newpin( $mmogameid, $db, MMOGAME_PIN_DIGITS);
        }

        if (!isset( $defaultvalues->enabled)) {
            $defaultvalues->enabled = 1;
        }

        $this->set_data_categories( $defaultvalues);

        parent::set_data($defaultvalues);
    }

    /**
     * Set data about categories
     *
     * @param array $defaultvalues
     */
    public function set_data_categories(&$defaultvalues) {
        global $CFG, $DB;

        if (!isset( $defaultvalues->instance)) {
            $defaultvalues->instance = 0;
        }

        foreach ($defaultvalues as $key => $name) {
            if (substr( $key, 0, 10) != 'categoryid') {
                continue;
            }
            $name = 'categoryid'.$i;
            $defaultvalues->$name = 0;
            $name = 'numquestions'.$i;
            $defaultvalues->$name = 0;
        }

        if (isset( $defaultvalues->qbankparams)) {
            $a = explode( ',', $defaultvalues->qbankparams);
            if ($defaultvalues->qbank == MMOGAME_QBANK_MOODLEQUESTION) {
                $n = 0;
                foreach ($a as $s) {
                    $n++;
                    $name = 'categoryid'.$n;
                    $defaultvalues->$name = $s;
                    $name = 'numquestions'.$n;
                    $defaultvalues->$name = 1;
                }
            } else if ($defaultvalues->qbank == MMOGAME_QBANK_MOODLEGLOSSARY) {
                if (count( $a) >= 1) {
                    $defaultvalues->glossaryid = $a[0];
                }
                if (count( $a) >= 2) {
                    $defaultvalues->glossaryid = $a[0];
                    $defaultvalues->glossarycategoryid = $a[1];
                }
            }
        }
    }

    /**
     * Computes the categories of all glossaries of the current course;
     *
     * @param array $a array of id of glossaries to each name
     *
     * @return array of glossary categories
     */
    public function get_array_glossary_categories($a) {
        global $CFG, $DB;

        if (count( $a) == 0) {
            $select = 'gc.glossaryid = -1';
        } else if (count($a) == 1) {
            foreach ($a as $id => $name) {
                $select = 'gc.glossaryid = '.$id;
                break;
            }
        } else {
            $select = '';
            foreach ($a as $id => $name) {
                $select .= ','.$id;
            }
            $select = 'gc.glossaryid IN ('.substr( $select, 1).')';
        }

        $a = [];

        // Fills with the count of entries in each glossary.
        $a[0] = '';
        // Fills with the count of entries in each category.
        $sql2 = "SELECT COUNT(*) ".
        " FROM {$CFG->prefix}glossary_entries ge, {$CFG->prefix}glossary_entries_categories gec".
        " WHERE gec.categoryid=gc.id AND gec.entryid=ge.id";
        $sql = "SELECT gc.id,gc.name,g.name as name2,g.globalglossary,g.course, ($sql2) as c ".
        " FROM {$CFG->prefix}glossary_categories gc, {$CFG->prefix}glossary g".
        " WHERE $select AND gc.glossaryid=g.id".
        " ORDER BY g.name, gc.name";
        if ($recs = $DB->get_records_sql( $sql)) {
            foreach ($recs as $rec) {
                $a[$rec->id] = $rec->name2.' -> '.$rec->name.' ('.$rec->c.')';
            }
        }

        return $a;
    }

    /**
     * Computes the categories of all question of the current course
     *
     * @param object $mform
     * @return array of question categories
     */
    public function definition_question(&$mform) {
        $numcategories = 3;

        for ($i = 1; $i <= $numcategories; $i++) {
            $name1 = 'categoryid'.$i;
            $mform->addElement('select', $name1, get_string('category', 'question').$i, $this->get_array_question_categories());
            $mform->setType($name1, PARAM_INT);
            $mform->hideIf($name1, 'qbank', 'neq', MMOGAME_QBANK_MOODLEQUESTION);
        }
    }

    /**
     * Show fields about selecting glossaries
     *
     * @param object $mform
     * @return array of question categories
     */
    public function definition_glossary(&$mform) {
        global $DB, $COURSE, $CFG;

        $a = [];
        $sql = "SELECT id,name,globalglossary,course FROM {$CFG->prefix}glossary ".
            "WHERE course={$COURSE->id} OR globalglossary=1 ORDER BY globalglossary DESC,name";
        if ($recs = $DB->get_records_sql($sql)) {
            foreach ($recs as $rec) {
                if (($rec->globalglossary != 0) && ($rec->course != $COURSE->id)) {
                    $rec->name = '*'.$rec->name;
                }
                $a[$rec->id] = $rec->name;
            }
        }
        $mform->addElement('select', 'glossaryid', get_string('sourcemodule_glossary', 'mmogame'), $a);
        $mform->hideIf('glossaryid', 'qbank', 'neq', 'moodleglossary');

        $a = $this->get_array_glossary_categories( $a);
        $mform->addElement('select', 'glossarycategoryid', get_string('sourcemodule_glossarycategory', 'mmogame'), $a);
        $mform->hideIf('glossarycategoryid', 'qbank', 'neq', 'moodleglossary');

        // Only approved.
        $mform->addElement('selectyesno', 'glossaryonlyapproved', get_string('glossary_only_approved', 'mmogame'));
        $mform->hideIf('glossaryonlyapproved', 'qbank', 'neq', 'moodleglossary');
    }
}
