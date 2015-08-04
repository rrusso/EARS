<?php

require_once('../../config.php');
require_once('lib.php');

require_login();

// Course id required
$courseid = required_param('id', PARAM_INT);

$course = has_reporting_permission($courseid);

// Print the header and heading
$blockname = get_string('blockname', 'block_student_reporting');
$heading = get_string('report', 'block_student_reporting');
$navigation = array(
              array('name' => $course->shortname, 
                    'link' => $CFG->wwwroot. '/course/view.php?id='. $courseid,
                    'type' => 'title'),
              array('name' => $blockname, 'link' => '', 'type' => 'title'),
              array('name' => get_string('select', 'block_student_reporting'),
                    'link' => $CFG->wwwroot . 
                              '/blocks/student_reporting/select.php?id='.$courseid,
                    'type' => 'title'),
              array('name' => $heading, 'link' => '', 'type' => 'title'));

print_header_simple($heading, '', build_navigation($navigation));

// If they selected some members, then get those;
// otherwise prepare a report for every student
// in this course
if($data = data_submitted()) {
    $referral_form = new report_form($courseid, $data);
} else {
    $referral_form = new report_form($courseid);
    $referral_form->users = get_all_students($course);
}

$referral_form->print_heading($heading);

if($referral_form->submitted()) {
    // Notify students that they have been referred
    $referral_form->process($data);
    print_footer();
    die();
}

$referral_form->print_selected_users();
$referral_form->print_form();
print_footer();
?>
