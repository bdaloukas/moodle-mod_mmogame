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
 * Structure step to restore one mmogame activity
 *
 * @package    mod_mmogame
 * @copyright  2024 Vasilis Daloukas
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class restore_mmogame_activity_structure_step extends restore_activity_structure_step {
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

        // Insert the mmogame record.
        $newitemid = $DB->insert_record('mmogame', $data);

        // Immediately after inserting "activity" record, call this.
        $this->apply_activity_instance($newitemid);
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

        $rec = $DB->get_record_select( 'mmogame_aa_palettes',
            'color1=? AND color2=? AND color3=? AND color4=? AND color5=?',
            [$data->color1, $data->color2, $data->color3, $data->color4, $data->color5]);
        if ($rec === false) {
            $newitemid = $DB->insert_record('mmogame_aa_palettes', $data);
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
        //$data->queryid
        $data->auserid = $this->get_mappingid('mmogame_auser', $data->auserid);
        //$data->teamid

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
}
