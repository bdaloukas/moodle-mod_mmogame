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
 * mod_mmogametype_quiz generator tests
 *
 * @package    mmogametype_quiz
 * @category   test
 * @copyright  2024 Vasilis Daloukas
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();

global $CFG;

/**
 * Generator tests class for mod_mmogame.
 *
 * @package    mod_mmogame
 * @category   test
 * @copyright  2024 Vasilis Daloukas
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_mmogame_generator_testcase extends advanced_testcase {

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
            ['course' => $course, 'qbank' => 'moodlequestion', 'qbankparams' => $categoryid,
                'numgame' => 1, 'type' => 'quiz', 'model' => 'aduel', 'kinduser' => 'guid']);

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

        $game = $this->getDataGenerator()->create_module('mmogame',
            ['course' => $course, 'qbank' => 'moodlequestion', 'qbankparams' => $categoryid,
                'numgame' => 1, 'type' => 'quiz', 'model' => 'alone', 'kinduser' => 'guid']);
        $records = $DB->get_records('mmogame', ['course' => $course->id], 'id');
        $this->assertEquals(1, count($records));
        $this->assertArrayHasKey($game->id, $records);
    }
}
