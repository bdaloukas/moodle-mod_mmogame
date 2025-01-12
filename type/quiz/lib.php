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
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Library of functions and constants for submodule Quiz
 *
 * @package    mmogametype_quiz
 * @copyright  2024 Vasilis Daloukas
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Given an ID of an instance of this module, this function will permanently delete the instance and any data that depends on it.
 *
 * @param int $mmogameid (id of the module instance)
 * @throws dml_exception
 */
function mmogametype_quiz_delete_instance(int $mmogameid): void {
    global $DB;

    $a = ['mmogame_quiz_attempts'];
    foreach ($a as $table) {
        $DB->delete_records_select( $table, 'mmogameid=?', [$mmogameid]);
    }
}

/**
 * Actual implementation of the reset course functionality, delete all the game responses for course $data->courseid.
 *
 * @param object $data (the data submitted from the reset course).
 * @param string $ids (id of all mmogame that have to be deleted).
 * @throws coding_exception
 * @throws dml_exception
 */
function mmogametype_quiz_reset_userdata(object $data, string $ids): void {
    global $DB;

    $a = ['mmogame_quiz_attempts', 'mmogame_aa_grades'];

    [$insql, $inparams] = $DB->get_in_or_equal( explode(',', $ids));

    if (!empty($data->reset_mmogame_all)) {
        foreach ($a as $table) {
            $DB->delete_records_select($table, "mmogameid $insql", $inparams);
        }
    }

    if (!empty($data->reset_mmogame_deleted_course)) {
        foreach ($a as $table) {
            $sql = "DELETE FROM {".$table."} t WHERE NOT EXISTS ( SELECT * FROM {mmogame} g WHERE t.mmogameid=g.id)";
            $DB->execute($sql);
        }
    }
}

/**
 * Return the models that this sub-plugin implements.
 *
 * @return array (model => name)
 * @throws coding_exception
 */
function mmogametype_quiz_get_models(): array {
    return [
        'alone' => get_string( 'model_alone', 'mmogametype_quiz'),
        'aduel' => get_string( 'model_aduel', 'mmogametype_quiz'),
    ];
}
