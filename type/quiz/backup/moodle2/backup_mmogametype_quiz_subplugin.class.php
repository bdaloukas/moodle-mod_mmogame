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
 * This file contains the class for backup of this type plugin
 *
 * @package mmogametype_quiz
 * @copyright  2024 Vasilis Daloukas
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Provides the information to backup quiz type
 *
 * This just adds quiz info
 *
 * @package mmogametype_quiz
 * @copyright  2024 Vasilis Daloukas
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class backup_mmogametype_quiz_subplugin extends backup_subplugin {

    /**
     * Returns the subplugin information to attach to game element
     *
     * @return backup_subplugin_element
     */
    protected function define_type_subplugin_structure() {
        // Create XML elements.
        $subplugin = $this->get_subplugin_element();
        $subpluginwrapper = new backup_nested_element($this->get_recommended_name());
        $subpluginelement = new backup_nested_element('mmogametype_quiz', null,
            ['auserid', 'numgame', 'numteam', 'numattempt', 'queryid', 'useranswerid', 'useranswer', 'iscorrect',
            'layout', 'timestart', 'timeclose', 'timeanswer', 'fraction', 'score', 'score2', 'iscorrect2', ]);

        // Connect XML elements into the tree.
        $subplugin->add_child($subpluginwrapper);
        $subpluginwrapper->add_child($subpluginelement);

        // Set source to populate the data.
        $subpluginelement->set_source_table('mmogame_quiz_attempts', ['mmogameid' => backup::VAR_PARENTID]);

        return $subplugin;
    }
}
