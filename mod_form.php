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

use mod_mmogame\local\database\mmogame_database_moodle;
use mod_mmogame\local\mmogame;

defined('MOODLE_INTERNAL') || die();

define( 'MMOGAME_PIN_DIGITS', 6);

define( 'MMOGAME_KINDUSER_GUID', 'guid');
define( 'MMOGAME_KINDUSER_MOODLE', 'moodle');

require_once(__DIR__ . '/../../course/moodleform_mod.php');

/**
 * class mod_mmogame_mod_form extends class moodleform_mod
 *
 * @package    mod_mmogame
 * @copyright  2024 Vasilis Daloukas
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_mmogame_mod_form extends moodleform_mod {

    /**
     * Return the id.
     */
    public function get_id() {
        return $this->_instance;
    }

    /**
     * Definition of form.
     * @throws coding_exception
     * @throws dml_exception
     */
    protected function definition(): void {
        global $CFG;
        $mform = $this->_form;

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
     * Definition for params about models
     *
     * @param object $mform
     * @throws coding_exception
     */
    protected function definition_models(object $mform): void {
        $types = mmogame_get_types();
        $dir = __DIR__.'/type';
        $models = [];
        foreach ($types as $type) {
            require_once( $dir.'/'.$type.'/lib.php');
            $function = 'mmogametype_'.$type.'_get_models';
            $map = $function();
            foreach ($map as $model => $value) {
                $models[$type.'-'.$model] = $value;
            }
        }

        $mform->addElement('select', 'typemodel', get_string('type', 'mmogame'), $models);

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
     * @param stdClass $defaultvalues
     */
    public function data_preprocessing(&$defaultvalues): void {
        // Completion settings check.
        if (empty($defaultvalues['completionusegrade'])) {
            $defaultvalues['completionpass'] = 0; // Forced unchecked.
        }
    }

    /**
     * validation
     *
     * @param array $data
     * @param array $files
     *
     * @return array
     * @throws coding_exception
     */
    public function validation($data, $files): array {

        $errors = parent::validation($data, $files);

        if ($data['qbank'] == 'question') {
            if (!array_key_exists( 'questioncategoryid', $data) || $data['questioncategoryid'] == 0) {
                $errors['questioncategoryid'] = get_string( 'sourcemodule_questioncategory', 'mmogame');
            }
        }

        if ($data['kinduser'] == MMOGAME_KINDUSER_GUID) {
            if (intval( $data['pin']) == 0) {
                $errors['pin'] = get_string( 'missing_pin', 'mmogame');
            }
        }

        return $errors;
    }

    /**
     * Display module-specific activity completion rules.
     * Part of the API defined by moodleform_mod
     * @return array Array of string IDs of added items, empty array if none
     */
    public function add_completion_rules(): array {
        return [];
    }

    /**
     * Called during validation. Indicates whether a module-specific completion rule is selected.
     *
     * @param array $data Input data (not yet validated)
     * @return bool True if one or more rules is enabled, false if none are.
     */
    public function completion_rule_enabled($data): bool {
        return !empty($data['completionattemptsexhausted']) || !empty($data['completionpass']);
    }

    /**
     * Computes the categories of all question of the current course;
     *
     * @return bool|array of question categories
     * @throws dml_exception
     */
    public function get_array_question_categories(): ?array {
        global $DB, $COURSE;

        $courseid = $COURSE->id;

        if ($DB->get_manager()->table_exists('qbank')) {
            $sql = "SELECT ctx.id AS contextid
                FROM {context} ctx
                JOIN {course_modules} cm ON cm.id = ctx.instanceid
                JOIN {modules} m ON m.id = cm.module
                WHERE ctx.contextlevel = 70 AND cm.course = ? AND m.name = ?";
            $recs = $DB->get_records_sql( $sql, [$courseid, 'qbank']);
            $contextids = [];
            foreach ($recs as $rec) {
                $contextids[] = $rec->contextid;
            }
        } else {
            $contextids = [game_get_context_course_instance( $courseid)->id];
        }
        if (count( $contextids) == 0) {
            return [];
        }

        $a = [];
        $table = "{question} q, {qtype_multichoice_options} qmo";
        $select = " AND q.qtype='multichoice' AND qmo.single = 1 AND qmo.questionid=q.id";
        $sql2 = "SELECT COUNT(DISTINCT questionbankentryid) FROM $table,{question_bank_entries} qbe,".
            " {question_versions} qv ".
            " WHERE qbe.questioncategoryid = qc.id AND qbe.id=qv.questionbankentryid AND q.id=qv.questionid $select";
        [$insql, $params] = $DB->get_in_or_equal($contextids);
        $sql = "SELECT id,name,($sql2) as c FROM {question_categories} qc WHERE contextid ".$insql;
        if ($recs = $DB->get_records_sql( $sql, $params)) {
            foreach ($recs as $rec) {
                $a[$rec->id] = $rec->name.' ('.$rec->c.')';
            }
        }

        return $a;
    }

    /**
     * Set data
     *
     * @param object $defaultvalues
     */
    public function set_data($defaultvalues): void {
        $mmogameid = isset( $defaultvalues->id) ? intval($defaultvalues->id) : 0;

        if (isset( $defaultvalues->type) && isset( $defaultvalues->model)) {
            $defaultvalues->typemodel = $defaultvalues->type.'-'.$defaultvalues->model;
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
     * @param object $defaultvalues
     */
    public function set_data_categories(object $defaultvalues): void {
        if (!isset( $defaultvalues->instance)) {
            $defaultvalues->instance = 0;
        }

        if (isset( $defaultvalues->qbankparams)) {
            $a = explode( ',', $defaultvalues->qbankparams);
            if ($defaultvalues->qbank == MMOGAME_QBANK_MOODLEQUESTION) {
                $n = 0;
                foreach ($a as $s) {
                    $n++;
                    $name = 'categoryid'.$n;
                    $defaultvalues->$name = $s;
                }
            }
        }
    }


    /**
     * Computes the categories of all question of the current course
     *
     * @param object $mform
     * @throws coding_exception
     * @throws dml_exception
     */
    public function definition_question(object $mform): void {
        $numcategories = 3;

        for ($i = 1; $i <= $numcategories; $i++) {
            $name1 = 'categoryid'.$i;
            $mform->addElement('select', $name1, get_string('category', 'question').$i, $this->get_array_question_categories());
            $mform->setType($name1, PARAM_INT);
            $mform->hideIf($name1, 'qbank', 'neq', MMOGAME_QBANK_MOODLEQUESTION);
        }
    }
}
