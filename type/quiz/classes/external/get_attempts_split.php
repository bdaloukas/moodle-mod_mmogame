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
use core\context\module;
use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_multiple_structure;
use core_external\external_single_structure;
use core_external\external_value;

use core_external\restricted_context_exception;
use invalid_parameter_exception;
use mod_mmogame\local\database\mmogame_database_moodle;
use mod_mmogame\local\mmogame;
use required_capability_exception;

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
            'mmogameid' => new external_value(PARAM_INT, 'The ID of the mmogame'),
            'kinduser' => new external_value(PARAM_ALPHA, 'The kind of user'),
            'user' => new external_value(PARAM_ALPHANUMEXT, 'The user data'),
            'avatarids' => new external_value(PARAM_RAW, 'The user data'),
            'splits' => new external_value(PARAM_RAW, 'The user data'),
            'subcommand' => new external_value(PARAM_ALPHANUMEXT, 'Subcommands like tool1, tool2, tool3'),
        ]);
    }

    /**
     * Implements the service logic.
     *
     * @param int $mmogameid
     * @param string $kinduser
     * @param string $user
     * @param ?string $avatarids
     * @param ?string $splits
     * @param string $subcommand
     * @return array
     * @throws coding_exception
     * @throws invalid_parameter_exception
     * @throws required_capability_exception
     * @throws restricted_context_exception
     */
    public static function execute(int $mmogameid, string $kinduser, string $user,
                                   ?string $avatarids = null,
                                   ?string $splits = null, string $subcommand = ''): array {
        $start = microtime(true);

        // Validate the parameters.
        self::validate_parameters(self::execute_parameters(), [
            'mmogameid' => $mmogameid,
            'kinduser' => $kinduser,
            'user' => $user,
            'avatarids' => $avatarids,
            'splits' => $splits,
            'subcommand' => $subcommand,
        ]);

        $splits = explode(',', $splits);
        $avatarids = $avatarids != null ? explode(',', $avatarids) : null;

        // Perform security checks.
        if ($kinduser == 'moodle') {
            $cm = get_coursemodule_from_instance('mmogame', $mmogameid);
            $context = module::instance($cm->id);
            self::validate_context($context);
            require_capability('mod/mmogame:play', $context);
        }

        $mmogame = mmogame::create(new mmogame_database_moodle(), $mmogameid);
        $create = $avatarids !== null;
        $auserids = [];
        foreach ($splits as $split) {
            if ($split == '') {
                continue;
            }
            $auserids[] = mmogame::get_asuerid($mmogame->get_db(), $kinduser, $user, $create, $split);
        }
        if (count($auserids) == 0) {
            $ret = ['avatars' => [], 'attempts' => [], 'attemptqueryids' => [],
                'numattempts' => [], 'querydefinitions' => [],
                'queryanswerids' => [], 'answertexts' => [],
                'aduels' => [], 'aduelavatars' => [], 'aduelcorrects' => [],
                'auserids' => [], 'queryanswerids0' => [], 'grades' => []];
            return $ret;
        }
        if ($avatarids !== null) {
            foreach ($splits as $pos => $split) {
                $info = $mmogame->get_avatar_info($auserids[$pos], false);
                $mmogame->get_db()->update_record('mmogame_aa_grades',
                    ['id' => $info->id, 'avatarid' => $avatarids[$pos]]);
            }
        }
        $isaduel = $onlygroup = false;
        $modelparams = $mmogame->get_rgame()->modelparams;
        if ($modelparams != '') {
            $data = json_decode($modelparams, true);
            if (isset($data['isaduel']) && $data['isaduel'] > 0) {
                $isaduel = true;
            }
            if (isset($data['onlygroup']) && $data['onlygroup'] > 0) {
                $onlygroup = true;
            }
        }
        $aduelauserids = $onlygroup ? $mmogame->get_auserids_split($kinduser, $user) : [];

        $recs = $mmogame->get_attempts($auserids, $isaduel, $aduelauserids);

        $queryids = [];  /* Queries that are used */
        $attemptqueryids = []; /* Which query has every attempt */
        $attemptids = $attemptnums = [];
        $querypositions = [];
        $aduelavatars = $aduels = $aduelcorrects = [];
        $numgame = 0;
        foreach ($recs as $position => $attempts) {
            $nums = $ids = $newids = [];
            $found = false;
            $isaduel = false;
            $corrects = [];
            foreach ($attempts as $attempt) {
                if (!$found) {
                    $found = true;
                    $numgame = $attempt->numgame;
                    $auserid = intval($auserids[$position]);
                    $aduel = $mmogame->get_db()->get_record_select('mmogame_am_aduel_pairs', 'id=?', [$attempt->numteam]);
                    if (intval($aduel->auserid2) == $auserid) {
                        $aduels[] = count($aduels);
                        $info = $mmogame->get_avatar_info($aduel->auserid1, false);
                        $aduelavatars[] = $info->avatar;
                        $isaduel = true;
                    } else {
                        $aduels[] = -1;
                        $aduelavatars[] = '';
                    }
                }
                if (array_key_exists($attempt->queryid, $querypositions)) {
                    $pos = $querypositions[$attempt->queryid];
                } else {
                    $pos = count( $querypositions);
                    $querypositions[$attempt->queryid] = $pos;
                }
                $nums[] = $attempt->numattempt;
                $ids[] = $attempt->id;
                $newids[] = $pos;
                $queryids[$attempt->queryid] = $attempt->queryid;

                if (!$isaduel) {
                    $corrects[] = '';
                } else {
                    $attempt2 = $mmogame->get_db()->get_record_select( 'mmogame_quiz_attempts',
                        "mmogameid=? AND numgame=? AND numteam=? AND auserid=? AND numattempt=?",
                        [$attempt->mmogameid, $attempt->numgame, $attempt->numteam, $aduel->auserid1, $attempt->numattempt]);
                    $corrects[] = $attempt2->iscorrect;
                }
            }
            $attemptnums[] = implode( ',', $nums);
            $attemptids[] = implode( ',', $ids);
            $attemptqueryids[] = implode( ',', $newids);
            $aduelcorrects[] = $isaduel ? implode( ',', $corrects) : '';
        }

        $queries = $mmogame->get_qbank()->load_many($queryids);

        $definitions = [];
        $newanserids = [];
        $answerids = [];
        $answertexts = [];
        $queryanswerids0 = [];
        foreach ($querypositions as $queryid => $position) {
            $query = $queries[$queryid];
            $definitions[] = $query->definition;
            $a = [];
            $queryanswerids = [];
            foreach ($query->answerids as $pos => $answerid) {
                if (!array_key_exists( $answerid, $newanserids)) {
                    $newid = count( $newanserids);
                } else {
                    $newid = $newanserids[$answerid];
                }
                $newanserids[$answerid] = $newid;
                $queryanswerids[$answerid] = $answerid;
                $answertexts[] = $query->answers[$pos];
                $a[] = $newid;
            }
            $answerids[] = implode( ',', $a);
            $queryanswerids0[] = implode( ',', $queryanswerids);
        }

        [$insql, $inparams] = $mmogame->get_db()->get_in_or_equal($auserids);
        $sql = "SELECT g.auserid, g.sumscore, a.directory, a.filename
            FROM {mmogame_aa_grades} g
            LEFT JOIN {mmogame_aa_avatars} a ON a.id=g.avatarid
            WHERE g.mmogameid=? AND g.numgame=? AND g.auserid $insql";
        $recs = $mmogame->get_db()->get_records_sql( $sql,
            array_merge([$mmogameid, $numgame], $inparams));
        $grades = $avatars = [];
        foreach ($auserids as $auserid) {
            foreach ($recs as $rec) {
                if ($rec->auserid == $auserid) {
                    $grades[] = $rec->sumscore;
                    $avatars[] = $rec->directory.'/'.$rec->filename;
                    break;
                }
            }
        }

        $ret = ['avatars' => $avatars, 'attempts' => $attemptids, 'attemptqueryids' => $attemptqueryids,
            'numattempts' => $attemptnums, 'querydefinitions' => $definitions,
            'queryanswerids' => $answerids, 'answertexts' => $answertexts,
            'aduels' => $aduels, 'aduelavatars' => $aduelavatars, 'aduelcorrects' => $aduelcorrects,
            'auserids' => $auserids, 'queryanswerids0' => $queryanswerids0, 'grades' => $grades];

        return $ret;
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
            'attempts' => new external_multiple_structure(
                new external_value(PARAM_RAW, 'Attempts data')
            ),
            'attemptqueryids' => new external_multiple_structure(
                new external_value(PARAM_RAW, 'Query IDs of attempts')
            ),
            'querydefinitions' => new external_multiple_structure(
                new external_value(PARAM_RAW, 'Query definitions')
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
            'aduels' => new external_multiple_structure(
                new external_value(PARAM_RAW, 'Aduels')
            ),
            'aduelavatars' => new external_multiple_structure(
                new external_value(PARAM_RAW, 'Avatars')
            ),
            'aduelcorrects' => new external_multiple_structure(
                new external_value(PARAM_RAW, 'Corrects')
            ),
            'auserids' => new external_multiple_structure(
                new external_value(PARAM_RAW, 'ausers')
            ),
            'queryanswerids0' => new external_multiple_structure(
                new external_value(PARAM_RAW, 'Original queryids')
            ),
            'grades' => new external_multiple_structure(
                new external_value(PARAM_FLOAT, 'Grades per user')
            ),
        ]);
    }
}
