<?php

require_once('../../config.php');
require_once('lib.php');
require_once($CFG->dirroot . '/blocks/courseprefs/ui/lib.php');
require_once($CFG->dirroot . '/blocks/courseprefs/classes/CoursePrefsUser.php');
require_once($CFG->libdir . '/grade/grade_item.php');
require_once($CFG->libdir . '/grade/grade_grade.php');
require_once($CFG->libdir . '/gradelib.php');
require_once($CFG->dirroot . '/grade/report/simple_grader/lib/simple_gradelib.php');

require_login();

// Course id required
$courseid = required_param('id', PARAM_INT);

$course = has_reporting_permission($courseid);

// I need Jquery for nice things
require_js(array($CFG->wwwroot . '/blocks/student_reporting/jquery.min.js',
                 $CFG->wwwroot . '/blocks/student_reporting/functions.js'));

// Print the header and heading
$blockname = get_string('blockname', 'block_student_reporting');
$heading = get_string('select', 'block_student_reporting');
$navigation = array(
              array('name' => $course->shortname, 
                    'link' => $CFG->wwwroot. '/course/view.php?id='. $courseid,
                    'type' => 'title'),
              array('name' => $blockname, 'link' => '', 'type' => 'title'),
              array('name' => $heading, 'link' => '', 'type' => 'title'));

print_header_simple($heading, '', build_navigation($navigation));
print_heading_with_help($heading, 'select', 'block_student_reporting');

$filters = cps_user_component::build_filters('select.php', array('id' => $courseid), 
            array('section' => array('course' => $course))
           );
$filters->display();

// Permission and dependencies aside, let's get to the logic
// Obtain CPS sections tied to this course idnumber
$user = CoursePrefsUser::findByUnique($USER->username);

$where = $filters->where_clause(create_function('$k,$w', '
            switch($k) {
                case "section": return "s." . $w;
                default: return "cpsu." . $w;
            }
'));

if(is_siteadmin($USER->id)) {
    $sections = cps_sections($course, $where['section']);
} else if($user) {
    $sections = $user->getSectionsForMoodleCourse($course);
} else {
    echo '<span class="error">'.get_string('no_permission', 'block_student_gradeviewer').'</span>';
    print_footer(); 
    die();
}

echo '<form method="POST" action="report.php">
        <div class="report_wrap">';

// Grab the course item
$course_item = grade_item::fetch_course_item($courseid);

unset($where['section']);
$printed = false;
// For each section, grab the students for each sections
foreach($sections as $id => $section) {
    $students = get_students($section, $where);

    // No students means skip this section
    if(!$students) {
        continue;
    }
    $printed = true;

    $referrals = get_referred_students($section);

    $name = get_string('format_section', 'block_courseprefs', $section); 
    echo '<h2 class="section_header">' . 
          print_checkbox('section_' . $section->id, 1, false, 
                         '', '', '', true) . $name. '</h2>
            <ul id="section_'.$section->id.'" class="student_list">';
    foreach($students as $usersid => $student) {

        $images = array_reduce(
                    array_map('print_source', 
                        array_filter($referrals, 
                            create_function('$referral', '
                                return $referral->usersid == '.$usersid.';
                            ')
                        )
                    ), 'reduce_source');

        $grade = new grade_grade(array('userid' => $student->moodleid,
                                       'itemid' => $course_item->id));

        echo '<li>';
        print_checkbox('user_'.$usersid, 1, false);
        echo    $images . '<a class="'.passing_grade($course_item, $grade).' userlink" id="user_'.
                $student->moodleid.'" href="'.$CFG->wwwroot.
                '/grade/report/user/index.php?userid='.
                $student->moodleid.'&amp;id='.$courseid.'">'.
                fullname($student) . ' ' . 
                simple_grade_format_gradevalue($grade->finalgrade, $course_item, true). '</a>';
        echo '<div class="dynamic" style="display:none;" id="report_user_'.
             $student->moodleid.'"></div></li>';
    }
    echo '  </ul>';
}

if(!$printed) {
    echo '</div></form>';
    echo '<div class="no_results">'.get_string('no_results', 
         'block_student_reporting').'</div>';
    print_footer();
    die();
}

echo '  <input type="hidden" name="id" value="'.$courseid.'">
        <input type="submit" value="'.get_string('report', 'block_student_reporting').'">
        </div>
      </form>';

print_footer();

function reduce_source($in, $image) {
    $inter = (empty($in)) ? '' : $in;
    return $inter . ' ' . $image;
}

?>
