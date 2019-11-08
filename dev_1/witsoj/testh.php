<?php

class testh{

  public function helloWorld(){
    return 'Hello world';
  }

  public function get_feedback_witsoj($gradeid)
  {
      global $DB;
      return $DB->get_record('assignfeedback_witsoj', array('grade'=>$gradeid));
  }

}

?>
