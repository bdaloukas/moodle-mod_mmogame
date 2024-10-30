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
                'numgame' => 'privacy:metadata:mmogame_grades:numgame',
                'avatar' => 'privacy:metadata:mmogame_grades:avatar',
                'nickname' => 'privacy:metadata:mmogame_grades:nickaname',
                'colorpalette' => 'privacy:metadata:mmogame_grades:colorpalette',
                'usercode' => 'privacy:metadata:mmogame_grades:usercode',
                'sumscore' => 'privacy:metadata:mmogame_grades:sumscore',
                'score' => 'privacy:metadata:mmogame_grades:score',
                'sumscore2' => 'privacy:metadata:mmogame_grades:sumscore2',
                'numteam' => 'privacy:metadata:mmogame_grades:numteam',
                'timemodified' => 'privacy:metadata:mmogame_grades:timemodified',
            ], 'privacy:metadata:mmogame_aa_grades');

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

        $sql = "SELECT DISTINCT c.id
                FROM {context} c
            INNER JOIN {course_modules} cm ON cm.id = c.instanceid AND c.contextlevel = :contextlevel
            INNER JOIN {modules} m ON m.id = cm.module AND m.name = :modname
            INNER JOIN {mmogame} game ON game.id = cm.instance
            INNER JOIN {mmogame_aa_grades} mg ON mg.mmogameid = game.id
            INNER JOIN {mmogame_aa_users} mu ON mu.kind=:kinduser AND mu.instance=:userid
            WHERE mg.userid=mu.id";
        $params = [
            'modname'           => 'mmogame',
            'contextlevel'      => CONTEXT_MODULE,
            'userid'  => $userid,
        ];

        $contextlist->add_from_sql($sql, $params);

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

        $user = $contextlist->get_user();
        $userid = $user->id;
        list($contextsql, $contextparams) = $DB->get_in_or_equal($contextlist->get_contextids(), SQL_PARAMS_NAMED);

        $sql = "SELECT
                    g.*,
                    gg.id AS hasgrade,
                    gg.score AS bestscore,
                    gg.timemodified AS grademodified,
                    c.id AS contextid,
                    cm.id AS cmid
                  FROM {context} c
            INNER JOIN {course_modules} cm ON cm.id = c.instanceid AND c.contextlevel = :contextlevel
            INNER JOIN {modules} m ON m.id = cm.module AND m.name = :modname
            INNER JOIN {mmogame} g ON g.id = cm.instance
             LEFT JOIN {mmogame_aa_grades} gg ON gg.mmogameid = g.id AND gg.userid = :userid
                 WHERE c.id {$contextsql}";

        $params = [
            'contextlevel' => CONTEXT_MODULE,
            'modname' => 'mmogame',
            'userid' => $userid,
        ];
        $params += $contextparams;

        // Fetch the individual games.
        $games = $DB->get_recordset_sql($sql, $params);
        foreach ($games as $game) {
            list($course, $cm) = get_course_and_cm_from_cmid($game->cmid, 'game');
            $context = mmogame_get_context_module_instance( $cm->id);

            $gamedata = \core_privacy\local\request\helper::get_context_data($context, $contextlist->get_user());

            \core_privacy\local\request\helper::export_context_files($context, $contextlist->get_user());

            $gamedata->accessdata = (object) [];

            if (empty((array) $gamedata->accessdata)) {
                unset($gamedata->accessdata);
            }

            writer::with_context($context)->export_data([], $gamedata);
        }
        $games->close();
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
     * Get the list of users who have data within a context.
     *
     * @param   userlist    $userlist   The userlist containing the list of users who have data in this context/plugin combination.
     */
    public static function get_users_in_context(userlist $userlist) {
        $context = $userlist->get_context();

        if (!$context instanceof \context_module) {
            return;
        }

        $params = [
            'contextid' => $context->id,
            'contextlevel' => CONTEXT_MODULE,
            'modname' => 'mmogame',
        ];

        // Find users with mmogame grades.
        $sql = "SELECT au.instance as userid
                    FROM {context} c
                    JOIN {course_modules} cm ON cm.id = c.instanceid AND c.contextlevel = :contextlevel
                    JOIN {modules} m ON m.id = cm.module AND m.name = :modname
                    JOIN {mmogame} game ON game.id = cm.instance
                    JOIN {mmogame_aa_grades} ga ON ga.mmogameid = game.id
                    JOIN {mmogame_aa_users} au ON au.kind='moodle' AND au.id=ga.auserid
                WHERE c.id = :contextid";

        $userlist->add_from_sql('userid', $sql, $params);
    }


    /**
     * Delete multiple users within a single context.
     *
     * @param   approved_userlist    $userlist The approved context and user information to delete information for.
     */
    public static function delete_data_for_users(approved_userlist $userlist) {

    }
}
