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
 * This file keeps track of upgrades to the MMOGame module
 *
 * @package    mod_mmogame
 * @copyright  2024 Vasilis Daloukas
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Upgrades database
 *
 * @param int $oldversion
 * @return bool
 * @throws ddl_change_structure_exception
 * @throws ddl_exception
 * @throws ddl_field_missing_exception
 * @throws ddl_table_missing_exception
 * @throws dml_exception
 */
function xmldb_mmogame_upgrade(int $oldversion): bool {
    global $DB;

    $dbman = $DB->get_manager();

    if ($oldversion < ($ver = 2024102602)) {
        $table = new xmldb_table('mmogame_aa_grades');
        $field = new xmldb_field('history');

        if ($dbman->field_exists($table, $field)) {
            $dbman->drop_field($table, $field);
        }

        upgrade_mod_savepoint(true, $ver, 'mmogame');
    }

    if ($oldversion < ($ver = 2024102700)) {
        $table = new xmldb_table('mmogame_aa_grades');
        $field = new xmldb_field('historyscore');

        if ($dbman->field_exists($table, $field)) {
            $dbman->drop_field($table, $field);
        }

        upgrade_mod_savepoint(true, $ver, 'mmogame');
    }

    if ($oldversion < ($ver = 2024102800)) {
        // Define field numgame to be added to mmogame.
        $table = new xmldb_table('mmogame');
        $field = new xmldb_field('numgame', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED);

        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        upgrade_mod_savepoint(true, $ver, 'mmogame');
    }

    if ($oldversion < ($ver = 2024102803)) {
        // Define field type to be added to mmogame.
        $table = new xmldb_table('mmogame');
        $field = new xmldb_field('type', XMLDB_TYPE_CHAR, '30', null, XMLDB_NOTNULL);

        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        upgrade_mod_savepoint(true, $ver, 'mmogame');
    }

    if ($oldversion < ($ver = 2024102804)) {
        // Define field type to be added to mmogame.
        $table = new xmldb_table('mmogame');
        $field = new xmldb_field('model', XMLDB_TYPE_CHAR, '30', null, XMLDB_NOTNULL);

        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        upgrade_mod_savepoint(true, $ver, 'mmogame');
    }

    if ($oldversion < ($ver = 2024102805)) {
        // Define field kinduser to be added to mmogame.
        $table = new xmldb_table('mmogame');
        $field = new xmldb_field('kinduser', XMLDB_TYPE_CHAR, '10', null, XMLDB_NOTNULL);

        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        upgrade_mod_savepoint(true, $ver, 'mmogame');
    }

    if ($oldversion < ($ver = 2024102808)) {
        // Define field numattempt to be added to mmogame.
        $table = new xmldb_table('mmogame');
        $field = new xmldb_field('numattempt', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, '0');

        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        upgrade_mod_savepoint(true, $ver, 'mmogame');
    }

    if ($oldversion < ($ver = 2024102820)) {
        $table = new xmldb_table('mmogame_aa_users_code');
        $index = new xmldb_index('ginstanceidcode', XMLDB_INDEX_UNIQUE, ['ginstanceid', 'code']);

        if ($dbman->index_exists($table, $index)) {
            $dbman->drop_index($table, $index);
        }

        upgrade_mod_savepoint(true, $ver, 'mmogame');
    }

    if ($oldversion < ($ver = 2024102821)) {
        $table = new xmldb_table('mmogame_aa_users_code');
        $field = new xmldb_field('ginstanceid');

        if ($dbman->field_exists($table, $field)) {
            $dbman->drop_field($table, $field);
        }

        upgrade_mod_savepoint(true, $ver, 'mmogame');
    }

    if ($oldversion < ($ver = 2024102900)) {
        $table = new xmldb_table('mmogame_aa_grades');
        $index = new xmldb_index('ginstanceidnumgameauserid', XMLDB_INDEX_UNIQUE, ['ginstanceid', 'numgame', 'auserid']);

        if ($dbman->index_exists($table, $index)) {
            $dbman->drop_index($table, $index);
        }

        upgrade_mod_savepoint(true, $ver, 'mmogame');
    }

    if ($oldversion < ($ver = 2024102902)) {
        $table = new xmldb_table('mmogame_aa_grades');
        $index = new xmldb_index('mmogameidnumgameauserid', XMLDB_INDEX_UNIQUE, ['mmogameid', 'numgame', 'auserid']);

        if (!$DB->get_manager()->index_exists($table, $index)) {
            $DB->get_manager()->add_index($table, $index);
        }

        upgrade_mod_savepoint(true, $ver, 'mmogame');
    }

    if ($oldversion < ($ver = 2024102903)) {
        $table = new xmldb_table('mmogame_aa_grades');
        $field = new xmldb_field('ginstanceid');

        if ($dbman->field_exists($table, $field)) {
            $dbman->drop_field($table, $field);
        }

        upgrade_mod_savepoint(true, $ver, 'mmogame');
    }

    if ($oldversion < ($ver = 2024102904)) {
        $table = new xmldb_table('mmogame_aa_stats');
        $index = new xmldb_index('index_unique', XMLDB_INDEX_UNIQUE, ['ginstanceid', 'numgame', 'queryid', 'auserid', 'teamid']);

        if ($dbman->index_exists($table, $index)) {
            $dbman->drop_index($table, $index);
        }

        upgrade_mod_savepoint(true, $ver, 'mmogame');
    }

    if ($oldversion < ($ver = 2024102905)) {
        $table = new xmldb_table('mmogame_aa_stats');
        $index = new xmldb_index('index_unique', XMLDB_INDEX_UNIQUE, ['mmogameid', 'numgame', 'queryid', 'auserid', 'teamid']);

        if (!$DB->get_manager()->index_exists($table, $index)) {
            $DB->get_manager()->add_index($table, $index);
        }

        upgrade_mod_savepoint(true, $ver, 'mmogame');
    }

    if ($oldversion < ($ver = 2024102906)) {
        $table = new xmldb_table('mmogame_aa_stats');
        $field = new xmldb_field('ginstanceid');

        if ($dbman->field_exists($table, $field)) {
            $dbman->drop_field($table, $field);
        }

        upgrade_mod_savepoint(true, $ver, 'mmogame');
    }

    if ($oldversion < ($ver = 2024102907)) {
        $table = new xmldb_table('mmogame_aa_states');
        $field = new xmldb_field('ginstanceid');

        if ($dbman->field_exists($table, $field)) {
            $dbman->drop_field($table, $field);
        }

        upgrade_mod_savepoint(true, $ver, 'mmogame');
    }

    if ($oldversion < ($ver = 2024102908)) {
        $table = new xmldb_table('mmogame_am_aduel_pairs');
        $index = new xmldb_index('ginstanceidnumgameauserid1isclosed1timestart1', XMLDB_INDEX_NOTUNIQUE,
            ['ginstanceid', 'numgame', 'auserid1', 'isclosed1', 'timestart1']);

        if ($dbman->index_exists($table, $index)) {
            $dbman->drop_index($table, $index);
        }

        upgrade_mod_savepoint(true, $ver, 'mmogame');
    }

    if ($oldversion < ($ver = 2024102909)) {
        $table = new xmldb_table('mmogame_am_aduel_pairs');
        $index = new xmldb_index('mmogameidnumgameauserid1isclosed1timestart1', XMLDB_INDEX_NOTUNIQUE,
            ['mmogameid', 'numgame', 'auserid1', 'isclosed1', 'timestart1']);

        if (!$DB->get_manager()->index_exists($table, $index)) {
            $DB->get_manager()->add_index($table, $index);
        }

        upgrade_mod_savepoint(true, $ver, 'mmogame');
    }

    if ($oldversion < ($ver = 2024102910)) {
        $table = new xmldb_table('mmogame_am_aduel_pairs');
        $index = new xmldb_index('ginstanceidnumgameauserid2isclosed2timestart2', XMLDB_INDEX_NOTUNIQUE,
            ['ginstanceid', 'numgame', 'auserid2', 'isclosed2', 'timestart2']);

        if ($dbman->index_exists($table, $index)) {
            $dbman->drop_index($table, $index);
        }

        upgrade_mod_savepoint(true, $ver, 'mmogame');
    }

    if ($oldversion < ($ver = 2024102911)) {
        $table = new xmldb_table('mmogame_am_aduel_pairs');
        $index = new xmldb_index('mmogameididnumgameauserid2isclosed2timestart2', XMLDB_INDEX_NOTUNIQUE,
            ['mmogameid', 'numgame', 'auserid2', 'isclosed2', 'timestart2']);

        if (!$DB->get_manager()->index_exists($table, $index)) {
            $DB->get_manager()->add_index($table, $index);
        }

        upgrade_mod_savepoint(true, $ver, 'mmogame');
    }

    if ($oldversion < ($ver = 2024102912)) {
        // Define field enabled to be added to mmogame.
        $table = new xmldb_table('mmogame');
        $field = new xmldb_field('enabled', XMLDB_TYPE_INTEGER, '1', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, '1');

        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        upgrade_mod_savepoint(true, $ver, 'mmogame');
    }

    if ($oldversion < ($ver = 2024102913)) {
        // Define field enabled to be added to mmogame.
        $table = new xmldb_table('mmogame');
        $field = new xmldb_field('fastjson', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED);

        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        upgrade_mod_savepoint(true, $ver, 'mmogame');
    }

    if ($oldversion < ($ver = 2024102914)) {
        // Define field enabled to be added to mmogame.
        $table = new xmldb_table('mmogame');
        $field = new xmldb_field('timefastjson', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED);

        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        upgrade_mod_savepoint(true, $ver, 'mmogame');
    }

    if ($oldversion < ($ver = 2024102915)) {
        // Define field numgame to be added to mmogame.
        $table = new xmldb_table('mmogame');
        $field = new xmldb_field('striptags', XMLDB_TYPE_INTEGER, '1', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, '0');

        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        upgrade_mod_savepoint(true, $ver, 'mmogame');
    }

    if ($oldversion < ($ver = 2024102930)) {
        // Define field pin to be added to mmogame.
        $table = new xmldb_table('mmogame');
        $field = new xmldb_field('pin', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, '0');

        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        upgrade_mod_savepoint(true, $ver, 'mmogame');
    }

    if ($oldversion < ($ver = 2024102931)) {
        $table = new xmldb_table('mmogame_aa_instances');
        if ($dbman->table_exists($table)) {
            $mmogames = $DB->get_records( 'mmogame');
            foreach ($mmogames as $mmogame) {
                $recs = $DB->get_records_select( 'mmogame_aa_instances', 'mmogameid=?', [$mmogame->id], 'id', '*', 0, 1);
                foreach ($recs as $rec) {
                    $updrec = new stdClass();
                    $updrec->id = $mmogame->id;
                    $updrec->type = $rec->type;
                    $updrec->model = $rec->model;
                    $updrec->kinduser = $rec->kinduser;
                    $updrec->enabled = $rec->enabled;
                    $updrec->pin = $rec->pin;
                    $updrec->numattempt = $rec->numattempt;
                    $updrec->striptags = $rec->striptags;
                    $DB->update_record( 'mmogame', $updrec);
                }
            }
        }
        upgrade_mod_savepoint(true, $ver, 'mmogame');
    }

    if ($oldversion < ($ver = 2024102932)) {
        $table = new xmldb_table('mmogame_aa_instances');

        if ($dbman->table_exists($table)) {
            $dbman->drop_table($table);
        }
        upgrade_mod_savepoint(true, $ver, 'mmogame');
    }

    if ($oldversion < ($ver = 2024102933)) {
        $table = new xmldb_table('mmogame_am_aduel_pairs');
        $field = new xmldb_field('ginstanceid');

        if ($dbman->field_exists($table, $field)) {
            $dbman->drop_field($table, $field);
        }

        upgrade_mod_savepoint(true, $ver, 'mmogame');
    }

    if ($oldversion < ($ver = 2024103100)) {
        $table = new xmldb_table('mmogame');
        $field = new xmldb_field('typeparams');

        if ($dbman->field_exists($table, $field)) {
            $dbman->drop_field($table, $field);
        }

        upgrade_mod_savepoint(true, $ver, 'mmogame');
    }

    if ($oldversion < ($ver = 2024110905)) {
        $table = new xmldb_table('mmogame');
        $field = new xmldb_field('grade');

        if ($dbman->field_exists($table, $field)) {
            $dbman->drop_field($table, $field);
        }

        upgrade_mod_savepoint(true, $ver, 'mmogame');
    }

    if ($oldversion < ($ver = 2024110908)) {
        $table = new xmldb_table('mmogame');
        $field = new xmldb_field('decimalpoints');

        if ($dbman->field_exists($table, $field)) {
            $dbman->drop_field($table, $field);
        }

        upgrade_mod_savepoint(true, $ver, 'mmogame');
    }

    if ($oldversion < ($ver = 2024110909)) {
        $table = new xmldb_table('mmogame');
        $field = new xmldb_field('completionpass');

        if ($dbman->field_exists($table, $field)) {
            $dbman->drop_field($table, $field);
        }

        upgrade_mod_savepoint(true, $ver, 'mmogame');
    }

    if ($oldversion < ($ver = 2024110910)) {
        $table = new xmldb_table('mmogame');
        $field = new xmldb_field('casesensitive');

        if ($dbman->field_exists($table, $field)) {
            $dbman->drop_field($table, $field);
        }

        upgrade_mod_savepoint(true, $ver, 'mmogame');
    }

    if ($oldversion < ($ver = 2024110911)) {
        $table = new xmldb_table('mmogame');
        $field = new xmldb_field('modelparams');

        if ($dbman->field_exists($table, $field)) {
            $dbman->drop_field($table, $field);
        }

        upgrade_mod_savepoint(true, $ver, 'mmogame');
    }

    if ($oldversion < ($ver = 2024110912)) {
        $table = new xmldb_table('mmogame');
        $field = new xmldb_field('enablejson');

        if ($dbman->field_exists($table, $field)) {
            $dbman->drop_field($table, $field);
        }

        upgrade_mod_savepoint(true, $ver, 'mmogame');
    }

    if ($oldversion < ($ver = 2024110913)) {
        $table = new xmldb_table('mmogame');
        $field = new xmldb_field('timeopen');

        if ($dbman->field_exists($table, $field)) {
            $dbman->drop_field($table, $field);
        }

        upgrade_mod_savepoint(true, $ver, 'mmogame');
    }

    if ($oldversion < ($ver = 2024110914)) {
        $table = new xmldb_table('mmogame');
        $field = new xmldb_field('timeclose');

        if ($dbman->field_exists($table, $field)) {
            $dbman->drop_field($table, $field);
        }

        upgrade_mod_savepoint(true, $ver, 'mmogame');
    }

    if ($oldversion < ($ver = 2024110915)) {
        $table = new xmldb_table('mmogame');
        $field = new xmldb_field('timelimit');

        if ($dbman->field_exists($table, $field)) {
            $dbman->drop_field($table, $field);
        }

        upgrade_mod_savepoint(true, $ver, 'mmogame');
    }

    if ($oldversion < ($ver = 2024110916)) {
        $table = new xmldb_table('mmogame_am_aduel_pairs');
        $field = new xmldb_field('intro');

        if ($dbman->field_exists($table, $field)) {
            $dbman->drop_field($table, $field);
        }

        upgrade_mod_savepoint(true, $ver, 'mmogame');
    }

    if ($oldversion < ($ver = 2024111903)) {
        $table = new xmldb_table('mmogame_aa_stats');
        $index = new xmldb_index('index_unique', XMLDB_INDEX_UNIQUE,
            ['mmogameid', 'numgame', 'queryid', 'auserid', 'teamid']);

        if ($dbman->index_exists($table, $index)) {
            $dbman->drop_index($table, $index);
        }

        upgrade_mod_savepoint(true, $ver, 'mmogame');
    }

    if ($oldversion < ($ver = 2024111904)) {
        $table = new xmldb_table('mmogame_aa_stats');
        $field = new xmldb_field('teamid');

        if ($dbman->field_exists($table, $field)) {
            $dbman->drop_field($table, $field);
        }

        upgrade_mod_savepoint(true, $ver, 'mmogame');
    }

    if ($oldversion < ($ver = 2024111915)) {
        $table = new xmldb_table( 'mmogame_aa_stats');
        $field = new xmldb_field( 'teamid', XMLDB_TYPE_INTEGER, 10, null, null, null, '0');
        if ($dbman->field_exists($table, $field)) {
            $dbman->rename_field($table, $field, 'numteam');
        }
        upgrade_mod_savepoint(true, $ver, 'mmogame');
    }

    if ($oldversion < ($ver = 2024111916)) {
        $table = new xmldb_table('mmogame_aa_stats');
        $index = new xmldb_index('index_unique', XMLDB_INDEX_UNIQUE,
            ['mmogameid', 'numgame', 'queryid', 'auserid', 'numteam']);

        if (!$dbman->index_exists($table, $index)) {
            $dbman->add_index($table, $index);
        }
        upgrade_mod_savepoint(true, $ver, 'mmogame');
    }

    if ($oldversion < ($ver = 2025021500)) {
        $table = new xmldb_table('mmogame_aa_grades');
        $field = new xmldb_field('score');

        if ($dbman->field_exists($table, $field)) {
            $dbman->drop_field($table, $field);
        }
        upgrade_mod_savepoint(true, $ver, 'mmogame');
    }

    if ($oldversion < ($ver = 2025021501)) {
        $table = new xmldb_table('mmogame_aa_grades');
        $field = new xmldb_field('sumscore2');

        if ($dbman->field_exists($table, $field)) {
            $dbman->drop_field($table, $field);
        }
        upgrade_mod_savepoint(true, $ver, 'mmogame');
    }

    if ($oldversion < ($ver = 2025021504)) {
        // Define field numattempt to be added to mmogame.
        $table = new xmldb_table('mmogame_aa_grades');
        $field = new xmldb_field('percent', XMLDB_TYPE_FLOAT, null, null, XMLDB_NOTNULL, null, '0');

        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        upgrade_mod_savepoint(true, $ver, 'mmogame');
    }

    if ($oldversion < ($ver = 2025021507)) {
        $table = new xmldb_table('mmogame_aa_grades');
        $field = new xmldb_field('percentcompleted');

        if ($dbman->field_exists($table, $field)) {
            $dbman->drop_field($table, $field);
        }

        upgrade_mod_savepoint(true, $ver, 'mmogame');
    }

    if ($oldversion < ($ver = 2025021509)) {
        // Define field percent to be added to mmogame_am_aduel_pairs.
        $table = new xmldb_table('mmogame_am_aduel_pairs');
        $field = new xmldb_field('percent', XMLDB_TYPE_FLOAT, null, null, XMLDB_NOTNULL, null, '0');

        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        upgrade_mod_savepoint(true, $ver, 'mmogame');
    }

    if ($oldversion < ($ver = 2025021510)) {
        $table = new xmldb_table( 'mmogame_aa_stats');
        $field = new xmldb_field( 'islastcorrect', XMLDB_TYPE_INTEGER, 1, null, null, null, '0');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        upgrade_mod_savepoint(true, $ver, 'mmogame');
    }

    if ($oldversion < ($ver = 2025021511)) {
        $table = new xmldb_table('mmogame_aa_stats');
        $field = new xmldb_field('position');

        if ($dbman->field_exists($table, $field)) {
            $dbman->drop_field($table, $field);
        }

        upgrade_mod_savepoint(true, $ver, 'mmogame');
    }

    if ($oldversion < ($ver = 2025021512)) {
        $table = new xmldb_table('mmogame_aa_stats');
        $field = new xmldb_field('count1');

        if ($dbman->field_exists($table, $field)) {
            $dbman->drop_field($table, $field);
        }

        upgrade_mod_savepoint(true, $ver, 'mmogame');
    }

    if ($oldversion < ($ver = 2025021513)) {
        $table = new xmldb_table('mmogame_aa_stats');
        $field = new xmldb_field('count2');

        if ($dbman->field_exists($table, $field)) {
            $dbman->drop_field($table, $field);
        }

        upgrade_mod_savepoint(true, $ver, 'mmogame');
    }

    if ($oldversion < ($ver = 2025021514)) {
        $table = new xmldb_table('mmogame_aa_stats');
        $field = new xmldb_field('countanswers');

        if ($dbman->field_exists($table, $field)) {
            $dbman->drop_field($table, $field);
        }

        upgrade_mod_savepoint(true, $ver, 'mmogame');
    }

    if ($oldversion < ($ver = 2025021515)) {
        $table = new xmldb_table('mmogame_aa_stats');
        $field = new xmldb_field('countcompleted');

        if ($dbman->field_exists($table, $field)) {
            $dbman->drop_field($table, $field);
        }

        upgrade_mod_savepoint(true, $ver, 'mmogame');
    }

    if ($oldversion < ($ver = 2025021601)) {
        $table = new xmldb_table( 'mmogame_aa_grades');
        $field = new xmldb_field( 'countquestions', XMLDB_TYPE_INTEGER, 10, null, null, null, '0');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        upgrade_mod_savepoint(true, $ver, 'mmogame');
    }

    if ($oldversion < ($ver = 2025022400)) {
        $table = new xmldb_table('mmogame_aa_avatars');
        $field = new xmldb_field('ishidden');

        if ($dbman->field_exists($table, $field)) {
            $dbman->drop_field($table, $field);
        }

        upgrade_mod_savepoint(true, $ver, 'mmogame');
    }

    if ($oldversion < ($ver = 2025050600)) {
        $table = new xmldb_table( 'mmogame_am_aduel_pairs');
        $field = new xmldb_field('percent', XMLDB_TYPE_FLOAT, null, null, XMLDB_NOTNULL, null, '0');
        if ($dbman->field_exists($table, $field)) {
            $dbman->rename_field($table, $field, 'score');
        }
        upgrade_mod_savepoint(true, $ver, 'mmogame');
    }

    if ($oldversion < ($ver = 2025050601)) {
        // Define field numattempt to be added to mmogame.
        $table = new xmldb_table('mmogame_aa_grades');
        $field = new xmldb_field('theta', XMLDB_TYPE_FLOAT, null, null, XMLDB_NOTNULL, null, '0');

        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        upgrade_mod_savepoint(true, $ver, 'mmogame');
    }

    if ($oldversion < ($ver = 2025050608)) {
        // Define field serialcorrects to be added to mmogame_aa_grades.
        $table = new xmldb_table('mmogame_aa_stats');
        $field = new xmldb_field('serialcorrects', XMLDB_TYPE_INTEGER, 10, true, XMLDB_NOTNULL, null, '0');

        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        upgrade_mod_savepoint(true, $ver, 'mmogame');
    }

    if ($oldversion < ($ver = 2025050700)) {
        $table = new xmldb_table('mmogame_aa_irt');

        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, XMLDB_SEQUENCE);
        $table->add_field('mmogameid', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, '0');
        $table->add_field('numgame', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, '0');
        $table->add_field('queryid', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, '0');
        $table->add_field('difficulty', XMLDB_TYPE_FLOAT, null, null, XMLDB_NOTNULL, null, '0');

        $table->add_key('PRIMARY', XMLDB_KEY_PRIMARY, ['id']);
        $table->add_key('mmogameidnumgamequeryid', XMLDB_KEY_UNIQUE, ['mmogameid', 'numgame', 'queryid']);

        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }
        upgrade_mod_savepoint(true, $ver, 'mmogame');
    }

    if ($oldversion < ($ver = 2025050701)) {
        // Define field serialcorrects to be added to mmogame_aa_grades.
        $table = new xmldb_table('mmogame_aa_irt');
        $field = new xmldb_field('timemodified', XMLDB_TYPE_INTEGER, 10, true, XMLDB_NOTNULL, null, '0');

        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        upgrade_mod_savepoint(true, $ver, 'mmogame');
    }

    if ($oldversion < ($ver = 2025050702)) {
        // Define field serialcorrects to be added to mmogame_aa_grades.
        $table = new xmldb_table('mmogame_aa_grades');
        $field = new xmldb_field('countalone', XMLDB_TYPE_INTEGER, 10, true, XMLDB_NOTNULL, null, '0');

        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        upgrade_mod_savepoint(true, $ver, 'mmogame');
    }

    if ($oldversion < ($ver = 2025050703)) {
        // Define field serialcorrects to be added to mmogame_aa_grades.
        $table = new xmldb_table('mmogame_aa_states');
        $field = new xmldb_field('info', XMLDB_TYPE_TEXT);

        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        upgrade_mod_savepoint(true, $ver, 'mmogame');
    }

    if ($oldversion < ($ver = 2025050704)) {
        // Define field nextquery to be added to mmogame_aa_grades.
        $table = new xmldb_table('mmogame_aa_stats');
        $field = new xmldb_field('nextquery', XMLDB_TYPE_INTEGER, 10, true, XMLDB_NOTNULL, null, '0');

        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        upgrade_mod_savepoint(true, $ver, 'mmogame');
    }

    if ($oldversion < ($ver = 2025050705)) {
        // Define field nextquery to be added to mmogame_aa_grades.
        $table = new xmldb_table('mmogame_aa_irt');
        $field = new xmldb_field('irtrank', XMLDB_TYPE_INTEGER, 10, true, XMLDB_NOTNULL, null, '0');

        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        upgrade_mod_savepoint(true, $ver, 'mmogame');
    }

    if ($oldversion < ($ver = 2025050706)) {
        // Define field nextquery to be added to mmogame_aa_grades.
        $table = new xmldb_table('mmogame_aa_grades');
        $field = new xmldb_field('irtrank', XMLDB_TYPE_INTEGER, 10, true, XMLDB_NOTNULL, null, '0');

        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        upgrade_mod_savepoint(true, $ver, 'mmogame');
    }

    if ($oldversion < ($ver = 2025052502)) {
        // Define field nextquery to be added to mmogame_aa_grades.
        $table = new xmldb_table('mmogame_aa_grades');
        $field = new xmldb_field('thetafull', XMLDB_TYPE_FLOAT, null, null, XMLDB_NOTNULL, null, '0');

        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        upgrade_mod_savepoint(true, $ver, 'mmogame');
    }

    if ($oldversion < ($ver = 2025052503)) {
        // Define field nextquery to be added to mmogame_aa_grades.
        $table = new xmldb_table('mmogame_aa_irt');
        $field = new xmldb_field('difficultyfull', XMLDB_TYPE_FLOAT, null, null, XMLDB_NOTNULL, null, '0');

        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        upgrade_mod_savepoint(true, $ver, 'mmogame');
    }

    if ($oldversion < ($ver = 2025052505)) {
        $table = new xmldb_table('mmogame_aa_irt_log');

        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, XMLDB_SEQUENCE);
        $table->add_field('mmogameid', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, '0');
        $table->add_field('numgame', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, '0');
        $table->add_field('auserid', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, '0');
        $table->add_field('theta', XMLDB_TYPE_FLOAT, null, null, XMLDB_NOTNULL, null, '0');
        $table->add_field('queryid', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, '0');
        $table->add_field('difficulty', XMLDB_TYPE_FLOAT, null, null, XMLDB_NOTNULL, null, '0');
        $table->add_field('serialcorrects', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, '0');
        $table->add_field('numquery', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, '0');
        $table->add_field('nextquery', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, '0');
        $table->add_field('step', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, '0');
        $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, '0');
        $table->add_field('bestscore', XMLDB_TYPE_FLOAT, null, null, XMLDB_NOTNULL, null, '0');
        $table->add_field('info', XMLDB_TYPE_TEXT);
        $table->add_key('PRIMARY', XMLDB_KEY_PRIMARY, ['id']);

        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }
        upgrade_mod_savepoint(true, $ver, 'mmogame');
    }

    if ($oldversion < ($ver = 2025081901)) {
        // Define field enabled to be added to mmogame.
        $table = new xmldb_table('mmogame_aa_users');
        $field = new xmldb_field('splitnum', XMLDB_TYPE_INTEGER, '4');

        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        upgrade_mod_savepoint(true, $ver, 'mmogame');
    }

    if ($oldversion < ($ver = 2025082705)) {
        $table = new xmldb_table('mmogame_aa_irt_key');

        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, XMLDB_SEQUENCE);
        $table->add_field('mmogameid', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, '0');
        $table->add_field('numgame', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, '0');
        $table->add_field('userid', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, '0');
        $table->add_field('timecreated', XMLDB_TYPE_INTEGER, 10, null, XMLDB_NOTNULL, null, '0');
        $table->add_field("filter", XMLDB_TYPE_TEXT);
        $table->add_key('PRIMARY', XMLDB_KEY_PRIMARY, ['id']);
        $table->add_index('index_unique', XMLDB_INDEX_UNIQUE, ['mmogameid', 'numgame', 'userid']);

        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }
        upgrade_mod_savepoint(true, $ver, 'mmogame');
    }

    if ($oldversion < ($ver = 2025082802)) {
        $table = new xmldb_table('mmogame_aa_irt_ausers');

        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, XMLDB_SEQUENCE);
        $table->add_field('keyid', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, '0');
        $table->add_field('position', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, '0');
        $table->add_field('mmogameid', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, '0');
        $table->add_field('numgame', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, '0');
        $table->add_field('auserid', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, '0');
        $table->add_field('theta', XMLDB_TYPE_FLOAT);
        $table->add_field('theta_online', XMLDB_TYPE_FLOAT);
        $table->add_field('corrects', XMLDB_TYPE_INTEGER, 10);
        $table->add_field('wrongs', XMLDB_TYPE_INTEGER, 10);
        $table->add_field('nulls', XMLDB_TYPE_INTEGER, 10);
        $table->add_field('queries', XMLDB_TYPE_INTEGER, 10);
        $table->add_field('percent', XMLDB_TYPE_FLOAT);

        $table->add_key('PRIMARY', XMLDB_KEY_PRIMARY, ['id']);
        $table->add_index('keyid', XMLDB_INDEX_NOTUNIQUE, ['keyid']);

        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }
        upgrade_mod_savepoint(true, $ver, 'mmogame');
    }

    if ($oldversion < ($ver = 2025082803)) {
        $table = new xmldb_table('mmogame_aa_irt_queries');

        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, XMLDB_SEQUENCE);
        $table->add_field('keyid', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, '0');
        $table->add_field('position', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, '0');
        $table->add_field('queryid', XMLDB_TYPE_INTEGER, 10);
        $table->add_field('name', XMLDB_TYPE_CHAR, 20);
        $table->add_field('querytext', XMLDB_TYPE_TEXT);
        $table->add_field('b', XMLDB_TYPE_FLOAT);
        $table->add_field('b_online', XMLDB_TYPE_FLOAT);
        $table->add_field('seb', XMLDB_TYPE_FLOAT);
        $table->add_field('infit', XMLDB_TYPE_FLOAT);
        $table->add_field('std_infit', XMLDB_TYPE_FLOAT);
        $table->add_field('outfit', XMLDB_TYPE_FLOAT);
        $table->add_field('std_outfit', XMLDB_TYPE_FLOAT);
        $table->add_field('corrects', XMLDB_TYPE_INTEGER, 10);
        $table->add_field('wrongs', XMLDB_TYPE_INTEGER, 10);
        $table->add_field('nulls', XMLDB_TYPE_INTEGER, 10);
        $table->add_field('percent', XMLDB_TYPE_FLOAT, 10);

        $table->add_key('PRIMARY', XMLDB_KEY_PRIMARY, ['id']);
        $table->add_index('keyid', XMLDB_INDEX_NOTUNIQUE, ['keyid']);

        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }
        upgrade_mod_savepoint(true, $ver, 'mmogame');
    }

    return true;
}
