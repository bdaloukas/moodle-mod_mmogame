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
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * mmogame_quiz_aduelsplit_admin class
 *
 * @package    mmogametype_quiz
 * @copyright  2024 Vasilis Daloukas
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mmogametype_quiz\local;

use moodleform;

defined('MOODLE_INTERNAL') || die;

global $CFG;

require_once($CFG->libdir.'/formslib.php');

/**
 * The class mmogame_quiz_alone_admin extentes the class moodleform
 *
 * @package    mmogametype_quiz
 * @copyright  2024 Vasilis Daloukas
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mmogametype_quiz_split_admin extends moodleform {
    /** @var int $_id: the course_module id. */
    protected int $_id;

    /** @var object _mmogame: the coresponding mmogame. */
    protected object $_mmogame;

    /**
     * Constructor.
     *
     * @param int $id
     * @param object $mmogame
     */
    public function __construct($id, $mmogame) {
        $this->_id = $id;
        $this->_mmogame = $mmogame;

        parent::__construct();
    }

    /**
     * Definition of form.
     */
    public function definition(): void {
        $mform = $this->_form;
        $rgame = $this->_mmogame->get_rgame();

        $state = $this->_mmogame->get_state();
        if ($state == 0) {
            $statename = get_string( 'state0', 'mmogametype_quiz');
        } else {
            $statename = get_string( $rgame->model.'_state'.$state, 'mmogametype_quiz');
        }

        $mform->addElement('hidden', 'id', $this->_id);
        $mform->setType( 'id', PARAM_INT);

        // Name of the game.
        $mform->addElement('static', 'gamename', '', get_string('js_name', 'mmogame') . ': '.$rgame->name);
        $mform->addElement('html', '<br>');

        $mform->addElement('html', '<table border=1>');

        // First line numgame.
        $mform->addElement('html', '<tr>');
        $mform->addElement('html', '<td>'.get_string('numgame', 'mmogame').':</td>');
        $mform->addElement('html', '<td>');
        if ($rgame->numgame > 1) {
            $mform->addElement('html', '<button id="prevnumgame" name="prevnumgame">⟪</button>');
        }
        $mform->addElement('html', '<span class="value" id="numgame">'.$rgame->numgame.'</span>');
        $mform->addElement('html', '<button id="nextnumgame" name="nextnumgame">⟫</button>');
        $mform->addElement('html', '<td>');
        $mform->addElement('html', '</tr>');

        // Second line state.
        $mform->addElement('html', '<tr>');
        $mform->addElement('html', '<td>'.get_string('state', 'mmogame').':</td>');
        $mform->addElement('html', '<td>');
        if ($state > 0) {
            $mform->addElement('html', '<button id="prevstate" name="prevstate">⟪</button>');
        }
        $mform->addElement('html', '<span class="value" id="state">'.$statename.'</span>');
        if ($state < 1) {
            $mform->addElement('html', '<button id="nextstate" name="nextstate">⟫</button><td>');
        }
        $mform->addElement('html', '</tr>');

        // Number of attempts.
        $mform->addElement('html', '<tr>');
        $mform->addElement('html', '<td>'."Number of attempts  mmogameid={$rgame->id} AND numgame={$rgame->numgame}".':</td>');

        global $DB;
        $sql = 'SELECT COUNT(*) as c FROM {mmogame_quiz_attempts} WHERE mmogameid=? AND numgame=?';
        $rec = $DB->get_record_sql( $sql, [$rgame->id, $rgame->numgame]);
        $attempts = $rec->c;
        $mform->addElement('html', '<td>'.$rec->c.'</td>');
        $mform->addElement('html', '</tr>');

        // Count of users.
        $mform->addElement('html', '<tr>');
        $mform->addElement('html', '<td>Count of users:</td>');

        global $DB;
        $sql = 'SELECT COUNT(*) as c FROM {mmogame_aa_grades} WHERE mmogameid=? AND numgame=?';
        $rec = $DB->get_record_sql( $sql, [$rgame->id, $rgame->numgame]);
        $mform->addElement('html', '<td>'.$rec->c.'</td>');
        $mform->addElement('html', '</tr>');

        if ($attempts > 0) {
            // Start time.
            $mform->addElement('html', '<tr>');
            $mform->addElement('html', '<td>Start time:</td>');

            global $DB;
            $sql = 'SELECT MIN(timeanswer) as c, MAX(timeanswer) as c2
                FROM {mmogame_quiz_attempts}
                WHERE mmogameid=? AND numgame=? AND timeanswer > 0';
            $rec = $DB->get_record_sql($sql, [$rgame->id, $rgame->numgame]);
            $mform->addElement('html', '<td>' . date('d/m/Y H:i', $rec->c) . '</td>');
            $mform->addElement('html', '</tr>');

            // Finish time.
            $mform->addElement('html', '<tr>');
            $mform->addElement('html', '<td>Finish time:</td>');
            $mform->addElement('html', '<td>' . date('d/m/Y H:i', $rec->c2) . '</td>');
            $mform->addElement('html', '</tr>');

            // Grades.
            $mform->addElement('html', '<tr>');
            $mform->addElement('html', '<td>Start time:</td>');

            global $DB;
            $subsql = "SELECT COUNT(*)
                FROM {mmogame_quiz_attempts}
                WHERE mmogameid=g.mmogameid AND numgame=g.numgame AND auserid=g.auserid";
            $sql = "SELECT auserid,sumscore,($subsql) as answers
                FROM {mmogame_aa_grades} g
                WHERE mmogameid=? AND numgame=? ORDER BY auserid";
            $recs = $DB->get_records_sql($sql, [$rgame->id, $rgame->numgame]);
            $a = [];
            foreach ($recs as $rec) {
                $a[$rec->auserid] = $rec->auserid.':'.$rec->sumscore.'-'.$rec->answers;
            }
            $mform->addElement('html', '<td>' . implode( ', ', $a) . '</td>');
            $mform->addElement('html', '</tr>');

            // Info.
            $sql = 'SELECT info FROM {mmogame_aa_states} WHERE mmogameid=? AND numgame=?';
            $rec = $DB->get_record_sql($sql, [$rgame->id, $rgame->numgame]);
            $mform->addElement('html', '<tr>');
            $mform->addElement('html', '<td>Info:</td>');

            $mform->addElement('html', '<td>' . $rec->info. '</td>');
            $mform->addElement('html', '</tr>');
        }

        // End of the table.
        $mform->addElement('html', '</table>');

        // Player and Answer Information.
        $mform->addElement('html', '<div id="mmogame_info"></div>');
    }
}
