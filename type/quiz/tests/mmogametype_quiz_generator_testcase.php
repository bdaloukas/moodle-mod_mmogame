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
 * mmogametype_quiz generator tests
 *
 * @package    mmogametype_quiz
 * @category   test
 * @copyright  2024 Vasilis Daloukas
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use mod_mmogame\local\database\mmogame_database_moodle;

defined('MOODLE_INTERNAL') || die();

global $CFG;

/**
 * Generator tests class for mmogametype_quiz.
 *
 * @package    mmogametype_quiz
 * @category   test
 * @copyright  2024 Vasilis Daloukas
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mmogametype_quiz_generator_testcase extends advanced_testcase {
    /**
     * Test for creating a quiz alone.
     */
    public function test_quiz_alone() {
        global $DB, $USER;

        $this->resetAfterTest();
        $this->setAdminUser();

        $course = $this->getDataGenerator()->create_course();

        $this->assertFalse($DB->record_exists('mmogame', ['course' => $course->id]));
        $generator = $this->getDataGenerator()->get_plugin_generator('mod_mmogame');

        $new = new stdClass();
        $new->name = 'Test category';
        $new->context = 1;
        $new->info = 'Info';
        $new->stamp = rand();
        $categoryid = $DB->insert_record( 'question_categories', $new);

        // Create mmoGame.
        $rgame = $this->getDataGenerator()->create_module('mmogame',
            ['course' => $course, 'qbank' => 'moodlequestion', 'categoryid1' => $categoryid, 'pin' => rand(),
                'numgame' => 1, 'type' => 'quiz', 'model' => 'alone', 'typemodel' => 'quiz,alone',
                'kinduser' => 'guid', 'enabled' => 1]);
        $records = $DB->get_records('mmogame', ['course' => $course->id], 'id');
        $this->assertEquals(1, count($records));
        $this->assertArrayHasKey($rgame->id, $records);
        $mmogame = mod_mmogame\local\mmogame::create( new mmogame_database_moodle(), $rgame->id);
        $mmogame->update_state( 1);

        // Command get_attempt with empty questionbank.
        $mmogame->update_state( 1);
        $classgetattempt = new mmogametype_quiz\external\get_attempt();
        $result = json_decode( $classgetattempt->execute($rgame->id, 'moodle', $USER->id, 'Test', 1, 1));
        $this->assertTrue($result->attempt == 0);

        // Command get_attempt with 1 question.

        $answerids = $answertexts = [];
        $generator->create_multichoice_question($categoryid, '1', '1',
            ['ONE', 'TWO', 'THREE', 'FOUR', 'FIVE', 'SIX', 'SEVEN'], $answerids, $answertexts);
        $mmogame->update_state( 1);
        $result = json_decode( $classgetattempt->execute($rgame->id, 'moodle', $USER->id, 'Test', 1, 1));
        $this->assertTrue($result->attempt != 0);

        // Command set_answer correct.
        $classsetanswer = new mmogametype_quiz\external\set_answer();
        $result = json_decode( $classsetanswer->execute( $rgame->id, 'moodle', $USER->id,
            $result->attempt, $answertexts[0], $answerids[0], ''));
        $this->assertTrue($result->iscorrect == 1);

        // Command set_answer error.
        $this->assertTrue($result->attempt != 0);
        $result = json_decode( $classsetanswer->execute( $rgame->id, 'moodle', $USER->id,
            $result->attempt, $answertexts[1], $answerids[1], ''));
        $this->assertTrue($result->iscorrect == 0);
    }

    /**
     * Test for playing a quiz aduel.
     */
    public function test_quiz_aduel() {
        global $DB;

        $this->resetAfterTest();
        $this->setAdminUser();

        $course = $this->getDataGenerator()->create_course();

        $this->assertFalse($DB->record_exists('mmogame', ['course' => $course->id]));
        $generator = $this->getDataGenerator()->get_plugin_generator('mod_mmogame');

        $new = new stdClass();
        $new->name = 'Test category';
        $new->context = 1;
        $new->info = 'Info';
        $new->stamp = rand();
        $categoryid = $DB->insert_record( 'question_categories', $new);

        $answerids = $answertexts = [];
        $questionid = $generator->create_multichoice_question($categoryid, '1', '1',
            ['ONE', 'TWO', 'THREE', 'FOUR', 'FIVE', 'SIX', 'SEVEN'], $answerids, $answertexts);
        $recs = $DB->get_records('question_answers', ['question' => $questionid], 'fraction DESC', '*', 0, 1);
        $this->assertTrue( count($recs) == 1);

        $rgame = $this->getDataGenerator()->create_module('mmogame',
            ['course' => $course, 'qbank' => 'moodlequestion', 'categoryid1' => $categoryid, 'pin' => rand(),
                'numgame' => 1, 'type' => 'quiz', 'model' => 'aduel', 'typemodel' => 'quiz,aduel',
                'kinduser' => 'guid', 'enabled' => 1]);
        $records = $DB->get_records('mmogame', ['course' => $course->id], 'id');
        $this->assertEquals(1, count($records));
        $this->assertArrayHasKey($rgame->id, $records);
        $rgame = reset( $records);
        $this->assertEquals($rgame->qbankparams, $categoryid);

        $mmogame = mod_mmogame\local\mmogame::create( new mmogame_database_moodle(), $rgame->id);

        // Set state to playing.
        $mmogame->update_state( 1);

        // Gets the first question.
        $classgetattempt = new mmogametype_quiz\external\get_attempt();
        global $USER;
        $result = json_decode( $classgetattempt->execute($rgame->id, 'moodle', $USER->id, 'Test', 1, 1));
        $this->assertTrue( $result->attempt != 0);

        // Gives the correct answer.
        $classsetanswer = new mmogametype_quiz\external\set_answer();
        $result = json_decode( $classsetanswer->execute( $rgame->id, 'moodle', $USER->id,
            $result->attempt, $answertexts[0], $answerids[0], ''));
        $this->assertTrue( $result->iscorrect == 1);

        // Gives the wrong answer.
        $result = json_decode( $classgetattempt->execute($rgame->id, 'moodle', $USER->id, 'Test', 1, 1));
        $this->assertTrue( $result->attempt != 0);
        $result = json_decode( $classsetanswer->execute( $rgame->id, 'moodle', $USER->id,
            $result->attempt, $answertexts[0], $answerids[1], ''));
        $this->assertTrue( $result->iscorrect == 0);

        // Use tool1 (50x50).
        $result = json_decode( $classgetattempt->execute($rgame->id, 'moodle', $USER->id, 'Test', 1, 1, 'tool1'));
        $this->assertTrue( $result->attempt != 0);

        $classgethighscore = new mmogametype_quiz\external\get_highscore();
        $result = json_decode( $classgethighscore->execute( $rgame->id, 'moodle', $USER->id, 3));
        $this->assertTrue( $result->count == 1);

        $data = new stdClass();
        $data->reset_mmogame_all = 1;
        $data->reset_mmogame_deleted_course = 1;
        mmogametype_quiz_reset_userdata( $data, $mmogame->get_id());
    }
}
