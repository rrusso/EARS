<?php

require_once('../../config.php');
require_once('lib.php');
require_once($CFG->libdir . '/grade/grade_item.php');
require_once($CFG->libdir . '/grade/grade_grade.php');
require_once($CFG->libdir . '/gradelib.php');
require_once($CFG->dirroot . '/grade/report/simple_grader/lib/simple_grade_hook.php');
require_once($CFG->dirroot . '/grade/lib.php');

require_login();

$id = required_param('id');
$courseid = optional_param('courseid');

$context = get_context_instance(CONTEXT_SYSTEM);

$viewsports = has_capability('block/student_gradeviewer:sportsviewgrades', $context);
$viewgrades = has_capability('block/student_gradeviewer:viewgrades', $context);

// The user doesn't have permission to use the app
if (!$viewsports && !$viewgrades) {
    error(get_string('no_permission', 'block_student_gradeviewer'));
}

// We want to make sure that the course preference block is installed
// because we depend on those tables
if (!get_field('block', 'id', 'name', 'courseprefs')) {
    error(get_string('install_cps', 'block_student_gradeviewer'));
}

// The user we're zoning in on
$user = get_record('block_courseprefs_users', 'id', $id);

// Wait a minute this cps user doesn't exist :(
if(!$user) {
    error(get_string('bad_user', 'block_student_gradeviewer'));
}

// The courses for this student
$courses = pull_courses($user);

$blockname = get_string('blockname', 'block_student_gradeviewer');
$heading_main = get_string('view_grades', 'block_student_gradeviewer');
$navigation = array(
              array('name' => $blockname, 'link' => '', 'type' => 'title'),
              array('name' => $heading_main, 'link' => 'viewgrades.php', 'type' => 'title'),
              array('name' => fullname($user), 'link' => '', 'type'=>'title')
              );

// Begin form output
print_header_simple($heading_main, '', build_navigation($navigation));

// If they don't have any courses, then tell the user so and die
if(empty($courses)) {
    print_heading(get_string('no_courses', 'block_student_gradeviewer'));
    print_footer();
    die;
}

echo '<fieldset class="aligncenter">
        <legend><strong>Grades Overview for '. fullname($user) . '</strong></legend>
        <div class="student_courses">
            <span>'.implode(' | ', pull_grades_courses($courses)).'</span>
        </div>
      </fieldset>';

// If they selected a course, then use that course
$chosen = ($courseid) ? $courses[$courseid] : current($courses);
build_table($chosen);

print_footer();

function build_table($course) {
    global $CFG;

    // Pull an iterator for grades for that user
    $iter = pull_grade_iterator($course->userid, $course->id);

    // Start the table object
    $table = new stdClass;
    $table->head = array(get_string('itemname', 'grades'),
                         get_string('category', 'grades'),
                         get_string('overridden', 'grades'),
                         get_string('excluded', 'grades'),
                         get_string('range', 'grades'),
                         get_string('rank', 'grades'),
                         get_string('feedback', 'grades'),
                         get_string('finalgrade', 'grades'));
    $table->data = array();
    $table->rowclass = array();

    // Function to get ranks of students in a specific grade item
    $context = get_context_instance(CONTEXT_COURSE, $course->id);
    $total_users = get_total_users($course->id);

    foreach ($iter as $grade) {
        $line = array();
        $cat = $grade->sql_grade_item->get_parent_category();
        $fullname = $cat->fullname;
        $itemtype = $grade->sql_grade_item->itemtype;
        $decimals = $grade->sql_grade_item->get_decimals();
   
        $rowclass = '';
        if ($itemtype == 'category') {
            $rowclass = $itemtype;
            $itemname = $fullname . ' Category Total';
        } elseif($itemtype =='course') {
            $itemname = 'Course Total';
        } else {
            $itemname = $grade->sql_grade_item->itemname;
        }

        // Output the necessary information
        $line[] = $itemname;
        $line[] = ($fullname == '?') ? $course->fullname : $fullname;
        $line[] = ($grade->is_overridden()) ? 'Y' : 'N';
        $line[] = ($grade->is_excluded()) ? 'Y' : 'N';
        $line[] = format_float($grade->sql_grade_item->grademin, $decimals) .'-'.
                  format_float($grade->sql_grade_item->grademax, $decimals);
        $line[] = get_rank($grade, $total_users, $context);
        $line[] = format_text($grade->feedback, $grade->feedbackformat);
        $line[] = '<span class="'.passing_grade($grade->sql_grade_item->gradepass,
                                                $grade->finalgrade).'">'.
                  simple_grade_format_gradevalue($grade->finalgrade, 
                                                 $grade->sql_grade_item, true) .
                  '</span>';
        $table->data[] = $line;
        $table->rowclass[] = $rowclass;
    }

    print_table($table);
}

?>
