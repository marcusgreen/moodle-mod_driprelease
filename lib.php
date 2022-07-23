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

    // Add the database record.
    // $update = new stdClass();
    // $update->name = $formdata->name;
    // $update->timemodified = time();
    // $update->timecreated = time();
    // $update->course = $formdata->course;

    $id = $DB->insert_record('driprelease', $moduleinstance);
    return $id;

    // if ($data = $mform->get_data()) {
    // $timing['driprelease'] = $schedulerid;
    // $timing['timestart'] = $data->timestart;
    // $timing['repeatcount'] =(int) $data->repeatgroup['repeat'];
    // $timing['sessioncount'] = (int) $data->sessionsgroup['sessioncount'];
    // $timing['timefinish'] = $data->timefinish;
    // $timing['activitiespersession'] = $data->activitiespersession;
    // }

    // $timing = (object) $timing;

    // $id = $DB->insert_record('driprelease_timing',$timing);

    // return $id;
}

function get_contents(int $courseid, $options): array {
    $contents = \core_course_external::get_course_contents($courseid, $options);
    return $contents;
}

function show_contents($mform, $contents ) {
    global $DB;
    foreach ($contents as $content) {
        if (count($content['modules']) > 0) {
            $group = [];

            foreach ($content['modules'] as $module) {
                $details = $DB->get_record($module['modname'], ['id' => $module['instance']]);
                if(isset($details->intro)) {
                    $module['intro'] = pad($details->intro, 12);
                } else {
                      $module['intro'] = pad(" ",12," ");
                }
                $el = $mform->createElement('advcheckbox', $module['id']);
                $mform->setDefault('activities['.$module['id'].']',1);
                $group[] = $el;

            }
            $mform->addElement('html',"<div class='hide'>");
            $mform->addGroup($group, 'activities','', ' ',true,);
            $mform->addElement('html',"</div>");
        }
    }
    return $mform;
}
function pad($string, $lettercount) {
    $chopped = mb_substr(strip_tags($string),0,$lettercount);
    $chopped .= "...";

    $padded = str_pad($chopped,20, " ");
    return $padded;
}

function get_availability($module) {
        global $DB;
        $availability = [];

        $record = $DB->get_record('course_modules', ['id' => $module['id']], 'availability');
    if($record->availability > "") {
        $decoded = json_decode($record->availability);
        foreach($decoded->c as $restriction) {
            if($restriction->type == "date") {
                $operator = $restriction->d;
                if($operator == ">=") {
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
    global $DB;
    if ($data = $mform->get_data()) {
           $moduleinstance->repeatcount = $data->repeatgroup['repeatcount'];
           $moduleinstance->sessioncount = $data->sessionsgroup['sessioncount'];

    }
    // $week = strtotime('7 day', 0);
    // $activitiespersession = $data->activitiespersession;
    // $schedulestart = $data->timestart;
    // $schedulefinish = $data->timefinish;
    // $duration = $schedulefinish - $schedulestart;
    // $weekcount = $duration / $week;
    // $weekspersession = round($weekcount / $data->sessionsgroup['numberofsessions']);
    // $sessionlength = ($week * $weekspersession);

    // $activities = get_sequence($data);

    // $start = $schedulestart;
    // for ($week = 0; $week < $weekcount; $week++) {
    // $sessions[$week] = $schedulestart;
    // $start = $start + ($week * $weekspersession);
    // }
    // $activitycounter = 0;
    // $from = $schedulestart;
    // $activitycount = count($activities);
    // foreach ($sessions as $from) {
    // $to = $from + $sessionlength;
    // for ($i = 0; $i < $activitiespersession; $i++) {
    // if($activitycounter >= $activitycount) {
    // continue;
    // }
    // $availability = '{"op":"&","c":[{"type":"date","d":">=","t":' . $from . '},{"type":"date","d":"<","t":' . $to . '}],"showc":[true,true]}';
    // $DB->set_field('course_modules', 'availability', $availability, ['id' => $activities[$activitycounter]]);
    // $activitycounter++;
    // }
    // }
    // }

    $moduleinstance->timemodified = time();
    $moduleinstance->id = $moduleinstance->instance;
    return $DB->update_record('driprelease', $moduleinstance);
}

function get_sequence($data) {
    global $DB;
    $sql = 'select sequence from {course_sections} where course = :course and sequence > "" order by section';
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
 * Removes an instance of the mod_driprelease from the database.
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

    $DB->delete_records('driprelease', array('id' => $id));

    return true;
}
