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
     * Test for create instance.
     */
    public function test_create_instance() {
        global $DB;
        $this->resetAfterTest();
        $this->setAdminUser();

        $course = $this->getDataGenerator()->create_course();

        $categoryid = 1;

        $this->assertFalse($DB->record_exists('mmogame', ['course' => $course->id]));

        $mmogame = $this->getDataGenerator()->create_module('mmogame',
            ['course' => $course, 'qbank' => 'moodlequestion', 'qbankparams' => $categoryid, 'enabled' => 1,
                'numgame' => 1, 'typemodel' => 'quiz,alone', 'kinduser' => 'guid', 'pin' => rand()]);

        $records = $DB->get_records('mmogame', ['course' => $course->id], 'id');

        $this->assertEquals(1, count($records));
        $this->assertArrayHasKey($mmogame->id, $records);

        $this->test_create_quiz_alone_instance();
    }

    /**
     * Test for creating a quiz alone.
     */
    public function test_create_quiz_alone_instance() {
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
        $generator->create_multichoice_question($categoryid, '1', '1', ['ONE', 'TWO', 'THREE', 'FOUR', 'FIVE', 'SIX', 'SEVEN']);

        $rgame = $this->getDataGenerator()->create_module('mmogame',
            ['course' => $course, 'qbank' => 'moodlequestion', 'qbankparams' => $categoryid, 'pin' => rand(),
                'numgame' => 1, 'type' => 'quiz', 'model' => 'alone', 'typemodel' => 'quiz,alone',
                'kinduser' => 'guid', 'enabled' => 1]);
        $records = $DB->get_records('mmogame', ['course' => $course->id], 'id');
        $this->assertEquals(1, count($records));
        $this->assertArrayHasKey($rgame->id, $records);

        global $USER;
        $data = (object)['mmogameid' => $rgame->id, 'command' => 'getattempt',
            'kinduser' => 'moodle', 'user' => $USER->id,
            'nickname' => 'Nickname', 'avatarid' => 1, 'paletteid' => 1];

        require_once(__DIR__ .  '/../../../type/quiz/json.php');
        $ret = [];
        $mmogame = mod_mmogame\local\mmogame::create( new mmogame_database_moodle(), $rgame->id);

        $mmogame->update_state( 1);

        mmogame_json_quiz_getattempt($data, $mmogame, $ret);
    }

    /**
     * Test for playing a quiz aduel.
     */
    public function test_aduel() {
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

        $questionid = $generator->create_multichoice_question($categoryid, '1', '1',
            ['ONE', 'TWO', 'THREE', 'FOUR', 'FIVE', 'SIX', 'SEVEN']);
        $recs = $DB->get_records('question_answers', ['question' => $questionid], 'fraction DESC', '*', 0, 1);
        $this->assertTrue( count($recs) == 1);
        $answerid = reset( $recs)->id;

        $rgame = $this->getDataGenerator()->create_module('mmogame',
            ['course' => $course, 'qbank' => 'moodlequestion', 'categoryid1' => $categoryid, 'pin' => rand(),
                'numgame' => 1, 'type' => 'quiz', 'model' => 'aduel', 'typemodel' => 'quiz,aduel',
                'kinduser' => 'guid', 'enabled' => 1]);
        $records = $DB->get_records('mmogame', ['course' => $course->id], 'id');
        $this->assertEquals(1, count($records));
        $this->assertArrayHasKey($rgame->id, $records);
        $rgame = reset( $records);
        $this->assertEquals($rgame->qbankparams, $categoryid);

        global $USER;
        $data = (object)['mmogameid' => $rgame->id, 'command' => 'getattempt',
            'kinduser' => 'moodle', 'user' => $USER->id,
            'nickname' => 'Nickname', 'avatarid' => 1, 'paletteid' => 1];

        require_once(__DIR__ .  '/../../../type/quiz/json.php');
        $ret = [];
        $mmogame = mod_mmogame\local\mmogame::create( new mmogame_database_moodle(), $rgame->id);

        // Set state to playing.
        $mmogame->update_state( 1);

        // Gets the first question.
        mmogame_json_quiz_getattempt($data, $mmogame, $ret);
        $this->assertTrue( $ret['attempt'] != 0);

        $data = (object)['mmogameid' => $rgame->id, 'command' => 'answer', 'answer' => $answerid,
            'kinduser' => 'moodle', 'user' => $USER->id, 'attempt' => $ret['attempt'], 'submit' => 1, ];
        $ret = [];
        mmogame_json_quiz_answer($data, $mmogame, $ret);

        $data = (object)['mmogameid' => $rgame->id, 'command' => 'answer', 'answer' => $answerid,
            'kinduser' => 'moodle', 'user' => $USER->id, 'attempt' => $ret['attempt'], 'submit' => 1, ];
        mmogame_json_quiz_answer($data, $mmogame, $ret);
        $ret = [];
        $mmogame->get_highscore(3, $ret);

        $data = new stdClass();
        $data->reset_mmogame_all = 1;
        $data->reset_mmogame_deleted_course = 1;
        mmogametype_quiz_reset_userdata( $data, $mmogame->get_id());
    }

    /**
     * Test the get_services web service.
     */
    public function test_service_get_attempt() {
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
        $generator->create_multichoice_question($categoryid, '1', '1', ['ONE', 'TWO', 'THREE', 'FOUR']);

        // Alone.
        // Game without categoryid.
        $rgame = $this->getDataGenerator()->create_module('mmogame',
            ['course' => $course, 'qbank' => 'moodlequestion', 'pin' => rand(),
                'numgame' => 1, 'type' => 'quiz', 'model' => 'alone', 'typemodel' => 'quiz,alone',
                'kinduser' => 'guid', 'enabled' => 1]);
        $mmogame = mod_mmogame\local\mmogame::create( new mmogame_database_moodle(), $rgame->id);
        $mmogame->update_state( 1);
        $class = new mmogametype_quiz\external\get_attempt();
        $result = $class->execute($rgame->id, 'moodle', $USER->id, 'Test', 1, 1);
        $this->assertTrue($result['attempt'] == 0);

        // Game with categoryid.
        $rgame = $this->getDataGenerator()->create_module('mmogame',
            ['course' => $course, 'qbank' => 'moodlequestion', 'categoryid1' => $categoryid, 'pin' => rand(),
                'numgame' => 1, 'type' => 'quiz', 'model' => 'alone', 'typemodel' => 'quiz,alone',
                'kinduser' => 'guid', 'enabled' => 1]);
        $mmogame = mod_mmogame\local\mmogame::create( new mmogame_database_moodle(), $rgame->id);
        $mmogame->update_state( 1);
        $class = new mmogametype_quiz\external\get_attempt();
        $result = $class->execute($rgame->id, 'moodle', $USER->id, 'Test', 1, 1);
        $this->assertTrue($result['attempt'] != 0);

        // Aduel.
        $rgame = $this->getDataGenerator()->create_module('mmogame',
            ['course' => $course, 'qbank' => 'moodlequestion', 'categoryid1' => $categoryid, 'pin' => rand(),
                'numgame' => 1, 'type' => 'quiz', 'model' => 'aduel', 'typemodel' => 'quiz,aduel',
                'kinduser' => 'guid', 'enabled' => 1]);
        $mmogame = mod_mmogame\local\mmogame::create( new mmogame_database_moodle(), $rgame->id);
        $mmogame->update_state( 1);
        $class = new mmogametype_quiz\external\get_attempt();
        $result = $class->execute($rgame->id, 'moodle', $USER->id, 'Test', 1, 1);
        $this->assertTrue($result['attempt'] != 0);
    }
}
