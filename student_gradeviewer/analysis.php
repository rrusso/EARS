<?php

require_once('../../config.php');
require_once('visualizer/lib.php');

require_login();

// Course id is optional
$courseid = optional_param('id', NULL, PARAM_INT);

if($courseid) {
    $course = has_reporting_permission($courseid);
} else {
    $course = null;
}
$export = optional_param('export');
$key = optional_param('key', ($courseid) ? 'course' : null);
$keyid = optional_param('keyid');
$page = optional_param('page', 0);
$per_page = optional_param('per_page', 50);

$visualizer = referral_visualizer::build_visualizer($course, $key, $keyid, 
                                                    $page, $per_page, $export);

if($visualizer->exporting and $visualizer->exportable()) {
    $visualizer->export();
    die();
}

// Print the header and heading
$blockname = get_string('blockname', 'block_student_gradeviewer');
$heading = get_string('analysis', 'block_student_gradeviewer');
$navigation = array();
if($courseid) $navigation[] = array('name' => $course->shortname, 
                    'link' => $CFG->wwwroot. '/course/view.php?id='. $courseid,
                    'type' => 'title');
$navigation[] = array('name' => $blockname, 'link' => '', 'type' => 'title');
$navigation[] = array('name' => $heading, 
                    'link' => $CFG->wwwroot . 
                              '/blocks/student_gradeviewer/analysis.php?id='.$courseid, 
                    'type' => 'title');

$extra = $visualizer->extra_navigation();
if(!empty($extra)) $navigation[] = $visualizer->extra_navigation();

print_header_simple($heading, '', build_navigation($navigation));

$visualizer->print_filters('analysis.php');
$visualizer->print_heading($heading);

if($visualizer->print_table() and $visualizer->exportable()) {
    echo '<form method="GET">
            <input type="hidden" name="export" value="1">
            '.(($courseid) ? "<input type='hidden' name='id' value='{$courseid}'>": '').'
            <input type="hidden" name="key" value="'.$key.'">
            <input type="hidden" name="keyid" value="'.$keyid.'">
            <input type="hidden" name="page" value="'.$page.'">
            <input type="hidden" name="per_page" value="'.$per_page.'">
            <input type="submit" value="Export">
          </form>';
}

print_footer();

?>
