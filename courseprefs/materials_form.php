<?php

/**
 * Moodle form allowing visual creataion of a materials course
 * @author Philip Cali
 */

require_once($CFG->libdir . '/formslib.php');
require_once('classes/CoursePrefsUser.php');
require_once('classes/CoursePrefsCourse.php');
require_once('classes/CoursePrefsMaterial.php');

// Prefix used when generating dynamic checkboxes for selected courses
define('MATERIAL_COURSE_FIELD', 'materials_course_');

//Perl regex used to strip ID from field names
define('MATERIAL_COURSE_PATTERN', '/^' . MATERIAL_COURSE_FIELD. '(\d+)$/');

class materials_form extends moodleform {

    function definition() {

        global $CFG, $USER;

        $form =& $this->_form;
        $user = CoursePrefsUser::findByUnique($USER->username);

        $courses = $user->getCoursesAsTeacher(); 

        // Course-specific settings
        $form->addElement('header', 'course_settings', get_string('materials_courses', 'block_courseprefs'));
        $form->setHelpButton('course_settings', array('materials_courses',
                get_string('materials_courses_help', 'block_courseprefs'), 'block_courseprefs', true));

        // Remove courses to be generated if they have been created already; i.e. create_flag isn't set
        $materials = $user->getMaterials();

        foreach ($materials as $material){

            if (!$material->getCreateFlag()) {
                unset($courses[$material->getCoursesId()]);
            }
        }

        // Generate checkboxes for courses that haven't had a materials courses created yet
        foreach ($courses as $course) {
            $form->addElement('checkbox', MATERIAL_COURSE_FIELD . $course->getId(), NULL,
                $course->getDepartment() . ' ' . $course->getCourseNumber());
        }

        // BUTTONS BELOW FORM
        $buttonarray = array();
        $buttonarray[] = &$form->createElement('submit', 'submitbutton', get_string('savechanges'));
        $buttonarray[] = &$form->createElement('cancel');
        $form->addGroup($buttonarray, 'buttonar', '', array(' '), false);
        $form->closeHeaderBefore('buttonar');
    }
}

?>

