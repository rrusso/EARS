<?php

/**
 * The materials form that handles the creation of materials courses
 * @author Philip Cali
 */
require_once('../../config.php');
require_once('lib.php');
require_once('materials_form.php');
require_once('classes/CoursePrefsUser.php');
require_once('classes/CoursePrefsCourse.php');
require_once('classes/CoursePrefsMaterial.php');

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

$form = new materials_form();

// Redirect user away from the page if they canceled editing their preferences.
if ($form->is_cancelled()) {
    redirect($CFG->wwwroot);
}

// Process form submission if it passed all validation checks
$heading = null;

if ($data = $form->get_data()) {

    // Prematurely set heading that change were saved; overwrite on error
    $heading = get_string('changessaved');

    // Process form submission to determine courses selected for materials courses
    $courses = $user->getCoursesAsTeacher();
    $new_materials_courses = array();

    foreach ($data as $key => $value) {

        $matches = array();

        if (preg_match(MATERIAL_COURSE_PATTERN, $key, $matches) && $courses[$matches[1]]) {
            $new_materials_courses[$matches[1]] = true;
        }
    }
    
    // Delete old materials entries if they haven't been created and weren't selected
    $existing_materials = $user->getMaterials();

    foreach ($existing_materials as $material) {

        // Skip entry if materials course was already created; i.e. create_flag isn't set
        if (!$material->getCreateFlag()) {
            unset($new_materials_courses[$material->getCoursesId()]);
            continue;
        }

        // Skip entry if it was selected in form submission
        if ($new_materials_courses[$material->getCoursesId()]) {
            unset($new_materials_courses[$material->getCoursesId()]);
            continue;
        }

        delete_records('block_courseprefs_materials', 'id', $material->getId());
    }

    // Create new materials entries based on selected courses that don't exist in the database
    foreach ($new_materials_courses as $coursesid => $boolean) {

        $material = new CoursePrefsMaterial($coursesid, $user->getId());
        
        try {
           $material->save();
        } catch(Exception $e) {
            $heading = get_string('changes_not_saved', 'block_courseprefs');
            add_to_log(SITEID, 'courseprefs', 'insert', 'blocks/courseprefs/materials.php',
                'Unable to insert new materials course preference; ' .
                 "Course ID: {$coursesid}");
        }
    }

} else if (!$form->is_submitted()) {

    // Set user preferences since the form wasn't submitted
    $materials = $user->getMaterials();
    $data = array();

    foreach ($materials as $material) {
        $data[MATERIAL_COURSE_FIELD . $material->getCoursesId()] = true;
    }

    $form->set_data($data);
}

// Render the page headers and forms
$heading_main = get_string('materials_heading', 'block_courseprefs');
$navigation = array(
        array('name' => get_string('blockname', 'block_courseprefs'), 'link' => '', 'type' => 'title'),
        array('name' => $heading_main, 'link' => '', 'type' => 'title',)
        );

print_header_simple($heading_main, '', build_navigation($navigation));
print_heading_with_help($heading_main, 'materials', 'block_courseprefs');

if ($heading) {
    print_heading($heading, 'center', 3);
}

$form->display();
print_footer();

?>
