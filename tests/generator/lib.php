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
 * mod_mmogame Data generator.
 *
 * @package    mod_mmogame
 * @category   test
 * @copyright  2024 Vasilis Daloukas
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * mod_game Data generator class.
 *
 * @package    mod_mmogame
 * @category   test
 * @copyright  2024 Vasilis Daloukas
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_mmogame_generator extends testing_module_generator {

    /**
     * Creates instance of game record with default values.
     *
     * @param null $record
     * @param array|null $options
     * @return object mmogame instance
     * @throws coding_exception
     */
    public function create_instance($record = null, ?array $options = null): object {
        global $CFG;
        require_once($CFG->libdir.'/resourcelib.php');

        return parent::create_instance($record, (array)$options);
    }

    /**
     * Creates a question in the database.
     *
     * @param int $categoryid
     * @param string $name
     * @param string $questiontext
     * @param array $answers
     * @param array $answerids
     * @param array $answertexts
     * @return int question id
     * @throws dml_exception
     */
    public function create_multichoice_question(int $categoryid, string $name, string $questiontext, array $answers,
                                                &$answerids, &$answertexts): int {
        global $DB, $USER;

        $answerids = $answertexts = [];

        // Insert a record in the question table.
        $new = new stdClass();
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
            $new = new stdClass();
            $new->question = $questionid;
            $answertexts[] = $new->answer = $answer;
            $new->fraction = $first ? 1 : 0;
            $new->feedback = '';
            $new->feedbackformat = FORMAT_MOODLE;
            $answerids[] = $DB->insert_record('question_answers', $new);

            $first = false;
        }

        // Add multiple-choice-specific options.
        $qtypeoptions = new stdClass();
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
        $qbe = new stdClass();
        $qbe->questioncategoryid = $categoryid;
        $qbe->ownerid = $USER->id;
        $qbe->timecreated = time();
        $qbe->timemodified = time();
        $qbe->status = 0;
        $qbeid = $DB->insert_record('question_bank_entries', $qbe);

        // Insert a record in the question_versions table.
        $qv = new stdClass();
        $qv->version = 1;
        $qv->questionbankentryid = $qbeid;
        $qv->questionid = $questionid;
        $qv->status = 'ready';
        $DB->insert_record('question_versions', $qv);

        return $questionid;
    }
}
