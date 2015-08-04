<?php

/*
 * Script rendering and processing form for teachers' preferences on splitting courses.
 *
 * @author Adam Zapletal
 */

require_once('../../config.php');
require_once('lib.php');
require_once('split_form.php');
require_once('classes/CoursePrefsCourse.php');
require_once('classes/CoursePrefsUser.php');
require_once('classes/CoursePrefsSplit.php');
require_once('classes/CoursePrefsLog.php');

$selected = optional_param('selected');
$number = optional_param('number');
$mode = optional_param('mode');
$submit = optional_param('submit');

if ($submit == "Back") {
    redirect('split.php');
}

// Require users be logged in before accessing page
require_login();

// Disallow anyone who was not processed from the mainframe input files to use this page
$user = CoursePrefsUser::findByUnique($USER->username);

if (!$user) {
    redirect_error(ERROR_USER_MISSING);
}

// Disallow anyone who does not have any splittable courses
$splits = $user->getSplittableCourses();

if (!$splits) {
    redirect_error(ERROR_SPLIT_MISSING);
}

$current_splits = $user->getSplits();

list($semestersid, $coursesid) = split_selected($selected);

// Reset post processing
if ($mode == 'perform_reset') {
    $my_splits = $current_splits[$semestersid][$coursesid];
    if (!$my_splits) {
        redirect_error(ERROR_COURSE_NONE);
    }
    reset_prefs($my_splits);
    redirect($CFG->wwwroot . '/blocks/courseprefs/split.php');
}
require_js($CFG->wwwroot . '/blocks/courseprefs/functions.js');

// Render the page headers and forms
$heading_main = get_string('split_heading', 'block_courseprefs');
$navigation = array(
        array('name' => get_string('blockname', 'block_courseprefs'), 'link' => '', 'type' => 'title'),
        array('name' => $heading_main, 'link' => '', 'type' => 'title',)
        );

print_header_simple($heading_main, '', build_navigation($navigation));
print_heading_with_help($heading_main, 'split', 'block_courseprefs');
    
if ($user->getCrDelete()) {
    echo '<div class="cps_error">'.get_string('error_preference', 'block_courseprefs');
    $split = $splits[$semestersid][$coursesid];
    echo (isset($split)) ? $split->year . ' ' . $split->name. ' '. 
                           $split->department. ' '. $split->course_number . '.' : 
                           get_string('error_preference_any', 'block_courseprefs');
    echo '<p class="cps_smallerror">' . get_string('error_preference_sub', 'block_courseprefs') . '</p>';
    echo '</div>';
}

// There hasn't been a selection, so the teacher must select one
if (empty($selected)) {
    $params = array($splits, $current_splits);
    build_form('initial_content', $params, 'Next', '');
} else if (empty($number)) {
    // Try to hack it, brah? Not going to work
    if (!array_key_exists($coursesid, $splits[$semestersid])) {
        redirect_error(ERROR_COURSE_NONE);
    }

    if (!empty($current_splits) and array_key_exists($coursesid, $current_splits[$semestersid])) {
        $split = $splits[$semestersid][$coursesid];
        $params = array($current_splits[$semestersid][$coursesid], $split, 
                        $user, $semestersid);
        build_form('option_content', $params);
    } else {
        $split = $splits[$semestersid][$coursesid];
        $params = array($selected, $split);
        build_form('selected_content', $params);
    }
} else if ($mode == 'reset') {
    $split = $splits[$semestersid][$coursesid];
    build_form('reset_content', $split, 'Reset', 'Back',
                "POST");
} else if ($number && $number == $splits[$semestersid][$coursesid]->count) {
    // if they have already split, then tell them to reset first
    // they would only get this message if they tried to hack it
    if (!array_key_exists($coursesid, $splits[$semestersid])) {
        redirect_error('');
    }

    $new_splits = array();
    $sections = $user->getSectionsForCourse($semestersid, $coursesid, false);
    $count=0;
    foreach ($sections as $sectionsid => $section) {
        $split = new CoursePrefsSplit($user->getId(), $sectionsid,
            ++$count, "Section {$section->getSectionNumber()}", 'todo');
        $split->save();
        $new_splits[$split->getShellName()] = $sections[$sectionsid];
    }
    $params = array(array(), $new_splits, array());
    build_form('finished_content', $params, 'Next', '');
} else {
    $params = array($semestersid, $coursesid, $number, $user, $current_splits);
    build_form('numbered_content', $params, get_string('submit'), 
        "", "POST", "post_split.php", 'onsubmit="return validate();"');
}

print_footer();


?> 
