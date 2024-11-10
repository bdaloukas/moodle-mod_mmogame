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
 * Structure step to restore one choice activity
 */
class restore_mmogame_activity_structure_step extends restore_activity_structure_step {

    protected function define_structure() {

        $paths = array();
        $userinfo = $this->get_setting_value('userinfo');
        $paths[] = new restore_path_element('mmogame_state', '/activity/mmogame/states/state');

        $paths[] = new restore_path_element('mmogame', '/activity/mmogame');
        $paths[] = new restore_path_element('mmogame_auser', '/activity/mmogame/auser/');
        if ($userinfo) {
            $paths[] = new restore_path_element('mmogame_avatar', '/activity/mmogame/avatars/avatar');
            $paths[] = new restore_path_element('mmogame_palette', '/activity/mmogame/palettes/palette');
            $paths[] = new restore_path_element('mmogame_grade', '/activity/mmogame/grades/grade');
            $paths[] = new restore_path_element('mmogame_stat', '/activity/mmogame/stats/stat');
            $paths[] = new restore_path_element('mmogame_pair', '/activity/mmogame/pairs/pair');
        }

        // Return the paths wrapped into standard activity structure
        return $this->prepare_activity_structure($paths);
    }

    protected function process_mmogame($data) {
        global $DB;

        $data = (object)$data;
        $oldid = $data->id;
        $data->course = $this->get_courseid();

        //$data->timeopen = $this->apply_date_offset($data->timeopen);
        //$data->timeclose = $this->apply_date_offset($data->timeclose);

        // insert the choice record
        $newitemid = $DB->insert_record('mmogame', $data);
        // immediately after inserting "activity" record, call this
        $this->apply_activity_instance($newitemid);
    }

    protected function process_mmogame_auser($data) {
        global $DB;

        $data = (object)$data;
        $oldid = $data->id;

        $data->instanceid = $this->get_mappingid('user', $data->instanceid);
        $rec = $DB->get_record_select( 'mmogame_aa_users',
            'kind=? AND instanceid=?', [$data->kind, $data->instanceid]);
        if( $rec === false) {
            $newitemid = $DB->insert_record('mmogame_aa_users', $data);
        } else {
            $newitemid = $rec->id;
        }

        $this->set_mapping('mmogame_aa_users', $oldid, $newitemid);
    }

    protected function process_mmogame_avatar($data) {
        global $DB;

        $data = (object)$data;
        $oldid = $data->id;

        $rec = $DB->get_record_select( 'mmogame_aa_avatars',
            'id=?', [$data->kind, $data->id]);
        if( $rec === false) {
            $newitemid = $DB->insert_record('mmogame_aa_avatars', $data);
        } else {
            $newitemid = $rec->id;
        }

        $this->set_mapping('mmogame_aa_avatars', $oldid, $newitemid);
    }
    
    protected function process_mmogame_grade($data) {
        global $DB;

        $data = (object)$data;
        $oldid = $data->id;

        $data->mmogameid = $this->get_new_parentid('mmogame');
        $data->auserid = $this->get_mappingid('auser', $data->auserid);
        //$data->avatarid
        //$data->colorpaletteid

        $newitemid = $DB->insert_record('mmogame_aa_grades', $data);
        $this->set_mapping('mmogame_grade', $oldid, $newitemid);
    }

    protected function process_mmogame_stat($data) {
        global $DB;

        $data = (object)$data;

        $data->mmogameid = $this->get_new_parentid('mmogame');
        //$data->queryid
        //$data->auserid
        //$data->teamid

        $newitemid = $DB->insert_record('mmogame_aa_stats', $data);
    }

    protected function process_mmogame_aduel_pair($data) {
        global $DB;

        $data = (object)$data;

        $data->mmogameid = $this->get_new_parentid('mmogame');
        //$data->auserid1
        //$data->auserid2

        $newitemid = $DB->insert_record('mmogame_aa_aduel_pairs', $data);
    }

    protected function process_mmogame_state($data) {
        global $DB;

        $data = (object)$data;

        $data->mmogameid = $this->get_new_parentid('mmogame');

        $newitemid = $DB->insert_record('mmogame_aa_states', $data);
    }

    protected function after_execute() {
        // Add choice related files, no need to match by itemname (just internally handled context)
        $this->add_related_files('mod_mmogame', 'intro', null);
    }
}