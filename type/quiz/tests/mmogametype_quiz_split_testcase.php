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
 * Generator tests class for mmogametype_quiz_split.
 *
 * @package    mmogametype_quiz_split
 * @category   test
 * @copyright  2024 Vasilis Daloukas
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mmogametype_quiz_split_testcase extends advanced_testcase {

    /**
     * Test for playing a quiz.
     */
    public function test_quiz_split() {
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
        $generator->create_multichoice_question($categoryid, '1', '1',
            ['ONE', 'TWO', 'THREE', 'FOUR', 'FIVE', 'SIX', 'SEVEN'], $answerids, $answertexts);

        $generator->create_multichoice_question($categoryid, '2', '1',
            ['TWO', 'ONE', 'THREE', 'FOUR', 'FIVE', 'SIX', 'SEVEN'], $answerids, $answertexts);

        $rgame = $this->getDataGenerator()->create_module('mmogame',
            ['course' => $course, 'qbank' => 'moodlequestion', 'categoryid1' => $categoryid, 'pin' => rand(),
                'numgame' => 1, 'type' => 'quiz', 'model' => 'split', 'typemodel' => 'quiz,split',
                'kinduser' => 'guid', 'enabled' => 1]);
        $records = $DB->get_records('mmogame', ['course' => $course->id], 'id');
        $this->assertEquals(1, count($records));
        $this->assertArrayHasKey($rgame->id, $records);
        $rgame = reset( $records);
        $this->assertEquals($rgame->qbankparams, $categoryid);

        $mmogame = mod_mmogame\local\mmogame::create( new mmogame_database_moodle(), $rgame->id);

        // Set state to playing.
        $mmogame->update_state( 1);

        global $USER;

        for ($step = 1; $step <= 100; $step++) {
            $classgetattempt = new mmogametype_quiz\external\get_attempts_split();
            $result = $classgetattempt->execute($rgame->id, 'guid', 'testq',
                '1,2,3,4', '0,1,2,3');
            $splits = [0, 1, 2, 2];
            $iscorrects = [0, 1, 1, 1];
            $attempts = $timestarts = $timeanswers = $answers = [];
            $pos = -1;
            $newsplits = [];
            foreach ($splits as $split) {
                $pos++;
                $a = explode(',', $result['attempts'][$split]);
                $attempts[] = array_shift($a);
                $result['attempts'][$split] = implode(',', $a);

                if (count($a) == 0) {
                    $newsplits[] = $split;
                }

                $a = explode(',', $result['attemptqueryids'][$split]);
                $queryid = array_shift($a);
                $result['attemptqueryids'][$split] = implode(',', $a);

                $answerids = $result['queryanswerids'][$queryid];
                $iscorrect = $iscorrects[$pos];
                $answers[] = $iscorrect ? $answerids[0] : $answerids[1];

                $timestarts[] = time() - 3000;
                $timeanswers[] = time();
            }
            $classsendanswers = new mmogametype_quiz\external\send_answers_split();
            $classsendanswers->execute($rgame->id, 'guid', 'testq',
                implode(',', $splits), implode(',', $attempts),
                implode(',', $iscorrects), implode(',', $answers),
                implode(',', $timestarts), implode(',', $timeanswers),
                implode(',', $newsplits));
        }
        $rec = $DB->get_record_sql( "SELECT COUNT(*) FROM {mmogame_quiz_attempts}");
    }
}
