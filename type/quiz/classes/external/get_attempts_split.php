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

namespace mmogametype_quiz\external;

use coding_exception;
use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_multiple_structure;
use core_external\external_single_structure;
use core_external\external_value;
use dml_exception;
use invalid_parameter_exception;
use mod_mmogame\local\database\mmogame_database_moodle;
use mod_mmogame\local\mmogame;

/**
 * External function for starting a new attempt or continuing the last attempt.
 *
 * @package   mmogametype_quiz
 * @copyright 2024 Vasilis Daloukas
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class get_attempts_split extends external_api {
    /**
     * Returns description of method parameters
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'sessionkeys' => new external_value(PARAM_TEXT, 'Comma-separated session keys'),
            'avatarids' => new external_value(PARAM_TEXT, 'Comma-separated avatar IDs', VALUE_DEFAULT, ''),
        ]);
    }

    /**
     * Implements the service logic.
     *
     * @param string $sessionkeys
     * @param ?string $avatarids
     * @return array
     * @throws dml_exception
     * @throws coding_exception
     * @throws invalid_parameter_exception
     */
    public static function execute(
        string $sessionkeys,
        ?string $avatarids = null
    ): array {
        // Validate the parameters.
        self::validate_parameters(self::execute_parameters(), [
            'sessionkeys' => $sessionkeys,
            'avatarids' => $avatarids,
        ]);

        $sessionkeys = explode(',', $sessionkeys);
        $avatarids = $avatarids !== null && $avatarids !== '' ? explode(',', $avatarids) : null;

        if (null !== $avatarids && count($avatarids) !== count($sessionkeys)) {
            return self::error('invalid_avatarids');
        }

        $db = new mmogame_database_moodle();
        $mmogameid = null;
        $ausers = [];
        foreach ($sessionkeys as $sessionkey) {
            $auser = mmogame::get_auser_from_sessionkey($db, $sessionkey);
            if ($auser === null) {
                return self::error('no_user');
            }
            if ($mmogameid === null) {
                $mmogameid = (int)$auser->mmogameid;
            } else if ($mmogameid != (int)$auser->mmogameid) {
                return self::error('invalid_sessionkey');
            }

            $ausers[] = $auser;
        }
        $mmogame = mmogame::create($db, $mmogameid);
        $state = $mmogame->get_state();
        $statetime = $mmogame->get_statetime();
        if (count($ausers) == 0 || $state == 0 || $statetime == 0) {

            return [
                'avatars' => [],
                'attempts' => [],
                'attemptkeys' => [],
                'attemptqueryids' => [],
                'numattempts' => [],
                'querydefinitions' => [],
                'querytips' => [],
                'queryanswerids' => [],
                'answertexts' => [],
                'aduelavatars' => [],
                'aduelcorrects' => [],
                'queryanswerids0' => [],
                'grades' => [],
                'countqueries' => 0,
                'countmastered' => [],
                'islastcorrect' => [],
                'ranks' => [],
                'queryranks' => [],
                'hasidea' => 0,
                'state' => $state,
                'statetime' => $statetime,
            ];
        }
        if ($avatarids !== null) {
            foreach ($sessionkeys as $pos => $sessionkey) {
                $info = $mmogame->get_avatar_info($ausers[$pos]->id, false, true);
                $db->update_record(
                    'mmogame_aa_grades',
                    ['id' => $info->id, 'avatarid' => $avatarids[$pos]]
                );
            }
        }

        for (;;) {
            $numgame = 0;
            $attemptids = $attemptqueryids = $attemptnums = $definitions = $tips =
            $answerids = $answertexts = $attemptkeys = $queryanswerids0 = [];

            $countmastered = [];
            $islastcorrect = [];
            $queryranks = [];
            $retry = self::get_attempts(
                $mmogame,
                $ausers,
                $numgame,
                $attemptids,
                $attemptkeys,
                $attemptqueryids,
                $attemptnums,
                $definitions,
                $tips,
                $answerids,
                $answertexts,
                $queryanswerids0,
                $countmastered,
                $islastcorrect,
                $queryranks
            );
            if (!$retry) {
                // Have to compute again. It is not computed in get_attempts.
                break;
            }
        }

        $auserids = [];
        foreach ($ausers as $auser) {
            $auserids[] = $auser->id;
        }

        [$grades, $ranks, $avatars] = $mmogame->get_selection()->compute_ranks($auserids);

        return ['avatars' => $avatars, 'attemptkeys' => $attemptkeys,
            'attemptqueryids' => $attemptqueryids,
            'numattempts' => $attemptnums, 'querydefinitions' => $definitions,
            'querytips' => $tips, 'queryanswerids' => $answerids, 'answertexts' => $answertexts,
            'queryanswerids0' => $queryanswerids0, 'grades' => $grades,
            'countqueries' => $mmogame->get_rstate()->countqueries, 'countmastered' => $countmastered,
            'islastcorrect' => $islastcorrect,
            'ranks' => $ranks, 'queryranks' => $queryranks, 'hasidea' => 0, 'state' => $state, 'statetime' => 0];
    }

    /**
     * Describe the return types.
     *
     * @return external_single_structure
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'avatars' => new external_multiple_structure(
                new external_value(PARAM_RAW, 'Avatar IDs')
            ),
            'attemptkeys' => new external_multiple_structure(
                new external_value(PARAM_RAW, 'attemptkeys')
            ),
            'attemptqueryids' => new external_multiple_structure(
                new external_value(PARAM_RAW, 'attemptqueryids')
            ),
            'querydefinitions' => new external_multiple_structure(
                new external_value(PARAM_RAW, 'Query definitions')
            ),
            'querytips' => new external_multiple_structure(
                new external_value(PARAM_RAW, 'Query tips')
            ),
            'queryanswerids' => new external_multiple_structure(
                new external_value(PARAM_RAW, 'Answer IDs')
            ),
            'numattempts' => new external_multiple_structure(
                new external_value(PARAM_RAW, 'Answer IDs')
            ),
            'answertexts' => new external_multiple_structure(
                new external_value(PARAM_RAW, 'Answer texts')
            ),
            'queryanswerids0' => new external_multiple_structure(
                new external_value(PARAM_RAW, 'Original queryids')
            ),
            'grades' => new external_multiple_structure(
                new external_value(PARAM_FLOAT, 'Grades per user')
            ),
            'countqueries' => new external_value(
                PARAM_INT,
                'Total number of questions'
            ),
            'countmastered' => new external_multiple_structure(
                new external_value(PARAM_INT, 'Total number of corrects')
            ),
            'islastcorrect' => new external_multiple_structure(
                new external_value(PARAM_INT, 'Correct answers array')
            ),
            'ranks' => new external_multiple_structure(
                new external_value(PARAM_INT, 'Rank array')
            ),
            'queryranks' => new external_multiple_structure(
                new external_value(PARAM_RAW, 'Rank array')
            ),
            'hasidea' => new external_value(
                PARAM_INT,
                'Has idea'
            ),
            'state' => new external_value(
                PARAM_INT,
                'State'
            ),
            'statetime' => new external_value(
                PARAM_FLOAT,
                'State time'
            ),
        ]);
    }

    /**
     * Call get_attempts of mmogame.
     * @param mmogame $mmogame
     * @param array $ausers
     * @param int $numgame
     * @param array $attemptids
     * @param array $attemptkeys
     * @param array $attemptqueryids
     * @param array $attemptnums
     * @param array $definitions
     * @param array $tips
     * @param array $answerids
     * @param array $answertexts
     * @param array $queryanswerids0
     * @param array $countmastered
     * @param array $islastcorrect
     * @param array $queryranks
     * @return bool
     */
    private static function get_attempts(
        mmogame $mmogame,
        array $ausers,
        int &$numgame,
        array &$attemptids,
        array &$attemptkeys,
        array &$attemptqueryids,
        array &$attemptnums,
        array &$definitions,
        array &$tips,
        array &$answerids,
        array &$answertexts,
        array &$queryanswerids0,
        array &$countmastered,
        array &$islastcorrect,
        array &$queryranks
    ): bool {
        $queryranks = [];
        [$recs, $countmastered] = $mmogame->get_attempts($ausers);

        $queryids = [];  /* Queries that are used */
        $attemptqueryids = []; /* Which query has every attempt */
        $attemptids = $attemptnums = $attemptkeys = [];
        $querypositions = [];
        $querytoatttempt = [];
        $numgame = 0;
        foreach ($recs as $attempts) {
            if ($attempts === null || count($attempts) === 0) {
                // No questions.
                return false;
            }
            $nums = $ids = $newids = $attemptkeys1 = [];
            $found = false;
            $numattempt = 0;
            foreach ($attempts as $attempt) {
                if (!$found) {
                    $found = true;
                    $numgame = $attempt->numgame;
                }
                if (array_key_exists($attempt->queryid, $querypositions)) {
                    $pos = $querypositions[$attempt->queryid];
                } else {
                    $pos = count($querypositions);
                    $querypositions[$attempt->queryid] = $pos;
                }
                $nums[] = ++$numattempt;
                $ids[] = $attempt->id;
                $attemptkeys1[] = $attempt->attemptkey;
                $newids[] = $pos;
                $queryids[$attempt->queryid] = $attempt->queryid;

                $querytoatttempt[$attempt->queryid] = $attempt->id;

                $stat = $mmogame->get_db()->get_record_select(
                    'mmogame_aa_stats',
                    'mmogameid=? AND numgame=? AND auserid=? AND queryid=?',
                    [$attempt->mmogameid, $attempt->numgame, $attempt->auserid, $attempt->queryid]
                );
                $islastcorrect[] = $stat !== null ? ($stat->serialcorrects >= 0 ? 1 : 0) : null;
            }
            $attemptnums[] = implode(',', $nums);
            $attemptids[] = implode(',', $ids);
            $attemptkeys[] = implode(',', $attemptkeys1);
            $attemptqueryids[] = implode(',', $newids);
        }

        $queries = $mmogame->get_qbank()->load_many($queryids);

        $definitions = [];
        $newanserids = [];
        $answerids = [];
        $answertexts = [];
        $tips = [];
        $queryanswerids0 = [];
        foreach ($querypositions as $queryid => $position) {
            if (!array_key_exists($queryid, $queries)) {
                // Maybe deleted from question database.
                $mmogame->delete_attempt($querytoatttempt[$queryid]);
                return true;
            }

            $query = $queries[$queryid];
            $definitions[] = $query->definition;
            $tips[] = $query->generalfeedback;
            $queryranks[] = $mmogame->get_selection()->get_rankquery($queryid);

            $a = [];
            $queryanswerids = [];
            foreach ($query->answerids as $pos => $answerid) {
                if (!array_key_exists($answerid, $newanserids)) {
                    $newid = count($newanserids);
                } else {
                    $newid = $newanserids[$answerid];
                }
                $newanserids[$answerid] = $newid;
                $queryanswerids[$answerid] = $answerid;
                $answertexts[] = $query->answers[$pos];
                $a[] = $newid;
            }
            $answerids[] = implode(',', $a);
            $queryanswerids0[] = implode(',', $queryanswerids);
        }
        return false;
    }

    /**
     * Returns error code
     *
     * @param string $error
     *
     * @return array
     */
    private static function error(string $error): array {
            return ['errorcode' => $error];
    }
}
