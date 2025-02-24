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

use coding_exception;
use context;
use core\context\module;
use core_privacy\local\request\core_userlist_provider;
use core_privacy\local\request\helper;
use core_privacy\local\request\writer;
use core_privacy\local\request\contextlist;
use core_privacy\local\request\approved_userlist;
use core_privacy\local\request\userlist;
use core_privacy\local\request\approved_contextlist;
use core_privacy\local\metadata\collection;
use core_privacy\manager;

use dml_exception;
use mod_mmogame\local\database\mmogame_database_moodle;
use mod_mmogame\local\mmogame;

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
    core_userlist_provider,

    // This plugin currently implements the original plugin_provider interface.
    \core_privacy\local\request\plugin\provider {

    /** Interface for all assign submission sub-plugins. */
    const MMOGAMETYPE_INTERFACE = 'mod_mmogame\privacy\mmogametype_provider';

    /**
     * Get the list of contexts that contain user information for the specified user.
     *
     * @param   collection  $collection  The collection to add metadata to.
     * @return  collection  The array of metadata
     */
    public static function get_metadata(collection $collection): collection {
        // The table 'mmogame' stores a record for each mmogame.
        // It does not contain user personal data, but data is returned from it for contextual requirements.

        $collection->add_database_table('mmogame', [
                'type' => 'privacy:metadata:mmogame:type',
                'model' => 'privacy:metadata:mmogame:model',
            ], 'privacy:metadata:mmogame');

        // The table 'mmogame_aa_grades' contains the current grade for each game/user combination.
        $collection->add_database_table('mmogame_aa_grades', [
                'numgame' => 'privacy:metadata:mmogame_grades:numgame',
                'avatar' => 'privacy:metadata:mmogame_grades:avatar',
                'nickname' => 'privacy:metadata:mmogame_grades:nickname',
                'color1' => 'privacy:metadata:mmogame_grades:color1',
                'color2' => 'privacy:metadata:mmogame_grades:color2',
                'color3' => 'privacy:metadata:mmogame_grades:color3',
                'color4' => 'privacy:metadata:mmogame_grades:color4',
                'color5' => 'privacy:metadata:mmogame_grades:color5',
                'usercode' => 'privacy:metadata:mmogame_grades:usercode',
                'sumscore' => 'privacy:metadata:mmogame_grades:sumscore',
                'score' => 'privacy:metadata:mmogame_grades:score',
                'sumscore2' => 'privacy:metadata:mmogame_grades:sumscore2',
                'numteam' => 'privacy:metadata:mmogame_grades:numteam',
                'timemodified' => 'privacy:metadata:mmogame_grades:timemodified',
            ], 'privacy:metadata:mmogame_aa_grades');

        return $collection;
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
            INNER JOIN {mmogame_aa_users} mu ON mu.kind=:kinduser AND mu.instanceid=:userid
            INNER JOIN {mmogame_aa_grades} mg ON mg.mmogameid = cm.instance
            WHERE mg.auserid=mu.id";
        $params = [
            'modname' => 'mmogame',
            'contextlevel' => CONTEXT_MODULE,
            'userid' => $userid,
            'kinduser' => 'moodle',
        ];

        $resultset->add_from_sql($sql, $params);

        return $resultset;
    }

    /**
     * Write out the user data filtered by contexts.
     *
     * @param approved_contextlist $contextlist The approved contexts to export information for.
     * @throws coding_exception
     * @throws dml_exception
     */
    public static function export_user_data(approved_contextlist $contextlist): void {
        global $DB;

        if (!count($contextlist)) {
            return;
        }

        $user = $contextlist->get_user();
        $rec = $DB->get_record('mmogame_aa_users', ['kind' => 'moodle', 'instanceid' => $user->id], 'id');
        if ($rec === false) {
            return;
        }
        $auserid = $rec->id;

        list($contextsql, $contextparams) = $DB->get_in_or_equal($contextlist->get_contextids(), SQL_PARAMS_NAMED);

        $sql = "SELECT DISTINCT cm.id, cm.instance, g.type, g.model
            FROM {context} c
            INNER JOIN {course_modules} cm ON cm.id = c.instanceid AND c.contextlevel = :contextlevel
            INNER JOIN {modules} m ON m.id = cm.module AND m.name = :modname
            INNER JOIN {mmogame_aa_grades} gg ON gg.mmogameid = cm.instance AND gg.auserid = :auserid
            INNER JOIN {mmogame} g ON gg.mmogameid=g.id
            WHERE c.id $contextsql";

        $params = [
            'contextlevel' => CONTEXT_MODULE,
            'modname' => 'mmogame',
            'auserid' => $auserid,
            'kind' => 'moodle',
        ];
        $params += $contextparams;

        // Fetch the individual games.
        $cms = $DB->get_recordset_sql($sql, $params);
        foreach ($cms as $cm) {
            $context = module::instance($cm->id);

            $data = helper::get_context_data($context, $contextlist->get_user());

            helper::export_context_files($context, $contextlist->get_user());

            unset($data->accessdata);
            $data->type = $cm->type;
            $data->model = $cm->model;
            writer::with_context($context)->export_data([], $data);

            $path = [get_string('privacy:metadata:mmogame:numgame', 'mod_mmogame')];
            static::export_numgames( $context, $cm->instance, $auserid, $cm->type, $cm->model, $path);
        }
    }

    /**
     * Export each numgame.
     *
     * @param module|false $context The approved contexts to export information for.
     * @param int $mmogameid
     * @param int $auserid
     * @param string $type
     * @param string $model
     * @param array $path
     * @throws dml_exception
     */
    protected static function export_numgames($context, int $mmogameid, int $auserid,
                                              string $type, string $model, array $path): void {
        global $DB;

        $sql = "SELECT gg.id, gg.numgame, gg.nickname, gg.usercode, gg.sumscore,
            gg.numteam, gg.timemodified, CONCAT( a.directory, '/', a.filename) as avatar,
            mc.color1, mc.color2, mc.color3, mc.color4, mc.color5
            FROM {mmogame_aa_grades} gg
            LEFT JOIN {mmogame_aa_avatars} a ON gg.avatarid = a.id
            LEFT JOIN {mmogame_aa_colorpalettes} mc ON gg.colorpaletteid = mc.id
            WHERE gg.mmogameid=? AND gg.auserid=?";
        $recs = $DB->get_records_sql( $sql, [$mmogameid, $auserid]);
        foreach ($recs as $rec) {
            $newpath = array_merge( $path, [$rec->numgame]);
            unset( $rec->id);
            writer::with_context($context)->export_data( $newpath, $rec);

            manager::component_class_callback('mmogametype_'.$type, self::MMOGAMETYPE_INTERFACE,
                'export_type_user_data',
                [$context, $mmogameid, $model, $auserid, $rec->numgame, $newpath]);
        }
    }

    /**
     * Delete all data for all users in the specified context.
     *
     * @param context $context The specific context to delete data for.
     * @throws coding_exception
     */
    public static function delete_data_for_all_users_in_context(context $context) {

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
        mmogame::delete_auser( new mmogame_database_moodle(), $cm->instance, null);
    }

    /**
     * Delete all user data for the specified user, in the specified contexts.
     *
     * @param approved_contextlist $contextlist The approved contexts and user information to delete information for.
     * @throws coding_exception
     * @throws dml_exception
     */
    public static function delete_data_for_user(approved_contextlist $contextlist) {
        $db = new mmogame_database_moodle();

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
            $auserid = mmogame::get_auserid_from_db( $db, 'moodle', $user->id, false);
            if ($auserid != 0) {
                $rgame = $db->get_record_select( 'mmogame', 'id=?', [$cm->instance]);
                // This will delete all attempts and mmogame grades for this mmogame.
                mmogame::delete_auser( $db, $rgame, $auserid);
            }
        }
    }

    /**
     * Get the list of users who have data within a context.
     *
     * @param   userlist    $userlist   The userlist containing the list of users who have data in this context/plugin combination.
     */
    public static function get_users_in_context(userlist $userlist) {
        $context = $userlist->get_context();

        if (!$context instanceof module) {
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
     * @param approved_userlist $userlist The approved context and user information to delete information for.
     * @throws coding_exception
     * @throws dml_exception
     */
    public static function delete_data_for_users(approved_userlist $userlist) {
        $db = new mmogame_database_moodle();

        $context = $userlist->get_context();
        if ($context->contextlevel != CONTEXT_MODULE) {
            return;
        }
        $cm = get_coursemodule_from_id('mmogame', $context->instanceid);

        $rgame = $db->get_record_select( 'mmogame', 'id=?', [$cm->instance]);

        $userids = $userlist->get_userids();
        foreach ($userids as $userid) {
            $auserid = mmogame::get_auserid_from_db( $db, 'moodle', $userid, false);
            if ($auserid != 0) {
                mmogame::delete_auser( $db, $rgame, $auserid);
            }
        }
    }
}
