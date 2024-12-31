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

use invalid_parameter_exception;
use mod_mmogame\local\database\mmogame_database_moodle;
use mod_mmogame\local\mmogame;

/**
 * External function for saving the answer of each question.
 *
 * @package   mmogametype_quiz
 * @copyright 2024 Vasilis Daloukas
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class set_answer extends external_api {

    /**
     * Returns description of method parameters
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'mmogameid' => new external_value(PARAM_INT, 'The ID of the mmogame'),
            'kinduser' => new external_value(PARAM_ALPHA, 'The kind of user'),
            'user' => new external_value(PARAM_ALPHANUM, 'The user data'),
            'answer' => new external_value(PARAM_RAW, 'The answer'),
            'answerid' => new external_value(PARAM_INT, 'The id of the answer'),
        ]);
    }

    /**
     * Implements the service logic.
     *
     * @param int $mmogameid
     * @param string $kinduser
     * @param string $user
     * @param int $attempt
     * @param string $answer
     * @param int|null $answerid
     * @param string $subcommand
     * @return array
     * @throws coding_exception
     * @throws invalid_parameter_exception
     */
    public static function execute(int $mmogameid, string $kinduser, string $user, int $attempt, string $answer,
                                   ?int $answerid, string $subcommand): array {
        // Validate the parameters.
        self::validate_parameters(self::execute_parameters(), [
            'mmogameid' => $mmogameid,
            'kinduser' => $kinduser,
            'user' => $user,
            'attempt' => $attempt,
            'answer' => $answer,
            'answerid' => $answerid ?? null,
            'subcommand' => $subcommand,
        ]);

        $ret = [];

        $mmogame = mmogame::create( new mmogame_database_moodle(), $mmogameid);
        $auserid = mmogame::get_asuerid( $mmogame->get_db(), $kinduser, $user);

        $mmogame->login_user( $auserid);

        $mmogame->set_answer_model( $ret, $attempt, $answer, $answerid, $subcommand);

        $formattedret = [];
        foreach ($ret as $key => $value) {
            $formattedret[] = [
                'key' => $key,
                'value' => $value,
            ];
        }

        return ['ret' => $formattedret];
    }

    /**
     * Describe the return types.
     *
     * @return external_single_structure
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'ret' => new external_multiple_structure(
                new external_single_structure([
                    'key' => new external_value(PARAM_TEXT, 'The key of the entry'),
                    'value' => new external_value(PARAM_RAW, 'The value of the entry'),
                ]),
                'The list of key-value pairs'
            ),
        ]);
    }

    /**
     * Creates a question in the database.
     *
     * @param int $categoryid
     * @param string $name
     * @param string $questiontext
     * @param array $answers
     * @param array $ansersids
     * @param array $answertexts
     * @param $answerids
     * @param $answertexts
     * @return int question id
     * @throws dml_exception
     */
    public function create_multichoice_question(int $categoryid, string $name, string $questiontext, array $answers,
                                                array &$answerids, array &$answertexts): int {
        global $DB, $USER;

        $answerids = $answertexts = [];

        // Insert a record in the question table.
        $new = new \stdClass();
        $new->category = $categoryid;
        $new->name = $name;
        $new->questiontext = $questiontext;
        $new->questiontextformat = FORMAT_MOODLE;
        $new->qtype = 'multichoice';
        $new->defaultmark = 1;
        $new->penalty = 0.333333;
        $new->single = 0;
        $new->shuffleanswers = 1;
        $new->flags = 0;
        $new->generalfeedback = '';
        $questionid = $DB->insert_record('question', $new);

        $first = true;
        foreach ($answers as $answer) {
            $new = new \stdClass();
            $new->question = $questionid;
            $answertexts[] = $new->answer = $answer;
            $new->fraction = $first ? 1 : 0;
            $new->feedback = '';
            $new->feedbackformat = FORMAT_MOODLE;
            $answerids[] = $DB->insert_record('question_answers', $new);

            $first = false;
        }

        // Add multiple-choice-specific options.
        $qtypeoptions = new \stdClass();
        $qtypeoptions->questionid = $questionid;
        $qtypeoptions->layout = 0; // 0 = Vertical layout
        $qtypeoptions->single = 1; // Only one choice allowed.
        $qtypeoptions->shuffleanswers = 1; // Shuffle answer order.
        $qtypeoptions->correctfeedback = ''; // Feedback for correct answers.
        $qtypeoptions->correctfeedbackformat = FORMAT_HTML;
        $qtypeoptions->partiallycorrectfeedback = '';
        $qtypeoptions->partiallycorrectfeedbackformat = FORMAT_HTML;
        $qtypeoptions->incorrectfeedback = '';
        $qtypeoptions->incorrectfeedbackformat = FORMAT_HTML;
        $qtypeoptions->answernumbering = 'abc'; // Answer numbering.
        $DB->insert_record('qtype_multichoice_options', $qtypeoptions);

        // Insert a new record in the question_bank_entries table.
        $qbe = new \stdClass();
        $qbe->questioncategoryid = $categoryid;
        $qbe->ownerid = $USER->id;
        $qbe->timecreated = time();
        $qbe->timemodified = time();
        $qbe->status = 0;
        $qbeid = $DB->insert_record('question_bank_entries', $qbe);

        // Insert a record in the question_versions table.
        $qv = new \stdClass();
        $qv->version = 1;
        $qv->questionbankentryid = $qbeid;
        $qv->questionid = $questionid;
        $qv->status = 'ready';
        $DB->insert_record('question_versions', $qv);

        return $questionid;
    }

}
