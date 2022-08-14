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
 * Library of interface functions and constants.
 *
 * @package     mod_driprelease
 * @copyright   2022 Marcus Green
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();
use \core_availability\info_module;
use \core_availability\info;
/**
 * Return if the plugin supports $feature.
 *
 * @param string $feature Constant representing the feature.
 * @return true | null True if the feature is supported, null otherwise.
 */
function driprelease_supports($feature) {
    switch ($feature) {
        case FEATURE_MOD_INTRO:
            return true;
        case FEATURE_MOD_PURPOSE:
            return MOD_PURPOSE_ADMINISTRATION;
        default:
            return null;
    }
}

/**
 * Saves a new instance of the mod_driprelease into the database.
 *
 * Given an object containing all the necessary data, (defined by the form
 * in mod_form.php) this function will create a new instance and return the id
 * number of the instance.
 *
 * @param object $moduleinstance An object from the form.
 * @param mod_driprelease_mod_form $mform The form.
 * @return int The id of the newly inserted record.
 */
function driprelease_add_instance($moduleinstance, $mform = null) {
    global $DB;

    $moduleinstance->timecreated = time();

    $id = $DB->insert_record('driprelease', $moduleinstance);
    return $id;

}


 /**
  *  Get an array of topics with an array of items in a course
  *
  * @param integer $courseid
  * @param array $options
  * @return array
  */
function get_course_contents(int $courseid, array $options): array {
    $contents = \core_course_external::get_course_contents($courseid, $options);
    return $contents;
}

function get_contents_table(MoodleQuickForm $mform, array $contents, \stdClass $current) {
    global $DB;
    foreach ($contents as $content) {
        if (count($content['modules']) > 0) {
            $group = [];

            foreach ($content['modules'] as $module) {
                $details = $DB->get_record($module['modname'], ['id' => $module['instance']]);
                if (isset($details->intro)) {
                    $module['intro'] = pad($details->intro, 12);
                } else {
                    $module['intro'] = pad(" ", 12, " ");
                }
                $el = $mform->createElement('advcheckbox', $module['id']);
                $mform->setDefault('activities[' . $module['id'] . ']', 1);
                $group[] = $el;
            }

            $mform->addElement('html', "<div class='hide'>");
            $mform->addGroup($group, 'activities', '', ' ', true);
            $mform->addElement('html', "</div>");
        }
    }
    return $mform;
}
/**
 * Get the course module items with availability dates calculated based
 * on supplied form values.
 *
 * @param array $contents
 * @param object $current
 * @return array
 */
function get_content_data(array $contents, stdClass $current) :array {
    $timing['start'] = $current->schedulestart;
    $timing['end'] = $current->schedulefinish;
    $timing['repeatcount'] = $current->repeatcount;
    $timing['persession'] = $current->activitiespersession;
    global $DB;
    $contentcounter = 0;
    $sessioncounter = 0;
    foreach ($contents as $content) {
        if (count($content['modules']) > 0) {
            $availability = [];

            foreach ($content['modules'] as $module) {
                if ($contentcounter % ($current->activitiespersession + 1) == 0) {
                    $module = calculate_availability($module, $timing, $sessioncounter);
                    $availability['start'] = $module['start'];
                    $sessioncounter++;
                    $row['issessionrow'] = true;
                    $row['sessioncounter'] = $sessioncounter;
                    $row['startformatted'] = $module['startformatted'];
                    $row['start'] = $module['start'];
                    $data['activities'][] = $row;
                    $contentcounter++;
                    continue;
                }
                $contentcounter++;


                $questions = $DB->get_records('quiz_slots',['quizid' => $module['instance']]);
                $details = $DB->get_record($module['modname'], ['id' => $module['instance']]);
                //$availability = $DB->get_record('course_modules', ['id' => $module['instance']], 'availability');

                $module['questioncount'] = count($questions);
                $module['name'] = $details->name;
                $module['intro'] = strip_tags($details->intro);
                $module['availability']  = get_availability($module);
                $module['dripavailability']['start'] = $availability['start'];
                $module['issessionrow'] = false;
                $data['activities'][] = $module;
            }
        }
    }
    return $data;
}
function pad($string, $lettercount) {
    $chopped = mb_substr(strip_tags($string),0,$lettercount);
    $chopped .= "...";

    $padded = str_pad($chopped, 20, " ");
    return $padded;
}

function calculate_availability(array $module, array $timing, $contentcounter ) {
    $weekrepeat = $contentcounter * $timing['repeatcount'];
    $start = strtotime(' + '. $weekrepeat .'week', $timing['start']);
    $module['start'] = $start;
    $module['startformatted'] = date('D d M Y h:h', $start);
    return $module;
}
function get_availability(array $module) {
    global $DB;
    $availability = [];

    $record = $DB->get_record('course_modules', ['id' => $module['id']], 'availability');
    if ($record->availability > "") {
        $decoded = json_decode($record->availability);
        foreach ($decoded->c as $restriction) {
            if ($restriction->type == "date") {
                $operator = $restriction->d;
                if ($operator == ">=") {
                    $datetime = $restriction->t;
                    $availability['from'] = date('D d M Y h:h', $datetime);
                } else {
                    $datetime = $restriction->t;
                    $availability['to'] = date('D d M Y h:h', $datetime);
                }
            }
        }
    }
    return $availability;
}
/**
 * Updates an instance of the mod_driprelease in the database.
 *
 * Given an object containing all the necessary data (defined in mod_form.php),
 * this function will update an existing instance with new data.
 *
 * @param object $moduleinstance An object from the form in mod_form.php.
 * @param mod_driprelease_mod_form $mform The form.
 * @return bool True if successful, false otherwise.
 */
function driprelease_update_instance($moduleinstance, $mform = null) {
    global $DB, $COURSE;
    $options = [["name" => "modname", "value" => 'quiz']];

    $contents = get_course_contents($COURSE->id, $options);
    $data = get_content_data($contents, $mform->get_current());
    update_availability($data['activities']);

    if ($formdata = $mform->get_data()) {
           $moduleinstance->repeatcount = $formdata->repeatgroup['repeatcount'];
    }
    $moduleinstance->timemodified = time();
    $moduleinstance->id = $moduleinstance->instance;
    return $DB->update_record('driprelease', $moduleinstance);
}
/**
 * $fromform = $mform->get_data();
 *
 * @param [type] $data
 * @return void
 */
function update_availability($data) {
    global $DB;

    foreach ($data as $module) {
        if (!$module['issessionrow']) {
            // $modinfo = get_fast_modinfo($COURSE->id, $USER->id);
            // $info = $modinfo->get_cm($module['id']);

            // $availability = json_decode($info->availability);
            $s = '{"op":"&","c":[{"type":"date","d":">=","t":'.$module['dripavailability']['start'].'}],"showc":[true]}';
            $DB->set_field('course_modules', 'availability', $s, ['id' => $module['id']]);

        }

    }
}

function get_sequence($data) {
    global $DB;
    $sql = 'SELECT sequence FRO {course_sections} WHERE course = :course AND sequence > "" ORDER BY section';
    $coursesequence = $DB->get_records_sql($sql, ['course' => $data->course]);
    $activitiesordered = [];
    $i = 0;
    foreach ($coursesequence as $item) {
        $temp = explode(',', $item->sequence);
        foreach ($temp as $t) {
            if (array_key_exists($t, $data->activities)) {
                $activitiesordered[$i] = $t;
                $i++;
            }
        }
    }
    return $activitiesordered;
}

/**
 * Remove an instance of the mod_driprelease from the database.
 *
 * @param int $id Id of the module instance.
 * @return bool True if successful, false on failure.
 */
function driprelease_delete_instance($id) {
    global $DB;

    $exists = $DB->get_record('driprelease', array('id' => $id));
    if (!$exists) {
        return false;
    }

    $DB->delete_records('driprelease', ['id' => $id]);

    return true;
}
