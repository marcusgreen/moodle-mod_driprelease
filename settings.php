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
 * Data to control defaults when creating an instance of dripreleaswe
 *
 * @package    mod_driprelease
 * @copyright  2022Marcus Green
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();

if ($ADMIN->fulltree) {
        $settings->add(new admin_setting_configtext(
                'driprelease/repeatcount',
                get_string('repeatcount', 'driprelease'),
                get_string('repeatcount_text', 'driprelease'),
                '1',
                PARAM_ALPHANUMEXT,
                3
        ));

        $settings->add(new admin_setting_configtext(
                'driprelease/activitiespersession',
                get_string('activitiespersession', 'driprelease'),
                get_string('activitiespersession_text', 'driprelease'),
                '5',
                PARAM_ALPHANUMEXT,
                3
        ));
        $settings->add(new admin_setting_configtext(
                'driprelease/defaultname',
                get_string('defaultname', 'driprelease'),
                get_string('defaultname_text', 'driprelease'),
                'Content schedule',
                PARAM_RAW,
                40
        ));
}
