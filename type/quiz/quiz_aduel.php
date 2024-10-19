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
 * mmogame_quiz_aduel class
 *
 * @package    mmogame_quiz
 * @copyright  2024 Vasilis Daloukas
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once(dirname(__FILE__) . '/quiz_alone.php');

class mmogame_quiz_aduel extends mmogame_quiz_alone {
    protected $numquestions;
    protected $aduel = false;
    protected $maxalone = 200;

    public function __construct( $db, $rgame, $rinstance) {
        $rgame->usemultichoice = true;

        parent::__construct( $db, $rgame, $rinstance);

        $this->numquestions = 4;
        $this->timelimit = $rinstance->timelimit != 0 ? $rinstance->timelimit : 300;
        if (isset( $rinstance->typeparams)) {
            $params = json_decode( $rinstance->typeparams);
            if (isset( $params->numquestions) && $params->numquestions > 1) {
                $this->numquestions = $params->numquestions;
            }
        }
        $this->callupdategrades = true;
    }

    public function set_attempt( $attempt) {
        $this->aduel = $this->db->get_record_select( 'mmogame_am_aduel_pairs', 'id=?', [$attempt->numteam]);
    }

    public function get_aduel() {
        return $this->aduel;
    }

    public function get_maxalone() {
        return $this->maxalone;
    }

    public function get_attempt() {
        if ($this->rstate->state != MMOGAME_ADUEL_STATE_PLAY) {
            return false;
        }

        require_once( dirname(__FILE__).'/../../model/aduel.php');

        for ($step = 1; $step <= 2; $step++) {
            $this->aduel = mmogameModel_aduel::get_aduel( $this, $newplayer1, $newplayer2);
            if ($this->aduel === false) {
                $this->set_errorcode( ERRORCODE_ADUEL_NO_RIVALS);
                return false;
            }

            if ($newplayer1 == false && $newplayer2 == false) {
                $rec = mmogameModel_aduel::get_attempt( $this, $this->aduel);
                if ($rec !== false) {
                    if ($rec->timestart == 0) {
                        $rec->timestart = time();
                        $this->db->update_record( 'mmogame_quiz_attempts',
                            ['id' => $rec->id, 'timestart' => $rec->timestart]);
                    }
                    return $rec;
                }
                continue;
            }

            if ($newplayer1) {
                return $this->get_attempt_new1();
            } else {
                return $this->get_attempt_new2();
            }
        }

        return false;
    }

    protected function get_attempt_new1() {
        $queries = $this->get_queries_aduel( 4, mt_rand( 1, 2) == 1);
        if ($queries === false) {
            return false;
        }
        $num = 0;
        $ret = 0;
        $a = [];
        foreach ($queries as $query) {
            $a['mmogameid'] = $this->rinstance->mmogameid;
            $a['ginstanceid'] = $this->rinstance->id;
            $a['numgame'] = $this->rinstance->numgame;
            $a['numteam'] = $this->aduel->id;
            $a['auserid'] = $this->auserid;
            $a['numattempt'] = ++$num;
            $a['queryid'] = $query->id;
            $a['timestart'] = $ret == 0 ? time() : 0;
            $a['timeanswer'] = 0;
            $a['timeclose'] = $this->aduel->timelimit != 0 && $ret == 0 ? $a['timestart'] + $this->aduel->timelimit : 0;
            $a['layout'] = $this->qbank->get_layout( $query);
            $id = $this->db->insert_record( $this->get_table_attempts(), $a);
            if ($ret == 0) {
                $ret = $id;
            }
        }
        return $this->db->get_record_select( $this->get_table_attempts(), 'id=?', [$ret]);
    }


    protected function get_attempt_new2() {
        $table = 'mmogame_quiz_attempts';
        $ginstanceid = $this->get_ginstanceid();

        $recs = $this->db->get_records_select( $table, 'ginstanceid=? AND numgame=? AND numteam=? AND auserid=?',
            [$ginstanceid, $this->get_rinstance()->numgame, $this->aduel->id, $this->aduel->auserid1], 'numattempt');
        $ret = 0;
        foreach ($recs as $rec) {
            $a = ['mmogameid' => $this->get_id(), 'ginstanceid' => $ginstanceid,
                'auserid' => $this->aduel->auserid2, 'queryid' => $rec->queryid, 'numgame' => $rec->numgame,
                'timestart' => 0, 'numteam' => $rec->numteam,
                'numattempt' => $rec->numattempt, 'layout' => $rec->layout, 'timeanswer' => 0, ];
            $a['timeclose'] = $ret == 0 ? time() + $this->aduel->timelimit : 0;
            $id = $this->db->insert_record( $table, $a);
            if ($ret == 0) {
                $ret = $id;
            }

            $this->qbank->update_stats( $this->aduel->auserid2, null, $rec->queryid, 1, 0, 0);
            $this->qbank->update_stats( $this->aduel->auserid2, null, null, 1, 0, 0);
            $this->qbank->update_stats( null, null,  $rec->queryid, 1, 0, 0);
        }

        return $this->db->get_record_select( $table, 'id=?', [$ret]);
    }

    public function set_answer( $attempt, $query, $useranswer, $autograde, $submit, &$ret) {
        $retvalue = parent::set_answer( $attempt, $query, $useranswer, $autograde, $submit, $ret);
        if (!$submit) {
            return $retvalue;
        }

        $ret['iscorrect'] = $attempt->iscorrect;
        if ($this->auserid == $this->aduel->auserid1) {
            $rec = $this->db->get_record_select_first( 'mmogame_quiz_attempts',
                'ginstanceid=? AND numgame=? AND numteam=? AND timeanswer = 0 AND auserid=?',
                [$attempt->ginstanceid, $attempt->numgame, $this->aduel->id, $this->auserid], 'id');
            if ($rec === false) {
                // We finished.
                $this->db->update_record( 'mmogame_am_aduel_pairs', ['id' => $this->aduel->id, 'isclosed1' => 1]);
            }

            return $retvalue;
        }

        if ($attempt->score < 0) {
            $attempt->score = 0;
        }
        $oposite = $this->db->get_record_select( 'mmogame_quiz_attempts',
            'ginstanceid=? AND numgame=? AND numteam=? AND numattempt=? AND auserid = ?',
            [$attempt->ginstanceid, $attempt->numgame, $this->aduel->id, $attempt->numattempt, $this->aduel->auserid1]
        );
        if ($oposite !== false) {
            if ($attempt->iscorrect == 1) {
                // Check the answer of oposite. If is wrong duplicate my points.
                if ($oposite->iscorrect == 0) {
                    $attempt->score *= 2;
                    if ($this->aduel->tool3numattempt2 == $attempt->numattempt) {
                        // The wizard tool.
                        $attempt->score -= 2;
                    }
                    $ret['addscore'] = $attempt->score >= 0 ? '+'.$attempt->score : $attempt->score;
                    $this->db->update_record( 'mmogame_quiz_attempts',
                        ['id' => $attempt->id, 'score' => $attempt->score]);
                    $this->qbank->update_grades( $attempt->auserid, $attempt->score, 0, 0);
                }
            } else if ($attempt->iscorrect == 0) {
                // Check the answer of oposite. If is right duplicate other's points.
                if ($oposite->iscorrect == 0) {
                    $this->db->update_record( 'mmogame_quiz_attempts',
                        ['id' => $oposite->id, 'score' => 2 * $oposite->score]);
                    $this->qbank->update_grades( $oposite->auserid, $oposite->score, 0, 0);
                }
            }
        } else if ($this->aduel->tool3numattempt2 == $attempt->numattempt) {
            // The wizard tool.
            $attempt->score -= 1;
            $ret['addscore'] = $attempt->score >= 0 ? '+'.$attempt->score : $attempt->score;
            $this->db->update_record( 'mmogame_quiz_attempts',
                ['id' => $attempt->id, 'score' => $attempt->score]);
            $this->qbank->update_grades( $attempt->auserid, -1, 0, 0);
        }

        $rec = $this->db->get_record_select_first( 'mmogame_quiz_attempts',
            'ginstanceid=? AND numgame=? AND numteam=? AND timeanswer = 0 AND auserid=?',
            [$attempt->ginstanceid, $attempt->numgame, $this->aduel->id, $this->aduel->auserid2], 'id');
        if ($rec === false) {
            // We finished.
            $this->db->update_record( 'mmogame_am_aduel_pairs', ['id' => $this->aduel->id, 'isclosed2' => 1]);
        }

        return $retvalue;
    }

    public function append_json( &$ret, $attempt, $data) {
        $query = parent::append_json( $ret, $attempt, $data);

        $auserid = $this->get_auserid();

        if ($this->aduel === false) {
            return;
        }

        if (isset( $data->subcommand)) {
            if ($data->subcommand === 'tool1') {
                if ($this->auserid == $this->aduel->auserid1) {
                    if ($this->aduel->tool1numattempt1 == 0 || $this->aduel->tool1numattempt1 == $attempt->numattempt) {
                        $this->aduel->tool1numattempt1 = $attempt->numattempt;
                        $this->db->update_record( 'mmogame_am_aduel_pairs',
                        ['id' => $this->aduel->id, 'tool1numattempt1' => $attempt->numattempt]);
                    }
                } else if ($this->auserid == $this->aduel->auserid2) {
                    if ($this->aduel->tool1numattempt2 == 0 || $this->aduel->tool1numattempt2 == $attempt->numattempt) {
                        $this->aduel->tool1numattempt2 = $attempt->numattempt;
                        $this->db->update_record( 'mmogame_am_aduel_pairs',
                        ['id' => $this->aduel->id, 'tool1numattempt2' => $attempt->numattempt]);
                    }
                }
            } else if ($data->subcommand == 'tool3' && $this->iswizard( $attempt->id)) {
                if ($this->auserid == $this->aduel->auserid1) {
                    if ($this->aduel->tool3numattempt1 == 0 || $this->aduel->tool3numattempt1 == $attempt->numattempt) {
                        $this->aduel->tool3numattempt1 = $attempt->numattempt;
                        $this->db->update_record( 'mmogame_am_aduel_pairs',
                        ['id' => $this->aduel->id, 'tool3numattempt1' => $attempt->numattempt]);
                    }
                } else if ($this->auserid == $this->aduel->auserid2) {
                    if ($this->aduel->tool3numattempt2 == 0 || $this->aduel->tool3numattempt2 == $attempt->numattempt) {
                        $this->aduel->tool3numattempt2 = $attempt->numattempt;
                        $this->db->update_record( 'mmogame_am_aduel_pairs',
                        ['id' => $this->aduel->id, 'tool3numattempt2' => $attempt->numattempt]);
                    }
                }
            }
        }

        $ret['timestart'] = $attempt != null ? $attempt->timestart : 0;
        $ret['timeclose'] = $attempt != null ? $attempt->timeclose : 0;
        $ret['aduel_attempts'] = $attempt != null ?
            $this->db->count_records_select( 'mmogame_quiz_attempts',
            'ginstanceid = ? AND numteam=? AND auserid=?',
            [$attempt->ginstanceid, $attempt->numteam, $attempt->auserid]) : 0;
        $ret['aduel_attempt'] = $attempt != null ? $attempt->numattempt : 0;

        $player = ( $this->aduel->auserid1 == $auserid ? 1 : 2);
        $ret['aduel_player'] = $player;

        if ($player == 2) {
            $info = $this->get_avatar_info( $this->aduel->auserid1);
            $ret['aduel_score'] = $info->sumscore;
            $ret['aduel_avatar'] = $info->avatar;
            $ret['aduel_nickname'] = $info->nickname;
            $ret['aduel_rank'] = $this->get_rank_alone( $this->aduel->auserid1, 'sumscore');
            $ret['aduel_percent'] = $info->percentcompleted;
            $ret['colors'] = implode( ',', $info->colors);     // Get the colors of oposite.
            $ret['tool1numattempt'] = $this->aduel->tool1numattempt2;
            $ret['tool2numattempt'] = $this->aduel->tool2numattempt2;
            $ret['tool3numattempt'] = $this->aduel->tool3numattempt2;
        } else {
            $ret['tool1numattempt'] = $this->aduel->tool1numattempt1;
            $ret['tool2numattempt'] = $this->aduel->tool2numattempt1;
            $ret['tool3numattempt'] = $this->aduel->tool3numattempt1;
        }

        $numattempt = $attempt !== false ? $attempt->numattempt : 0;
        $attemptid = $attempt !== false ? $attempt->id : 0;
        if ($player == 1 && $this->aduel->tool1numattempt1 == $numattempt
        || $player == 2 && $this->aduel->tool1numattempt2 == $numattempt) {
            $this->append_json_only2( $ret, $query, $attemptid);
        } else if ($player == 1 && $this->aduel->tool3numattempt1 == $numattempt
        || $player == 2 && $this->aduel->tool3numattempt2 == $numattempt) {
            $this->append_json_only1( $ret, $query, $attemptid);
        }

        if ($this->iswizard( $attempt->id)) {
            $ret['tool3'] = 1;
        }
    }

    public function iswizard( $attemptid) {
        return $attemptid % (2 * $this->numquestions) == 0;
    }

    protected function append_json_only2( &$ret, $query, $attemptid) {
        $correctid = $query->correctid;
        $ids = [];
        foreach ($query->answers as $answer) {
            if ($answer->id != $correctid) {
                $ids[] = $answer->id;
            }
        }

        $pos = $attemptid % count( $ids);
        $answerid2 = $ids[$pos];

        $count = $ret['answers'];
        for ($i = 1; $i <= $count; $i++) {
            $id = intval( $ret['answerid_'.$i]);
            if ($id != $correctid && $id != $answerid2) {
                $ret['answerid_'.$i] = '';
                $ret['answer_'.$i] = '';
            }
        }
    }

    protected function append_json_only1( &$ret, $query, $attemptid) {
        $correctid = $query->correctid;

        $count = $ret['answers'];
        for ($i = 1; $i <= $count; $i++) {
            $id = intval( $ret['answerid_'.$i]);
            if ($id != $correctid) {
                $ret['answerid_'.$i] = '';
                $ret['answer_'.$i] = '';
            }
        }
    }

    public function get_queries_aduel( $count, $mostused) {
        // Get the ids of all the queries.
        $ids = $this->qbank->get_queries_ids();
        if ($ids === false) {
            return false;
        }

        // Initializes data.
        $qs = [];
        foreach ($ids as $id) {
            $q = new stdClass();
            $q->id = $id;
            $q->qpercent = $q->qcountused = $q->ucountused  = $q->utimeerror = $q->uscore = $q->upercent = 0;

            $qs[$id] = $q;
        }

        $rinstance = $this->get_rinstance();
        $stat = $this->db->get_record_select( 'mmogame_aa_stats',
            'ginstanceid=? AND numgame=? AND auserid = ? AND queryid IS NULL',
            [$rinstance->id, $rinstance->numgame, $this->auserid]);

        // Computes statistics per question.
        $sids = implode( ',', $ids);
        $recs = $this->db->get_records_select( 'mmogame_aa_stats',
            "ginstanceid=? AND numgame=? AND auserid IS NULL AND queryid IN ($sids)",
            [$rinstance->id, $rinstance->numgame], null, 'id,queryid,percent,countused');
        foreach ($recs as $rec) {
            $q = $qs[$rec->queryid];
            $q->qpercent = $rec->percent;
            $q->qcountused = $rec->countused;
            $qs[$rec->queryid] = $q;
        }

        // Computes statistics per user.
        $recs = $this->db->get_records_select( 'mmogame_aa_stats',
            "ginstanceid=? AND numgame=? AND auserid = ? AND queryid IN ($sids)",
            [$rinstance->id, $rinstance->numgame, $this->auserid], null,
            'queryid,countused,countcorrect,counterror,timeerror,percent');
        foreach ($recs as $rec) {
            $q = $qs[$rec->queryid];
            $q->utimeerror = $rec->timeerror;
            $q->ucountused = $rec->countused;
            $q->upercent = $rec->percent;
            $q->uscore = $rec->countcorrect - 2 * $rec->counterror;
            $qs[$rec->queryid] = $q;
        }
        $map = [];
        $min = 0;
        foreach ($qs as $q) {
            if ($q->uscore < $min) {
                $min = $q->uscore;
            }
        }

        foreach ($qs as $q) {
            $key = sprintf( "%10d %10d %10d %5d %5d %10d %5d %10d",
                // If it has big negative score give priority to them.
                $q->uscore < 0 ? -$min + $q->uscore : 999999999,
                // If it has negative score more priority has the older question.
                $q->uscore < 0 ? $q->utimeerror : 0,
                // Less times used by user more priority has.
                $q->ucountused,
                // If question is easier than user sorts by distance.
                $q->qpercent < $stat->percent ? round( 100 * $stat->percent - 100 * $q->qpercent) : 0,
                // If question is more difficult than user sorts by distance.
                $q->qpercent > $stat->percent ? round( -100 * $stat->percent + 100 * $q->qpercent) : 0,
                // Prioritizes question that fewer times by everyone.
                round( 100 * $q->qcountused),
                rand(1, 9999),
                $q->id
            );
            $map[$key] = $q;
        }
        ksort( $map);
        $map2 = [];
        // Selects 3 * count of needed.
        foreach ($map as $key => $q) {
            if (count( $map2) >= 3 * $count) {
                break;
            }
            $key2 = sprintf( "%10.6f %10d", $q->qpercent, $q->id);
            $map2[$key2] = $q;
        }
        // Deleted 2 * count so to remain count.
        for ($i = 1; $i <= 2 * $count; $i++) {
            $key = array_rand( $map2, 1);
            unset( $map2[$key]);
        }
        ksort( $map2);
        $ret = [];
        foreach ($map2 as $key => $q) {
            $q2 = $this->qbank->load( $q->id, true);
            $ret[] = $q2;
        }

        // Update statistics.
        foreach ($ret as $q) {
            $this->qbank->update_stats( $this->auserid, null, $q->id, 1, 0, 0);
            $this->qbank->update_stats( null, null, $q->id, 1, 0, 0);
        }
        $this->qbank->update_stats( $this->auserid, null, null, count( $ret), 0, 0,
            ['countanswers' => count( $ids)]);

        return count( $ret) ? $ret : false;
    }

    public function get_queries_aduel_method1_blocks( $count, $mostused) {
        // Get the ids of all the queries.
        $ids = $this->qbank->get_queries_ids();
        if ($ids === false) {
            return false;
        }

        // Initializes data.
        $qs = [];
        foreach ($ids as $id) {
            $q = new stdClass();
            $q->id = $id;
            $q->qpercent = $q->qcountused = $q->ucountused = $q->ucountcorrect = $q->ucounterror = $q->utimeerror = null;

            $qs[$id] = $q;
        }

        // Computes statistics per question.
        $rinstance = $this->get_rinstance();
        $sids = implode( ',', $ids);
        $recs = $this->db->get_records_select( 'mmogame_aa_stats',
            "ginstanceid=? AND numgame=? AND auserid IS NULL AND queryid IN ($sids)",
            [$rinstance->id, $rinstance->numgame], null, 'id,queryid,percent,countused');
        foreach ($recs as $rec) {
            $q = &$qs[$rec->queryid];
            $q->qpercent = $rec->percent;
            $q->qcountused = $rec->countused;
        }

        // Computes statistics per user.
        $recs = $this->db->get_records_select( 'mmogame_aa_stats',
            "ginstanceid=? AND numgame=? AND auserid = ? AND queryid IN ($sids)",
            [$rinstance->id, $rinstance->numgame, $this->auserid], null,
            'queryid,countused,countcorrect,counterror, timeerror');
        foreach ($recs as $rec) {
            $q = &$qs[$rec->queryid];
            $q->ucountused = $rec->countused;
            $q->ucountcorrect = $rec->countcorrect;
            $q->ucounterror = $rec->counterror;
            $q->utimeerror = $rec->timeerror;
        }

        // Selects a question per block of questions.
        $map = $this->get_queries_aduel_blocks( $this->numquestions, $qs);

        $ret = [];
        foreach ($map as $id) {
            if (count( $ret) >= $count) {
                break;
            }
            $q = $this->qbank->load( $id, true);
            $ret[] = $q;
        }

        // Update statistics.
        foreach ($ret as $q) {
            $this->qbank->update_stats( $this->auserid, null, $q->id, 1, 0, 0);
            $this->qbank->update_stats( null, null, $q->id, 1, 0, 0);
        }
        $this->qbank->update_stats( $this->auserid, null, null, count( $ret), 0, 0);

        return count( $ret) ? $ret : false;
    }

    private function get_queries_aduel_blocks( $count, $qs) {
        $qsort = [];
        // I split question in groups dependly on percent on all users.
        foreach ($qs as $q) {
            $key = sprintf( '%10.7f %10d %10d', 1 - $q->qpercent, mt_rand( 0, 1000 * 1000 - 1), $q->id);
            $qsort[$key] = $q;
        }
        ksort( $qsort);

        $blocks = [];
        $block = [];
        $num = 0;
        $numblock = 1;
        $stop = round( $numblock * count( $qsort) / $count);
        foreach ($qsort as $q) {
            $block[] = $q;
            $num++;
            if ($num == $stop) {
                $blocks[] = $block;
                $block = [];
                $stop = round( (++$numblock) * count( $qsort) / $count);
            }
        }
        if (count( $block)) {
            $blocks[] = $block;
        }

        $ret = [];
        foreach ($blocks as $block) {
            $ret[] = $this->get_queries_aduel_blocks_one( $block);
        }

        return $ret;
    }

    public function get_queries_aduel_blocks_one( $block) {
        $t = [];

        foreach ($block as $q) {
            $r = mt_rand( 0, 1000 * 1000 - 1);
            $count = $q->ucountcorrect - $q->ucounterror + 10000;
            $key = sprintf( '%10d %10d %10d %10.2f %10d %10d',
                $count, $q->ucountused, $q->qcountused, $q->qpercent, $r, $q->id);
            $t[$key] = $q;
        }
        ksort( $t);

        foreach ($t as $rec) {
            return $rec->id;
        }

        return false;
    }
}
