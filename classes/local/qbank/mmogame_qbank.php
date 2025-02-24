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
use mod_mmogame;
use mod_mmogame\local\mmogame;
use stdClass;

/**
 * The class mmogame_qbank is a based class for saved questions (e.g., glossary, question bank)
 *
 * @package    mmogame_qbank_moodlequestion
 * @copyright  2024 Vasilis Daloukas
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
abstract class mmogame_qbank {
    /** @var mod_mmogame\local\mmogame: the object mmogame that is connected to the question bank. */
    protected mod_mmogame\local\mmogame $mmogame;

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
     * @param bool $usenumattempt
     * @param int $countquestions
     * @param int $corrects
     * @return ?array
     */
    public function get_attempt_new(int $count, bool $usenumattempt, int &$countquestions, int &$corrects): ?array {
        $db = $this->mmogame->get_db();
        $rgame = $this->mmogame->get_rgame();
        $auserid = $this->mmogame->get_auserid();

        $queries = $this->get_queries( $auserid, null, $count, $countquestions, $corrects);
        if ($queries === null) {
            return null;
        }

        if ($usenumattempt) {
            $rec = $db->get_record_select( $this->mmogame->get_table_attempts(), 'mmogameid=? AND numgame=? AND auserid=?',
                [$rgame->id, $rgame->numgame, $auserid], 'max(numattempt) as num');
            $numattempt = $rec->num + 1;
        }

        $a = [];

        $a['mmogameid'] = $rgame->id;
        $a['numgame'] = $rgame->numgame;
        $a['auserid'] = $auserid;
        if ($usenumattempt) {
            $a['numattempt'] = $numattempt;
        }
        $a['timeclose'] = 0;
        if ($count == 1) {
            $a['queryid'] = $queries[0]->id;
            $a['layout'] = $this->get_layout( $queries[0]);
        }
        $a['queries'] = $queries;
        $a['useranswerid'] = 0;
        $a['useranswer'] = '';
        $a['timeanswer'] = 0;
        $a['iscorrect'] = -1;

        return $a;
    }

    /**
     * Returns the id of selected queries.
     *
     * @param int $auserid
     * @param ?int $numteam
     * @param int $count (how many queries they want)
     * @param int $countquestions return the count of questions
     * @param int $corrects returns the correct questions
     * @return ?array of int or false if no queries found.
     */
    protected function get_queries(int $auserid, ?int $numteam, int $count, int &$countquestions, int &$corrects): ?array {
        $ids = $this->get_queries_ids();
        $countquestions = count( $ids );
        if ($ids === false) {
            return null;
        }

        $db = $this->mmogame->get_db();
        $rgame = $this->mmogame->get_rgame();

        $fields = 'queryid,countused,countcorrect,counterror,islastcorrect';
        if ($numteam === null) {
            $stats = $db->get_records_select( 'mmogame_aa_stats', 'mmogameid=? AND numgame=? AND auserid=?',
                [$rgame->id, $rgame->numgame, $auserid], '', $fields);
        } else {
            $stats = $db->get_records_select( 'mmogame_aa_stats', 'mmogameid=? AND numgame=? AND numteam=?',
                [$rgame->id, $rgame->numgame, $numteam], '', $fields);
        }

        $map = [];
        $corrects = 0;
        foreach ($ids as $id => $qtype) {
            $r = mt_rand( 0, 1000 * 1000 - 1);
            $stat = array_key_exists( $id, $stats) ? $stats[$id] : false;
            $group = 0;

            $countused = $stat ? $stat->countused : 0;
            $countcorrect = $stat !== false ? $stat->countcorrect : 0;
            $key = sprintf( "%10d-%10d-%10d-%10d-%10d", $countcorrect, $countused, $group, $r, $id);
            $map[$key] = $id;

            if ($stat !== false && $stat->islastcorrect) {
                $corrects++;
            }
        }
        ksort( $map);

        $ret = [];
        foreach ($map as $id) {
            if (count( $ret) >= $count) {
                break;
            }
            $q = $this->load( $id);
            $ret[] = $q;
        }
        shuffle( $ret);

        foreach ($ret as $q) {
            if ($numteam != null && $numteam != 0) {
                $this->update_stats( null, $numteam, $q->id, true, false, false);
            } else {
                $this->update_stats( $auserid, null, $q->id, true, false, false);
            }
        }

        return count( $ret) ? $ret : null;
    }

    /**
     * Updates the grade in database (table mmogame_aa_grades).
     *
     * The score2 is a temporary score e.g. chat phase of arguegraph.
     *
     * @param int $auserid
     * @param int $score
     * @param int $score2
     * @param int $countscore (how to increase the score)
     */
    public function update_grades(int $auserid, int $score, int $score2, int $countscore): void {
        $db = $this->mmogame->get_db();
        $rgame = $this->mmogame->get_rgame();
        $rec = $db->get_record_select( 'mmogame_aa_grades', 'mmogameid=? AND numgame=? AND auserid=?',
                [$rgame->id, $rgame->numgame, $auserid]);
        if ($rec === null) {
            $this->mmogame->get_grade( $auserid);
            $rec = $db->get_record_select( 'mmogame_aa_grades', 'mmogameid=? AND numgame=? AND auserid=?',
                [$rgame->id, $rgame->numgame, $auserid]);
        }
        if ($rec !== null) {
            $a = ['id' => $rec->id];
            if ($countscore > 0) {
                $a['countscore'] = $rec->countscore + $countscore;
                $a['score'] = max( 0, $rec->sumscore + $score) / ($rec->countscore + $countscore);
            }
            if ($score != 0) {
                $a['sumscore'] = max( 0, $rec->sumscore + $score);
            }
            if ($score2 != 0) {
                $a['sumscore2'] = max( 0, $rec->sumscore2 + $score2);
            }
            if (count( $a) > 1) {
                $db->update_record( 'mmogame_aa_grades', $a);
            }
        } else {
            $db->insert_record( 'mmogame_aa_grades',
                ['mmogameid' => $rgame->id, 'numgame' => $rgame->numgame, 'auserid' => $auserid, 'sumscore' => max( 0, $score),
                'countscore' => $countscore,
                'score' => max( 0, $score), 'sumscore2' => max( 0, $score2), 'timemodified' => time(), ]);
        }
    }

    /**
     * Updates statistics in the database (table mmogame_aa_stats).
     *
     * The score2 is a temporary score e.g. chat phase of arguegraph.
     *
     * @param ?int $auserid
     * @param ?int $numteam
     * @param ?int $queryid
     * @param bool $isused
     * @param bool $iscorrect
     * @param bool $iserror
     * @param ?array $values
     */
    public function update_stats(?int $auserid, ?int $numteam, ?int $queryid, bool $isused, bool $iscorrect, bool $iserror,
        ?array $values = null): void {

        $db = $this->mmogame->get_db();
        $rgame = $this->mmogame->get_rgame();

        $select = 'mmogameid=? AND numgame=? ';
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

        $rec = $db->get_record_select( 'mmogame_aa_stats', $select, $a);
        if ($rec !== null) {
            $a = ['id' => $rec->id];
            if ($isused) {
                $a['countused'] = $rec->countused + 1;
            }
            if ($iscorrect) {
                $a['countcorrect'] = ++$rec->countcorrect;
                $a['islastcorrect'] = 1;
            }
            if ($iserror) {
                $a['counterror'] = ++$rec->counterror;
                $a['timeerror'] = time();
                $a['islastcorrect'] = 0;
            }

            $count = $rec->countcorrect + $rec->counterror;
            $a['percent'] = $count == 0 ? null : $rec->countcorrect / $count;
            if ($values !== null) {
                foreach ($values as $key => $value) {
                    $a[$key] = $value;
                }
            }
            $db->update_record( 'mmogame_aa_stats', $a);
        } else {
            $count = $iscorrect + ($iserror ? 1 : 0);
            $percent = $count == 0 ? null : ($iscorrect ? 1 : 0) / $count;
            $a = ['mmogameid' => $rgame->id,
                'numgame' => $rgame->numgame, 'queryid' => $queryid != 0 ? $queryid : null, 'auserid' => $auserid,
                'numteam' => $numteam != 0 ? $numteam : null, 'percent' => $percent, 'countused' => $isused ? 1 : 0,
                'countcorrect' => $iscorrect ? 1 : 0, 'counterror' => $iserror ? 1 : 0, 'islastcorrect' => $iscorrect ? 1 : 0, ];
            if ($values !== null) {
                foreach ($values as $key => $value) {
                    $a[$key] = $value;
                }
            }

            $db->insert_record( 'mmogame_aa_stats', $a);
        }
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
