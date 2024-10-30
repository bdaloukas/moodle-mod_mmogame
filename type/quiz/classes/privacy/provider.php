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

class provider implements
    // This plugin does store personal user data.
    \core_privacy\local\metadata\provider,

    // This plugin is a subplugin of mmogame and must meet that contract.
    \mod_mmogame\privacy\mmogame_provider {
   /**
     * Return meta data about this plugin.
     *
     * @param  collection $collection A list of information to add to.
     * @return collection Return the collection after adding to it.
     */
    public static function get_metadata(collection $collection): collection {
        //$collection->link_subsystem('core_comment', 'privacy:metadata:commentpurpose');
        return $collection;
    }

    /**
     * It is possible to make a comment as a teacher without creating an entry in the submission table, so this is required
     * to find those entries.
     *
     * @param  int $userid The user ID that we are finding contexts for.
     * @param  contextlist $contextlist A context list to add sql and params to for contexts.
     */
    public static function get_context_for_userid_within_submission(int $userid, contextlist $contextlist) {

    }

    /**
     * Due to the fact that we can't rely on the queries in the mod_mmogame provider we have to add some additional sql.
     *
     * @param  \mod_mmogame\privacy\useridlist $useridlist An object for obtaining user IDs of students.
     */
    public static function get_student_user_ids(\mod_mmogame\privacy\useridlist $useridlist) {

    }

    /**
     * If you have tables that contain userids and you can generate entries in your tables without creating an
     * entry in the mmogame_aa_grades table then please fill in this method.
     *
     * @param  \core_privacy\local\request\userlist $userlist The userlist object
     */
    public static function get_userids_from_context(\core_privacy\local\request\userlist $userlist) {

    }

    /**
     * Export all user data for this plugin.
     *
     * @param  mmogame_plugin_request_data $exportdata Data used to determine which context and user to export and other useful
     * information to help with exporting.
     */
    public static function export_submission_user_data(mmogame_plugin_request_data $exportdata) {

    }

    /**
     * Delete all the comments made for this context.
     *
     * @param  mmogame_plugin_request_data $requestdata Data to fulfill the deletion request.
     */
    public static function delete_submission_for_context(mmogame_plugin_request_data $requestdata) {

    }

    /**
     * A call to this method should delete user data (where practical) using the userid and submission.
     *
     * @param  mmogame_plugin_request_data $exportdata Details about the user and context to focus the deletion.
     */
    public static function delete_submission_for_userid(mmogame_plugin_request_data $exportdata) {
        // Create an approved context list to delete the comments.

    }

    /**
     * Deletes all submissions for the submission ids / userids provided in a context.
     * mmogame_plugin_request_data contains:
     * - context
     * - mmogame object
     * - submission ids (pluginids)
     * - user ids
     * @param  mmogame_plugin_request_data $deletedata A class that contains the relevant information required for deletion.
     */
    public static function delete_submissions(mmogame_plugin_request_data $deletedata) {

    }
}
