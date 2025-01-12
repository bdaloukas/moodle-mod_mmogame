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
 * This file contains the class for restore of this submission plugin
 *
 * @package mmogametype_quiz
 * @copyright  2024 Vasilis Daloukas
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Restore subplugin class.
 *
 * Provides the necessary information needed to restore
 * one mmogame_type subplugin.
 *
 * @package mmogametype_quiz
 * @copyright  2024 Vasilis Daloukas
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class restore_mmogametype_quiz_subplugin extends restore_subplugin {

    /**
     * Returns array the paths to be handled by the subplugin at mmogame level
     * @return array
     */
    protected function define_type_subplugin_structure(): array {
        $paths = [];

        $elename = $this->get_namefor('type');

        $elepath = $this->get_pathfor('/mmogame_quiz_attempts');
        $paths[] = new restore_path_element($elename, $elepath);

        return $paths;
    }

    /**
     * Processes one mmogametype_quiz element
     *
     * @param mixed $data
     * @throws dml_exception
     */
    public function process_mmogametype_quiz_type($data) {
        global $DB;

        $data = (object)$data;
        $data->mmogameid = $this->get_new_parentid('mmogame');
        $data->auserid = $this->get_mappingid('mmogame_auser', $data->auserid);
        if ($data->queryid != null) {
            $data->queryid = $this->get_mappingid('question', $data->queryid);
        }
        if ($data->useranswerid != null) {
            $data->useranswerid = $this->get_mappingid('question_answer', $data->useranswerid);
        }

        $DB->insert_record('mmogame_quiz_attempts', $data);
    }
}
