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

namespace mmogametype_quiz\privacy;
use mod_mmogame\privacy\mmogame_plugin_request_data;
use core_privacy\local\metadata\collection;
use core_privacy\local\request\writer;
use core_privacy\local\request\approved_userlist;
use core_privacy\local\request\userlist;

/**
 * Privacy class for requesting user data.
 *
 * @package    mmogametype_quiz
 * @copyright  2024 Vasilis Daloukas
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class provider implements
    // This plugin does store personal user data.
    \core_privacy\local\metadata\provider,

    // This plugin is a subplugin of mmogame and must meet that contract.
    \mod_mmogame\privacy\mmogametype_provider,
    \core_privacy\local\request\core_userlist_provider {
    /**
     * Return meta data about this plugin.
     *
     * @param  collection $collection A list of information to add to.
     * @return collection Return the collection after adding to it.
     */
    public static function get_metadata(collection $collection): collection {

        $collection->add_database_table('mmogame_quiz_attempts', [
                'numteam' => 'privacy:metadata:mmogame_quiz_attempt:numteam',
                'numattempt' => 'privacy:metadata:mmogame_quiz_attempt:numattempt',
                'queryid' => 'privacy:metadata:mmogame_quiz_attempt:queryid',
                'useranswerid' => 'privacy:metadata:mmogame_quiz_attempt:useranswerid',
                'useranswer' => 'privacy:metadata:mmogame_quiz_attempt:useranswer',
                'iscorrect' => 'privacy:metadata:mmogame_quiz_attempt:iscorrect',
                'layout' => 'privacy:metadata:mmogame_quiz_attempt:layout',
                'timestart' => 'privacy:metadata:mmogame_quiz_attempt:timestart',
                'timeclose' => 'privacy:metadata:mmogame_quiz_attempt:timeclose',
                'timeanswer' => 'privacy:metadata:mmogame_quiz_attempt:timeanswer',
                'fraction' => 'privacy:metadata:mmogame_quiz_attempt:fraction',
                'score' => 'privacy:metadata:mmogame_quiz_attempt:score',
                'score2' => 'privacy:metadata:mmogame_quiz_attempt:score2',
                'iscorrect2' => 'privacy:metadata:mmogame_quiz_attempt:iscorrect2',
            ], 'privacy:metadata:mmogame_quiz_attempts');

        return $collection;
    }

    /**
     * Get the list of users who have data within a context.
     *
     * @param   userlist    $userlist   The userlist containing the list of users who have data in this context/plugin combination.
     */
    public static function get_users_in_context(userlist $userlist) {
    }


    /**
     * Export all user data for this plugin.
     *
     * @param object $context
     * @param int $mmogameid
     * @param string $model
     * @param int $auserid
     * @param int $numgame
     * @param array $path
     * information to help with exporting.
     */
    public static function export_type_user_data(\context $context, int $mmogameid, string $model, int $auserid, int $numgame, array $path) {
        global $DB;

        $recs = $DB->get_records_select( 'mmogame_quiz_attempts',
            'mmogameid=? AND auserid=? AND numgame=?', [$mmogameid, $auserid, $numgame], 'id',
            'id,numattempt,queryid,useranswerid,useranswer,iscorrect,layout,timestart,timeclose,timeanswer,'.
            'fraction,score, score2,iscorrect2');
        $i = 0;
        foreach ($recs as $rec) {
            $newpath = array_merge( $path,
                [get_string('privacy:metadata:mmogame_quiz_attempts', 'mmogametype_quiz').(++$i)]);
            unset( $rec->id);
            writer::with_context($context)->export_data( $newpath, $rec);
        }
    }

    /**
     * Delete multiple users within a single context.
     *
     * @param   approved_userlist    $userlist The approved context and user information to delete information for.
     */
    public static function delete_data_for_users(approved_userlist $userlist) {
        // There is nothing to delete.
    }
}
