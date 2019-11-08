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
 * This file defines the admin settings for this plugin
 *
 * @package   assignfeedback_witsoj
 * @copyright 2012 NetSpot {@link http://www.netspot.com.au}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$settings->add(new admin_setting_configcheckbox('assignfeedback_witsoj/default',
                   new lang_string('default', 'assignfeedback_witsoj'),
                   new lang_string('default_help', 'assignfeedback_witsoj'), 0));

$settings->add(new admin_setting_configtext('assignfeedback_witsoj/basepath',
                   new lang_string('basepath', 'assignfeedback_witsoj'),
                   new lang_string('basepath_help', 'assignfeedback_witsoj'), '/var/www/'));

$settings->add(new admin_setting_configtext('assignfeedback_witsoj/markers',
                   new lang_string('markers', 'assignfeedback_witsoj'),
                   new lang_string('markers_help', 'assignfeedback_witsoj'), ''));

$settings->add(new admin_setting_configtext('assignfeedback_witsoj/secret',
                   new lang_string('secret', 'assignfeedback_witsoj'),
                   new lang_string('secret_help', 'assignfeedback_witsoj'), md5("Secret")));

$settings->add(new admin_setting_configtext('assignfeedback_witsoj/languages',
                   new lang_string('languages', 'assignfeedback_witsoj'),
                   new lang_string('languages_help', 'assignfeedback_witsoj'), ''));

if (isset($CFG->maxbytes)) {

    $name = new lang_string('maximumtestcasesize', 'assignfeedback_witsoj');
    $description = new lang_string('configmaxbytes', 'assignfeedback_witsoj');

    $maxbytes = get_config('assignfeedback_witsoj', 'maxbytes');
    $element = new admin_setting_configselect('assignfeedback_witsoj/maxbytes',
                                              $name,
                                              $description,
                                              $CFG->maxbytes,
                                              get_max_upload_sizes($CFG->maxbytes, 0, 0, $maxbytes));
    $settings->add($element);
}

#$setting->set_advanced_flag_options(admin_setting_flag::ENABLED, false);
#$setting->set_locked_flag_options(admin_setting_flag::ENABLED, false);

#$settings->add($setting);
