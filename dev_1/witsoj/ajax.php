<?php
#echo "Hello World" ;

#requiring this file so that we can access its functionality

require_once('../../../../config.php');

define('ASSIGNFEEDBACK_WITSOJ_STATUS_PENDING', 0);
define('ASSIGNFEEDBACK_WITSOJ_STATUS_JUDGING', 1);

$gradeid = $_POST['sendID'];
global $DB;

// $sql = "SELECT * FROM {assignfeedback_witsoj} s ".
// 	" WHERE userid=".$id . " AND assignment = ".$assignid .
// 	" ORDER BY timemodified ASC ";
// $rec = $DB->get_records_sql($sql);

$feedbackcomment = $DB->get_record('assignfeedback_witsoj', array('grade'=>$gradeid));
$feedbackstatus = $feedbackcomment->status ;
$assignmentid = $feedbackcomment->assignment;
$current_time = $feedbackcomment->timemodified;


if($feedbackstatus  == ASSIGNFEEDBACK_WITSOJ_STATUS_PENDING){
	console.log("reached if statement");
	$sql = " SELECT position FROM
		(SELECT grade, @position := @position + 1 as position FROM
		(SELECT @position := 0) A,
		(SELECT * FROM mdl_assignfeedback_witsoj WHERE status= 0 ORDER BY timemodified ASC) B
	    WHERE assignment= '$assignmentid') C
	    WHERE grade = '$gradeid'";
	$queue_size = -1;
	$sql2 = "SELECT COUNT(*) FROM mdl_assignfeedback_witsoj WHERE status = 0";
	$rec = $DB->get_records_sql($sql);
	$rec2 = $DB->get_records_sql($sql2);
	$positionofSub = -1;
	foreach($rec as $position => $v){
			$positionofSub = $position;
	}
	foreach ($rec2 as $key => $value) {
		$queue_size = $key;
	}
	// $queue_size++;
	echo "Your position in the queue is " .$positionofSub. " out of ".$queue_size;

}elseif ($feedbackstatus  == ASSIGNFEEDBACK_WITSOJ_STATUS_JUDGING){
	console.log("reached elseif statement");
	echo "Judging" ;

}else{
	console.log("reached else statement");
	echo "GRADED";
	//echo $feedbackcomment->commenttext;
}
?>
