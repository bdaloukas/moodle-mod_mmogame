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
 * Privacy Subsystem implementation for mod_mmogametype_quiz.
 *
 * @package    mmogametype_quiz
 * @copyright  2024 Vasilis Daloukas
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_mmogametype_quiz\privacy;
use mod_mmogame\privacy\mmogame_plugin_request_data;
use core_privacy\local\metadata\collection;

class provider implements
    // This plugin does store personal user data.
    \core_privacy\local\metadata\provider,

    // This plugin is a subplugin of mmogame and must meet that contract.
    \mod_mmogame\privacy\mmogametype_provider {
    /**
     * Return meta data about this plugin.
     *
     * @param  collection $collection A list of information to add to.
     * @return collection Return the collection after adding to it.
     */
    public static function get_metadata(collection $collection): collection {

        $items->add_database_table('mmogame_quiz_attempts', [
                'mmogame' => 'privacy:metadata:mmogame_grades:mmogame',
                'numgame' => 'privacy:metadata:mmogame_grades:numgame',
                'numteam' => 'privacy:metadata:mmogame_grades:numteam',
                'numattempt' => 'privacy:metadata:mmogame_grades:numattempt',
                'queryid' => 'privacy:metadata:mmogame_grades:queryid',
                'useranswerid' => 'privacy:metadata:mmogame_grades:useranswerid',
                'useranswer' => 'privacy:metadata:mmogame_grades:useranswer',
                'iscorrect' => 'privacy:metadata:mmogame_grades:iscorrect',
                'layout' => 'privacy:metadata:mmogame_grades:layout',
                'timestart' => 'privacy:metadata:mmogame_grades:timestart',
                'timeclose' => 'privacy:metadata:mmogame_grades:timeclose',
                'timeanswer' => 'privacy:metadata:mmogame_grades:timeanswer',
                'fraction' => 'privacy:metadata:mmogame_grades:fraction',
                'score' => 'privacy:metadata:mmogame_grades:score',
                'score2' => 'privacy:metadata:mmogame_grades:score2',
                'iscorrect2' => 'privacy:metadata:mmogame_grades:iscorrect2',
            ], 'privacy:metadata:mmogame_aa_grades');

        return $collection;
    }

    /**
     * Export all user data for this plugin.
     *
     * @param  mmogame_plugin_request_data $exportdata Data used to determine which context and user to export and other useful
     * information to help with exporting.
     */
    public static function export_type_user_data(mmogame_plugin_request_data $exportdata) {

    }

    /**
     * Delete all the comments made for this context.
     *
     * @param  mmogame_plugin_request_data $requestdata Data to fulfill the deletion request.
     */
    public static function delete_type_for_context(mmogame_plugin_request_data $requestdata) {

    }

    /**
     * A call to this method should delete user data (where practical) using the userid and submission.
     *
     * @param  mmogame_plugin_request_data $exportdata Details about the user and context to focus the deletion.
     */
    public static function delete_type_for_userid(mmogame_plugin_request_data $exportdata) {
        // Create an approved context list to delete the comments.

    }
}
