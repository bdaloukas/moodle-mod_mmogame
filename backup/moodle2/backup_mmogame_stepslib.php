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
 * Define the complete choice structure for backup, with file and id annotations
 *
 * @package mod_mmogame
 * @copyright  2024 Vasilis Daloukas
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class backup_mmogame_activity_structure_step extends backup_activity_structure_step {
    /**
     * Define the structure for the assign activity
     * @return backup_nested_element
     */
    protected function define_structure() {
        global $DB;

        // To know if we are including userinfo.
        $userinfo = $this->get_setting_value('userinfo');

        // Define each element separated.
        $mmogame = new backup_nested_element('mmogame', ['id'],
            ['name', 'intro', 'introformat', 'language', 'qbank', 'qbankparams', 'modelparams', 'numgame',
            'type', 'model', 'kinduser', 'pin', 'enabled', 'numattempt', 'fastjson', 'timefastjson', 'striptags', ]);

        $grades = new backup_nested_element('grades');

        $grade = new backup_nested_element('grade', ['id'],
            ['numgame', 'auserid', 'timemodified', 'avatarid', 'usercode', 'nickname', 'colorpaletteid',
            'sumscore', 'countscore', 'score', 'sumscore2', 'numteam', 'percentcompleted', ]);

        $stats = new backup_nested_element('stats');

        $stat = new backup_nested_element('stat', ['id'],
            ['numgame', 'queryid', 'auserid', 'teamid', 'counterror', 'timeerror', 'countcorrect',
            'countused', 'position', 'count1', 'count2', 'percent', 'countanswers', 'countcompleted', ]);

        $states = new backup_nested_element('states');
        $state = new backup_nested_element('state', ['id'],
            ['numgame', 'state', 'param1', 'remark', 'param2']);

        $pairs = new backup_nested_element('aduel_pairs');
        $pair = new backup_nested_element('aduel_pair', ['id'],
            ['numgame', 'auserid1', 'auserid2', 'timestart1', 'timestart2', 'timeclose', 'timelimit', 'isclosed1',
            'isclosed2', 'tool1numattempt1', 'tool1numattempt2', 'tool2numattempt1', 'tool2numattempt2',
            'tool3numattempt1', 'tool3numattempt2', ]);

        $types = new backup_nested_element( 'types');
        $type = new backup_nested_element( 'type');

        // Build the tree.
        $mmogame->add_child($grades);
        $grades->add_child($grade);
        $mmogame->add_child($stats);
        $stats->add_child($stat);
        $mmogame->add_child($states);
        $states->add_child($state);
        $mmogame->add_child($pairs);
        $pairs->add_child($pair);
        $mmogame->add_child($types);
        $types->add_child($type);

        // Define sources.
        $mmogame->set_source_table('mmogame', ['id' => backup::VAR_ACTIVITYID]);

        // All the rest of elements only happen if we are including user info.
        if ($userinfo) {
            $params = ['mmogameid' => backup::VAR_ACTIVITYID];
            $grade->set_source_table('mmogame_aa_grades', $params);
            $stat->set_source_table('mmogame_aa_stats', $params);
            $state->set_source_table('mmogame_aa_states', $params);
            $pair->set_source_table('mmogame_am_aduel_pairs', $params);
        }

        // Define id annotations.

        // Define file annotations.

        // Call sub-plugins.
        $this->add_subplugin_structure('mmogametype', $type, true);

        return $this->prepare_activity_structure($mmogame);
    }
}
