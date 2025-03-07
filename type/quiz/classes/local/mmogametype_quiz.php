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
 * mmogamekind_quiz class
 *
 * @package    mmogametype_quiz
 * @copyright  2024 Vasilis Daloukas
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mmogametype_quiz\local;

use mod_mmogame\local\database\mmogame_database;
use mod_mmogame\local\mmogame;
use stdClass;

defined('MOODLE_INTERNAL') || die();

define( 'ERRORCODE_NO_QUERIES', 'no_questions');
define( 'ERRORCODE_ADUEL_NO_RIVALS', 'aduel_no_rivals');

require_once(dirname(__FILE__) . '/../../lib.php');

/**
 * mmogame_quiz is responsible for managing and facilitating quiz gameplay
 * within the mmogame system, including handling attempts, scoring,
 * and maintaining related user data.
 */
abstract class mmogametype_quiz extends mmogame {
    /** @var bool $stopatend: stops at the end of this game. */
    protected bool $callupdategrades = true;

    /**
     * return the name of tabler attempts.
     */
    public static function get_table_attempts(): string {
        return 'mmogame_quiz_attempts';
    }

    /**
     * Saves to array $ret information about the $attempt.
     *
     * @param array $ret (returns info about the current attempt)
     * @param ?stdClass $attempt
     * @param string $subcommand
     * @return ?stdClass
     */
    public function append_json(array &$ret, ?stdClass $attempt, string $subcommand = ''): ?stdClass {
        $auserid = $this->get_auserid();

        $info = $this->get_avatar_info( $auserid);
        $ret['avatar'] = $info->avatar;
        $ret['nickname'] = $info->nickname;
        $ret['colors'] = implode( ',', $info->colors);
        $ret['fastjson'] = $this->rgame->fastjson;
        $ret['name'] = $this->rgame->name;
        $ret['state'] = $this->rstate->state;
        $ret['rank'] = $this->get_rank( $info->sumscore, 'sumscore');
        $ret['sumscore'] = $info->sumscore;
        $ret['timefastjson'] = $this->rgame->timefastjson;
        $ret['percent'] = $info->percent;
        $ret['percentrank'] = $this->get_rank( $info->percent, 'percent');

        if ($attempt === null) {
            $attempt = new stdClass();
            $attempt->id = 0;
            $attempt->timestart = 0;
            $attempt->timeclose = 0;
            $attempt->queryid = 0;
            $attempt->useranswer = '';
        }
        $ret['attempt'] = $attempt->id;

        $recquery = null;
        if ($attempt->queryid != 0) {
            $recquery = $this->get_qbank()->load_json( $this, $ret, '', $attempt->queryid, $attempt->layout, false);
        }

        $ret['timestart'] = $attempt->timestart;
        $ret['timeclose'] = $attempt->timeclose;

        return $recquery;
    }

    /**
     * Return the score with negative values. If "n" is the number of answers, if it corrects returns (n-1) else returns (-1)
     *
     * @param bool $iscorrect
     * @param stdClass $query
     * @return int
     */
    protected function get_score_query_negative(bool $iscorrect, stdClass $query): int {
        if (!$this->qbank->is_multichoice( $query)) {
            return $iscorrect ? 1 : 0;
        }

        return $iscorrect ? count( $query->answers) - 1 : -1;
    }

    /**
     * Deletes info for a given mmogame and auser
     *
     * @param mmogame_database $db
     * @param stdClass $rgame
     * @param ?int $auserid
     */
    public static function delete_auser(mmogame_database $db, stdClass $rgame, ?int $auserid): void {
        $db->delete_records_select( 'mmogame_quiz_attempts', 'mmogameid=? AND auserid=?', [$rgame->id, $auserid]);
    }

    /**
     * Set the state of the current game.
     *
     * @param int $state
     * @return string
     */
    public function set_state(int $state): string {
        $timefastjson = round( 10 * microtime( true));

        $statecontents = $state . "-" . $timefastjson;
        $filecontents = '';

        $this->save_state($state, $statecontents, $filecontents, $timefastjson);

        return $statecontents;
    }
}
