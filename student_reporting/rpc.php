<?php

require_once('../../config.php');

require_once $CFG->libdir.'/gradelib.php';
require_once $CFG->dirroot.'/grade/lib.php';
require_once $CFG->dirroot.'/grade/report/user/lib.php';

$courseid = required_param('id');
$userid   = optional_param('userid', $USER->id, PARAM_INT);

/// basic access checks
if (!$course = get_record('course', 'id', $courseid)) {
    print_error('nocourseid');
}
require_login($course);

$context     = get_context_instance(CONTEXT_COURSE, $course->id);
require_capability('gradereport/user:view', $context);

if (empty($userid)) {
    require_capability('moodle/grade:viewall', $context);

} else {
    if (!get_complete_user_data('id', $userid)) {
        error("Incorrect userid");
    }
}

$access = true;
if (has_capability('moodle/grade:viewall', $context)) {
    //ok - can view all course grades

} else if ($userid == $USER->id and has_capability('moodle/grade:view', $context) and $course->showgrades) {
    //ok - can view own grades

} else if (has_capability('moodle/grade:viewall', get_context_instance(CONTEXT_USER, $userid)) and $course->showgrades) {
    // ok - can view grades of this user- parent most probably

} else {
    $access = false;
}
/// return tracking object
$gpr = new grade_plugin_return(array('type'=>'report', 'plugin'=>'user', 'courseid'=>$courseid, 'userid'=>$userid));

if($access) {
    grade_regrade_final_grades($courseid);

    $report = new grade_report_user($courseid, $gpr, $context, $userid);

    if($report->fill_table()) {
        echo $report->print_table(true);
    }
}
?>
