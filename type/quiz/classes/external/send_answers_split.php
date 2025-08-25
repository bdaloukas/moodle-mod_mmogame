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
class send_answers_split extends external_api {
    /**
     * Returns description of method parameters
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'mmogameid' => new external_value(PARAM_INT, 'The ID of the mmogame'),
            'kinduser' => new external_value(PARAM_ALPHA, 'The kind of user'),
            'user' => new external_value(PARAM_ALPHANUMEXT, 'The user data'),
            'splits' => new external_value(PARAM_RAW, 'The user data'),
            'attempts' => new external_value(PARAM_RAW, 'The user data'),
            'iscorrects' => new external_value(PARAM_RAW, 'The user data'),
            'answers' => new external_value(PARAM_RAW, 'The user data'),
            'timestarts' => new external_value(PARAM_RAW, 'The user data'),
            'timeanswers' => new external_value(PARAM_RAW, 'The user data'),
            'returnsplits' => new external_value(PARAM_RAW, 'The split that wants new questions'),
        ]);
    }

    /**
     * Implements the service logic.
     *
     * @param int $mmogameid
     * @param string $kinduser
     * @param string $user
     * @param string|null $splits
     * @param string|null $attempts
     * @param string|null $iscorrects
     * @param string|null $answers
     * @param string|null $timestarts
     * @param string $timeanswers
     * @param ?string $returnsplits
     * @return array
     * @throws coding_exception
     * @throws invalid_parameter_exception
     * @throws required_capability_exception
     * @throws restricted_context_exception
     */
    public static function execute(int $mmogameid, string $kinduser, string $user,
                                   ?string $splits = null,
                                   ?string $attempts = null, ?string $iscorrects = null,
                                   ?string $answers = null, ?string $timestarts = '',
                                   string $timeanswers = '', ?string $returnsplits = null): array {
        global $DB;
        $start = microtime(true);

        // Validate the parameters.
        self::validate_parameters(self::execute_parameters(), [
            'mmogameid' => $mmogameid,
            'kinduser' => $kinduser,
            'user' => $user,
            'splits' => $splits,
            'attempts' => $attempts,
            'iscorrects' => $iscorrects,
            'answers' => $answers,
            'timestarts' => $timestarts,
            'timeanswers' => $timeanswers,
            'returnsplits' => $returnsplits,
        ]);

        $splits = explode(',', $splits);
        $attempts = explode(',', $attempts);
        $iscorrects = explode(',', $iscorrects);
        $answers = explode(',', $answers);
        $timeanswers = explode(',', $timeanswers);
        $timestarts = explode(',', $timestarts);

        // Perform security checks.
        $cm = get_coursemodule_from_instance('mmogame', $mmogameid);
        if ($kinduser == 'moodle') {
            $context = module::instance($cm->id);
            self::validate_context($context);
            require_capability('mod/mmogame:play', $context);
        }

        $mmogame = mmogame::create(new mmogame_database_moodle(), $mmogameid);
        $auserids = [];
        foreach ($splits as $split) {
            $auserids[] = mmogame::get_asuerid($mmogame->get_db(), $kinduser, $user, true, $split);
        }
        $mmogame = mmogame::create( new mmogame_database_moodle(), $mmogameid);

        $pos = -1;
        $ret = [];
        $ids = [];

        while (count( $attempts)) {
            if (intval(reset($attempts)) == 0) {
                array_shift($attempts);
            } else {
                break;
            }
        }
        foreach ($attempts as $attemptid) {
            $pos++;
            $ids[] = $auserids[$pos];
            $mmogame->login_user_nolog( $auserids[$pos]);

            $mmogame->set_answer_model( $ret, $attemptid, $answers[$pos], $timestarts[$pos], $timeanswers[$pos],
                intval($answers[$pos]), '');
        }

        $mmogame->login_user_log( $ids);

        $classgetattempt = new get_attempts_split();
        $result = $classgetattempt->execute($mmogameid, $kinduser, $user,
            null, $returnsplits, '', implode(',', $splits));

        // Attempts that saved to database.
        $result['savedattempts'] = $attempts !== null ? $attempts : [];
        $result['auserids'] = $auserids;

        return $result;
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
            'numattempts' => new external_multiple_structure(
                new external_value(PARAM_RAW, 'num attempts')
            ),
            'querydefinitions' => new external_multiple_structure(
                new external_value(PARAM_RAW, 'Query definitions')
            ),
            'queryanswerids' => new external_multiple_structure(
                new external_value(PARAM_RAW, 'Answer IDs')
            ),
            'answertexts' => new external_multiple_structure(
                new external_value(PARAM_RAW, 'Answer texts')
            ),
            'aduels' => new external_multiple_structure(
                new external_value(PARAM_RAW, 'Aduels, one per split')
            ),
            'aduelavatars' => new external_multiple_structure(
                new external_value(PARAM_RAW, 'Aduels, avatars')
            ),
            'aduelcorrects' => new external_multiple_structure(
                new external_value(PARAM_RAW, 'Corrects')
            ),
            'auserids' => new external_multiple_structure(
                new external_value(PARAM_RAW, 'auserids')
            ),
            'queryanswerids0' => new external_multiple_structure(
                new external_value(PARAM_RAW, 'Answer IDs original')
            ),
            'grades' => new external_multiple_structure(
                new external_value(PARAM_FLOAT, 'Grades per user')
            ),
            'savedattempts' => new external_multiple_structure(
                new external_value(PARAM_INT, 'attempts')
            ),
        ]);
    }

}
