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
 * lib
 *
 * @package    mod_mmogame
 * @copyright  2024 Vasilis Daloukas
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace mod_mmogame\local\qbank;
use coding_exception;
use dml_exception;
use mod_mmogame;
use mod_mmogame\local\mmogame;
use stdClass;

/**
 * The class mmogame_qbank is a based class for saved questions (e.g., glossary, question bank)
 *
 * @package    mmogame_qbank
 * @copyright  2024 Vasilis Daloukas
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
abstract class mmogame_qbank {
    /** @var mmogame: the object mmogame that is connected to the question bank. */
    protected mmogame $mmogame;

    /**
     * Constructor.
     *
     * @param mmogame $mmogame
     */
    public function __construct(mmogame $mmogame) {
        $this->mmogame = $mmogame;
    }

    /**
     * The base function for a new attempt.
     *
     * @param int $count
     * @param int $numattempt
     * @return ?array
     * @throws coding_exception
     * @throws dml_exception
     */
    public function get_attempt_new(int $count, int $numattempt): ?array {
        $mmogame = $this->mmogame;
        $rgame = $mmogame->get_rgame();
        $auserid = $mmogame->get_auserid();

        $queries = $mmogame->get_selection()->get_queries(
            $this->get_queries_ids(),
            $count,
            $numattempt
        );

        if (count($queries) == 0) {
            return null;
        }

        $a = [];

        $a['mmogameid'] = $rgame->id;
        $a['numgame'] = $rgame->numgame;
        $a['auserid'] = $auserid;
        $a['numattempt'] = $numattempt;
        $a['timeclose'] = 0;
        $a['queries'] = $queries;
        $a['useranswerid'] = 0;
        $a['useranswer'] = '';
        $a['timeanswer'] = 0;
        $a['iscorrect'] = -1;
        $a['attemptkey'] = mmogame::createkey();

        return $a;
    }

    /**
     * Updates the grade in database (table mmogame_aa_grades).
     *
     * @param int $auserid
     * @param int $addgrade
     * @param int $addcountmastered
     * @param array|null $fields
     * @return stdClass (the new grade)
     */
    public function update_grades(int $auserid, int $addgrade, int $addcountmastered, ?array $fields = null): stdClass {
        $db = $this->mmogame->get_db();
        $rgrade = $this->mmogame->get_rgrade($auserid, true);
        $a = ['id' => $rgrade->id];
        if ($fields !== null) {
            foreach ($fields as $field => $value) {
                $a[$field] = $value;
            }
        }

        if ($addgrade != 0) {
            $rgrade->grade = max(0, $rgrade->grade + $addgrade);
            $a['grade'] = $rgrade->grade;
        }
        if ($addcountmastered != 0) {
            if ($rgrade->countmastered === null) {
                $rgrade->countmastered = 0;
            }
            $rgrade->countmastered = max(0, $rgrade->countmastered + $addcountmastered);
            $a['countmastered'] = $rgrade->countmastered;
        }
        $countqueries = $this->mmogame->get_rstate()->countqueries;
        $rgrade->percent = $countqueries > 0 ? $rgrade->countmastered / $countqueries : 0;
        if (count($a) > 1) {
            $db->update_record('mmogame_aa_grades', $a);
        }

        return $rgrade;
    }

    /**
     * Updates statistics in the database (table mmogame_aa_stats).
     *
     * The score2 is a temporary score e.g. chat phase of arguegraph.
     *
     * @param ?int $numteam
     * @param ?int $queryid
     * @param bool $iscorrect
     * @param bool $iserror
     * @param int $nextattempt
     * @param int $addcountmastered
     */
    public function update_stats(
        ?int $numteam,
        ?int $queryid,
        bool $iscorrect,
        bool $iserror,
        int $nextattempt,
        int &$addcountmastered
    ) {
        $addcountmastered = 0;
        $db = $this->mmogame->get_db();
        $rgame = $this->mmogame->get_rgame();
        $select = 'mmogameid=? AND numgame=? ';
        $auserid = $this->mmogame->get_auserid();
        $a = [$rgame->id, $rgame->numgame];
        if ($auserid !== null) {
            $select .= ' AND auserid=?';
            $a[] = $auserid;
        } else {
            $select .= ' AND auserid IS NULL';
        }
        if ($numteam !== null) {
            $select .= ' AND numteam=?';
            $a[] = $numteam;
        } else {
            $select .= ' AND numteam IS NULL';
        }
        if ($queryid !== null) {
            $select .= ' AND queryid=?';
            $a[] = $queryid;
        } else {
            $select .= ' AND queryid IS NULL';
        }

        $rec = $db->get_record_select('mmogame_aa_stats', $select, $a);
        if ($rec !== null) {
            $a = ['id' => $rec->id];
            $a['countused'] = $rec->countused + 1;
            if ($iscorrect && $rec->serialcorrects == 0) {
                $rec->serialcorrects = 1;
                $addcountmastered = 1;
            } else if ($iscorrect && $rec->serialcorrects > 0) {
                $rec->serialcorrects = $rec->serialcorrects + 1;
            } else if ($iscorrect == 0) {
                if ($rec->serialcorrects > 0) {
                    $addcountmastered = -1;
                }
                $rec->serialcorrects = 0;
            }
            $a['serialcorrects'] = $rec->serialcorrects;

            if ($iscorrect) {
                $a['countcorrect'] = ++$rec->countcorrect;
            }
        } else {
            $a['mmogameid'] = $rgame->id;
            $a['numgame'] = $rgame->numgame;
            $a['queryid'] = $queryid;
            $a['auserid'] = $auserid;
            $a['numteam'] = $numteam != 0 ? $numteam : null;
            $a['randkey'] = mt_rand(0, PHP_INT_MAX);
            $a['countused'] = 1;
            $a['serialcorrects'] = $iscorrect ? 1 : 0;
            if ($iscorrect) {
                $addcountmastered++;
                $a['countcorrect'] = 1;
            }
        }

        $a['nextattempt'] = $nextattempt;

        if ($iserror) {
            $a['counterror'] = ++$rec->counterror;
            $a['timeerror'] = time();
            $a['islastcorrect'] = 0;
            $a['serialcorrects'] = 0;
        }

        if (array_key_exists('id', $a)) {
            $db->update_record('mmogame_aa_stats', $a);
        } else {
            $db->insert_record('mmogame_aa_stats', $a);
        }

        if ($this->mmogame->get_selection()->can_update_heuristic()) {
            $this->mmogame->get_selection()->update_stats($queryid, $iscorrect);
        }
    }

    /**
     * Return the layout (the positions of answer) for the question $queryid
     *
     * @param int $queryid
     * @return string|null
     */
    public function get_layout_queryid(int $queryid): ?string {
        $query = $this->load($queryid);
        if ($query === null) {
            return null;
        }
        return $this->get_layout($query);
    }

    /**
     * Loads data for question id.
     *
     * @param int $id
     * @param bool $loadextra
     * @param string $fields
     * @return ?stdClass
     */
    abstract public function load(int $id, bool $loadextra = true, string $fields = ''): ?stdClass;
}
