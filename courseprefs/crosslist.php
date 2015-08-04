<?php

require_once('../../config.php');
require_once('lib.php');
require_once('crosslist_form.php');
require_once('classes/CoursePrefsCourse.php');
require_once('classes/CoursePrefsCrosslist.php');
require_once('classes/CoursePrefsUser.php');

require_login();

$user = CoursePrefsUser::findByUnique($USER->username);

if(!$user) {
    redirect_error(ERROR_USER_MISSING);
}

$crosslistable = $user->getCrosslistableSections();

$semesters = get_records('block_courseprefs_semesters');
if(!$crosslistable || count($crosslistable) < 2) {
    redirect_error(ERROR_CROSSLIST_MISSING);
}
require_js($CFG->wwwroot . '/blocks/courseprefs/functions.js');

// Render the page headers and forms
$heading_main = get_string('crosslist_heading', 'block_courseprefs');
$navigation = array(
        array('name' => get_string('blockname', 'block_courseprefs'), 'link' => '', 'type' => 'title'),
        array('name' => $heading_main, 'link' => '', 'type' => 'title',)
        );

$next = "Next";
$back = "Back";
$script = '';
$action = 'crosslist.php';

if ($data = data_submitted()) {

    $fields = get_object_vars($data);

    if ($fields['submit'] == "Back") {
        redirect($action);
    } else {
        unset($fields['submit']);
        load_session($fields);
    }

    $semestersid = is_set_null('semestersid', $SESSION->crosslist_preferences);
    $keys = is_set_null('courses', $SESSION->crosslist_preferences);
    $number = is_set_null('number', $SESSION->crosslist_preferences);
    $mode = is_set_null('mode', $SESSION->crosslist_preferences);

    $current_crosslists = $user->getCrosslists(false, true);

    // Try to find existing ones
    $accepted_cr = findCrosslist($keys, $semestersid, $current_crosslists);

    if ($mode && $mode == 'reset') {
        $courseids = implode(',', $keys);
        $sql = "SELECT * FROM {$CFG->prefix}block_courseprefs_courses WHERE id IN ({$courseids})";
        $params = get_records_sql($sql); 
        $callback = 'reset_content';
    } else if ($mode == 'perform_reset') {
        findResetables($user, $keys, $semestersid);
        redirect($action);
    } else if (!$number) {

        if (!empty ($accepted_cr)){
            $params = array($accepted_cr, $keys, $semestersid, $user);
            $callback = 'option_content';
        } else {
            $courses = $user->getCrosslistableSections(true, true);
            $params = array($courses, $keys, $semestersid);
            $callback = 'selected_content';
        }
    } else {
        if ($mode == 'regroup') {
            // They just want to edit their current setup, no problem
            $numbers = $user->findClNumbers($semestersid, $keys);
        } else if ($mode == 'regroup_add') {
            // Were going to add shells to their setup
            $numbers = $user->findClNumbers($semestersid, $keys);
            $total = $numbers[count($numbers) -1];
            for ($i = 1; $i <= $data->extra; $i++) {
                $numbers[] = $total + $i;
            }
        } else {
            // They never did anything before
            $originals = $user->findClNumbers($semestersid);
            $original = (!empty($originals)) ? $originals[count($originals) -1] : 1;
            $numbers = range($original + 1, $number + $original);
        }

        $SESSION->crosslist_preferences['numbers'] = $numbers;       

        $courses = array_map('find_sections', $keys);
        $params = array($semestersid, $numbers, $courses, $user);
        $callback = 'move_sections';
        $script = 'onsubmit="return crosslist_validate();"';
        $action = 'crosslist_post.php';
        $back= '';
    }
} else {
    // Let's do a session cleanup, just in case
    unset($SESSION->crosslist_preferences);

    $current_crosslists = $user->getCrosslists(false, true);
    $params = array($current_crosslists, $crosslistable);
    $callback = 'initial_content';
    $back = '';
    $script = 'onsubmit="return crosslist_initial_validate();"';
}

print_header_simple($heading_main, '', build_navigation($navigation));
print_heading_with_help($heading_main, 'crosslist', 'block_courseprefs');

if($user->getCrDelete()) {
    echo '<div class="cps_error">'.get_string('error_preference_may', 'block_courseprefs');
    if (!empty($keys)) {
        $course_names = array();
        foreach ($crosslistable as $course) {
            if (in_array($course->coursesid, $keys)) {
                $course_names[] = $course->year . ' ' . $course->name . ' ' .
                        $course->department . ' '. $course->course_number;
            }
        }
        echo implode (' and ', $course_names);

    } else {
        echo get_string('error_preference_any', 'block_courseprefs');
    }
    echo '<p class="cps_smallerror">' . get_string('error_preference_sub', 'block_courseprefs') . '</p>';
    echo '</div>';
}

build_form($callback, $params, $next, $back, "POST", 
            $action, $script);

print_footer();

function find_sections($key) {
    global $CFG, $USER, $SESSION;
    $semestersid = $SESSION->crosslist_preferences['semestersid'];
    $coursesid = $key;
    $sql = "SELECT sec.*, sm.year, sm.name,
                cou.department, cou.course_number
                FROM
                {$CFG->prefix}block_courseprefs_sections sec,
                {$CFG->prefix}block_courseprefs_courses  cou,
                {$CFG->prefix}block_courseprefs_semesters sm,
                {$CFG->prefix}block_courseprefs_teachers t,
                {$CFG->prefix}block_courseprefs_users    u
              WHERE u.username='{$USER->username}'
                AND t.usersid = u.id
                AND t.sectionsid = sec.id
                AND sec.semestersid = sm.id
                AND sec.coursesid = cou.id
                AND t.primary_flag = 1
                AND t.status != 'unenrolled'
                AND cou.id = {$coursesid}
                AND sm.id = {$semestersid}
                AND sec.status != 'unwant'
                AND sec.status != 'unwanted'
              ORDER BY sec.section_number";
    return get_records_sql($sql);
}

function is_set_null($key, $arr) {
    return (isset($arr[$key])) ? $arr[$key] : null;
}

function findCrosslist($keys, $semestersid, $crosslists) {
    $results = array();
    foreach ($keys as $key) {
        if (array_key_exists($semestersid, $crosslists) &&
            array_key_exists($key, $crosslists[$semestersid])) {
            $results += $crosslists[$semestersid][$key];
        }
    }
    return $results;
}

function load_session($fields) {
    global $SESSION;

    if (!isset($SESSION->crosslist_preferences)) {
        $keys = array_keys($fields);
        if (count($keys) <= 1) {
            // This is a problem
            redirect('crosslist.php');
        } 

        $SESSION->crosslist_preferences = array();
        foreach ($keys as $key) {
            list($semestersid, $coursesid) = explode('_', $key);
            if (isset($SESSION->crosslist_preferences['semestersid']) 
              && $SESSION->crosslist_preferences['semestersid'] != $semestersid) {
                // Crosslisting between different semesters: not allowed
                redirect('crosslist.php');
            }
            $SESSION->crosslist_preferences['semestersid'] = $semestersid;
            if (!isset($SESSION->crosslist_preferences['courses'])) {
                $SESSION->crosslist_preferences['courses'] = array();
            }
            $SESSION->crosslist_preferences['courses'][] = $coursesid;
        }
    } else {
        foreach ($fields as $k=>$v) {
            $SESSION->crosslist_preferences[$k] = $v;
        }
    }
}

function findResetables($user, $courses, $semestersid) {
    global $CFG, $USER;
  
    $semester = get_record('block_courseprefs_semesters', 'id', $semestersid);
    $semester_name = "{$semester->year}{$semester->name}{$user->getUsername()}cl";
    $numbers = $user->findClNumbers($semestersid, $courses); 

    foreach ($numbers as $number) {
        $crosslists = CoursePrefsCrosslist::findByNumber($semester_name . $number);
        if (empty($crosslists)) {
            continue;
        }        
        reset_prefs($crosslists);
    }
}

?>
