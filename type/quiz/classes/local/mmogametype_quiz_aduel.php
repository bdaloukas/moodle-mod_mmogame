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
 * Duel quiz game type implementation for MMOGame.
 *
 * @package    mmogametype_quiz
 * @copyright  2024 Vasilis Daloukas
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mmogametype_quiz\local;

use coding_exception;
use dml_exception;
use mod_mmogame\local\mmogame;
use mod_mmogame\local\mode\mmogame_mode_aduel;
use mod_mmogame\local\database\mmogame_database;
use stdClass;

/**
 * Handles duel quiz attempts, answer submission and tool visibility.
 */
class mmogametype_quiz_aduel extends mmogametype_quiz_alone {
    /** @var int $numqueries: number of queries that contain one group of queries. */
    protected int $numqueries = 4;
    /** @var ?stdClass $aduel: ADuel object or false if no object yet. */
    protected ?stdClass $aduel = null;

    /** @var int $maxalone: maximum number of questions that a user can play without an opponent. */
    protected int $maxalone = 200;

    public const TOOL_5050 = 'tool1';
    public const TOOL_SKIP = 'tool2';
    public const TOOL_WIZARD = 'tool3';
    /**
     * Constructor.
     *
     * @param mmogame_database $db (the database)
     * @param stdClass $rgame (a record from table mmogame)
     * @throws coding_exception
     */
    public function __construct(mmogame_database $db, stdClass $rgame) {
        $rgame->usemultichoice = true;

        parent::__construct($db, $rgame);

        $this->timelimit = 300;
    }

    /**
     * Loads the aduel record from table mmogame_am_aduel_pairs.
     *
     * @param stdClass $attempt
     */
    public function set_attempt(stdClass $attempt): void {
        $this->aduel = $this->db->get_record_select('mmogame_am_aduel_pairs', 'id=?', [$attempt->numteam]);
    }

    /**
     * Tries to find an attempt of open games, otherwise creates a new attempt.
     *
     * @return ?stdClass (a new attempt of false if no attempt)
     * @throws coding_exception
     * @throws dml_exception
     */
    public function get_attempt(): ?stdClass {
        if ($this->rstate->state != MMOGAME_QUIZ_STATE_PLAY) {
            return null;
        }
        $newplayer1 = $newplayer2 = false;

        $this->aduel = mmogame_mode_aduel::get_aduel($this, $this->maxalone, $newplayer1, $newplayer2, true);
        if ($this->aduel === null) {
            $this->set_errorcode("no_rivals");
            return null;
        }
        if (!$newplayer1 && !$newplayer2) {
            $rec = mmogame_mode_aduel::get_attempt($this, $this->aduel);
            if ($rec !== null) {
                if ($rec->timestart == 0) {
                    $rec->timestart = time();
                    $this->db->update_record(
                        'mmogame_quiz_attempts',
                        ['id' => $rec->id, 'timestart' => $rec->timestart]
                    );
                }
                return $rec;
            } else {
                // Have to close this.
                $this->db->update_record(
                    'mmogame_am_aduel_pairs',
                    ['id' => $this->aduel->id, 'isclosed1' => 1, 'isclosed2' => 1, 'timeclose' => time()]
                );
                return $this->get_attempt_new1();
            }
        }
        if ($newplayer1) {
            return $this->get_attempt_new1();
        } else {
            return $this->get_attempt_new2();
        }
    }

    /**
     * Creates a new attempt for the first player. Also select which question will be contained in the attempt.
     *
     * @return ?stdClass (a new attempt of false if no attempt)
     * @throws coding_exception
     * @throws dml_exception
     */
    protected function get_attempt_new1(): ?stdClass {
        $num = $this->compute_next_numattempt('mmogame_quiz_attempts', $this->get_auserid());
        $a = $this->qbank->get_attempt_new($this->numqueries, $num);
        if ($a === null) {
            $this->set_errorcode('no_queries');
            return null;
        }
        $queries = $a['queries'];
        unset($a['queries']);
        $ret = 0;
        $numqueryround = 1;
        foreach ($queries as $queryid) {
            $a['numattempt'] = $num++;
            $a['numqueryround'] = $numqueryround++;
            $a['queryid'] = $queryid;
            $a['numteam'] = $this->aduel->id;
            $a['layout'] = $this->qbank->get_layout_queryid($queryid);
            $a['attemptkey'] = $this->createkey();
            $id = $this->db->insert_record($this->get_table_attempts(), $a);
            if ($ret === 0) {
                $ret = $id;
            }
        }

        return $this->db->get_record_select($this->get_table_attempts(), 'id=?', [$ret]);
    }


    /**
     * Creates a new attempt for the second player.
     *
     * @return ?stdClass (a new attempt of false if no attempt)
     */
    protected function get_attempt_new2(): ?stdClass {
        $table = 'mmogame_quiz_attempts';

        $recs = $this->db->get_records_select(
            $table,
            'mmogameid=? AND numgame=? AND numteam=? AND auserid=?',
            [$this->rgame->id, $this->rgame->numgame, $this->aduel->id, $this->aduel->auserid1],
            'numattempt'
        );
        $ret = 0;
        $numattempt = $this->compute_next_numattempt('mmogame_quiz_attempts', $this->aduel->auserid2);
        foreach ($recs as $rec) {
            $a = [
                'mmogameid' => $this->get_id(),
                'auserid' => $this->aduel->auserid2, 'queryid' => $rec->queryid, 'numgame' => $rec->numgame,
                'timestart' => 0, 'numteam' => $rec->numteam,
                'numattempt' => $numattempt++,
                'numqueryround' => $rec->numqueryround, 'layout' => $rec->layout, 'timeanswer' => 0,
                'attemptkey' => mmogame::createkey(),
            ];
            $a['timeclose'] = $ret == 0 ? time() + $this->aduel->timelimit : 0;
            $id = $this->db->insert_record($table, $a);
            if ($ret == 0) {
                // Returns the first id.
                $ret = $id;
            }
        }

        return $this->db->get_record_select($table, 'id=?', [$ret]);
    }

    /**
     * Saves information about the user's answer.
     *
     * @param stdClass $attempt The quiz attempt object.
     * @param stdClass $query The query object related to the quiz.
     * @param ?string $useranswer The user's answer as a string.
     * @param array $ret Output array for additional information.
     */
    public function set_answer(
        stdClass $attempt,
        stdClass $query,
        ?string $useranswer,
        array &$ret
    ): void {
        parent::set_answer($attempt, $query, $useranswer, $ret);

        $ret['iscorrect'] = $attempt->iscorrect;
        if ($this->auserid == $this->aduel->auserid1) {
            $recs = $this->db->get_records_select(
                'mmogame_quiz_attempts',
                'mmogameid=? AND numgame=? AND numteam=? AND timeanswer = 0 AND auserid=?',
                [$attempt->mmogameid, $attempt->numgame, $this->aduel->id, $this->auserid],
                'id'
            );
            $rec = reset($recs);
            if ($rec === false) {
                // We finished.
                $rankvalue1 = $this->get_rankvalue1($this->auserid);
                $this->db->update_record(
                    'mmogame_am_aduel_pairs',
                    ['id' => $this->aduel->id, 'isclosed1' => 1, 'rankvalue1' => $rankvalue1]
                );
            }

            return;
        }

        // Auser2.

        // Adjust the attempt grade if negative.
        if ($attempt->grade < 0) {
            $attempt->grade = 0;
        }
        $opposite = $this->db->get_record_select(
            'mmogame_quiz_attempts',
            'mmogameid=? AND numgame=? AND numteam=? AND numqueryround=? AND auserid = ?',
            [$attempt->mmogameid, $attempt->numgame, $this->aduel->id, $attempt->numqueryround, $this->aduel->auserid1]
        );
        if ($opposite !== null) {
            if ($attempt->iscorrect) {
                // Correct answer (auserid2). Check the answer of opposite. If is wrong duplicate my points.
                // When I use tools no duplication of grade.
                if ((int)$opposite->iscorrect === 0 && (int)$attempt->tools === 0) {
                    $attempt->grade *= 2;
                    $ret['addgrade'] = '+' . $attempt->grade;
                    $this->db->update_record(
                        'mmogame_quiz_attempts',
                        ['id' => $attempt->id, 'grade' => $attempt->grade]
                    );
                    $this->qbank->update_grades($attempt->auserid, $attempt->grade, 0);
                }
            } else if ($attempt->iscorrect == 0) {
                // Check the answer of opposite. If is right duplicate other points.
                if ($opposite->iscorrect) {
                    if ($opposite->tools === 0) {
                        $opposite->grade *= 2;
                        $this->db->update_record(
                            'mmogame_quiz_attempts',
                            ['id' => $opposite->id, 'grade' => $opposite->grade]
                        );
                    }
                    $this->qbank->update_grades($opposite->auserid, $opposite->grade, 0);
                }
            }
        }

        $recs = $this->db->get_record_select(
            'mmogame_quiz_attempts',
            'mmogameid =? AND numgame=? AND numteam=? AND timeanswer = 0 AND auserid=?',
            [$attempt->mmogameid, $attempt->numgame, $this->aduel->id, $this->aduel->auserid2],
            'id'
        );
        if ($recs === null) {
            // We finished.
            $this->db->update_record('mmogame_am_aduel_pairs', ['id' => $this->aduel->id, 'isclosed2' => 1]);
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
        $query = parent::append_json($ret, $attempt);

        $auserid = $this->get_auserid();

        if ($this->aduel === null || $query === null) {
            return null;
        }

        if ($subcommand === self::TOOL_5050) {
            $this->update_tool( $attempt, MMOGAME_QUIZ_TOOL_5050);
        } else if ($subcommand == self::TOOL_WIZARD) {
            $this->update_tool( $attempt, MMOGAME_QUIZ_TOOL_WIZARD);
        }

        $ret['time'] = round(1000 * microtime(true));
        $ret['timestart'] = $attempt != null ? $attempt->timestart : 0;
        $ret['timeclose'] = $attempt != null ? $attempt->timeclose : 0;
        $ret['aduelAttempt'] = $attempt != null ? $attempt->numattempt : 0;

        if ($this->aduel == null) {
            return null;
        }

        $player = intval($this->aduel->auserid1) === $auserid ? 1 : 2;
        $ret['aduelPlayer'] = $player;

        if ($player == 2) {
            $info = $this->get_avatar_info($this->aduel->auserid1);
            $ret['aduelGrade'] = $info->grade;
            $ret['aduelAvatar'] = $info->avatar;
            $ret['aduelNickname'] = $info->nickname;
            $ret['aduelRank'] = $this->get_rank($info->grade, 'grade');
            $ret['aduelCountMastered'] = $info->countmastered;
            $ret['aduelRankMastered'] = $this->get_rank($info->countmastered, 'countmastered');
            $ret['colors'] = implode(',', $info->colors);     // Get the colors of opposite.
        }

        $ret[self::TOOL_5050] = $this->isvisibletool(MMOGAME_QUIZ_TOOL_5050, $attempt) ? 1 : 0;
        $ret[self::TOOL_SKIP] = $this->isvisibletool(MMOGAME_QUIZ_TOOL_SKIP, $attempt) ? 1 : 0;
        $ret[self::TOOL_WIZARD] = $this->isvisibletool(MMOGAME_QUIZ_TOOL_WIZARD, $attempt) ? 1 : 0;

        $attemptid = $attempt !== false ? $attempt->id : 0;
        if (
            $player == 1 && ($this->aduel->tools1 & MMOGAME_QUIZ_TOOL_5050)
            || $player == 2 && ($this->aduel->tools2 & MMOGAME_QUIZ_TOOL_5050)
        ) {
            if ($subcommand == self::TOOL_5050) {
                $this->append_json_5050($ret, $query, $attemptid);
            }
        } else if (
            $player == 1 && ($this->aduel->tools1 & MMOGAME_QUIZ_TOOL_WIZARD)
            || $player == 2 && ($this->aduel->tools2 & MMOGAME_QUIZ_TOOL_WIZARD)
        ) {
            if ($subcommand == self::TOOL_WIZARD) {
                $this->append_json_wizard($ret, $query);
            }
        }

        return null;
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

        $pos = $attemptid % count($ids);
        $answerid2 = $ids[$pos];

        $answers = $answerids = [];
        $count = count($ret['answers']);
        for ($i = 0; $i < $count; $i++) {
            $id = $ret['answerids'][$i];
            if ($id == $correctid || $id == $answerid2) {
                $answers[] = $ret['answers'][$i];
                $answerids[] = $ret['answerids'][$i];
            }
        }

        $ret['answers'] = $answers;
        $ret['answerids'] = $answerids;
    }

    /**
     * Adds wizard-tool data to the response array.
     *
     * @param array $ret (returns info about the current attempt)
     * @param object $query
     */
    protected function append_json_wizard(array &$ret, object $query): void {
        $correctid = $query->correctid;

        $count = count($ret['answers']);
        for ($i = 0; $i < $count; $i++) {
            $id = $ret['answerids'][$i];
            if ($id == $correctid) {
                $ret['answers'] = [$ret['answers'][$i]];
                $ret['answerids'] = [$ret['answerids'][$i]];
                return;
            }
        }
    }

    /**
     * Updates the database and array $ret about the correctness of user's answer
     *
     * @param array $ret
     * @param ?string $attemptkey
     * @param ?string $answer
     * @param string $subcommand
     * @return ?stdClass: the attempt
     */
    public function set_answer_mode(
        array &$ret,
        ?string $attemptkey,
        ?string $answer,
        string $subcommand = ''
    ): ?stdClass {
        $attempt = parent::set_answer_mode($ret, $attemptkey, $answer, $subcommand);

        $aduel = $this->aduel;

        $player = ($aduel->auserid1 == $this->auserid ? 1 : 2);
        $ret['aduelPlayer'] = $player;

        if ($subcommand === self::TOOL_SKIP) {
            $field = 'tool2numattempt' . $player;
            if ($aduel->$field == 0) {
                $this->db->update_record('mmogame_am_aduel_pairs', ['id' => $aduel->id, $field => $attempt->numattempt]);
                $aduel->$field = $attempt->numattempt;
            }

            if ($aduel->$field != 0 && $aduel->$field != null) {
                $ret[self::TOOL_SKIP] = $aduel->$field;
            }
        }

        if ($aduel->auserid1 === $this->auserid) {
            return null;
        }

        $attempt1 = $this->db->get_record_select(
            'mmogame_quiz_attempts',
            'mmogameid=? AND auserid=? AND numteam=? AND numqueryround=?',
            [$aduel->mmogameid, $aduel->auserid1, $aduel->id, $attempt->numqueryround]
        );

        if ($attempt1 !== null) {
            if ($aduel->auserid2 == $this->auserid) {
                $ret['aduelIscorrect'] = $attempt1->iscorrect;
                $ret['aduelUseranswer'] = $attempt1->useranswer;
            }

            $query = $this->qbank->load($attempt->queryid);
            $ret['correct'] = $query->concept;
        }
        if ($aduel->isclosed2) {
            $ret['endofgame'] = 1;
        }
        $info = $this->get_avatar_info($aduel->auserid1);
        $ret['aduelGrade'] = $info->grade;
        $ret['aduelRank'] = $this->get_rank($info->grade, 'grade');
        $ret['aduelMastered'] = $info->countmastered;
        $ret['aduelMasteredRank'] = $this->get_rank($info->countmastered, 'countmastered');

        return $attempt;
    }

    /**
     *  Check if a tool have to be visible or not
     * @param int $tool
     * @param stdClass $attempt
     * @return bool
     */
    private function isvisibletool(int $tool, stdClass $attempt): bool {
        if ($tool === MMOGAME_QUIZ_TOOL_WIZARD) {
            return $attempt->id % 8 == 0;
        }

        if ($tool === MMOGAME_QUIZ_TOOL_5050) {
            return $attempt->id % 8 == 4;
        }

        if ($tool === MMOGAME_QUIZ_TOOL_SKIP) {
            return $attempt->id % 8 == 6;
        }

        return false;
    }

    /**
     * Return the value used for comparing users to find the nearest one
     *
     * @param int $auserid
     * @return ?float
     */
    public function get_rankvalue1(int $auserid): ?float {
        $rgrade = $this->get_rgrade($auserid);

        $name = $this->get_selection()->get_field_rankvalue1();

        return $rgrade !== null ? $rgrade->$name : null;
    }

    private function update_tool(?stdClass $attempt, int $tool) {
        $player = ($this->auserid == $this->aduel->auserid2) ? 2 : 1;

        $name = 'tools'.($player == 2 ? 2 : 1);
        if ($this->aduel->$name & $tool) {
            return;
        }

        // First user press tool.
        $this->aduel->$name |= $tool;
        $this->db->update_record(
            'mmogame_am_aduel_pairs',
            ['id' => $this->aduel->id, $name => $this->aduel->$name]
        );
        $attempt->tools |= $tool;
        $this->db->update_record(
            'mmogame_quiz_attempts',
            ['id' => $attempt->id, 'tools' => $attempt->tools],
        );
    }
}
