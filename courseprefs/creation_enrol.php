<?php

/**
 * Script rendering and processing form for teachers' preferences on when their courses should be
 * created and students should be enrolled.  Teachers can set defaults for all courses and/or set
 * individual course settings based off the courses they are currently teaching.
 *
 * @Original author Andrew Feller
 */

require_once('../../config.php');
require_once('lib.php');
require_once('creation_enrol_form.php');
require_once('classes/CoursePrefsUser.php');
require_once('classes/CoursePrefsCourse.php');
require_once('classes/CoursePrefsEnroll.php');

// Require users be logged in before accessing page
require_login();

// Disallow anyone who was not processed from the mainframe input files to use this page
$user = CoursePrefsUser::findByUnique($USER->username);

if (!$user) {
    redirect_error(ERROR_USER_MISSING);
}

// Disallow anyone who is not teaching any courses
$courses = $user->getCoursesAsTeacher();

if (!$courses) {
    redirect_error(ERROR_COURSE_NONE);
}

$form = new creation_enrol_form();

// Redirect user away from the page if they canceled editing their preferences.
if ($form->is_cancelled()) {
    redirect($CFG->wwwroot);
}

// Process form submission if it passed all validation checks 
$heading = null;

if ($data = $form->get_data()) {

    // Update user's default preferences
    $user->setFormat($data->format);
    $user->setNumsections($data->numsections);
    $user->setVisible($data->visible);
    //$user->setCrDelete($data->delete);

    $user->save();

    /*$user->setCourseCreateDays($data->course_create_days);
    $user->setCourseEnrollDays($data->course_enroll_days);
    
    try {
        $user->save();
    } catch(Exception $ex) {
        add_to_log(SITEID, 'courseprefs', 'update', 'blocks/courseprefs/creation_enrol.php',
            'Unable to update user default creation/enrollment preferences');
        redirect_error(ERROR_CREATION_DEFAULT_UPDATE, $CFG->wwwroot . '/blocks/courseprefs/creation_enrol.php');
    }*/

    // Build list of courses from existing enroll entries and delete old entries for courses user doesn't teach
    $existing_enroll = $user->getEnrolls();
    $existing_enroll_coursesids = array();

    foreach ($existing_enroll as $enroll) {

        //Delete regard of semester id, at this point.  If they don't have a course. then it's gone
        if (!$courses[$enroll->getCoursesId()]) {
            CoursePrefsEnroll::deleteById($enroll->getId());
        }
        
        if (!$existing_enroll_coursesids[$enroll->getSemestersId()]) {
            $existing_enroll_coursesids[$enroll->getSemestersId()] = array();
        }
        
        $existing_enroll_coursesids[$enroll->getSemestersId()][] = $enroll->getCoursesId();
    }

    // Update user's course-specific preferences
    foreach ($existing_enroll as $enroll) {

        $update_enroll = new CoursePrefsEnroll($enroll->getSemestersId(), $enroll->getCoursesId(), 
            $enroll->getUsersId(), $form->course_create[$enroll->getSemestersId()][$enroll->getCoursesId()], 
            $form->course_enroll[$enroll->getSemestersId()][$enroll->getCoursesId()], $enroll->getId());

        try {
            $update_enroll->save();
        } catch(Exception $ex) {
            add_to_log(SITEID, 'courseprefs', 'update', 'blocks/courseprefs/creation_enrol.php',
                'Unable to update user course-specific creation/enrollment preferences; ' .
                "Semester ID: {$enroll->getSemestersId()}" .
                "Course ID: {$enroll->getCoursesId()}");
            redirect_error(ERROR_CREATION_COURSE_UPDATE, $CFG->wwwroot . '/blocks/courseprefs/creation_enrol.php');
        }
    }
    
    foreach ($form->course_create as $semestersid => $form_courses) {        
        // Determine courses that don't have existing enroll entries and create new ones
        $existing_courses = $existing_enroll_coursesids[$semestersid];
        $new_enroll = array_diff(array_keys($form_courses), ($existing_courses) ? $existing_courses : array());

        foreach ($new_enroll as $coursesid) {

            if (!$form->course_create[$semestersid][$coursesid]
             && !$form->course_enroll[$semestersid][$coursesid]) {
                //Skipping creating the enroll record, due to the record being invalid
                continue;
            }

            $insert_enroll = new CoursePrefsEnroll($semestersid, $coursesid, $user->getId(), 
                $form->course_create[$semestersid][$coursesid], $form->course_enroll[$semestersid][$coursesid]);
        
            try {
                $insert_enroll->save();
            } catch(Exception $ex) {
                add_to_log(SITEID, 'courseprefs', 'update', 'blocks/courseprefs/creation_enrol.php',
                    'Unable to insert new user course-specific creation/enrollment preferences; ' .
                    "Semester ID: {$enroll->getSemestersId()}".
                    "Course ID: {$enroll->getCoursesId()}");
                redirect_error(ERROR_CREATION_COURSE_INSERT, $CFG->wwwroot . '/blocks/courseprefs/creation_enrol.php');
            }
        }    
    }


    // Set heading that change were saved
    $heading = get_string('changessaved');

} else if (!$form->is_submitted()) {

    // Set user preferences since the form wasn't submitted
    $enrolls = $user->getEnrolls();
    $data = array();

    $data['format'] = $user->getFormat();
    $data['numsections'] = $user->getNumsections();
    $data['visible'] = $user->getVisible();
    $data['delete'] = $user->getCrDelete();

    foreach($enrolls as $enroll) {
        $data[$enroll->getSemestersId(). CREATE_DAYS_FIELD . $enroll->getCoursesId()] = $enroll->getCourseCreateDays();
        $data[$enroll->getSemestersId(). ENROLL_DAYS_FIELD . $enroll->getCoursesId()] = $enroll->getCourseEnrollDays();
    }

    $form->set_data($data);
}

// Render the page headers and forms
$heading_main = get_string('creation_heading', 'block_courseprefs');
$navigation = array(
        array('name' => get_string('blockname', 'block_courseprefs'), 'link' => '', 'type' => 'title'),
        array('name' => $heading_main, 'link' => '', 'type' => 'title',)
        );

print_header_simple($heading_main, '', build_navigation($navigation));
print_heading_with_help($heading_main, 'creation_enrol', 'block_courseprefs');

if ($heading) {
    print_heading($heading, 'center', 3);
}

$form->display();
print_footer();

?>
