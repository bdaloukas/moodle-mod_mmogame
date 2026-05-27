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
 * Submits split-mode quiz answers through the REST external API.
 *
 * @package   mmogametype_quiz
 * @copyright 2024 Vasilis Daloukas
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
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
use required_capability_exception;

/**
 * External API endpoint for saving answers from split-mode quiz attempts.
 */
class send_answers_split extends external_api {
    /**
     * Returns description of method parameters
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'sessionkeys' => new external_value(PARAM_TEXT, 'Comma-separated session keys'),
            'attemptkeys' => new external_value(PARAM_TEXT, 'Comma-separated attempt keys'),
            'answers' => new external_value(PARAM_TEXT, 'Comma-separated answer IDs'),
            'timestarts' => new external_value(PARAM_TEXT, 'Comma-separated Unix timestamps'),
            'timeanswers' => new external_value(PARAM_TEXT, 'Comma-separated Unix timestamps'),
            'returnsplits' => new external_value(PARAM_TEXT, 'Comma-separated return split IDs'),
            'tools' => new external_value(PARAM_TEXT, 'Comma-separated tool flags'),
        ]);
    }

    /**
     * Implements the service logic.
     *
     * @param string $sessionkeys
     * @param string|null $attemptkeys
     * @param ?string $answers
     * @param ?string $timestarts
     * @param string $timeanswers
     * @param ?string $returnsplits
     * @param ?string $tools
     * @return array
     * @throws coding_exception
     * @throws invalid_parameter_exception
     * @throws required_capability_exception
     * @throws dml_exception
     */
    public static function execute(
        string $sessionkeys,
        ?string $attemptkeys,
        ?string $answers = null,
        ?string $timestarts = '',
        string $timeanswers = '',
        ?string $returnsplits = null,
        ?string $tools = null
    ): array {
        // Validate the parameters.
        self::validate_parameters(self::execute_parameters(), [
            'sessionkeys' => $sessionkeys,
            'attemptkeys' => $attemptkeys,
            'answers' => $answers,
            'timestarts' => $timestarts,
            'timeanswers' => $timeanswers,
            'returnsplits' => $returnsplits,
            'tools' => $tools,
        ]);

        // Extract sessionkeys.
        $sessionkeys = explode(',', $sessionkeys);

        // Extracts array.
        $attemptkeys = explode(',', $attemptkeys);
        $answers = explode(',', $answers);
        $timeanswers = explode(',', $timeanswers);
        $timestarts = explode(',', $timestarts);
        $tools = explode(',', $tools);

        $db = new mmogame_database_moodle();
        $ausers = [];
        $mmogameid = null;
        foreach ($sessionkeys as $pos => $sessionkey) {
            $auser = mmogame::get_auser_from_sessionkey($db, $sessionkey);
            if ($auser === null) {
                return self::error('no_user');
            }
            if ($mmogameid === null) {
                $mmogameid = (int)$auser->mmogameid;
            } else if ($mmogameid !== (int)$auser->mmogameid) {
                return self::error('invalid_sessionkey ' . $pos);
            }
            $ausers[] = $auser;
        }
        $mmogame = mmogame::create($db, $mmogameid);

        $ret = [];

        $idea = 0;
        foreach ($attemptkeys as $pos => $attemptkey) {
            $tool = $tools[$pos];

            $mmogame->login_user($ausers[$pos]);

            if ($tool & 4) {
                // Special case (Idea button).
                $idea = $ausers[$pos]->id;
                continue;
            }

            if ($idea > 0) {
                $queryids = $mmogame->idea();
                return self::pack_idea($mmogame, $queryids);
            }

            // Checks also than sessionkey is valid for this attempt.
            $mmogame->set_answer_mode(
                $ret,
                $attemptkey,
                $answers[$pos],
                $timestarts[$pos],
                $timeanswers[$pos],
                intval($answers[$pos]),
                $tools[$pos]
            );
        }

        $getattempts = new get_attempts_split();
        return $getattempts->execute(implode(',', $sessionkeys));
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
                new external_value(PARAM_RAW, 'Attempt keys')
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
            'querytips' => new external_multiple_structure(
                new external_value(PARAM_RAW, 'Query tips')
            ),
            'queryanswerids' => new external_multiple_structure(
                new external_value(PARAM_RAW, 'Answer IDs')
            ),
            'answertexts' => new external_multiple_structure(
                new external_value(PARAM_RAW, 'Answer texts')
            ),
            'queryanswerids0' => new external_multiple_structure(
                new external_value(PARAM_RAW, 'Answer IDs original')
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
                new external_value(PARAM_INT, 'Rank array')
            ),
            'state' => new external_value(
                PARAM_INT,
                'Has idea'
            ),
            'statetime' => new external_value(
                PARAM_FLOAT,
                'Has idea'
            ),
        ]);
    }

    /**
     * Packs as an array the results of idea button.
     *
     * @param mmogame $mmogame
     * @param array $queryids
     * @return array
     */
    protected static function pack_idea(mmogame $mmogame, array $queryids): array {
        $queries = $mmogame->get_qbank()->load_many($queryids);

        $definitions = $tips = $answerids = $answertexts = $queryanswerids0 = [];

        foreach ($queries as $query) {
            $definitions[] = $query->definition;
            $tips[] = $query->generalfeedback;

            $a = [];
            $queryanswerids = [];
            foreach ($query->answerids as $pos => $answerid) {
                $queryanswerids[$answerid] = $answerid;
                $answertexts[] = $query->answers[$pos];
                $a[] = $answerid;
            }
            $answerids[] = implode(',', $a);
            $queryanswerids0[] = implode(',', $queryanswerids);
        }

        return ['avatars' => [], 'attemptqueryids' => [],
            'numattempts' => [], 'querydefinitions' => $definitions, 'querytips' => $tips,
            'queryanswerids' => $answerids, 'answertexts' => $answertexts,
            'queryanswerids0' => $queryanswerids0,
            'countqueries' => 0, 'countmastered' => [], 'islastcorrect' => [],
            'ranks' => [], 'grades' => [], 'savedattempts' => [], 'queryranks' => [],
            'state' => $mmogame->get_state(), 'statetime' => $mmogame->get_statetime()];
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
