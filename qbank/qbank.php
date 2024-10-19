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

define( 'MMOGAME_LEARN_MUL', 0.9);

class mmogameqbank {
    protected $mmogame;

    public function __construct( $mmogame) {
        $this->mmogame = $mmogame;
    }

    public function get_attempt_new( $gid, $count, $stopatend, $usenumattempt, $queries) {
        $db = $this->mmogame->get_db();
        $rinstance = $this->mmogame->get_rinstance();
        $auserid = $this->mmogame->get_auserid();

        if ($queries === false) {
            $queries = $this->get_queries_quotauser( $gid, null, $count);
            if ($queries === false) {
                return false;
            }
        }

        if ($usenumattempt) {
            $rec = $db->get_record_select( $this->mmogame->get_table_attempts(), 'ginstanceid=? AND numgame=? AND auserid=?',
                [$rinstance->id, $rinstance->numgame, $auserid], 'max(numattempt) as num');
            $numattempt = $rec->num + 1;
        }

        $a = [];

        $a['mmogameid'] = $rinstance->mmogameid;
        $a['ginstanceid'] = $rinstance->id;
        $a['numgame'] = $rinstance->numgame;
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

    public function get_queries_quotauser( $auserid, $teamid, $count) {
        $ids = $this->get_queries_ids();
        if ($ids === false) {
            return false;
        }

        $db = $this->mmogame->get_db();
        $rinstance = $this->mmogame->get_rinstance();

        if ($teamid == null) {
            $stats = $db->get_records_select( 'mmogame_aa_stats', 'ginstanceid=? AND numgame=? AND auserid=?',
                [$rinstance->id, $rinstance->numgame, $auserid], '', 'queryid,countused,countcorrect,counterror');
        } else {
            $stats = $db->get_records_select( 'mmogame_aa_stats', 'ginstanceid=? AND numgame=? AND teamid=?',
                [$rinstance->id, $rinstance->numgame, $teamid], '', 'queryid,countused,countcorrect,counterror');
        }

        $map = [];
        $errors = 0;
        foreach ($ids as $id => $qtype) {
            $r = mt_rand( 0, 1000 * 1000 - 1);
            $stat = array_key_exists( $id, $stats) ? $stats[$id] : false;
            $group = 0;

            $countused = $stat != false ? $stat->countused : 0;
            $countcorrect = $stat != false ? $stat->countcorrect : 0;
            $key = sprintf( "%10d-%10d-%10d-%10d-%10d", $countcorrect, $countused, $group, $r, $id);
            $map[$key] = $id;
        }
        ksort( $map);

        $ret = [];
        foreach ($map as $id) {
            if (count( $ret) >= $count) {
                break;
            }
            $q = $this->load( $id, true);
            $ret[] = $q;
        }
        shuffle( $ret);

        foreach ($ret as $q) {
            if ($teamid != null) {
                $this->update_stats( null, $teamid, $q->id, true, false, false);
            } else {
                $this->update_stats( $auserid, null, $q->id, true, false, false);
            }
        }

        return count( $ret) ? $ret : false;
    }

    // The score2 is a temporary score e.g. chat phase of arguegraph.
    public function update_grades( $auserid, $score, $score2, $countscore) {
        $db = $this->mmogame->get_db();
        $rinstance = $this->mmogame->get_rinstance();
        $rec = $db->get_record_select( 'mmogame_aa_grades', 'ginstanceid=? AND numgame=? AND auserid=?',
                [$rinstance->id, $rinstance->numgame, $auserid]);
        if ($rec === false) {
            $this->mmogame->get_grade( $auserid);
            $rec = $db->get_record_select( 'mmogame_aa_grades', 'ginstanceid=? AND numgame=? AND auserid=?',
                [$rinstance->id, $rinstance->numgame, $auserid]);
        }
        if ($rec != false) {
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
                ['mmogameid' => $rinstance->mmogameid, 'ginstanceid' => $rinstance->id,
                'numgame' => $rinstance->numgame, 'auserid' => $auserid, 'sumscore' => max( 0, $score),
                'countscore' => $countscore,
                'score' => max( 0, $score), 'sumscore2' => max( 0, $score2), 'timemodified' => time(), ]);
        }
    }

    public function update_stats( $auserid, $teamid, $queryid, $isused, $iscorrect, $iserror, $values = false) {
        $db = $this->mmogame->get_db();
        $rinstance = $this->mmogame->get_rinstance();

        $select = 'ginstanceid=? AND numgame=? ';
        $a = [$rinstance->id, $rinstance->numgame];
        if ($auserid !== null && $auserid != 0 && $auserid !== false) {
            $select .= ' AND auserid=?';
            $a[] = $auserid;
        } else {
            $select .= ' AND auserid IS NULL';
            $auserid = null;
        }
        if ($teamid != null && $teamid != 0 && $teamid !== false) {
            $select .= ' AND teamid=?';
            $a[] = $teamid;
        } else {
            $select .= ' AND teamid IS NULL';
            $teamid = null;
        }
        if ($queryid !== null && $queryid != 0 && $queryid !== false) {
            $select .= ' AND queryid=?';
            $a[] = $queryid;
        } else {
            $select .= ' AND queryid IS NULL';
            $queryid = null;
        }

        $rec = $db->get_record_select( 'mmogame_aa_stats', $select, $a);
        if ($rec !== false) {
            $a = ['id' => $rec->id];
            if ($isused > 0) {
                $a['countused'] = $rec->countused + $isused;
            }
            if ($iscorrect > 0) {
                $rec->countcorrect += $iscorrect;
                $a['countcorrect'] = $rec->countcorrect;
            }
            if ($iserror > 0) {
                $rec->counterror += $iserror;
                $a['counterror'] = $rec->counterror;
                $a['timeerror'] = time();
            }

            $count = $rec->countcorrect + $rec->counterror;
            $a['percent'] = $count == 0 ? null : $rec->countcorrect / $count;
            if ($values !== false) {
                foreach ($values as $key => $value) {
                    if ($value == 'percent2') {
                        $value /= $rec->countanswers;
                    }
                    $a[$key] = $value;
                }
                if ($queryid == null && array_key_exists( 'countcompleted', $values)) {
                    $n = $rec->countanswers;
                    $percentcompleted = $n != 0 ? $values['countcompleted'] / $n : 0;
                    $sql = "UPDATE {$db->prefix}mmogame_aa_grades ".
                        "SET percentcompleted=? WHERE ginstanceid=? AND numgame=? AND auserid=?";
                    $db->execute( $sql, [$percentcompleted, $rinstance->id, $rinstance->numgame, $auserid]);
                }
            }
            $db->update_record( 'mmogame_aa_stats', $a);
        } else {
            $count = $iscorrect + $iserror;
            $percent = $count == 0 ? null : $iscorrect / $count;
            $a = ['mmogameid' => $rinstance->mmogameid, 'ginstanceid' => $rinstance->id,
                'numgame' => $rinstance->numgame, 'queryid' => $queryid, 'auserid' => $auserid, 'teamid' => $teamid,
                'percent' => $percent, 'countused' => $isused, 'countcorrect' => $iscorrect,
                'counterror' => $iserror, ];
            if ($values !== false) {
                foreach ($values as $key => $value) {
                    $a[$key] = $value;
                }
            }
            $db->insert_record( 'mmogame_aa_stats', $a);
        }
    }
}
