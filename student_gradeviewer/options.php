<?php

require_once('../../config.php');
require_once('visualizer/lib.php');

require_login();

$courseid = optional_param('id');

$navigation = array();
if($courseid) {
    $course = get_record('course', 'id', $courseid);
    if($course) {
        $navigation[] = array('name' => $course->shortname, 
                              'link'=> $CFG->wwwroot . '/course/view.php?id='.$courseid,
                              'type'=> 'title');
    }
}

$options = referral_visualizer::build_visualizer(null, 'options', $USER->username);

require_js(array($CFG->wwwroot . '/blocks/student_gradeviewer/jquery.min.js',
                 $CFG->wwwroot . '/blocks/student_gradeviewer/option_functions.js'));

// Print the header and heading
$blockname = get_string('blockname', 'block_student_gradeviewer');
$heading = get_string('auto_options', 'block_student_gradeviewer');
$navigation[] = array('name' => $blockname, 'link' => '', 'type' => 'title');
$navigation[] = array('name' => $heading, 'link' => '', 'type' => 'title');

print_header_simple($heading, '', build_navigation($navigation));

$options->print_filters('options.php');
$options->print_heading($heading);

if($data = data_submitted()) {
    $options->process($data);
    $options->print_notices();
}

echo '<form method="post">';
$options->print_table();
echo '<div class="center_button">
        <input type="submit" value="'.get_string('submit').'">
      </div>';
echo '</form>';

print_footer();

?>
