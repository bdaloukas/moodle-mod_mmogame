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
 * mmogame generator tests
 *
 * @package    mod_mmogame
 * @category   test
 * @copyright  2024 Vasilis Daloukas
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use mod_mmogame\external\get_assets;

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
class mmogame_generator_testcase extends advanced_testcase {

    /**
     * Test the get_services web service.
     */
    public function test_services() {
        global $USER;

        $this->resetAfterTest();
        $this->setAdminUser();

        $course = $this->getDataGenerator()->create_course();

        $categoryid = 1;
        $rgame = $this->getDataGenerator()->create_module('mmogame',
            ['course' => $course, 'qbank' => 'moodlequestion', 'categoryid1' => $categoryid, 'pin' => rand(),
                'numgame' => 1, 'type' => 'quiz', 'model' => 'aduel', 'typemodel' => 'quiz,aduel',
                'kinduser' => 'guid', 'enabled' => 1]);

        // Test 1: Call without optional parameter avatars, colorpalettes.
        $class = new get_assets();
        $result = $class->execute($rgame->id, 'moodle', $USER->id);
        $this->assertArrayNotHasKey('avatars', $result);
        $this->assertArrayNotHasKey('colorpalettes', $result);

        // Test 2: Call with only 1 optional parameter avatars, colorpalettes.
        $result = $class->execute($rgame->id, 'moodle', $USER->id, 1, 1);
        $this->assertArrayHasKey('avatars', $result);
        $this->assertArrayHasKey('colorpalettes', $result);

        // Test 3: Call with 10 optional parameter avatars, colorpalettes.
        $result = $class->execute($rgame->id, 'moodle', $USER->id, 10, 10);
        $this->assertArrayHasKey('avatars', $result);
        $this->assertArrayHasKey('colorpalettes', $result);
    }
}
