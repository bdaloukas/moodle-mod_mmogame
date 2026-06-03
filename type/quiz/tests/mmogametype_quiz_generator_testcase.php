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

use mod_mmogame\external\start_session;
use mod_mmogame\external\start_sessions;
use mod_mmogame\local\database\mmogame_database_moodle;
use mod_mmogame\local\mmogame;

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
        global $DB;

        $this->resetAfterTest();
        $this->setAdminUser();

        [$course, $generator, $categoryid, $mmogame] = $this->create_course('quiz', 'alone', 'heuristic');

        $rec = $DB->get_record_sql("SELECT COUNT(*) AS c FROM {question}");
        if ($rec->c === 0) {
            $this->test_quiz_alone_empty($course, $categoryid);
        }

        [, $answerids] = $this->create_multichoice_question($generator, $categoryid);

        for ($step = 1; $step <= 2; $step++) {
            $this->test_quiz_alone_step($answerids, $mmogame);

            if ($step == 2) {
                break;
            }

            // Create mmoGame.
            $rgame = $this->getDataGenerator()->create_module(
                'mmogame',
                [
                    'course' => $course, 'qbank' => 'moodlequestion', 'categoryid1' => $categoryid, 'pin' => rand(),
                    'numgame' => 1, 'type' => 'quiz', 'mode' => 'alone', 'typemode' => 'quiz,alone',
                    'kinduser' => 'guid', 'selection' => 'irt',
                    'enabled' => 1,
                ]
            );
            $mmogame = mmogame::create(new mmogame_database_moodle(), $rgame->id);
            $mmogame->update_state(1);
        }
    }

    /**
     * Test for playing a quiz aduel.
     */
    public function test_quiz_aduel() {
        global $USER;


        [, $generator, $categoryid, $mmogame] = $this->create_course('quiz', 'aduel', 'heuristic');
        [, $answerids] = $this->create_multichoice_question($generator, $categoryid);

        // Set state to playing.
        $mmogame->update_state(1);

        $startsession = new start_session();
        $result = $startsession->execute($mmogame->get_id(), 'moodle', $USER->id, 10, 10);
        $sessionkey = $result['sessionkey'];

        // Gets the first question.
        $getattempt = new mmogametype_quiz\external\get_attempt();
        $result = json_decode($getattempt->execute($sessionkey, "Test", 1, 1));
        $this->assertTrue($result->attemptkey !== '');

        // Gives the correct answer.
        $setanswer = new mmogametype_quiz\external\set_answer();
        $result = json_decode(
            $setanswer->execute(
                $sessionkey,
                $result->attemptkey,
                $answerids[0],
                ''
            )
        );
        $this->assertTrue($result->iscorrect == 1);

        // Gives the wrong answer.
        $result = json_decode($getattempt->execute($sessionkey, 'moodle', 1, 1));
        $this->assertTrue($result->attemptkey !== '');
        $result = json_decode(
            $setanswer->execute(
                $sessionkey,
                $result->attemptkey,
                $answerids[1],
                '',
            )
        );
        $this->assertTrue($result->iscorrect === 0);

        // Use tool1 (50x50).
        $result = json_decode(
            $getattempt->execute(
                $sessionkey,
                'Test nickname',
                1,
                1,
                'tool1'
            )
        );
        $this->assertTrue($result->attemptkey !== '');

        $gethighscore = new mmogametype_quiz\external\get_highscore();
        $result = json_decode($gethighscore->execute($sessionkey, 3));
        $this->assertTrue($result->count == 1);

        // Reset data.
        $data = new stdClass();
        $data->reset_mmogame_all = 1;
        $data->reset_mmogame_deleted_course = 1;
        mmogametype_quiz_reset_userdata($data, $mmogame->get_id());
    }

    /**
     * Test for playing a quiz.
     */
    public function test_quiz_split() {
        global $USER;

        [, $generator, $categoryid, $mmogame] = $this->create_course('quiz', 'split', 'heuristic');
        [, $answerids] = $this->create_multichoice_question($generator, $categoryid);

        // Set state to playing.
        $mmogame->update_state(1);

        $startsessions = new start_sessions();
        $result = $startsessions->execute($mmogame->get_id(), 'moodle', $USER->id, 8, 10);
        $sessionkeys = $result['sessionkeys'];
        $firstsessionkey = $sessionkeys[0];

        for ($step = 1; $step <= 10; $step++) {
            if ($step == 2) {
                $mmogame->update_state(1);
            }

            $getattempt = new mmogametype_quiz\external\get_attempts_split();
            $result = $getattempt->execute(
                implode(',', $sessionkeys),
                '1,2,3,4,5,6,7,8',
            );

            $this->assertFalse(isset($result['errorcode']) && $result['errorcode'] != '');
            if ($result['state'] == 0 && $step == 1) {
                continue;   // What is expected (state=0).
            }
            $used = [0, 1, 2, 2];
            $iscorrects = [0, 1, 1, 1];
            $timestarts = $timeanswers = $answers = [];
            $tools = [];
            $attemptkeys = $usedsessionkeys = [];
            foreach ($used as $pos) {
                $usedsessionkeys[] = $sessionkeys[$pos];
                $s = $result['attemptkeys'][$pos];
                $a = explode(',', $s);
                $attemptkeys[] = $a[0];

                $iscorrect = $iscorrects[$pos];
                $answers[] = $iscorrect ? $answerids[0] : $answerids[1];

                $timestarts[] = time() - 3000;
                $timeanswers[] = time();
                $tools[] = 0;
            }
            $sendanswers = new mmogametype_quiz\external\send_answers_split();
            $result = $sendanswers->execute(
                implode(',', $usedsessionkeys),
                implode(',', $attemptkeys),
                implode(',', $answers),
                implode(',', $timestarts),
                implode(',', $timeanswers),
                $firstsessionkey,
                implode(',', $tools)
            );
            self::assertSameSize($result['attemptkeys'], $result['attemptqueryids']);
            self::assertSameSize($result['attemptkeys'], $result['numattempts']);
            self::assertSameSize($result['attemptkeys'], $result['islastcorrect']);
        }
    }

    /**
     * Run the tests for step $step in game mmogametype_quizalone
     *
     * @param array $answerids
     * @param $mmogame
     * @return void
     * @throws \core_external\restricted_context_exception
     * @throws coding_exception
     * @throws dml_exception
     * @throws invalid_parameter_exception
     * @throws required_capability_exception
     */
    private function test_quiz_alone_step(array $answerids, $mmogame): void {
        global $USER;

        $startsession = new start_session();
        $result = $startsession->execute($mmogame->get_id(), 'moodle', $USER->id, 10, 10);
        $sessionkey = $result['sessionkey'];

        $mmogame->update_state(1);

        $mmogame->update_state(1);
        $getattempt = new mmogametype_quiz\external\get_attempt();
        $result = json_decode($getattempt->execute($sessionkey, 'Test', 1, 1));
        $this->assertTrue($result->attemptkey !== '', json_encode($result, JSON_PRETTY_PRINT));

        // Command set_answer correct.
        $setanswer = new mmogametype_quiz\external\set_answer();
        $result2 = json_decode(
            $setanswer->execute(
                $sessionkey,
                $result->attemptkey,
                $answerids[0],
                ''
            )
        );

        $this->assertTrue(
            $result2->iscorrect === 1,
            "attemptkey=" . $result->attemptkey . " answer=" . $answerids[0] .
            json_encode($result2, JSON_PRETTY_PRINT)
        );

        // Command set_answer error.
        $this->assertTrue($result2->attempt != 0);
        $result2 = json_decode(
            $setanswer->execute(
                $sessionkey,
                $result->attemptkey,
                $answerids[1],
                ''
            )
        );
        $this->assertTrue($result2->iscorrect == 0);
    }

    /**
     * Runs tests on game mmogametype_quizalone with empty questionbank
     *
     * @param $course
     * @param $categoryid
     * @return void
     * @throws \core_external\restricted_context_exception
     * @throws coding_exception
     * @throws dml_exception
     * @throws invalid_parameter_exception
     * @throws required_capability_exception
     */
    private function test_quiz_alone_empty($course, $categoryid) {
        global $USER;

        // Command get_attempt with empty questionbank.
        $rgame = $this->getDataGenerator()->create_module(
            'mmogame',
            [
                'course' => $course, 'qbank' => 'moodlequestion', 'categoryid1' => $categoryid, 'pin' => rand(),
                'numgame' => 1, 'type' => 'quiz', 'mode' => 'alone', 'typemode' => 'quiz,alone',
                'kinduser' => 'guid', 'selection' => '',
                'enabled' => 1,
            ]
        );

        $mmogame = mmogame::create(new mmogame_database_moodle(), $rgame->id);
        $mmogame->update_state(1);

        $startsession = new start_session();
        $result = $startsession->execute($rgame->id, 'moodle', $USER->id, 10, 10);
        $sessionkey = $result['sessionkey'];

        $getattempt = new mmogametype_quiz\external\get_attempt();
        $result = json_decode($getattempt->execute($sessionkey, "test", 1, 1));
        $this->assertTrue($result->attemptkey === '', "result=" . json_encode($result, JSON_PRETTY_PRINT));

        $mmogame->get_db()->update_record(
            'mmogame',
            ['id' => $rgame->id, 'selection' => 'irt']
        );
        $result = json_decode($getattempt->execute($sessionkey, "test", 1, 1));
        $this->assertTrue($result->attemptkey === '', "result=" . json_encode($result, JSON_PRETTY_PRINT));
    }

    private function create_multichoice_question($generator, $categoryid) {
        global $DB;

        $answerids = $answertexts = [];
        $questionid = $generator->create_multichoice_question(
            $categoryid,
            '1',
            '1',
            ['ONE', 'TWO', 'THREE', 'FOUR', 'FIVE', 'SIX', 'SEVEN'],
            $answerids,
            $answertexts
        );

        $recs = $DB->get_records('question_answers', ['question' => $questionid], 'fraction DESC', '*', 0, 1);
        $this->assertTrue(count($recs) == 1);

        return [$questionid, $answerids];
    }

    private function create_course(string $type, string $model, string $selection): array {
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

        $categoryid = $DB->insert_record('question_categories', $new);

        $rgame = $this->getDataGenerator()->create_module(
            'mmogame',
            ['course' => $course, 'qbank' => 'moodlequestion', 'categoryid1' => $categoryid, 'pin' => rand(),
                'numgame' => 1, 'type' => $type, 'mode' => $model, 'typemode' => $type . ',' . $model,
                'kinduser' => 'guid', 'enabled' => 1, 'selection' => $selection
            ]
        );
        $records = $DB->get_records('mmogame', ['course' => $course->id], 'id');
        $this->assertEquals(1, count($records));
        $this->assertArrayHasKey($rgame->id, $records);
        $rgame = reset($records);
        $this->assertEquals($rgame->qbankparams, $categoryid);

        $mmogame = mmogame::create(new mmogame_database_moodle(), $rgame->id);

        return [$course->id, $generator, $categoryid, $mmogame];
    }
}
