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
 * @package    mmogametype_quiz
 * @copyright  2024 Vasilis Daloukas
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mmogametype_quiz\local;

use mod_mmogame\local\model\mmogame_model_aduel;
use mod_mmogame\local\database\mmogame_database;
use stdClass;

/** Identifier the state for "play" of model Aduel */
const STATE_PLAY = 1;

/**
 * The class mmogame_quiz_aduel play the game Quiz (Aduel).
 */
class mmogametype_quiz_aduel extends mmogametype_quiz_alone {
    /** @var int $numquestions: number of questions that contain one group of questions. */
    protected int $numquestions;
    /** @var ?stdClass $aduel: ADuel object or false if no object yet. */
    protected ?stdClass $aduel = null;

    /** @var int $maxalone: maximum number of questions that a user can play withoyt an oponent. */
    protected int $maxalone = 200;

    /**
     * Constructor.
     *
     * @param mmogame_database $db (the database)
     * @param stdClass $rgame (a record from table mmogame)
     */
    public function __construct(mmogame_database $db, stdClass $rgame) {
        $rgame->usemultichoice = true;

        parent::__construct( $db, $rgame);

        $this->numquestions = 4;
        $this->timelimit = 300;

        $this->callupdategrades = true;
    }

    /**
     * Loads the aduel record from table mmogame_am_aduel_pairs.
     *
     * @param stdClass $attempt
     */
    public function set_attempt(stdClass $attempt): void {
        $this->aduel = $this->db->get_record_select( 'mmogame_am_aduel_pairs', 'id=?', [$attempt->numteam]);
    }

    /**
     * Tries to find an attempt of open games, otherwise creates a new attempt.
     *
     * @return ?stdClass (a new attempt of false if no attempt)
     */
    public function get_attempt(): ?stdClass {
        if ($this->rstate->state != STATE_PLAY) {
            return null;
        }

        $newplayer1 = $newplayer2 = false;
        for ($step = 1; $step <= 2; $step++) {
            $this->aduel = mmogame_model_aduel::get_aduel( $this, $this->maxalone,  $newplayer1, $newplayer2);
            if ($this->aduel === null) {
                $this->set_errorcode( ERRORCODE_ADUEL_NO_RIVALS);
                return null;
            }

            if (!$newplayer1 && !$newplayer2) {
                $rec = mmogame_model_aduel::get_attempt( $this, $this->aduel);
                if ($rec !== null) {
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

        return null;
    }

    /**
     * Creates a new attempt for the first player. Also select which question will be contained in the attempt.
     *
     * @return ?stdClass (a new attempt of false if no attempt)
     */
    protected function get_attempt_new1(): ?stdClass {
        $queries = $this->get_queries_aduel( 4);
        if ($queries === null) {
            return null;
        }

        $num = 0;
        $ret = 0;
        $a = [];
        foreach ($queries as $query) {
            $a['mmogameid'] = $this->rgame->id;
            $a['numgame'] = $this->rgame->numgame;
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


    /**
     * Creates a new attempt for the second player.
     *
     * @return ?stdClass (a new attempt of false if no attempt)
     */
    protected function get_attempt_new2(): ?stdClass {
        $table = 'mmogame_quiz_attempts';

        $recs = $this->db->get_records_select( $table, 'mmogameid=? AND numgame=? AND numteam=? AND auserid=?',
            [$this->rgame->id, $this->rgame->numgame, $this->aduel->id, $this->aduel->auserid1], 'numattempt');
        $ret = 0;
        foreach ($recs as $rec) {
            $a = ['mmogameid' => $this->get_id(),
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

    /**
     * Saves information about the user's answer.
     *
     * @param stdClass $attempt The quiz attempt object.
     * @param stdClass $query The query object related to the quiz.
     * @param ?string $useranswer The user's answer as a string.
     * @param ?int $useranswerid Optional user answer ID.
     * @param bool $autograde Whether autograding is enabled.
     * @param array $ret Output array for additional information.
     */
    public function set_answer(stdClass $attempt, stdClass $query, ?string $useranswer, ?int $useranswerid,
                               bool $autograde, array &$ret): void {
        parent::set_answer( $attempt, $query, $useranswer, $useranswerid, $autograde, $ret);

        $ret['iscorrect'] = $attempt->iscorrect;
        if ($this->auserid == $this->aduel->auserid1) {
            $recs = $this->db->get_records_select( 'mmogame_quiz_attempts',
                'mmogameid=? AND numgame=? AND numteam=? AND timeanswer = 0 AND auserid=?',
                [$attempt->mmogameid, $attempt->numgame, $this->aduel->id, $this->auserid], 'id');
            $rec = reset($recs);
            if ($rec === false) {
                // We finished.
                $grade = $this->db->get_record_select('mmogame_aa_grades',
                    'mmogameid=? AND numgame=? AND auserid=?',
                    [$attempt->mmogameid, $attempt->numgame, $this->auserid]);
                $percent = $grade !== null ? $grade->percent : 0;
                $this->db->update_record( 'mmogame_am_aduel_pairs',
                    ['id' => $this->aduel->id, 'isclosed1' => 1, 'percent' => $percent]);
            }

            return;
        }

        // Adjust the attempt score if negative.
        if ($attempt->score < 0) {
            $attempt->score = 0;
        }
        $opposite = $this->db->get_record_select( 'mmogame_quiz_attempts',
            'mmogameid=? AND numgame=? AND numteam=? AND numattempt=? AND auserid = ?',
            [$attempt->mmogameid, $attempt->numgame, $this->aduel->id, $attempt->numattempt, $this->aduel->auserid1]
        );
        if ($opposite !== false) {
            if ($attempt->iscorrect == 1) {
                // Check the answer of opposite. If is wrong duplicate my points.
                if ($opposite->iscorrect == 0) {
                    $attempt->score *= 2;
                    if ($this->aduel->tool3numattempt2 == $attempt->numattempt) {
                        // The wizard tool.
                        $attempt->score -= 2;
                    }
                    $ret['addscore'] = '+'.$attempt->score;
                    $this->db->update_record( 'mmogame_quiz_attempts',
                        ['id' => $attempt->id, 'score' => $attempt->score]);
                    $this->qbank->update_grades( $attempt->auserid, $attempt->score, 0, 0);
                }
            } else if ($attempt->iscorrect == 0) {
                // Check the answer of opposite. If is right duplicate other points.
                if ($opposite->iscorrect) {
                    $this->db->update_record( 'mmogame_quiz_attempts',
                        ['id' => $opposite->id, 'score' => 2 * $opposite->score]);
                    $this->qbank->update_grades( $opposite->auserid, $opposite->score, 0, 0);
                }
            }
        } else if ($this->aduel->tool3numattempt2 == $attempt->numattempt) {
            // The wizard tool.
            $attempt->score -= 1;
            $ret['addscore'] = '+'.$attempt->score;
            $this->db->update_record( 'mmogame_quiz_attempts',
                ['id' => $attempt->id, 'score' => $attempt->score]);
            $this->qbank->update_grades( $attempt->auserid, -1, 0, 0);
        }

        $recs = $this->db->get_record_select( 'mmogame_quiz_attempts',
            'mmogameid =? AND numgame=? AND numteam=? AND timeanswer = 0 AND auserid=?',
            [$attempt->mmogameid, $attempt->numgame, $this->aduel->id, $this->aduel->auserid2], 'id');
        if ($recs === null) {
            // We finished.
            $this->db->update_record( 'mmogame_am_aduel_pairs', ['id' => $this->aduel->id, 'isclosed2' => 1]);
        }
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
        $query = parent::append_json( $ret, $attempt);

        $auserid = $this->get_auserid();

        if ($this->aduel === null || $query === null) {
            return null;
        }

        if ($subcommand === 'tool1') {
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
        } else if ($subcommand == 'tool3' && $this->iswizard( $attempt->id)) {
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

        $ret['time'] = round( 1000 * microtime( true));
        $ret['timestart'] = $attempt != null ? $attempt->timestart : 0;
        $ret['timeclose'] = $attempt != null ? $attempt->timeclose : 0;
        $ret['aduelAttempt'] = $attempt != null ? $attempt->numattempt : 0;

        $player = ( $this->aduel === null || intval($this->aduel->auserid1) === $auserid ? 1 : 2);
        $ret['aduelPlayer'] = $player;

        if ($this->aduel == null) {
            return null;
        }
        if ($player == 2) {
            $info = $this->get_avatar_info( $this->aduel->auserid1);
            $ret['aduelScore'] = $info->sumscore;
            $ret['aduelAvatar'] = $info->avatar;
            $ret['aduelNickname'] = $info->nickname;
            $ret['aduelRank'] = $this->get_rank( $info->sumscore, 'sumscore');
            $ret['aduelPercent'] = $info->percent;
            $ret['aduelPercentRank'] = $this->get_rank($info->percent, 'percent');
            $ret['colors'] = implode( ',', $info->colors);     // Get the colors of opossite.
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
            $this->append_json_5050( $ret, $query, $attemptid);
        } else if ($player == 1 && $this->aduel->tool3numattempt1 == $numattempt
        || $player == 2 && $this->aduel->tool3numattempt2 == $numattempt) {
            $this->append_json_wizard( $ret, $query);
        }

        if ($this->iswizard( $attempt->id)) {
            $ret['tool3'] = 1;
        }

        return null;
    }

    /**
     * Return true if this attempt will have the wizard tool.
     *
     * @param int $attemptid
     * @return bool (true or false)
     */
    public function iswizard(int $attemptid): bool {
        return $attemptid % (2 * $this->numquestions) == 0;
    }

    /**
     * Saves to array $ret information about the $attempt (only when using tool1=50x50).
     *
     * @param array $ret (returns info about the current attempt)
     * @param stdClass $query
     * @param int $attemptid
     */
    protected function append_json_5050(array &$ret, stdClass $query, int $attemptid): void {
        $correctid = $query->correctid;
        $ids = [];
        foreach ($query->answers as $answer) {
            if ($answer->id != $correctid) {
                $ids[] = $answer->id;
            }
        }

        $pos = $attemptid % count( $ids);
        $answerid2 = $ids[$pos];

        $answers = $answerids = [];
        $count = count( $ret['answers']);
        for ($i = 0; $i < $count; $i++) {
            $id = $ret[ 'answerids'][ $i];
            if ($id== $correctid || $id == $answerid2) {
                $answers[] = $ret['answers'][$i];
                //$answer[] = $ret['answer'][$i];
                $answerids[] = $ret['answerids'][$i];
            }
        }

        $ret[ 'answers' ] = $answers;
        //$ret[ 'answer' ] = $answer;
        $ret[ 'answerids' ] = $answerids;
    }

    /**
     * Saves to array $ret informations about the $attempt (only for wizard tool).
     *
     * @param array $ret (returns info about the current attempt)
     * @param object $query
     */
    protected function append_json_wizard(array &$ret, object $query): void {
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

    /**
     * Return the queries for ADuel.
     *
     * @param int $count (how many)
     * @return ?array or false
     */
    public function get_queries_aduel(int $count): ?array {
        // Get the ids of all the queries.
        $ids = $this->qbank->get_queries_ids();
        if ($ids === null || count( $ids) == 0) {
            return null;
        }

        // Initializes data.
        $qs = [];
        foreach ($ids as $id) {
            $q = new stdClass();
            $q->id = $id;
            $q->qpercent = $q->qcountused = $q->ucountused  = $q->utimeerror = $q->uscore = $q->upercent = 0;

            $qs[$id] = $q;
        }

        $grade = $this->get_grade( $this->auserid);

        // Computes statistics per question.
        [$insql, $inparams] = $this->db->get_in_or_equal( $ids);
        $recs = $this->db->get_records_select( 'mmogame_aa_stats',
            "mmogameid=? AND numgame=? AND auserid IS NULL AND queryid $insql",
            array_merge( [$this->rgame->id, $this->rgame->numgame], $inparams),
            '', 'id,queryid,percent,countused');
        foreach ($recs as $rec) {
            $q = $qs[$rec->queryid];
            $q->qpercent = $rec->percent;
            $q->qcountused = $rec->countused;
            $qs[$rec->queryid] = $q;
        }

        // Computes statistics per question and user.
        $recs = $this->db->get_records_select( 'mmogame_aa_stats',
            "mmogameid=? AND numgame=? AND auserid = ? AND queryid $insql",
            array_merge([$this->rgame->id, $this->rgame->numgame, $this->auserid], $inparams),
            '', 'queryid,countused,countcorrect,counterror,timeerror,percent,islastcorrect');

        $corrects = 0;
        foreach ($recs as $rec) {
            $q = $qs[$rec->queryid];
            $q->utimeerror = $rec->timeerror;
            $q->ucountused = $rec->countused;
            $q->upercent = $rec->percent;
            $q->uscore = $rec->countcorrect - 2 * $rec->counterror;
            $qs[$rec->queryid] = $q;

            if ($rec->islastcorrect) {
                $corrects++;
            }
        }
        $map = [];
        $min = 0;
        foreach ($qs as $q) {
            if ($q->uscore < $min) {
                $min = $q->uscore;
            }
        }

        $percent = $grade !== null ? $grade->percent : 0;
        foreach ($qs as $q) {
            $key = sprintf( "%10d %10d %10d %5d %10d %5d %10d",
                // If it has big negative score give priority to them.
                $q->uscore < 0 ? -$min + $q->uscore : 999999999,
                // If it has negative score more priority has the older question.
                $q->uscore < 0 ? $q->utimeerror : 0,
                // Fewer times used by user higher priority has.
                $q->ucountused,
                // Sorts by distance.
                $q->qpercent < $percent ? abs( round( 100 * $percent - 100 * $q->qpercent)) : 0,
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
        foreach ($map as $q) {
            if (count( $map2) >= 3 * $count) {
                break;
            }
            $key2 = sprintf( "%10.6f %10d", $q->qpercent, $q->id);
            $map2[$key2] = $q;
        }
        // Remove items so to remain $count.
        while (count( $map2) > $count) {
            $key = array_rand( $map2);
            unset( $map2[$key]);
        }

        ksort( $map2);
        $ret = [];
        foreach ($map2 as $q) {
            $q2 = $this->qbank->load( $q->id);
            $ret[] = $q2;
        }

        // Update statistics.
        foreach ($ret as $q) {
            $this->qbank->update_stats( $this->auserid, null, $q->id, 1, 0, 0);
            $this->qbank->update_stats( null, null, $q->id, 1, 0, 0);
        }
        $this->db->update_record( 'mmogame_aa_grades',
            ['id' => $grade->id, 'countquestions' => count( $ids), 'percent' => $corrects / count($ids)]);

        return count( $ret) ? $ret : null;
    }

    /**
     * Updates the database and array $ret about the correctness of user's answer
     *
     * @param array $ret
     * @param ?int $attemptid
     * @param ?string $answer
     * @param ?int $answerid
     * @param string $subcommand
     * @return ?stdClass: the attempt
     */
    public function set_answer_model(array &$ret, ?int $attemptid, ?string $answer, ?int $answerid = null,
                                     string $subcommand = ''): ?stdClass {
        $attempt = parent::set_answer_model($ret, $attemptid, $answer, $answerid, $subcommand);

        $aduel = $this->aduel;

        $player = ( $aduel->auserid1 == $this->auserid ? 1 : 2);
        $ret['aduelPlayer'] = $player;

        if ($subcommand === 'tool2') {
            $field = 'tool2numattempt'.$player;
            if ($aduel->$field == 0) {
                $this->db->update_record( 'mmogame_am_aduel_pairs', ['id' => $aduel->id, $field => $attempt->numattempt]);
                $aduel->$field = $attempt->numattempt;
            }

            if ($aduel->$field != 0 && $aduel->$field != null) {
                $ret['tool2'] = $aduel->$field;
            }
        }

        if ($aduel->auserid1 === $this->auserid) {
            return null;
        }

        $attempt1 = $this->db->get_record_select( 'mmogame_quiz_attempts',
            'mmogameid=? AND auserid=? AND numteam=? AND numattempt=?',
            [$aduel->mmogameid, $aduel->auserid1, $aduel->id, $attempt->numattempt]);
        if ($attempt1 !== false) {
            if ($aduel->auserid2 == $this->auserid) {
                $ret['aduelIscorrect'] = $attempt1->iscorrect;
                $ret['aduelUseranswer'] = $attempt1->useranswer;
            }

            $query = $this->qbank->load( $attempt->queryid);
            $ret['correct'] = $query->concept;
        }
        if ($aduel->isclosed2) {
            $ret['endofgame'] = 1;
        }
        $info = $this->get_avatar_info( $aduel->auserid1);
        $ret['aduelScore'] = $info->sumscore;
        $ret['aduelRank'] = $this->get_rank( $info->sumscore, 'sumscore');
        $ret['aduelPercent'] = $info->percent;
        $ret['aduelPercentRank'] = $this->get_rank($info->percent, 'percent');

        return $attempt;
    }
}
