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
 * This file contains the definition for the library class for comment feedback plugin
 *
 * @package   assignfeedback_witsoj
 * @copyright 2012 NetSpot {@link http://www.netspot.com.au}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

 /*
Edited by the Wronskians, 2018
 */

//defined('MOODLE_INTERNAL') || die();
define('ASSIGNFEEDBACK_WITSOJ_TESTCASE_FILEAREA', 'oj_testcases');

define('ASSIGNFEEDBACK_WITSOJ_STATUS_PENDING', 0);
define('ASSIGNFEEDBACK_WITSOJ_STATUS_JUDGING', 1);
define('ASSIGNFEEDBACK_WITSOJ_STATUS_COMPILEERROR', 2);
define('ASSIGNFEEDBACK_WITSOJ_STATUS_PRESENTATIONERROR', 3);
define('ASSIGNFEEDBACK_WITSOJ_STATUS_ACCEPTED', 4);
define('ASSIGNFEEDBACK_WITSOJ_STATUS_MIXED', 5);
define('ASSIGNFEEDBACK_WITSOJ_STATUS_INCORRECT', 6);
define('ASSIGNFEEDBACK_WITSOJ_STATUS_MARKERERROR', 7);
define('ASSIGNFEEDBACK_WITSOJ_STATUS_TIMELIMIT', 8);
define('ASSIGNFEEDBACK_WITSOJ_STATUS_ABORTED', 9);
define('ASSIGNFEEDBACK_WITSOJ_STATUS_TIMEOUT', 10);

define('ASSIGNFEEDBACK_WITSOJ_MARKER_FREE', 0);
define('ASSIGNFEEDBACK_WITSOJ_MARKER_BOOKED', 1);
define('ASSIGNFEEDBACK_WITSOJ_MARKER_BUSY', 2);

define('ASSIGNFEEDBACK_WITSOJ_MARKERTIMEOUT', 60);
define('ASSIGNFEEDBACK_WITSOJ_SUBMISSIONTIMEOUT', 10);
global $CFG;
//require_once($CFG->libdir . '/pagelib.php');


/**
 * Library class for comment feedback plugin extending feedback plugin base class.
 *
 * @package   assignfeedback_witsoj
 * @copyright 2012 NetSpot {@link http://www.netspot.com.au}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
//class assign_feedback_witsoj extends assign_feedback_plugin
class assign_feedback_witsoj
{
  /**
   * Connect to the stub database - used for unit testing
   * @codeCoverageIgnore
   */
    public function getConnection(){
      $mysql_host = getenv('MYSQL_HOST') ?: 'localhost';
      $mysql_user = getenv('MYSQL_USER') ?: 'root';
      $mysql_password = getenv('MYSQL_PASSWORD') ?: '';
      $connection_string = "mysql:host={$mysql_host};dbname=moodle";
      $db = new PDO($connection_string, $mysql_user, $mysql_password);
      return $db;
    }


    /**
     * Get the name of the online comment feedback plugin.
     * @return string
     * @codeCoverageIgnore
     */
     public function get_name()
    {
        return get_string('pluginname', 'assignfeedback_witsoj');
    }

    /**
     * Get the feedback comment from the database.
     *
     * @param int $gradeid
     * @return stdClass|false The feedback comments for the given grade if it exists.
     *                        False if it doesn't.
     */
     //Do not ignore for testing
    public function get_feedback_witsoj($gradeid)
    {
      $mysql_host = getenv('MYSQL_HOST') ?: 'localhost';
      $mysql_user = getenv('MYSQL_USER') ?: 'root';
      $mysql_password = getenv('MYSQL_PASSWORD') ?: '';
      $connection_string = "mysql:host={$mysql_host};dbname=moodle";
      $DB = new PDO($connection_string, $mysql_user, $mysql_password);
      //  global $DB;
      //  return $DB->get_record('assignfeedback_witsoj', array('grade'=>$gradeid));
        $getter=$DB->prepare("SELECT * FROM mdl_assignfeedback_witsoj WHERE id=$gradeid");
        $getter->execute();
        $result=$getter->fetchObject();
        return $result;
    }

    /**
     * Get quickgrading form elements as html.
     *
     * @param int $userid The user id in the table this quickgrading element relates to
     * @param mixed $grade - The grade data - may be null if there are no grades for this user (yet)
     * @return mixed - A html string containing the html form elements required for quickgrading
     * @codeCoverageIgnore
     */
    public function get_quickgrading_html($userid, $grade)
    {
        $commenttext = '';
        if ($grade) {
            $feedbackcomments = $this->get_feedback_witsoj($grade->id);
            if ($feedbackcomments) {
                $commenttext = $feedbackcomments->commenttext;
            }
        }

        $pluginname = get_string('pluginname', 'assignfeedback_witsoj');
        $labeloptions = array('for'=>'quickgrade_comments_' . $userid,
                              'class'=>'accesshide');
        $textareaoptions = array('name'=>'quickgrade_comments_' . $userid,
                                 'id'=>'quickgrade_comments_' . $userid,
                                 'class'=>'quickgrade');
        return html_writer::tag('label', $pluginname, $labeloptions) .
               html_writer::tag('textarea', $commenttext, $textareaoptions);
    }

    /**
     * Has the plugin quickgrading form element been modified in the current form submission?
     *
     * @param int $userid The user id in the table this quickgrading element relates to
     * @param stdClass $grade The grade
     * @return boolean - true if the quickgrading form element has been modified
     * @codeCoverageIgnore
     */
    public function is_quickgrading_modified($userid, $grade)
    {
        $commenttext = '';
        if ($grade) {
            $feedbackcomments = $this->get_feedback_witsoj($grade->id);
            if ($feedbackcomments) {
                $commenttext = $feedbackcomments->commenttext;
            }
        }
        // Note that this handles the difference between empty and not in the quickgrading
        // form at all (hidden column).
        $newvalue = optional_param('quickgrade_comments_' . $userid, false, PARAM_RAW);
        return ($newvalue !== false) && ($newvalue != $commenttext);
    }

    /**
     * Has the comment feedback been modified?
     *
     * @param stdClass $grade The grade object.
     * @param stdClass $data Data from the form submission.
     * @return boolean True if the comment feedback has been modified, else false.
     * @codeCoverageIgnore
     */
    public function is_feedback_modified(stdClass $grade, stdClass $data)
    {
        $commenttext = '';
        if ($grade) {
            $feedbackcomments = $this->get_feedback_witsoj($grade->id);
            if ($feedbackcomments) {
                $commenttext = $feedbackcomments->commenttext;
            }
        }

        if ($commenttext == $data->assignfeedbackcomments_editor['text']) {
            return false;
        } else {
            return true;
        }
    }


    /**
     * Override to indicate a plugin supports quickgrading.
     *
     * @return boolean - True if the plugin supports quickgrading
     */
    public function supports_quickgrading()
    {
        return false;
    }

    /**
     * Return a list of the text fields that can be imported/exported by this plugin.
     *
     * @return array An array of field names and descriptions. (name=>description, ...)
     * @codeCoverageIgnore
     */
    public function get_editor_fields()
    {
        return array('comments' => get_string('pluginname', 'assignfeedback_witsoj'));
    }

    /**
     * Get the saved text content from the editor.
     *
     * @param string $name
     * @param int $gradeid
     * @return string
     */
     //Do not ignore for testing
    public function get_editor_text($name, $gradeid)
    {
        if ($name == 'comments') {
            $feedbackcomments = $this->get_feedback_witsoj($gradeid);
            if ($feedbackcomments) {
                return $feedbackcomments->commenttext;
            }
        }

        return '';
    }

    /**
     * Get the saved text content from the editor.
     *
     * @param string $name
     * @param string $value
     * @param int $gradeid
     * @return string
     * @codeCoverageIgnore
     */
    public function set_editor_text($name, $value, $gradeid)
    {
        global $DB;

        if ($name == 'comments') {
            $feedbackcomment = $this->get_feedback_witsoj($gradeid);
            if ($feedbackcomment) {
                $feedbackcomment->commenttext = $value;
                return $DB->update_record('assignfeedback_witsoj', $feedbackcomment);
            } else {
                $feedbackcomment = new stdClass();
                $feedbackcomment->commenttext = $value;
                $feedbackcomment->commentformat = FORMAT_HTML;
                $feedbackcomment->grade = $gradeid;
                $feedbackcomment->assignment = $this->assignment->get_instance()->id;
                return $DB->insert_record('assignfeedback_witsoj', $feedbackcomment) > 0;
            }
        }

        return false;
    }

    /**
     * Save quickgrading changes.
     *
     * @param int $userid The user id in the table this quickgrading element relates to
     * @param stdClass $grade The grade
     * @return boolean - true if the grade changes were saved correctly
     * @codeCoverageIgnore
     */
    public function save_quickgrading_changes($userid, $grade)
    {
        global $DB;
        $feedbackcomment = $this->get_feedback_witsoj($grade->id);
        $quickgradecomments = optional_param('quickgrade_comments_' . $userid, null, PARAM_RAW);
        if (!$quickgradecomments && $quickgradecomments !== '') {
            return true;
        }
        if ($feedbackcomment) {
            $feedbackcomment->commenttext = $quickgradecomments;
            return $DB->update_record('assignfeedback_witsoj', $feedbackcomment);
        } else {
            $feedbackcomment = new stdClass();
            $feedbackcomment->commenttext = $quickgradecomments;
            $feedbackcomment->commentformat = FORMAT_HTML;
            $feedbackcomment->grade = $grade->id;
            $feedbackcomment->assignment = $this->assignment->get_instance()->id;
            return $DB->insert_record('assignfeedback_witsoj', $feedbackcomment) > 0;
        }
    }

    /**
     * Save the settings for feedback comments plugin
     *
     * @param stdClass $data
     * @return bool
     * @codeCoverageIgnore
     */
    public function save_settings(stdClass $data)
    {
        $this->set_config('language', $this->get_languages()[$data->assignfeedback_witsoj_language]);
        $this->set_config('cpu_limit', $this->get_cpu_limits()[$data->assignfeedback_witsoj_limit_cpu]);
        $this->set_config('mem_limit', $this->get_mem_limits()[$data->assignfeedback_witsoj_limit_mem]);
        $this->set_config('pe_ratio', $this->get_presentation_error_ratios()[$data->assignfeedback_witsoj_presentation_error_ratio]);

        if (isset($data->assignfeedback_witsoj_testcases)) {
            file_save_draft_area_files(
                $data->assignfeedback_witsoj_testcases,
                $this->assignment->get_context()->id,
                                       'assignfeedback_witsoj',
                ASSIGNFEEDBACK_WITSOJ_TESTCASE_FILEAREA,
                0
            );
        }

        if ($data->assignfeedback_witsoj_enabled) {
            error_log("Searching for orphaned submissions.");
            $this->rejudge_orphans();
        }

        return true;
    }
    /**
    * @codeCoverageIgnore
    **/
    public function get_languages()
    {
        return explode(',', get_config('assignfeedback_witsoj', 'languages'));
    }
    /**
    * @codeCoverageIgnore
    **/
    public function get_cpu_limits()
    {
        return array("1", "5", "10", "30", "60");
    }
    /**
    * @codeCoverageIgnore
    **/
    public function get_mem_limits()
    {
        return array("1MB", "2MB", "4MB", "8MB", "16MB", "32MB", "64MB");
    }
    /**
    * @codeCoverageIgnore
    **/
    public function get_presentation_error_ratios()
    {
        return array("1.0", "0.9", "0.8", "0.7", "0.6", "0.5", "0.4", "0.3", "0.2", "0.1", "0.0");
    }
    /**
    * @codeCoverageIgnore
    **/
    public function get_callback_url()
    {
        global $CFG;
        return $CFG->wwwroot . "/mod/assign/feedback/witsoj/insert_grade.php?id=" . $this->assignment->get_context()->instanceid;
    }
    /**
    * @codeCoverageIgnore
    **/
    public function get_rejudge_url($userid)
    {
        global $CFG;
        $id = $this->assignment->get_context()->instanceid;
        return $CFG->wwwroot . "/mod/assign/view.php?id=$id&action=viewpluginpage&pluginsubtype=assignfeedback&plugin=witsoj&pluginaction=rejudge&userid=$userid";
    }
    /**
    * @codeCoverageIgnore
    **/
    public function get_rejudge_all_url()
    {
        global $CFG;
        $id = $this->assignment->get_context()->instanceid;
        return $CFG->wwwroot . "/mod/assign/view.php?id=$id&action=viewpluginpage&pluginsubtype=assignfeedback&plugin=witsoj&pluginaction=rejudgeall";
    }
    /**
    * @codeCoverageIgnore
    **/
    public function get_prod_url()
    {
        global $CFG;
        $id = $this->assignment->get_context()->instanceid;
        return $CFG->wwwroot . "/mod/assign/view.php?id=$id&action=viewpluginpage&pluginsubtype=assignfeedback&plugin=witsoj&pluginaction=prod";
    }
    /**
    * @codeCoverageIgnore
    **/
    public function can_rejudge()
    {
        if (has_capability("mod/assign:managegrades", $this->assignment->get_context())) {
            return true;
        } else {
            return false;
        }
    }
    /**
    * @codeCoverageIgnore
    **/
    public function get_details_url($userid)
    {
        global $CFG;
        $id = $this->assignment->get_context()->instanceid;
        return $CFG->wwwroot . "/mod/assign/view.php?id=$id&action=viewpluginpage&pluginsubtype=assignfeedback&plugin=witsoj&pluginaction=viewdetails&userid=$userid";
    }
    /**
    * @codeCoverageIgnore
    **/
    public static function prod()
    {
        self::clean();
        while ($marker = self::marker_book()) {
            if ($marker) {
                error_log("Prod: booked marker $marker->id");
                if (!self::judge_next_submission($marker)) { // Marker is freed if no submissions
                    error_log("No more submissions: $marker->id");
                    break;
                }
            } else {
                error_log("Prod: no free marker");
            }
        }
    }
    /**
    * @codeCoverageIgnore
    **/
    public function rejudge($userid)
    {
        if (!$this->can_rejudge()) {
            print("Cannot Rejudge: Missing Permission");
            return false;
        }
        print($userid . "<br/>");
        error_log("rejudge:" . $userid);
        $status = ASSIGNFEEDBACK_WITSOJ_STATUS_PENDING;
        $text = "Rejudge Requested. Awaiting a free marker.";
        $this->set_witsoj_status($userid, $status, $text);
    }
    /**
    * @codeCoverageIgnore
    **/
    public function rejudge_all()
    {
        // List of all user submissions (even ones created while the plugin is off)
        if (!$this->can_rejudge()) {
            print("Cannot Rejudge: Missing Permission");
            return false;
        }
        global $DB;
        $sql = "SELECT userid FROM {assign_submission} s ".
        " WHERE status = 'submitted' AND assignment =  ". $this->assignment->get_instance()->id .
        " ORDER BY timemodified ASC ";
        $rec = $DB->get_records_sql($sql);
        var_dump($rec);
        print("<br/>");
        foreach ($rec as $userid => $v) {
            $this->rejudge($userid);
        }
        return count($rec);
        /*
        if(count($rec) == 0){
            return false;
        }
        $r = reset($rec);
        $plugin = self::get_plugin($r);
        $plugin->set_witsoj_status($r->userid, ASSIGNFEEDBACK_WITSOJ_STATUS_JUDGING, "Judging"); // TODO: Status Submitting
        return $r;
        */
    }
    /**
    * @codeCoverageIgnore
    **/
    public function rejudge_orphans()
    {
        // List of all user submissions (even ones created while the plugin is off)
        global $DB;
        $id = $this->assignment->get_instance()->id;
        error_log("Orphan Search: $id");
        $sql = "SELECT s.id, s.userid FROM {assign_submission} s LEFT OUTER JOIN {assignfeedback_witsoj} f ".
            "ON s.assignment = f.assignment AND s.userid = f.userid ".
            "WHERE s.assignment = $id AND f.assignment is null AND s.status='submitted'".
            "ORDER BY s.timemodified ASC "; // To find all orphans remove the $id from this query.
        // Note that the rejudge has to take place from the plugin the orphans belong to.
        $rec = $DB->get_records_sql($sql);
        $n = count($rec);
        error_log("Found $n orphaned submissions when enablling witsoj on assignment $id. Rejudging.");
        foreach ($rec as $k => $v) {
            error_log("Orphan: " . $v->userid);
            $this->rejudge($v->userid);
        }
        return $n;
    }
    /**
    * @codeCoverageIgnore
    **/
    public static function clean()
    {
        global $DB;
        $now = new DateTime("now", core_date::get_user_timezone_object());
        $nowts = $now->getTimestamp();
        $sql = "UPDATE {assignfeedback_witsoj} m SET status='".ASSIGNFEEDBACK_WITSOJ_STATUS_TIMEOUT."',commenttext = 'Marker did not respond with a mark.' ".
        " WHERE status = '".ASSIGNFEEDBACK_WITSOJ_STATUS_JUDGING."' AND :current-timemodified > ".ASSIGNFEEDBACK_WITSOJ_SUBMISSIONTIMEOUT. // If the marker is free, or hasn't delivered a mark in the last 2 minutes
        " LIMIT 1";
        $rec = $DB->execute($sql, array("current"=>$nowts));
    }
    /**
      * Return a list of the grading actions supported by this plugin.
      *
      * A grading action is a page that is not specific to a user but to the whole assignment.
      * @return array - An array of action and description strings.
      *                 The action will be passed to grading_action.
      * @codeCoverageIgnore
      */
    public function get_grading_actions()
    {
        return array("rejudgeall" => "Rejudge All", "rejudgeorphans" => "Rejudge Orphans", "prod" => "Prod Markers");
    }

    /**
      * Show a grading action form
      *
      * @param string $gradingaction The action chosen from the grading actions menu
      * @return string The page containing the form
      * @codeCoverageIgnore
      */
    public function grading_action($gradingaction)
    {
        error_log($gradingaction);
        if ($gradingaction == "rejudgeall") {
            error_log("RO All");
            $this->rejudge_all();
        } elseif ($gradingaction == "ro") {
            error_log("Orpgna");
            $this->rejudge_orphans();
        }
        return '';
    }
    /**
    * @codeCoverageIgnore
    **/
    public static function atomically_book_submission($feedbackid)
    {
        //return true;
        global $CFG;
        $conn = new mysqli($CFG->dbhost, $CFG->dbuser, $CFG->dbpass, $CFG->dbname);
        if ($conn->connect_error) {
            error_log("Connection failed: " . $conn->connect_error);
            die("Connection failed: " . $conn->connect_error);
        }
        $pre = $CFG->prefix;
        $sql = "UPDATE ${pre}assignfeedback_witsoj SET status=". ASSIGNFEEDBACK_WITSOJ_STATUS_JUDGING ." WHERE id=" . $feedbackid ." AND status=" . ASSIGNFEEDBACK_WITSOJ_STATUS_PENDING;
        error_log($sql);
        $conn->query($sql);
        $n = $conn->affected_rows;
        $conn->close();
        error_log("ATOMIC: " . $n);
        return $n > 0;
    }


    /*public static function set_witsoj_status_direct($assignment, $feedback, $status, $text){
    global $DB;
    $now = new DateTime("now", core_date::get_user_timezone_object());
    if($feedback){
        $feedback->status = $status;
        $feedback->commenttext = $text;
                $feedback->commentformat = FORMAT_HTML;
        $feedback->timemodified = $now->getTimestamp();
        return $DB->update_record('assignfeedback_witsoj', $feedback);
    }else{
        $feedback = new stdClass();
        $feedback->assignment = $assignment->get_instance()->id;
        $feedback->grade = $grade->id;
        $feedback->commenttext = $text;
                $feedback->commentformat = FORMAT_HTML;
        $feedback->status = $status;
        $feedback->timemodified = $now->getTimestamp();
        return $DB->insert_record('assignfeedback_witsoj', $feedback) > 0;
    }
    return false;
    }*/
    /**
    * @codeCoverageIgnore
    **/
    public function set_witsoj_status($userid, $status, $text)
    {
        global $DB;
        $grade = $this->assignment->get_user_grade($userid, true);
        $feedback = $this->get_feedback_witsoj($grade->id);
        // Atomically book the submission if its for judging
        error_log("TEXT: ". $text);
        if ($feedback->status == ASSIGNFEEDBACK_WITSOJ_STATUS_PENDING and $status == ASSIGNFEEDBACK_WITSOJ_STATUS_JUDGING) {
            error_log("Doing atomic booking");
            if (!self::atomically_book_submission($feedback->id)) {
                return false;
            }
            $feedback = $this->get_feedback_witsoj($grade->id);
        }

        if ($status == ASSIGNFEEDBACK_WITSOJ_STATUS_PENDING or $status == ASSIGNFEEDBACK_WITSOJ_STATUS_JUDGING) {
            error_log("Pending/Judging Status Change");
            $grade->grade = null;
            $this->assignment->update_grade($grade, false);
        }

        $now = new DateTime("now", core_date::get_user_timezone_object());
        if ($feedback) {
            error_log("Status: " . $feedback->status);
            if ($feedback->status == ASSIGNFEEDBACK_WITSOJ_STATUS_JUDGING and $status == ASSIGNFEEDBACK_WITSOJ_STATUS_PENDING) {
                error_log("Fail: " . substr($feedback->ojfeedback, 0, 6));
                if (substr($feedback->ojfeedback, 0, 6) === "Failed") {
                    error_log("Multiple failures, aborting submission.");
                    $feedback->status = ASSIGNFEEDBACK_WITSOJ_STATUS_ABORTED;
                    $feedback->ojfeedback = "Failed to submit to marker multiple times. Aborted.";
                    $text = $feedback->ojfeedback;
                } else {
                    error_log("First failure, retrying submission.");
                    $feedback->status = ASSIGNFEEDBACK_WITSOJ_STATUS_PENDING;
                    $feedback->ojfeedback = "Failed to submit to marker. Retrying.";
                }
            } else {
                $feedback->status = $status;
            }
            $feedback->commenttext = $text;
            $feedback->commentformat = FORMAT_HTML;
            $feedback->timemodified = $now->getTimestamp();
            $feedback->assignmentcontextid = $this->assignment->get_context()->instanceid;
            $feedback->userid = $userid;
            return $DB->update_record('assignfeedback_witsoj', $feedback);
        } else {
            $feedback = new stdClass();
            $feedback->assignment = $this->assignment->get_instance()->id;
            $feedback->grade = $grade->id;
            $feedback->commenttext = $text;
            $feedback->commentformat = FORMAT_HTML;
            $feedback->status = $status;
            $feedback->timemodified = $now->getTimestamp();
            $feedback->assignmentcontextid = $this->assignment->get_context()->instanceid;
            $feedback->userid = $userid;
            return $DB->insert_record('assignfeedback_witsoj', $feedback) > 0;
        }
    }
    /**
    * @codeCoverageIgnore
    **/
    public function get_marker_data($userid, $pathnamehash = null)
    {
        $data = array();
        $data["userid"]    = $userid;
        $data["language"]  = $this->get_config("language");
        $data["cpu_limit"] = $this->get_config("cpu_limit");
        $data["mem_limit"] = $this->get_config("mem_limit");
        $pe =$this->get_config("pe_ratio");
        if ($pe == null) {
            $pe = 0;
        }
        $data["pe_ratio"]  = floatval($pe);
        $data["callback"]  = $this->get_callback_url();

        $fs = get_file_storage();
        if ($files = $fs->get_area_files($this->assignment->get_context()->id, 'assignfeedback_witsoj', ASSIGNFEEDBACK_WITSOJ_TESTCASE_FILEAREA, '0', 'sortorder', false)) {
            $file = reset($files);
            $testcase = array();
            $fileurl = \moodle_url::make_pluginfile_url(
                $file->get_contextid(),
                $file->get_component(),
                $file->get_filearea(),
                $file->get_itemid(),
                $file->get_filepath(),
                $file->get_filename()
        );
            $download_url = $fileurl->get_port() ?
                            $fileurl->get_scheme() . '://' . $fileurl->get_host() . $fileurl->get_path() . ':' . $fileurl->get_port()
                            : $fileurl->get_scheme() . '://' . $fileurl->get_host() . $fileurl->get_path();
            $testcase["url"] = $download_url;
            $testcase["contenthash"] = $file->get_contenthash();
            $testcase["pathnamehash"] = $file->get_pathnamehash();
            $data["testcase"] = $testcase;
        } else {
            print("E1"); //TODO get rid of this
            return null;
        }

        $fs = get_file_storage();
        $file = null;
        if ($pathnamehash == null) {
            // Get submission from database
            global $DB;
            $arr = array('contextid'=>$this->assignment->get_context()->id,
                        'component'=>'assignsubmission_file',
                        'filearea'=>ASSIGNSUBMISSION_FILE_FILEAREA,
                        'userid'=>$userid);
            //var_dump($arr);
            $rec = $DB->get_records_sql(
            'SELECT pathnamehash FROM {files}'.
                        ' WHERE contextid = :contextid AND component = :component'.
                        ' AND filearea = :filearea AND userid = :userid AND NOT filename = "."',
                        $arr
        );
            if (count($rec) == 1) {
                $pathnamehash = reset($rec)->pathnamehash;
            } else {
                print("E4"); // TODO Deal with not getting a file.
                var_dump($rec);
                $pathnamehash = null;
                return null;
            }
        }
        if ($pathnamehash != null) {
            // File Path Provided
            $file = $fs->get_file_by_hash($pathnamehash);
            if (!$file or $file->is_directory()) {
                print("E2");
                return null;
            }
        } else {
            print("E5");	// TODO
            return null;
        }
        $source = array();
        $source["content"] = base64_encode($file->get_content());
        $source["ext"] = pathinfo($file->get_filename(), PATHINFO_EXTENSION);
        $data["source"] = $source;

        return $data;
    }
    /**
    * @codeCoverageIgnore
    **/
    public static function post_to_marker($marker, $data)
    {
        // Setup cURL
        error_log("Posting:" . $data["userid"]);
        $data['witsoj_token'] = get_config('assignfeedback_witsoj', 'secret');
        $data['markerid'] = $marker->id;

        $ch = curl_init($marker->url);
        curl_setopt_array($ch, array(
        CURLOPT_POST => count($data),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => array(
        //'Content-Type: application/json'
        'Content-Type: text/plain'
        ),
        CURLOPT_POSTFIELDS => json_encode($data)
    ));

        // Send the request
        $response = curl_exec($ch);

        // Check for errors
        if ($response === false) {
            error_log("Curl error");
            die("Curl Error: " . curl_error($ch));
        }

        // Decode the response
        $responseData = json_decode($response, true);

        // Print the date from the response
        //var_dump("Response: " . $response);
        //print("<br/>");
        //var_dump($responseData);
        return $responseData;
    }
    /**
    * @codeCoverageIgnore
    **/
    public static function get_plugin($submission)
    {
        list($course, $cm) = get_course_and_cm_from_cmid($submission->assignmentcontextid, 'assign');
        $context = context_module::instance($cm->id);
        $assign = new assign($context, $cm, $course);
        $plugin = $assign->get_feedback_plugin_by_type("witsoj");
        if (!$plugin->is_enabled()) {
            die('{"status" : "Coding Error"}');
        }
        return $plugin;
    }
    /**
    * @codeCoverageIgnore
    **/
    public static function judge_next_submission($marker = null, $submission = null)
    {
        if ($marker == null or $marker == false) {
            // Check if there is a free marker
            $marker = self::marker_book();
        }//else{
    //	$marker = self::marker_book($marker);
    //}
    if ($marker == null or $marker == false) { // TODO check that submission is null, otherwise unbook it.
        error_log("No marker.");
        // Still no free marker, nothing to do
        return false;
    }

        if ($submission == null) {
            error_log("###### No Submission Preloaded ######");
            $submission = self::fetch_pending_submission();
        }
        if ($submission == false) {
            error_log("No submission to fetch. Freeing Marker.");
            // There is no submission, free the marker
            self::marker_free($marker, false);
            return false;
        }

        $plugin = self::get_plugin($submission);
        if ($plugin == null) {
            error_log("Coding error123");
            die("Coding Error");
        }
        $userid = $submission->userid;
        // NB return whether there was a submission and marker available for judging. Not whether marking was successful.
        $plugin->judge($marker, $userid);
        return true;
    }
    /**
    * @codeCoverageIgnore
    **/
    public function judge($marker, $userid, $pathnamehash = null)
    {
        // Data To Send (including testcase metadata, student source, language, limits, pe_ratio, and callback
        $data = $this->get_marker_data($userid, $pathnamehash);
        if ($data == null) {
            $this->set_witsoj_status($userid, ASSIGNFEEDBACK_WITSOJ_STATUS_ABORTED, 'Unable to prepare data. Aborted.');
            return false;
        }
        // Send data to the marker
        $check = $this->post_to_marker($marker, $data);
        error_log("CHECK: " . print_r($check, true));
        error_log("CHECK: " . $check["status"]);
        if ($check["status"] == "0") {
            $this->set_witsoj_status($userid, ASSIGNFEEDBACK_WITSOJ_STATUS_JUDGING, 'Delivered to marker.');
            self::marker_busy($marker);
            return true;
        } else {
            $this->set_witsoj_status($userid, ASSIGNFEEDBACK_WITSOJ_STATUS_PENDING, 'Failed to deliver to marker. Retrying.');
            return false;
        }
    }
    /**
    * @codeCoverageIgnore
    **/
    public static function fetch_pending_submission()
    {
        global $DB;
        $success = false;
        while (!$success) {
            $sql = "SELECT * FROM {assignfeedback_witsoj} s ".
            " WHERE status = 0 ".
            " ORDER BY timemodified ASC ".
            " LIMIT 1";
            $rec = $DB->get_records_sql($sql);
            if (count($rec) == 0) {
                return false;
            }
            $r = reset($rec);
            $plugin = self::get_plugin($r);
            $success = $plugin->set_witsoj_status($r->userid, ASSIGNFEEDBACK_WITSOJ_STATUS_JUDGING, "Submitting to marker."); // TODO: Status Submitting
            if (!$success) {
                error_log("Selected submission scooped by another thread. Searching for another.");
            }
        }
        return $r;
    }
    /**
    * @codeCoverageIgnore
    **/
    public static function marker_free_by_id($markerid)
    {
        // Get marker with lastseen > 2minutes (assume its free)
        global $DB;
        $now = new DateTime("now", core_date::get_user_timezone_object());
        $nowts = $now->getTimestamp();
        $sql = "SELECT * FROM {assignfeedback_witsoj_mkr} m ".
        " WHERE id = :markerid ". // If the marker is free, or hasn't delivered a mark in the last 2 minutes
        " LIMIT 1";
        $rec = $DB->get_records_sql($sql, array("markerid"=>$markerid));
        if (count($rec) == 0) {
            return false;
        }

        $r = reset($rec);
        $r->lastseen = $nowts;
        $r->status = ASSIGNFEEDBACK_WITSOJ_MARKER_FREE;
        $DB->update_record('assignfeedback_witsoj_mkr', $r);
        return $r;
    }
    /**
    * @codeCoverageIgnore
    **/
    public static function marker_free($marker, $seen=true)
    {
        global $DB;
        $now = new DateTime("now", core_date::get_user_timezone_object());
        if ($seen) {
            $nowts = $now->getTimestamp();
            $marker->lastseen = $nowts;
        }
        $marker->status = ASSIGNFEEDBACK_WITSOJ_MARKER_FREE;
        $DB->update_record('assignfeedback_witsoj_mkr', $marker);
        return $marker;
    }
    /**
    * @codeCoverageIgnore
    **/
    public static function marker_busy($marker)
    {
        // Get marker with lastseen > 2minutes (assume its free)
        global $DB;
        $now = new DateTime("now", core_date::get_user_timezone_object());
        $nowts = $now->getTimestamp();
        $marker->lastseen = $nowts;
        $marker->status = ASSIGNFEEDBACK_WITSOJ_MARKER_BUSY;
        $DB->update_record('assignfeedback_witsoj_mkr', $marker);
        return $marker;
    }
    /**
    * @codeCoverageIgnore
    **/
    public static function marker_book($markerid = null)
    {
        // Get marker with lastseen > 2minutes (assume its free)
        global $DB;
        $now = new DateTime("now", core_date::get_user_timezone_object());
        $nowts = $now->getTimestamp();
        if ($markerid == null) {
            $sql = "SELECT * FROM {assignfeedback_witsoj_mkr} m ".
            " WHERE status = 0 OR :current-lastseen >  ".ASSIGNFEEDBACK_WITSOJ_MARKERTIMEOUT. // If the marker is free, or hasn't delivered a mark in the last 2 minutes
            " LIMIT 1";
            $rec = $DB->get_records_sql($sql, array("current"=>$nowts));
        } else {
            $sql = "SELECT * FROM {assignfeedback_witsoj_mkr} m ".
            " WHERE id = :current";
            $rec = $DB->get_records_sql($sql, array("current"=>$markerid));
        }

        if (count($rec) == 0) {
            return false;
        }
        $r = reset($rec);
        $r->lastseen = $nowts;
        $r->status = ASSIGNFEEDBACK_WITSOJ_MARKER_BOOKED;
        $DB->update_record('assignfeedback_witsoj_mkr', $r);
        return $r;
    }


    /**
     * Get the default setting for feedback comments plugin
     *
     * @param MoodleQuickForm $mform The form to add elements to
     * @return void
     * @codeCoverageIgnore
     */
    public function get_settings(MoodleQuickForm $mform)
    {
        $languages = $this->get_languages();
        $default_lang = array_search($this->get_config('language'), $languages);

        $cpu_limits = $this->get_cpu_limits();
        $default_cpu_limit = array_search($this->get_config('cpu_limit'), $cpu_limits);

        $mem_limits = $this->get_mem_limits();
        $default_mem_limit = array_search($this->get_config('mem_limit'), $mem_limits);

        $pe_ratios = $this->get_presentation_error_ratios();
        $default_pe_ratio = array_search($this->get_config('pe_ratio'), $pe_ratios);

        // Languages
        $mform->addElement('select', 'assignfeedback_witsoj_language', get_string('language', 'assignfeedback_witsoj'), $languages, null);
        $mform->addHelpButton('assignfeedback_witsoj_language', 'language', 'assignfeedback_witsoj');
        $mform->setDefault('assignfeedback_witsoj_language', $default_lang);

        // Limits
        $mform->addElement('select', 'assignfeedback_witsoj_limit_cpu', get_string('cpu_limit', 'assignfeedback_witsoj'), $cpu_limits, null);
        $mform->addHelpButton('assignfeedback_witsoj_limit_cpu', 'cpu_limit', 'assignfeedback_witsoj');
        $mform->setDefault('assignfeedback_witsoj_limit_cpu', $default_cpu_limit);

        $mform->addElement('select', 'assignfeedback_witsoj_limit_mem', get_string('mem_limit', 'assignfeedback_witsoj'), $mem_limits, null);
        $mform->addHelpButton('assignfeedback_witsoj_limit_mem', 'mem_limit', 'assignfeedback_witsoj');
        $mform->setDefault('assignfeedback_witsoj_limit_mem', $default_mem_limit);
        // Presentation Error Ratio
        $mform->addElement('select', 'assignfeedback_witsoj_presentation_error_ratio', get_string('presentation_error', 'assignfeedback_witsoj'), $pe_ratios, null);
        $mform->addHelpButton('assignfeedback_witsoj_presentation_error_ratio', 'presentation_error', 'assignfeedback_witsoj');
        $mform->setDefault('assignfeedback_witsoj_presentation_error_ratio', $default_pe_ratio);
        // Upload Test Cases ZIP with YML
        $max_bytes = get_config('assignfeedback_witsoj', 'maxbytes');
        $mform->addElement(
        'filemanager',
        'assignfeedback_witsoj_testcases',
        get_string('test_cases', 'assignfeedback_witsoj'),
        null,
                    array('subdirs' => 0, 'maxbytes' => $max_bytes, 'areamaxbytes' => $max_bytes*10000, 'maxfiles' => 50,
                          'accepted_types' => array('.zip'), 'return_types'=> FILE_INTERNAL | FILE_EXTERNAL)
    );
        $mform->addHelpButton('assignfeedback_witsoj_testcases', 'test_cases', 'assignfeedback_witsoj');

        $draftitemid = file_get_submitted_draft_itemid('assignfeedback_witsoj_testcases');
        file_prepare_draft_area(
        $draftitemid,
        $this->assignment->get_context()->id,
        'assignfeedeback_witsoj',
        'attachment',
        null,
                        array('subdirs' => 0, 'maxbytes' => $max_bytes, 'maxfiles' => 1)
    );

        // Disable comment online if comment feedback plugin is disabled.
        $mform->disabledIf('assignfeedback_witsoj_language', 'assignfeedback_witsoj_enabled', 'notchecked');
    }
    /**
    * @codeCoverageIgnore
    **/
    public function data_preprocessing(&$defaultvalues)
    {
        $draftitemid = file_get_submitted_draft_itemid('assignfeedback_witsoj_testcases');
        file_prepare_draft_area(
            $draftitemid,
            $this->assignment->get_context()->id,
            'assignfeedback_witsoj',
            ASSIGNFEEDBACK_WITSOJ_TESTCASE_FILEAREA,
                                0,
            array('subdirs' => 0)
        );
        $defaultvalues['assignfeedback_witsoj_testcases'] = $draftitemid;
        return;
    }
    /**
    * @codeCoverageIgnore
    **/
    public static function format_oj_feedback($oj_testcases, $oj_feedback, $getdetails_url)
    {
        $tc_result = json_decode($oj_testcases);
        error_log(print_r($tc_result[0], true));
        $out = "<table class='addtoall expandall' border='0' cellpadding=0 cellspacing=0>";

        if (count($tc_result) === 1) {
            $icon_code = "<a href='$getdetails_url' target='_blank' >&#9432</a>" ;
            if ($tc_result[0]->result == ASSIGNFEEDBACK_WITSOJ_STATUS_COMPILEERROR) {
                $feedback = $tc_result[0]->oj_feedback;
                $out .= "<tr style='color:#CCCC00'><td>&#10007;</td><td>Compilation Error</td><td>$feedback</td><td>$icon_code</td></tr>";
            } elseif ($tc_result[0]->result == ASSIGNFEEDBACK_WITSOJ_STATUS_MARKERERROR) {
                $feedback = $tc_result[0]->oj_feedback;
                $out .= "<tr style='color:#CC00CC'><td>&#10007;</td><td>Marker Error</td><td>$feedback</td><td>$icon_code</td></tr>";
            }
        } else {
            for ($i = 0; $i < count($tc_result); $i++) {
                $result = $tc_result[$i]->result;
                $feedback = $tc_result[$i]->oj_feedback;
                $grade = $tc_result[$i]->grade;
                $max = $tc_result[$i]->max_grade;
                //$gradeid = $grade->id ;
                //$details = $this->get_details_url();
                $new_url = $getdetails_url."&testcase=$i" ;
                $icon_code = "<a href='$new_url' target='_blank' >&#9432</a>" ;
                // $icon_code = "<a href='feedback/witsoj/details.php?id=123&tc=$i' target='_blank'>!</a>" ;

                //$icon_code = "<a href='feedback/witsoj/details.php?id=123&tc=$i'>!</a>" ;
                if ($result == ASSIGNFEEDBACK_WITSOJ_STATUS_PRESENTATIONERROR) {
                    $out .= "<tr style='color:#880'><td>&#10007;</td><td>TestCase $i</td><td>Presentation Error ($grade/$max)</td><td>$feedback</td><td>$icon_code</td></tr>";
                } elseif ($result == ASSIGNFEEDBACK_WITSOJ_STATUS_INCORRECT) {
                    $out .= "<tr style='color:#A00'><td>&#10007;</td><td>TestCase $i</td><td>Failed ($grade/$max)</td><td>$feedback</td><td>$icon_code</td></tr>";
                } elseif ($result == ASSIGNFEEDBACK_WITSOJ_STATUS_ACCEPTED) {
                    $out .= "<tr style='color:#070'><td>&#10004;</td><td>TestCase $i</td><td>Passed ($grade/$max)</td><td>$feedback</td><td>$icon_code</td></tr>";
                } else {
                    error_log("WITSOJ: What result is this?" . print_r($tc_result[$i]));
                    $out .= "<tr style='color:#AA0'><td>&#10004;</td><td>TestCase $i</td><td>???? ($grade/$max)</td><td>$feedback</td><td>$icon_code</td></tr>";
                }
            }
        }
        $out .= "</table>";
        return $out;
    }
    //Do not add ignore for testing
    /**
    * @codeCoverageIgnore
    **/
    public function returned_grade($markerid, $userid, $newgrade, $status, $oj_testcases, $oj_feedback)
    {
        global $DB;
        error_log("GRADE: " . $newgrade);
        $grade = $this->assignment->get_user_grade($userid, true);
        $grade->grade = $newgrade;
        //$grade->grader = 3;
        $this->assignment->update_grade($grade, false);

        $feedback = $this->get_feedback_witsoj($grade->id);
        $getdetails_url = $this->get_details_url($userid) ; // gets the new url when the user clicks the icon

        if ($feedback) {
            $feedback->status = $status;
            $feedback->commenttext = self::format_oj_feedback($oj_testcases, $oj_feedback, $getdetails_url);
            $feedback->ojtests = $oj_testcases;
            $feedback->ojfeedback = $oj_feedback;
            $now = new DateTime("now", core_date::get_user_timezone_object());
            $feedback->timemodified = $now->getTimestamp();
            $DB->update_record('assignfeedback_witsoj', $feedback);
        } else {
            error_log("Submission doesn't exist in the witsoj table yet we received a mark.");
        }
        $newsub = self::fetch_pending_submission();
        if (!$newsub) {
            error_log("Free by id: " . $markerid);
            $this->marker_free_by_id($markerid); // Rather check for a new submission to mark
        } else {
            // TODO load the assignment/plugin associated with the next submission.
            // Send to marker
            error_log(print_r($newsub, true));
            $marker = self::marker_book($markerid);
            error_log("Marker: " . $marker->id);
            self::judge_next_submission($marker, $newsub);
        }
    }
    //Do not add ignore for testing
    public function view_page($pluginaction, $witsoj_assignment_id,$witsoj_assign_userid,$can_rejudge_variable)
    {
        //@codeCoverageIgnoreStart
        if ($pluginaction == "prod") {
            error_log("Prod");
            self::prod();
        } elseif ($pluginaction == "rejudge") {
            if ($this->can_rejudge()) {
                error_log("rejudge");
                $userid = required_param('userid', PARAM_INT);
                $this->rejudge($userid);
                self::prod();
                print("Successfully queued 1 submissions.");
            } else {
                print("Cannot Rejudge: Missing Permission");
            }
        } elseif ($pluginaction == "rejudgeall") {
            if ($this->can_rejudge()) {
                error_log("rejudgeall");
                $n = $this->rejudge_all();
                self::prod();
                print("Successfully queued $n submissions.");
            } else {
                print("Cannot Rejudge: Missing Permission");
            }
        } elseif ($pluginaction == "rejudgeorphans") {
            if ($this->can_rejudge()) {
                error_log("rejudgeorphans");
                $n = $this->rejudge_orphans();
                self::prod();
                print("Successfully queued $n orphans for grading.");
            } else {
                print("Cannot Rejudge: Missing Permission");
            }
        } elseif ($pluginaction == "clean") {
            error_log("clean");
            $n = $this->clean();
            print("Successfully cleaned $n broken submissions.");
        //@codeCoverageIgnoreEnd
        } elseif ($pluginaction == "viewdetails") {
            error_log("viewdetails");
            //global $DB;
            //$witsoj_assign_userid = required_param('userid', PARAM_INT);
            //$witsoj_assignment_id = required_param('id', PARAM_INT);
            /*
            $sql = "SELECT ojtests FROM mdl_assignfeedback_witsoj WHERE
            (assignmentcontextid = '$witsoj_assignment_id' AND userid = '$witsoj_assign_userid')";
            $rec = $DB->get_records_sql($sql);
            */
            $db=$this->getConnection();
            $stmt = $db->prepare("SELECT ojtests FROM mdl_assignfeedback_witsoj WHERE
            (assignmentcontextid = '$witsoj_assignment_id' AND userid = '$witsoj_assign_userid')");
            $stmt->execute();
            $rec = $stmt->fetchColumn();
            $myarr = array();
            /*foreach ($rec as $ojtests => $v) {
                //$sub = substr($ojtests, 1, strlen($ojtests) - 2);
                //$jsond = json_decode($sub);
                $jsond = json_decode($ojtests, true) ;
            }*/
            $jsond = json_decode($rec, true);
            if ($can_rejudge_variable==True) {
                // lecturer
                if ($jsond[0]['result'] != 2) {
                    $testcase = 0;
                    return $jsond[$testcase]['progout'].$jsond[$testcase]['modelout'];
                    //return $jsond[$testcase]['result'];
                    //echo "Test Case: Progout = ".$jsond[$testcase]['progout']." and the Correct output = ".$jsond[$testcase]['modelout'];
                } else {
                    return $jsond[0]['stderr'] ;
                }
            } else {
                // student
                if ($jsond[0]['result'] == 2) {
                    return $jsond[0]['stderr'];
                } else {
                    return "Nothing to display" ;
                }
            }
        }
    }

    /**
     * Convert the text from any submission plugin that has an editor field to
     * a format suitable for inserting in the feedback text field.
     *
     * @param stdClass $submission
     * @param stdClass $data - Form data to be filled with the converted submission text and format.
     * @return boolean - True if feedback text was set.
     * @codeCoverageIgnore
     */
    protected function convert_submission_text_to_feedback($submission, $data)
    {
        $format = false;
        $text = '';

        foreach ($this->assignment->get_submission_plugins() as $plugin) {
            $fields = $plugin->get_editor_fields();
            if ($plugin->is_enabled() && $plugin->is_visible() && !$plugin->is_empty($submission) && !empty($fields)) {
                foreach ($fields as $key => $description) {
                    $rawtext = strip_pluginfile_content($plugin->get_editor_text($key, $submission->id));

                    $newformat = $plugin->get_editor_format($key, $submission->id);

                    if ($format !== false && $newformat != $format) {
                        // There are 2 or more editor fields using different formats, set to plain as a fallback.
                        $format = FORMAT_PLAIN;
                    } else {
                        $format = $newformat;
                    }
                    $text .= $rawtext;
                }
            }
        }

        if ($format === false) {
            $format = FORMAT_HTML;
        }
        $data->assignfeedbackcomments_editor['text'] = $text;
        $data->assignfeedbackcomments_editor['format'] = $format;

        return true;
    }

    /**
     * Get form elements for the grading page
     *
     * @param stdClass|null $grade
     * @param MoodleQuickForm $mform
     * @param stdClass $data
     * @return bool true if elements were added to the form
     * @codeCoverageIgnore
     */
    public function get_form_elements_for_user($grade, MoodleQuickForm $mform, stdClass $data, $userid)
    {
        $commentinlinenabled = $this->get_config('commentinline');
        $submission = $this->assignment->get_user_submission($userid, false);
        $feedbackcomments = false;

        if ($grade) {
            $feedbackcomments = $this->get_feedback_witsoj($grade->id);
        }

        if ($feedbackcomments && !empty($feedbackcomments->commenttext)) {
            $data->assignfeedbackcomments_editor['text'] = $feedbackcomments->commenttext;
            $data->assignfeedbackcomments_editor['format'] = $feedbackcomments->commentformat;
        } else {
            // No feedback given yet - maybe we need to copy the text from the submission?
            if (!empty($commentinlinenabled) && $submission) {
                $this->convert_submission_text_to_feedback($submission, $data);
            }
        }
        error_log("Test");
        $mform->addElement('editor', 'assignfeedbackcomments_editor', $this->get_name(), null, null);
        $mform->addElement('static', 'assignfeedbackwitsoj_rejudge', $this->get_name(), '#', null);

        return true;
    }

    /**
     * Saving the comment content into database.
     *
     * @param stdClass $grade
     * @param stdClass $data
     * @return bool
     * @codeCoverageIgnore
     */
    public function save(stdClass $grade, stdClass $data)
    {
        global $DB;
        $feedbackcomment = $this->get_feedback_witsoj($grade->id);
        if ($feedbackcomment) {
            $feedbackcomment->commenttext = $data->assignfeedbackcomments_editor['text'];
            $feedbackcomment->commentformat = $data->assignfeedbackcomments_editor['format'];
            return $DB->update_record('assignfeedback_witsoj', $feedbackcomment);
        } else {
            $feedbackcomment = new stdClass();
            $feedbackcomment->commenttext = $data->assignfeedbackcomments_editor['text'];
            $feedbackcomment->commentformat = $data->assignfeedbackcomments_editor['format'];
            $feedbackcomment->grade = $grade->id;
            $feedbackcomment->assignment = $this->assignment->get_instance()->id;
            return $DB->insert_record('assignfeedback_witsoj', $feedbackcomment) > 0;
        }
    }

    /**
     * Display the comment in the feedback table.
     *
     * @param stdClass $grade
     * @param bool $showviewlink Set to true to show a link to view the full feedback
     * @return string
     */
     //Do not add ignore for testing
    public function view_summary($grade_id)
    {
        error_log("OJ: VIEW SUMMARY");
        $feedbackcomments = $this->get_feedback_witsoj($grade_id);
        $buttons = "";
        /*
        if ($this->can_rejudge()) {
            $rejudge_cur = $this->get_rejudge_url($grade_userid);
            $rejudge_all = $this->get_rejudge_all_url();
            $prod = $this->get_prod_url();
            //$buttons .= "<form method='post' action='#'>";
            $buttons .=  "<a class='btn btn-secondary' href='$rejudge_cur'style='margin-bottom:5px;margin-right:5px;'>Rejudge</a>";
            //$buttons .=  "<a class='btn btn-secondary' href='$rejudge_all'style='margin-bottom:5px;'>Rejudge All</a><br/>";
            //$buttons .=  "<a class='btn btn-secondary' href='$prod'style='margin-bottom:5px;'>Prod</a><br/>";
        //$buttons .= "</form><br/>";
        }
        global $PAGE;
        global $CFG ;
        */
        if ($feedbackcomments) {
            if ($feedbackcomments->status == ASSIGNFEEDBACK_WITSOJ_STATUS_PENDING or $feedbackcomments->status == ASSIGNFEEDBACK_WITSOJ_STATUS_JUDGING) {
                //$PAGE->requires->js(new moodle_url($CFG->wwwroot . "/mod/assign/feedback/witsoj/loadAjax.php?gradeid=".$temp_id));
                require_once('loadAjax1.php');
                $output = loadAjax($grade_id);
                return $output;
            }
            else{
              return -2;
            }
            /*
            $text = format_text(
                $feedbackcomments->commenttext,
                                $feedbackcomments->commentformat,
                                array('context' => $this->assignment->get_context())
            );
            $short = shorten_text($text, 140);

            // Show the view all link if the text has been shortened.
            #$showviewlink = $short != $text;
            #$showviewlink = $short != $text;

            return $buttons."<div id='tmp'>".$text."</div>";
            */
        }
        else{
          return -1;
        }
        //return $buttons."<div id='tmp'></div>";
    }

    /**
     * Display the comment in the feedback table.
     *
     * @param stdClass $grade
     * @return string
     * @codeCoverageIgnore
     */
    public function view(stdClass $grade)
    {
        error_log("OJ: VIEW");
        $feedbackcomments = $this->get_feedback_witsoj($grade->id);
        if ($feedbackcomments) {
            return format_text(
                $feedbackcomments->commenttext,
                               $feedbackcomments->commentformat,
                               array('context' => $this->assignment->get_context())
            );
        }
        return '';
    }

    /**
     * Return true if this plugin can upgrade an old Moodle 2.2 assignment of this type
     * and version.
     *
     * @param string $type old assignment subtype
     * @param int $version old assignment version
     * @return bool True if upgrade is possible
     * @codeCoverageIgnore
     */
    public function can_upgrade($type, $version)
    {
        if (($type == 'upload' || $type == 'uploadsingle' ||
             $type == 'online' || $type == 'offline') && $version >= 2011112900) {
            return true;
        }
        return false;
    }

    /**
     * Upgrade the settings from the old assignment to the new plugin based one
     *
     * @param context $oldcontext - the context for the old assignment
     * @param stdClass $oldassignment - the data for the old assignment
     * @param string $log - can be appended to by the upgrade
     * @return bool was it a success? (false will trigger a rollback)
     * @codeCoverageIgnore
     */
    public function upgrade_settings(context $oldcontext, stdClass $oldassignment, & $log)
    {
        if ($oldassignment->assignmenttype == 'online') {
            $this->set_config('commentinline', $oldassignment->var1);
            return true;
        }
        return true;
    }

    /**
     * Upgrade the feedback from the old assignment to the new one
     *
     * @param context $oldcontext - the database for the old assignment context
     * @param stdClass $oldassignment The data record for the old assignment
     * @param stdClass $oldsubmission The data record for the old submission
     * @param stdClass $grade The data record for the new grade
     * @param string $log Record upgrade messages in the log
     * @return bool true or false - false will trigger a rollback
     * @codeCoverageIgnore
     */
    public function upgrade(
        context $oldcontext,
                            stdClass $oldassignment,
                            stdClass $oldsubmission,
                            stdClass $grade,
                            & $log
    ) {
        global $DB;

        $feedbackcomments = new stdClass();
        $feedbackcomments->commenttext = $oldsubmission->submissioncomment;
        $feedbackcomments->commentformat = FORMAT_HTML;

        $feedbackcomments->grade = $grade->id;
        $feedbackcomments->assignment = $this->assignment->get_instance()->id;
        if (!$DB->insert_record('assignfeedback_witsoj', $feedbackcomments) > 0) {
            $log .= get_string('couldnotconvertgrade', 'mod_assign', $grade->userid);
            return false;
        }

        return true;
    }

    /**
     * If this plugin adds to the gradebook comments field, it must specify the format of the text
     * of the comment
     *
     * Only one feedback plugin can push comments to the gradebook and that is chosen by the assignment
     * settings page.
     *
     * @param stdClass $grade The grade
     * @return int
     */
    public function format_for_gradebook($grade_id)
    {
        $feedbackcomments = $this->get_feedback_witsoj($grade_id);
        if ($feedbackcomments) {
            return $feedbackcomments->commentformat;
        }
        return FORMAT_MOODLE;
    }

    /**
     * If this plugin adds to the gradebook comments field, it must format the text
     * of the comment
     *
     * Only one feedback plugin can push comments to the gradebook and that is chosen by the assignment
     * settings page.
     *
     * @param stdClass $grade The grade
     * @return string
     */
    public function text_for_gradebook($id)
    {
        $feedbackcomments = $this->get_feedback_witsoj($id);
        if ($feedbackcomments) {
            return $feedbackcomments->commenttext;
        }
        return '';
    }

    /**
     * The assignment has been deleted - cleanup
     *
     * @return bool
     * @codeCoverageIgnore
     */
    public function delete_instance()
    {
        global $DB;
        // Will throw exception on failure.
        $DB->delete_records(
            'assignfeedback_witsoj',
                            array('assignment'=>$this->assignment->get_instance()->id)
        );
        return true;
    }

    /**
     * Returns true if there are no feedback comments for the given grade.
     *
     * @param stdClass $grade
     * @return bool
     * @codeCoverageIgnore
     */
    public function is_empty(stdClass $grade)
    {
        return $this->view($grade) == '';
    }

    /**
     * Return a description of external params suitable for uploading an feedback comment from a webservice.
     *
     * @return external_description|null
     * @codeCoverageIgnore
     */
    public function get_external_parameters()
    {
        $editorparams = array('text' => new external_value(PARAM_RAW, 'The text for this feedback.'),
                              'format' => new external_value(PARAM_INT, 'The format for this feedback'));
        $editorstructure = new external_single_structure($editorparams, 'Editor structure', VALUE_OPTIONAL);
        return array('assignfeedbackcomments_editor' => $editorstructure);
    }

    /**
     * Return the plugin configs for external functions.
     *
     * @return array the list of settings
     * @since Moodle 3.2
     * @codeCoverageIgnore
     */
    public function get_config_for_external()
    {
        return (array) $this->get_config();
    }
}
