<?php
// This file is part of Moodle - https://moodle.org/
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
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

/**
 * The main mod_driprelease configuration form.
 *
 * @package     mod_driprelease
 * @copyright   2022 Marcus Green
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/course/moodleform_mod.php');

/**
 * Module instance settings form.
 *
 * @package     mod_driprelease
 * @copyright   2022 Marcus Greebn
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_driprelease_mod_form extends moodleform_mod {
    /** @var array options to be used with date_time_selector fields in the quiz. */
    public static $datefieldoptions = array('optional' => false);

    /**
     * Defines forms elements
     */
    public function definition() {
        global $CFG, $COURSE, $PAGE, $OUTPUT ,$DB;

        $current = $this->get_current();

        $PAGE->requires->js_call_amd('mod_driprelease/modform', 'init');

        require_once($CFG->dirroot . '/course/externallib.php');

        // $activitytype = 'page';
        $activitytype = 'quiz';

        $options = [["name" => "modname", "value" => $activitytype]];
        $contents = get_course_contents($COURSE->id, $options);

        $mform = $this->_form;

        // Adding the "general" fieldset, where all the common settings are shown.
        $mform->addElement('header', 'general', get_string('general', 'form'));

        // Adding the standard "name" field.
        $mform->addElement('text', 'name', get_string('name', 'driprelease'), ['size' => '64']);
        $mform->setType('name', PARAM_TEXT);

        $mform->setDefault('name',  get_config('driprelease', 'defaultname'));

        $this->standard_intro_elements();


        $mform->addRule('name', null, 'required', null, 'client');
        $mform->addRule('name', get_string('maximumchars', '', 255), 'maxlength', 255, 'client');
        $mform->addHelpButton('name', 'dripreleasename', 'mod_driprelease');

        $mform->addElement('header', 'timing', get_string('timing', 'driprelease'));
        $mform->setExpanded('timing');

        // Start dates.
        $mform->addElement(
            'date_time_selector',
            'schedulestart',
            get_string('schedulestart', 'driprelease'),
           $current->schedulestart ?? 0
        );

        $mform->addHelpButton('schedulestart', 'schedulestart', 'driprelease');

        $repeatcount = $current->repeatcount ?? get_config('driprelease','repeatcount');
        $group[] = $mform->createElement('text', 'repeatcount', get_string('repeat', 'driprelease'), ['value'=> $repeatcount ?? 0,'size' => '3']);
        $group[] = $mform->createElement('html', get_string('weeks', 'driprelease'));

        $mform->addGroup($group, 'repeatgroup', get_string('repeat', 'driprelease') . '&nbsp;&nbsp;','', ' ', false);
        $mform->addRule('repeatgroup', null, 'required', null ,'client');

        $mform->setType('repeatgroup', PARAM_RAW);
        $mform->addHelpButton('repeatgroup', 'repeat', 'mod_driprelease');

        // Finish dates.
        $mform->addElement(
            'date_time_selector',
            'schedulefinish',
            get_string('schedulefinish', 'driprelease'),
            $current->schedulefinish ?? 0
        );
        $mform->setType('schedulefinish', PARAM_INT);

        $week = strtotime('7 day', 0);
        $sessioncount = get_config('sessioncount','driprelease');
        $finishdate = time() + ($week * $sessioncount);
        $mform->setDefault('schedulefinish', $finishdate);

        $mform->addHelpButton('schedulefinish', 'schedulefinish', 'mod_driprelease');

        $activitiespersession = $current->activitiespersession ?? get_config('driprelease','activitiespersession');

        $mform->addElement('text', 'activitiespersession', get_string('activitiespersession', 'driprelease'), ['value' => $activitiespersession, 'size' => '3']);
        $mform->addRule('activitiespersession', null, 'required', null, 'client');
        $mform->setType('activitiespersession', PARAM_INT);

        $mform->addHelpButton('activitiespersession', 'activitiespersession', 'mod_driprelease');

        $mform->addElement('header', 'activityheader', get_string('activities', 'mod_driprelease'));
        $mform->setExpanded('activityheader');

        $contentcounter = 0;
        $sessioncounter = 0;
        foreach ($contents as $content) {
            if (count($content['modules']) > 0) {
                foreach ($content['modules'] as $module) {

                    if ($contentcounter % ($current->activitiespersession + 1) == 0) {
                        $sessioncounter++;
                        $row['issessionrow'] = true;
                        $row['sessioncounter'] = $sessioncounter;
                        $data['activities'][] = $row;
                        $contentcounter++;
                        continue;
                    }
                    $contentcounter++;


                    $questions = $DB->get_records('quiz_slots',['quizid' => $module['instance']]);
                    $details = $DB->get_record($module['modname'], ['id' => $module['instance']]);
                    $availability = $DB->get_record('course_modules', ['id' => $module['instance']], 'availability');
                    $module['questioncount'] = count($questions);
                    $module['name'] = $details->name;
                    $module['intro'] = strip_tags($details->intro);
                    $module['availability']  = get_availability($module);
                    $data['activities'][] = $module;
                }
            }
        }
        //$mform = get_contents_table($mform, $contents, $current);
        $data['wwwroot'] = $CFG->wwwroot;
        $out = $OUTPUT->render_from_template('mod_driprelease/activities', $data);
        $mform->addElement('HTML',$out);

        // Add standard elements.
        $this->standard_coursemodule_elements();

        // Add standard buttons.
        $this->add_action_buttons();
    }

    public function validation($fromform, $data) {
        parent::validation($fromform, $data);
        $errors = [];
        if ($fromform['activitiespersession'] < 1) {
            $errors['activitiespersession'] = get_string('activitiespersessionerror','driprelease');
        }
        if ($fromform['repeatgroup']['repeatcount'] < 1) {
            $errors['repeatgroup'] = get_string('repeatcounterror', 'driprelease');
        }

        if ($errors) {
            return $errors;
        } else {
            return true;
        }
    }
}
