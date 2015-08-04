<?php

/*
 * Script rendering and processing form for teachers' preferences on unwanted courses.
 *
 * @Original author Adam Zapletal and Philip Cali
 */
require_once('../../config.php');
require_once('lib.php');
require_once('unwanted_form.php');
require_once('classes/CoursePrefsSection.php');
require_once('classes/CoursePrefsUser.php');
require_once('classes/CoursePrefsCourse.php');

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

$form = new unwanted_form();

// Redirect user away from the page if they canceled editing their preferences.
if ($form->is_cancelled()) {
    redirect($CFG->wwwroot);
}

require_js($CFG->wwwroot. '/blocks/courseprefs/functions.js');

// Process form submission if it passed all validation checks
$heading = null;

if ($data = $form->get_data()) {

    // Prematurely set heading that change were saved; overwrite on error
    $heading = get_string('changessaved');

    // Set user preferences since the form wasn't submitted
    $unwanted_array = $user->getUnwanted();

    $unwants = array_keys($unwanted_array);
    if (!empty($unwants)) {
        foreach ($unwanted_array as $unwanted) {

            // Skip existing unwanted section as it was selected on form submission
            if ($form->selected_sections[$unwanted->getCoursesId()][$unwanted->getId()]) {
                unset($form->selected_sections[$unwanted->getCoursesId()][$unwanted->getId()]);
                continue;
            }
      
            $unwanted->setStatus(CoursePrefsSection::STATUS_REQUESTED);
            $unwanted->save();

            CoursePrefsLog::add_to_log($user->getId(), $unwanted->getId(), time(), 'reset', 'Unwant reset');
            
        }

        $in = implode(',', $unwants);
        foreach (array('teachers', 'students') as $concern) {
            $sql = "UPDATE {$CFG->prefix}block_courseprefs_$concern t 
                        SET status='enroll'
                        WHERE t.sectionsid IN ({$in})
                          AND t.status = 'enrolled'";
            execute_sql($sql, false);
        }
    }

    // Iterate over new unwanted sections and store them in the database
    foreach ($form->selected_sections as $coursesid => $section_array) {

        foreach ($section_array as $sectionsid => $section) {

            try{
                $section->status = CoursePrefsSection::STATUS_UNWANT;
                update_record('block_courseprefs_sections', $section);

                CoursePrefsLog::add_to_log($user->getId(), $sectionsid, time(), 'unwant', 'unwant');
            } catch (Exception $e) {
                $heading = get_string('changes_not_saved', 'block_courseprefs');
                add_to_log(SITEID, 'courseprefs', 'update', 'blocks/courseprefs/unwanted.php',
                    'Unable to insert new user unwanted section preference; ' .
                    "Course ID: {$section->coursesid}, " .
                    "Section ID: {$sectionsid}");
            }
        }
    }

} else if (!$form->is_submitted()) {

    // Set user preferences since the form wasn't submitted
    $unwanted_array = $user->getUnwanted();

    $form_defaults = array();

    foreach ($unwanted_array as $unwanted) {

        $form_defaults[UNWANTED_SECTION_FIELD . $unwanted->getId() . '_' . $unwanted->getCoursesId()] = true;
    }

    $form->set_data($form_defaults);
}

// Render the page headers and forms
$heading_main = get_string('unwanted_heading', 'block_courseprefs');
$navigation = array(
        array('name' => get_string('blockname', 'block_courseprefs'), 'link' => '', 'type' => 'title'),
        array('name' => $heading_main, 'link' => '', 'type' => 'title'),
        );

print_header_simple($heading_main, '', build_navigation($navigation));
print_heading_with_help($heading_main, 'unwanted', 'block_courseprefs');

if ($heading) {
    print_heading($heading, 'center', 3);
}

$form->display();
print_footer();

?> 
