<?php

require_once('../../config.php');
require_once($CFG->dirroot . '/blocks/student_gradeviewer/report/lib.php');

require_login();

$courseid = required_param('id', PARAM_INT);

$course = has_reporting_permission($courseid);

// If they came here from a GET, redirect back
$data = data_submitted();

if(!$data) {
    redirect($CFG->wwwroot . '/course/view.php?id='.$courseid);
}

// Instantiate report form
$report_form = new report_form($courseid, $data);

// Print the header and heading
$blockname = get_string('blockname', 'block_athelete_reporting');
$heading = get_string('report', 'block_athelete_reporting');
$navigation = array(
              array('name' => $course->idnumber, 
                    'link' => $CFG->wwwroot. '/course/view.php?id='. $courseid,
                    'type' => 'title'),
              array('name' => $blockname, 'link' => '', 'type' => 'title'),
              array('name' => $heading, 'link' => '', 'type' => 'title'));

print_header_simple($heading, '', build_navigation($navigation));

// Print report heading
$report_form->print_heading($heading);

// If they submitted a report form, then process it and die
if($report_form->submitted()) {
    $report_form->process($data);
    print_footer();
    die();
}

// Print the list of selected users
$report_form->print_selected_users();
$report_form->print_form();

print_footer();

?>
