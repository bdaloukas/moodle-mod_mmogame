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
 * Privacy Subsystem implementation for mod_game.
 *
 * @package    mod_mmogame
 * @copyright  2024 Vasilis Daloukas
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_mmogame\privacy;

use core_privacy\local\request\writer;
use core_privacy\local\request\transform;
use core_privacy\local\request\contextlist;
use core_privacy\local\request\approved_userlist;
use core_privacy\local\request\userlist;
use core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\deletion_criteria;
use core_privacy\local\metadata\collection;
use core_privacy\manager;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/mod/mmogame/lib.php');
require_once($CFG->dirroot . '/mod/mmogame/locallib.php');

/**
 * Privacy Subsystem implementation for mod_mmogame.
 *
 * @package    mod_mmogame
 * @copyright  2024 Vasilis Daloukas
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class provider implements
    // This plugin has data.
    \core_privacy\local\metadata\provider,
    \core_privacy\local\request\core_userlist_provider,

    // This plugin currently implements the original plugin_provider interface.
    \core_privacy\local\request\plugin\provider {

    /**
     * Get the list of contexts that contain user information for the specified user.
     *
     * @param   collection  $items  The collection to add metadata to.
     * @return  collection  The array of metadata
     */
    public static function get_metadata(collection $items): collection {
        // The table 'mmogame' stores a record for each mmogame.
        // It does not contain user personal data, but data is returned from it for contextual requirements.

        // The table 'mmogame_aa_grades' contains the current grade for each game/user combination.
        $items->add_database_table('mmogame_aa_grades', [
                'mmogame' => 'privacy:metadata:mmogame_grades:mmogame',
                'ginstance' => 'privacy:metadata:mmogame_grades:ginstance',
                'numgame' => 'privacy:metadata:mmogame_grades:numgame',
                'auserid' => 'privacy:metadata:mmogame_grades:auserid',
                'avatar' => 'privacy:metadata:mmogame_grades:avatar',
                'nickname' => 'privacy:metadata:mmogame_grades:nickaname',
                'colorpalette' => 'privacy:metadata:mmogame_grades:colorpalette',
                'usercode' => 'privacy:metadata:mmogame_grades:usercode',
                'sumscore' => 'privacy:metadata:game_grades:sumscore',
                'score' => 'privacy:metadata:game_grades:score',
                'sumscore2' => 'privacy:metadata:game_grades:sumscore2',
                'numteam' => 'privacy:metadata:game_grades:numteam',
                'timemodified' => 'privacy:metadata:game_grades:timemodified',
            ], 'privacy:metadata:mmogame_aa_grades');
        );

        return $items;
    }

    /**
     * Get the list of contexts where the specified user has attempted a mmogame, or been involved with manual marking
     * and/or grading of a mmogame.
     *
     * @param   int             $userid The user to search.
     * @return  contextlist     $contextlist The contextlist containing the list of contexts used in this plugin.
     */
    public static function get_contexts_for_userid(int $userid): contextlist {

        $resultset = new contextlist();

        return $resultset;
    }

    /**
     * Write out the user data filtered by contexts.
     *
     * @param   approved_contextlist    $contextlist    The approved contexts to export information for.
     */
    public static function export_user_data(approved_contextlist $contextlist) {
        global $CFG, $DB;

        if (!count($contextlist)) {
            return;
        }
    }

    /**
     * Delete all data for all users in the specified context.
     *
     * @param   context                 $context   The specific context to delete data for.
     */
    public static function delete_data_for_all_users_in_context(\context $context) {

        if ($context->contextlevel != CONTEXT_MODULE) {
            // Only mmogame module will be handled.
            return;
        }

        $cm = get_coursemodule_from_id('mmogame', $context->instanceid);
        if (!$cm) {
            // Only mmogame module will be handled.
            return;
        }

        // This will delete all attempts and mmogame grades for this game.
        mmogame_delete_instance( $cm->instance);
    }

    /**
     * Delete all user data for the specified user, in the specified contexts.
     *
     * @param   approved_contextlist    $contextlist    The approved contexts and user information to delete information for.
     */
    public static function delete_data_for_user(approved_contextlist $contextlist) {
        global $DB;
        foreach ($contextlist as $context) {
            if ($context->contextlevel != CONTEXT_MODULE) {
                // Only mmogame module will be handled.
                continue;
            }

            $cm = get_coursemodule_from_id('mmogame', $context->instanceid);
            if (!$cm) {
                // Only mmogame module will be handled.
                continue;
            }

            // Fetch the details of the data to be removed.
            $user = $contextlist->get_user();

            // This will delete all attempts and mmogame grades for this mmogame.
            mmogame_delete_user_attempts( $cm->instance, $user);
        }
    }

    /**
     * Store all mmogame attempts for the contextlist.
     *
     * @param   approved_contextlist    $contextlist
     */
    protected static function export_mmogame_attempts(approved_contextlist $contextlist) {

    }

    /**
     * Get the list of users who have data within a context.
     *
     * @param   userlist    $userlist   The userlist containing the list of users who have data in this context/plugin combination.
     */
    public static function get_users_in_context(userlist $userlist) {
        $context = $userlist->get_context();

        if (!$context instanceof \context_module) {
            return;
        }
    }


    /**
     * Delete multiple users within a single context.
     *
     * @param   approved_userlist    $userlist The approved context and user information to delete information for.
     */
    public static function delete_data_for_users(approved_userlist $userlist) {

    }
}
