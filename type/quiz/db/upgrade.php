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
//
// Sometimes, changes between versions involve
// alterations to database structures and other
// major things that may break installations.
//
// The upgrade function in this file will attempt
// to perform all the necessary actions to upgrade
// your older installation to the current version.
//
// If there's something it cannot do itself, it
// will tell you what you need to do.
//
// The commands in here will all be database-neutral,
// using the methods of database_manager class
//
// Please do not forget to use upgrade_set_timeout()
// before any action that may take longer time to finish.

/**
 * This file keeps track of upgrades to the game module
 *
 * @package    mmogametype_quiz
 * @copyright  2024 Vasilis Daloukas
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Upgrades database
 *
 * @param int $oldversion
 */
function xmldb_mmogametype_quiz_upgrade( $oldversion) {

    global $CFG, $DB;

    $dbman = $DB->get_manager();

    if ($oldversion < ($ver = 2024103001)) {
        $table = new xmldb_table('mmogame_quiz_attempts');
        $index = new xmldb_index('ginstanceidnumattempt', XMLDB_INDEX_NOTUNIQUE, ['ginstanceid', 'numattempt']);

        if ($dbman->index_exists($table, $index)) {
            $dbman->drop_index($table, $index);
        }

         upgrade_plugin_savepoint(true, $ver, 'mmogametype', 'quiz');
    }

    if ($oldversion < ($ver = 2024103002)) {
        $table = new xmldb_table('mmogame_quiz_attempts');
        $index = new xmldb_index('mmogameidnumattempt', XMLDB_INDEX_NOTUNIQUE, ['mmogameid', 'numattempt']);

        if (!$DB->get_manager()->index_exists($table, $index)) {
            $DB->get_manager()->add_index($table, $index);
        }

         upgrade_plugin_savepoint(true, $ver, 'mmogametype', 'quiz');
    }

    if ($oldversion < ($ver = 2024103003)) {
        $table = new xmldb_table('mmogame_quiz_attempts');
        $field = new xmldb_field('ginstanceid');

        if ($dbman->field_exists($table, $field)) {
            $dbman->drop_field($table, $field);
        }

         upgrade_plugin_savepoint(true, $ver, 'mmogametype', 'quiz');
    }

    return true;
}
