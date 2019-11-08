<?php
defined('MOODLE_INTERNAL') || die();
/**
 * Serves intro attachment files.
 *
 * @param mixed $course course or id of the course
 * @param mixed $cm course module or id of the course module
 * @param context $context
 * @param string $filearea
 * @param array $args
 * @param bool $forcedownload
 * @param array $options additional options affecting the file serving
 * @return bool false if file not found, does not return if found - just send the file
 */
function assignfeedback_witsoj_pluginfile($course,
                $cm,
                context $context,
                $filearea,
                $args,
                $forcedownload,
                array $options=array()) {
    global $CFG;
    
    if ($context->contextlevel != CONTEXT_MODULE) {
        return false;
    }

    //require_login($course, false, $cm);
    //if (!has_capability('mod/assign:view', $context)) {
    //    return false;
    //}
    $auth = get_config('assignfeedback_witsoj', 'secret');
    if(!isset($_POST["witsoj_token"]) || $_POST["witsoj_token"] !== $auth){
        die("No Auth");
    }

    require_once($CFG->dirroot . '/mod/assign/locallib.php');
    $assign = new assign($context, $cm, $course);

    if ($filearea !== ASSIGNFEEDBACK_WITSOJ_TESTCASE_FILEAREA) {
        return false;
    }
    if (!$assign->show_intro()) {
        return false;
    }

    $itemid = (int)array_shift($args);
    if ($itemid != 0) {
        return false;
    }

    $relativepath = implode('/', $args);

    $fullpath = "/{$context->id}/assignfeedback_witsoj/$filearea/$itemid/$relativepath";

    $fs = get_file_storage();
    if (!$file = $fs->get_file_by_hash(sha1($fullpath)) or $file->is_directory()) {
        return false;
    }
    send_stored_file($file, 0, 0, $forcedownload, $options);
}

