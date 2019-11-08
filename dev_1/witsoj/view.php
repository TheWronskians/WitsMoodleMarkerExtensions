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
 * This file is the entry point to the assign module. All pages are rendered from here
 *
 * @package   mod_assign
 * @copyright 2012 NetSpot {@link http://www.netspot.com.au}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../../../config.php');
require_once($CFG->dirroot . '/mod/assign/locallib.php');
require_once($CFG->libdir . '/gradelib.php');

$id = required_param('id', PARAM_INT);
$userid = required_param('userid', PARAM_INT);

list ($course, $cm) = get_course_and_cm_from_cmid($id, 'assign');

require_login($course, true, $cm);

$context = context_module::instance($cm->id);

//require_capability('mod/assign:view', $context);

$assign = new assign($context, $cm, $course);
$urlparams = array('id' => $id,
                  'action' => optional_param('action', '', PARAM_ALPHA),
                  'rownum' => optional_param('rownum', 0, PARAM_INT),
                  'useridlistid' => optional_param('useridlistid', $assign->get_useridlist_key_id(), PARAM_ALPHANUM));

$url = new moodle_url('/mod/assign/feedback/witsoj/view.php', $urlparams);
$PAGE->set_url($url);

// Update module completion status.
$assign->set_module_viewed();

print("CURR:");
var_dump($assign);
// Use this to check that the userid is valid and registered etc.
//var_dump($assign->get_participant(695));

$test = $assign->get_user_grade($userid, true);
//var_dump($test);
//$item = $this->get_grade($gradeid);
$test->grade = 45.42;
//$test->attemptnumber = intval($test->attemptnumber);
$test->ojtests = "HelloWorld";
//print("CURR2:");
//var_dump($test);
print("UPDATED GRADE:");
var_dump($assign->update_grade($test, false));

//$gi = $assign->get_grade_item();
//print("GRADEITEM:");
//var_dump($gi);
//print("USERGRADES:");
//$usergrades = assign_update_grades($assign, 2);
//var_dump($usergrades);

// THIS INSERTS INTO THE GRADE BOOK, BUT NOT INTO THE ASSIGNMENT GRADE
//$grades = array("rawgrade" => 100, "userid" => 695, "usermodified" => 2, "datesubmitted" => NULL, "dategraded" => NULL);
//grade_update(
//	'mod/assign', $course->id, 'mod', 'assign', 4, 0, $grades, NULL
//);

// Apply overrides.
$assign->update_effective_access($USER->id);

// Get the assign class to
// render the page.
echo $assign->view(optional_param('action', '', PARAM_ALPHA));
