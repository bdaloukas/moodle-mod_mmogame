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
 * mmogametype_quiz data generator.
 *
 * @package    mod_mmogame
 * @category   test
 * @copyright  2024 Vasilis Daloukas
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * mod_game data generator class.
 *
 * @package    mmogametype_quiz
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
     * @return int question id
     * @throws dml_exception
     */
    public function create_multichoice_question(int $categoryid, string $name, string $questiontext, array $answers): int {
        global $DB;

        $new = new stdClass();
        $new->category = $categoryid;
        $new->name = $name;
        $new->questiontext = $questiontext;
        $new->questiontextformat = FORMAT_MOODLE;
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
            $new->answer = $answer;
            $new->fraction = $first ? 1 : 0;
            $new->feedback = '';
            $new->feedbackformat = FORMAT_MOODLE;
            $DB->insert_record('question_answers', $new);

            $first = false;
        }

        return $questionid;
    }
}
