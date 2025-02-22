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
 * This is the renderable for report overview
 *
 * @package    mmogametype_quiz
 * @copyright  2024 Vasilis Daloukas
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mmogametype_quiz\output\overview;

use dml_exception;
use renderable;
use stdClass;

/**
 * Renderable class to store data of the quiz attempts report.
 */
class overview_renderable implements renderable {
    /** @var int $id: The course module id. */
    public int $id;
    /** @var ?int $auserid: The auserid. */
    public ?int $auserid;
    /** @var stdClass $rgame: The records with result. */
    public stdClass $rgame;
    /** @var ?string $export: True if user asked for exporting to cvs format */
    public ?string $export;
    /** @var string $where: The WHERE part of an SQL */
    private string $where;
    /** @var array $params: The params of an SQL */
    private array $params;
    /** @var string $where0: The params of an SQL */
    private string $where0;
    /** @var array $params0: The params of an SQL */
    private array $params0;
    /** @var ?int $numgame: The params of an SQL */
    public ?int $numgame;
    /** @var ?int $queryid: The queryid */
    public ?int $queryid;
    /**
     * The constructor
     *
     * @param stdClass $rgame
     * @param int $id
     * @param int|null $numgame
     * @param ?int $auserid
     * @param int|null $queryid
     * @param ?string $export
     */
    public function __construct(stdClass $rgame, int $id, ?int $numgame, ?int $auserid, ?int $queryid, ?string $export) {
        $this->id = $id;
        $this->rgame = $rgame;

        $this->numgame = $numgame;
        $this->auserid = $auserid;
        $this->queryid = $queryid;
        $this->export = $export != '' ? $export : null;

        // Get data from the database.
        [$this->where0, $this->params0] = $this->get_where( $rgame->id, null, null, null);
        [$this->where, $this->params] = $this->get_where( $rgame->id, $numgame, $auserid, $queryid);
    }

    /**
     * Reads from database the data
     *
     * @return array
     * @throws dml_exception
     */
    public function get_data(): array {
        global $DB;

        return $DB->get_records_sql(
            "SELECT * FROM {mmogame_quiz_attempts} mqa WHERE $this->where ORDER BY id", $this->params);
    }

    /**
     * Computes the where part for an SQL
     *
     * @param int $mmogameid
     * @param int|null $numgame
     * @param int|null $auserid
     * @param int|null $queryid
     * @return array
     */
    protected function get_where(int $mmogameid, ?int $numgame, ?int $auserid, ?int $queryid): array {
        $where = 'mqa.mmogameid=?';
        $params = [$mmogameid];

        if ($numgame !== null && $numgame != 0) {
            $where .= ' AND mqa.numgame=?';
            $params[] = $numgame;
        }
        if ($auserid !== null && $auserid != 0) {
            $where .= ' AND auserid=?';
            $params[] = $auserid;
        }

        if ($queryid !== null && $queryid != 0) {
            $where .= ' AND mqa.queryid=?';
            $params[] = $queryid;
        }

        return [$where, $params];
    }

    /**
     * Reads from database the data
     *
     * @return array
     * @throws dml_exception
     */
    public function get_auserid_options(): array {
        global $DB;

        $ret = [ null => ''];

        if ($this->rgame->kinduser == 'moodle') {
            $sql = "SELECT DISTINCT au.id as auserid, u.lastname, u.firstname
                FROM {mmogame_quiz_attempts} mqa, {mmogame_aa_users} au, {user} u
                WHERE $this->where0
                AND mqa.auserid = au.id AND au.instanceid = u.id
                ORDER BY u.lastname, u.firstname, mqa.auserid";
            $recs = $DB->get_records_sql( $sql, $this->params0);
            foreach ($recs as $rec) {
                $ret[$rec->auserid] = $rec->lastname.' '.$rec->firstname;
            }
        } else if ($this->rgame->kinduser == 'guid') {
            $sql = "SELECT DISTINCT auserid
                FROM {mmogame_quiz_attempts} mqa
                WHERE $this->where0
                ORDER BY auserid";

            $recs = $DB->get_records_sql($sql, $this->params0);
            foreach ($recs as $rec) {
                $ret[$rec->auserid] = $rec->auserid;
            }
        }

        return $ret;
    }

    /**
     * Reads from database the data
     *
     * @return array
     * @throws dml_exception
     */
    public function get_queries_options(): array {
        global $DB;

        if ($this->rgame->qbank == 'moodlequestion') {
            $sql = "SELECT DISTINCT mqa.queryid, q.name
                FROM {mmogame_quiz_attempts} mqa, {question} q
                WHERE $this->where0 AND mqa.queryid = q.id
                ORDER BY q.name, mqa.queryid";
            $recs = $DB->get_records_sql( $sql, $this->params0);
            $ret = [ null => ''];
            foreach ($recs as $rec) {
                $ret[$rec->queryid] = $rec->name;
            }
            return $ret;
        }

        return [];
    }

    /**
     * Reads from numgame the data
     *
     * @return array
     * @throws dml_exception
     */
    public function get_numgame_options(): array {
        global $DB;

        $sql = "SELECT DISTINCT numgame
                FROM {mmogame_quiz_attempts} mqa
                WHERE $this->where0
                ORDER BY numgame";
        $recs = $DB->get_records_sql( $sql, $this->params0);
        $ret = [ null => ''];
        foreach ($recs as $rec) {
            $ret[$rec->numgame] = $rec->numgame;
        }

        return $ret;
    }

    /**
     * Reads answer from DB
     *
     * @param stdClass $rec
     * @param array $answers
     * @return ?string
     * @throws dml_exception
     */
    public function get_answer(stdClass $rec, array &$answers): ?string {
        global $DB;

        if ($this->rgame->qbank == 'moodlequestion') {
            if ($rec->useranswerid === null) {
                return $rec->useranswer;
            } else if ($rec->useranswerid === 0) {
                return '';
            } else if (array_key_exists( $rec->useranswerid, $answers)) {
                return $answers[$rec->useranswerid];
            } else {
                $rec2 = $DB->get_record_select( 'question_answers', 'id=?', [$rec->useranswerid]);
                $answer = $rec2 === false ? null : strip_tags( $rec2->answer);
                $answers[$rec->useranswerid] = $answer;

                return $answer;
            }
        } else {
            return $rec->useranswer;
        }
    }
}
