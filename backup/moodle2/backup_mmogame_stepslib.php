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
class backup_mmogame_activity_structure_step extends backup_questions_activity_structure_step {
    /**
     * Define the structure for the assign activity
     * @return backup_nested_element
     */
    protected function define_structure() {
        global $CFG;

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
            ['numgame', 'queryid', 'auserid', 'numteam', 'counterror', 'timeerror', 'countcorrect',
            'countused', 'position', 'count1', 'count2', 'percent', 'countanswers', 'countcompleted', ]);

        $states = new backup_nested_element('states');
        $state = new backup_nested_element('state', ['id'],
            ['numgame', 'state', 'param1']);

        $pairs = new backup_nested_element('aduel_pairs');
        $pair = new backup_nested_element('aduel_pair', ['id'],
            ['numgame', 'auserid1', 'auserid2', 'timestart1', 'timestart2', 'timeclose', 'timelimit', 'isclosed1',
            'isclosed2', 'tool1numattempt1', 'tool1numattempt2', 'tool2numattempt1', 'tool2numattempt2',
            'tool3numattempt1', 'tool3numattempt2', ]);

        $types = new backup_nested_element( 'types');
        $type = new backup_nested_element( 'type');

        $ausers = new backup_nested_element('ausers');
        $ausermoodle = new backup_nested_element('auser_moodle', ['id'],
            ['instanceid', 'lastlogin', 'lastip']);
        $auser = new backup_nested_element('auser', ['id'],
            ['kind', 'instanceid', 'lastlogin', 'lastip']);

        $avatars = new backup_nested_element( 'avatars');
        $avatar = new backup_nested_element('avatar', ['id'],
            ['directory', 'filename', 'numused', 'randomkey', 'ishidden']);

        $palettes = new backup_nested_element( 'palettes');
        $palette = new backup_nested_element('palette', ['id'],
            ['category', 'name', 'color1', 'color2', 'color3', 'color4', 'color5',
            'colorsort1', 'colorsort2', 'colorsort3', 'colorsort4', 'colorsort5', 'hue']);

        $uguids = new backup_nested_element( 'uguids');
        $uguid = new backup_nested_element('uguid', ['id'],
            ['guid']);

        // This module is using questions, so produce the related question states and sessions
        // attaching them to the $attempt element based in 'uniqueid' matching.
        $this->add_question_usages($stat, 'queryid');

        // Define id annotations.
        $ausermoodle->annotate_ids('user', 'instanceid');

        // Build the tree.
        $mmogame->add_child($uguids);
        $uguids->add_child($uguid);

        $mmogame->add_child($ausers);
        $ausers->add_child($ausermoodle);
        $ausers->add_child($auser);

        $mmogame->add_child($avatars);
        $avatars->add_child($avatar);

        $mmogame->add_child($palettes);
        $palettes->add_child($palette);

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
            $params = [backup::VAR_PARENTID];

            $sql = "SELECT * FROM {mmogame_aa_users} u
                WHERE kind='moodle'
                AND id IN (SELECT DISTINCT auserid FROM {mmogame_aa_grades} g WHERE mmogameid=?)";
            $ausermoodle->set_source_sql( $sql, $params);

            $sql = "SELECT * FROM {mmogame_aa_users} u
                WHERE kind<>'moodle'
                AND id IN (SELECT DISTINCT auserid FROM {mmogame_aa_grades} g WHERE mmogameid=?)";
            $auser->set_source_sql( $sql, $params);

            $sql = "SELECT uq.* FROM {mmogame_aa_users_guid} uq, {mmogame_aa_users} u
                WHERE uq.id = u.instanceid AND u.id IN
                (SELECT DISTINCT auserid FROM {mmogame_aa_grades} g WHERE mmogameid=?)";
            $uguid->set_source_sql( $sql, $params);

            $sql = "SELECT * FROM {mmogame_aa_avatars}
                WHERE id IN (SELECT DISTINCT avatarid FROM {mmogame_aa_grades} g WHERE mmogameid=?)";
            $avatar->set_source_sql( $sql, $params);

            $sql = "SELECT * FROM {mmogame_aa_colorpalettes}
                WHERE id IN (SELECT DISTINCT colorpaletteid FROM {mmogame_aa_grades} g WHERE mmogameid=?)";
            $palette->set_source_sql( $sql, $params);

            $params = ['mmogameid' => backup::VAR_PARENTID];
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
