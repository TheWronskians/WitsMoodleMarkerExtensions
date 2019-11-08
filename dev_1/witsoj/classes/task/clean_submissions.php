<?php

namespace assignfeedback_witsoj\task;

//require_once('../../../config.php');
require_once($CFG->libdir . '/accesslib.php');
require_once($CFG->dirroot . '/mod/assign/locallib.php');
require_once($CFG->dirroot . '/mod/assign/feedback/witsoj/locallib.php');

class clean_submissions extends \core\task\scheduled_task {      
    public function get_name() {
        // Shown in admin screens
	return "Clean WitsOJ Submissions";
    }

    public function execute() {       
	error_log("clean_submissions");
	print("clean_submissions");
	\assign_feedback_witsoj::clean();
	\assign_feedback_witsoj::prod();
    }
}
