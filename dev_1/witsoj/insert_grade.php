<?php

require_once('../../../../config.php');
require_once($CFG->dirroot . '/mod/assign/locallib.php');
require_once($CFG->libdir . '/gradelib.php');

$inputJSON = file_get_contents('php://input');  // Get input from the client
$params = json_decode($inputJSON, TRUE);

$id = required_param('id', PARAM_INT);

$auth = $params['witsoj_token'];
if($auth != get_config('assignfeedback_witsoj', 'secret')){
	die('{"status" : "Bad Auth"}');
}

$markerid = intval($params['markerid']);
$userid = intval($params['userid']);
$newgrade = floatval($params['grade']);
$status = intval($params['status']);
$oj_testcases = $params['oj_testcases'];
$oj_feedback = $params['oj_feedback'];


list ($course, $cm) = get_course_and_cm_from_cmid($id, 'assign');

//require_login($course, true, $cm);

$context = context_module::instance($cm->id);

//require_capability('mod/assign:view', $context);

$assign = new assign($context, $cm, $course);
$plugin = $assign->get_feedback_plugin_by_type("witsoj");
if(!$plugin->is_enabled()){
	die('{"status" : "Assignment does not use witsoj"}');
}
$plugin->returned_grade($markerid, $userid, $newgrade, $status, $oj_testcases, $oj_feedback);
die('{"status" : "0"}');

