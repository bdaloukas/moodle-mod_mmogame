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

define( 'MMOGAME_RESTORE_QBANK_MOODLEQUESTION', 1);

/**
 * Structure step to restore one mmogame activity
 *
 * @package    mod_mmogame
 * @copyright  2024 Vasilis Daloukas
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class restore_mmogame_activity_structure_step extends restore_questions_activity_structure_step {
    /**
     * Define the structure of the restore workflow.
     *
     * @return restore_path_element $structure
     */
    protected function define_structure() {
        $paths = [];
        $userinfo = $this->get_setting_value('userinfo');
        $paths[] = new restore_path_element('mmogame_state', '/activity/mmogame/states/state');

        $paths[] = new restore_path_element('mmogame', '/activity/mmogame');
        $paths[] = new restore_path_element('mmogame_auser', '/activity/mmogame/auser/');
        if ($userinfo) {
            $type = new restore_path_element('mmogame_type',
                                                   '/activity/mmogame/types/type');
            $paths[] = $type;
            $this->add_subplugin_structure('mmogametype', $type);
            
            $paths[] = new restore_path_element('mmogame_uguid', '/activity/mmogame/uquids/uguid');
            $paths[] = new restore_path_element('mmogame_avatar', '/activity/mmogame/avatars/avatar');
            $paths[] = new restore_path_element('mmogame_palette', '/activity/mmogame/palettes/palette');
            $paths[] = new restore_path_element('mmogame_grade', '/activity/mmogame/grades/grade');
            $paths[] = new restore_path_element('mmogame_stat', '/activity/mmogame/stats/stat');
            $paths[] = new restore_path_element('mmogame_aduel_pair', '/activity/mmogame/pairs/pair');
        }

        // Return the paths wrapped into standard activity structure.
        return $this->prepare_activity_structure($paths);
    }

    /**
     * Process an mmogame restore.
     *
     * @param object $data The data in object form
     * @return void
     */
    protected function process_mmogame($data) {
        global $DB;

        $data = (object)$data;
        $oldid = $data->id;
        $data->course = $this->get_courseid();

        if ($data->qbank == MMOGAME_QBANK_MOODLEQUESTION) {
            $a = explode( ',', $data->qbankparams);
            $new = '';
            foreach ($a as $id) {
                if (intval( $id) != 0) {
                    $new .= ($new != '' ? ',' : '').$this->get_mappingid( 'question_category', $id);
                }
            }
            $data->qbankparams = $new;
        }

        // Insert the mmogame record.
        $newitemid = $DB->insert_record('mmogame', $data);

        $this->set_mapping('mmogame_qbank', $newitemid,
            $data->qbank == MMOGAME_QBANK_MOODLEQUESTION ? MMOGAME_RESTORE_QBANK_MOODLEQUESTION : 0);

        // Immediately after inserting "activity" record, call this.
        $this->apply_activity_instance($newitemid);
    }

    /**
     * Process a type restore
     * @param object $data The data in object form
     * @return void
     */
    protected function process_mmogame_type($data) {
        global $DB;
/*
        if (!$this->includesubmission) {
            return;
        }

        $data = (object)$data;
        $oldid = $data->id;

        $data->assignment = $this->get_new_parentid('assign');

        if ($data->userid > 0) {
            $data->userid = $this->get_mappingid('user', $data->userid);
        }
        if (!empty($data->groupid)) {
            $data->groupid = $this->get_mappingid('group', $data->groupid);
            if (!$data->groupid) {
                // If the group does not exist, then the submission cannot be viewed and restoring can
                // violate the unique index on the submission table.
                return;
            }
        } else {
            $data->groupid = 0;
        }

        // We will correct this in set_latest_submission_field() once all submissions are restored.
        $data->latest = 0;

        $newitemid = $DB->insert_record('assign_submission', $data);

        // Note - the old contextid is required in order to be able to restore files stored in
        // sub plugin file areas attached to the submissionid.
        $this->set_mapping('submission', $oldid, $newitemid, false, null, $this->task->get_old_contextid());
*/        
    }

    /**
     * Process a auser restore
     * @param object $data The data in object form
     * @return void
     */
    protected function process_mmogame_auser($data) {
        global $DB;

        $data = (object)$data;
        $oldid = $data->id;

        if ($data->kind == 'guid') {
            $data->instanceid = get_mapping( 'mmogame_uguid', $data->instanceid, $data->instanceid);
        }

        $data->instanceid = $this->get_mappingid('user', $data->instanceid);
        $rec = $DB->get_record_select( 'mmogame_aa_users',
            'kind=? AND instanceid=?', [$data->kind, $data->instanceid]);
        if ($rec === false) {
            $newitemid = $DB->insert_record('mmogame_aa_users', $data);
        } else {
            $newitemid = $rec->id;
        }

        $this->set_mapping('mmogame_auser', $oldid, $newitemid);
    }

    /**
     * Process an uguid restore
     * @param object $data The data in object form
     * @return void
     */
    protected function process_mmogame_uguid($data) {
        global $DB;

        $data = (object)$data;
        $oldid = $data->id;

        $rec = $DB->get_record_select( 'mmogame_aa_users_guid', 'guid=?', [$data->guid]);
        if ($rec === false) {
            $newitemid = $DB->insert_record('mmogame_aa_users_guid', $data);
        } else {
            $newitemid = $rec->id;
        }

        $this->set_mapping('mmogame_uguid', $oldid, $newitemid);
    }

    /**
     * Process an avatar restore
     * @param object $data The data in object form
     * @return void
     */
    protected function process_mmogame_avatar($data) {
        global $DB;

        $data = (object)$data;
        $oldid = $data->id;

        $rec = $DB->get_record_select( 'mmogame_aa_avatars',
            'directory=? AND filename=?', [$data->directory, $data->filename]);
        if ($rec === false) {
            $newitemid = $DB->insert_record('mmogame_aa_avatars', $data);
        } else {
            $newitemid = $rec->id;
        }

        $this->set_mapping('mmogame_avatar', $oldid, $newitemid);
    }

    /**
     * Process a palettte restore
     * @param object $data The data in object form
     * @return void
     */
    protected function process_mmogame_palette($data) {
        global $DB;

        $data = (object)$data;
        $oldid = $data->id;

        $rec = $DB->get_record_select( 'mmogame_aa_colorpalettes',
            'colorsort1=? AND colorsort2=? AND colorsort3=? AND colorsort4=? AND colorsort5=?',
            [$data->colorsort1, $data->colorsort2, $data->colorsort3, $data->colorsort4, $data->colorsort5]);
        if ($rec === false) {
            $newitemid = $DB->insert_record('mmogame_aa_colorpalettes', $data);
        } else {
            $newitemid = $rec->id;
        }

        $this->set_mapping('mmogame_palette', $oldid, $newitemid);
    }

    /**
     * Process a grade restore
     * @param object $data The data in object form
     * @return void
     */
    protected function process_mmogame_grade($data) {
        global $DB;

        $data = (object)$data;
        $oldid = $data->id;

        $data->mmogameid = $this->get_new_parentid('mmogame');
        $data->auserid = $this->get_mappingid('mmogame_auser', $data->auserid);
        $data->avatarid = $this->get_mappingid('mmogame_avatar', $data->avatarid);
        $data->colorpaletteid = $this->get_mappingid('mmogame_colorpalette', $data->colorpaletteid);

        $newitemid = $DB->insert_record('mmogame_aa_grades', $data);
        $this->set_mapping('mmogame_grade', $oldid, $newitemid);
    }

    /**
     * Process a stat restore
     * @param object $data The data in object form
     * @return void
     */
    protected function process_mmogame_stat($data) {
        global $DB;

        $data = (object)$data;

        $data->mmogameid = $this->get_new_parentid('mmogame');
        $qbank = $this->get_mappingid('mmogame_qbank', $data->mmogameid);

        if ($data->queryid != null) {
            $data->queryid = $this->get_mappingid('question', $data->queryid);
        }
        $data->auserid = $this->get_mappingid('mmogame_auser', $data->auserid);

        $newitemid = $DB->insert_record('mmogame_aa_stats', $data);
    }

    /**
     * Process a aduel_pair restore
     * @param object $data The data in object form
     * @return void
     */
    protected function process_mmogame_aduel_pair($data) {
        global $DB;

        $data = (object)$data;

        $data->mmogameid = $this->get_new_parentid('mmogame');
        $data->auserid1 = $this->get_mappingid('mmogame_auser', $data->auserid1);
        $data->auserid2 = $this->get_mappingid('mmogame_auser', $data->auserid2);

        $newitemid = $DB->insert_record('mmogame_aa_aduel_pairs', $data);
    }

    /**
     * Process a state restore
     * @param object $data The data in object form
     * @return void
     */
    protected function process_mmogame_state($data) {
        global $DB;

        $data = (object)$data;

        $data->mmogameid = $this->get_new_parentid('mmogame');

        $newitemid = $DB->insert_record('mmogame_aa_states', $data);
    }

    /**
     * Once the database tables have been fully restored, restore the files
     */
    protected function after_execute() {
        // Add mmogame related files, no need to match by itemname (just internally handled context).
        $this->add_related_files('mod_mmogame', 'intro', null);
    }

    /**
     * Not used
     *
     * @param int $newusageid
     */
    protected function inform_new_usage_id($newusageid) {

    }
}
