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
 * Unit tests for mod_driprelease.
 *
 * @package    mod_driprelease
 * @copyright  2022 Marcus Green
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();

global $CFG;

/**
 * Unit tests for XXX
 *
 * @copyright  2022 Marcus Green
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class scheduling_test extends \advanced_testcase {

    public function test_something(){
        global $CFG, $USER;
        $this->resetAfterTest();

        $this->setAdminUser();

        // Create course with availability enabled
        $CFG->enableavailability = true;
        $generator = $this->getDataGenerator();
        $course = $generator->create_course(['enablecompletion' => 1]);
        $quizgenerator = $generator->get_plugin_generator('mod_quiz');
        $quiz = $quizgenerator->create_instance(array('course' => $course->id,
                'grademethod' => QUIZ_GRADEHIGHEST, 'grade' => 100.0, 'sumgrades' => 10.0,
                'attempts' => 10));


        $modinfo = get_fast_modinfo($course);
        $cm = $modinfo->get_cm($quiz->cmid);
        $info = new \core_availability\mock_info($course, $USER->id);
    }

    public function test_group_availability(){
        global $DB, $CFG;
        $CFG->enableavailability = true;

        $generator = $this->getDataGenerator();
        // $course = $generator->create_course(['enablecompletion' => 1]);
        $course = $generator->create_course();

        $quizgenerator = $generator->get_plugin_generator('mod_quiz');
        $quiz = $quizgenerator->create_instance(array('course' => $course->id,
                'grademethod' => QUIZ_GRADEHIGHEST, 'grade' => 100.0, 'sumgrades' => 10.0,
                'attempts' => 10));
          $modinfo = get_fast_modinfo($course);
          $cm = $modinfo->get_cm($quiz->cmid);
        // Add availability conditions.
          $availability =  '{"op":"&","c":[{"type":"date","d":">=","t":1650841200}],"showc":[true]}';
          $DB->set_field('course_modules', 'availability', $availability,
                        ['id' => $cm->id]);
    }

}
