<?php

/*
 * Form allowing teachers to set preferences related to unwanted courses.
 *
 * @Original author Adam Zapletal
 */

require_once($CFG->libdir . '/formslib.php');
require_once('classes/CoursePrefsCourse.php');
require_once('classes/CoursePrefsSection.php');
require_once('classes/CoursePrefsUser.php');
require_once('classes/CoursePrefsSemester.php');
require_once('lib.php');

// Prefix used when generating dynamic checkboxes for selected courses
define('UNWANTED_COURSE_FIELD',    'unwanted_course');
define('UNWANTED_SECTION_FIELD',   'unwanted_section');

// Perl regex used to strip ID from field names
define('UNWANTED_COURSE_PATTERN',  '/^' . UNWANTED_COURSE_FIELD . '(\d+)$/');
define('UNWANTED_SECTION_PATTERN', '/^' . UNWANTED_SECTION_FIELD . '(\d+)_(\d+)$/');

class unwanted_form extends moodleform {

    public $selected_courses;
    public $selected_sections;

    function definition() {
        global $CFG, $USER;
        $form =& $this->_form;

        $user = CoursePrefsUser::findByUnique($USER->username);
        $sections = $user->getSectionsInfoAsPrimaryTeacher();
        $courses = $user->getCoursesAsPrimaryTeacher();

        // Builds a lookup table associating sections with its parent course
        $coursesid_to_sections = array();
        $semester_cache = array();

        foreach ($sections as $sectionsid => $section) {

            $semestersid = $section->semestersid;
            $coursesid = $section->coursesid;

            // Lookup semester object and store in cache if not found
            if (!array_key_exists($semestersid, $semester_cache)) {
                $semester_cache[$semestersid] = CoursePrefsSemester::findById($semestersid);
            }

            $semester = $semester_cache[$semestersid];
            $course = $courses[$coursesid];

            // Associate section with course and build sorting string for cmpSemester
            if (!array_key_exists($coursesid, $coursesid_to_sections)){
                $coursesid_to_sections[$coursesid] = array();
            }

            $section_fullname = CoursePrefsSection::generateFullname($semester->getYear(), $semester->getName(),
                $course->getDepartment(), $course->getCourseNumber(), $section->section_number);
            $coursesid_to_sections[$coursesid][$sectionsid] = $section_fullname;
        }

        // Generate course-specific field sets
        foreach ($courses as $coursesid => $course) {

            $courses_sections = $coursesid_to_sections[$coursesid];

            // Build information to be substituted in listing
            $a = new stdClass();
            $a->course_department = $course->getDepartment();
            $a->course_number = $course->getCourseNumber();

            $form->addElement('header', 'course_settings' . $coursesid,
                get_string('unwanted_label_fieldset', 'block_courseprefs', $a));
            $form->addElement('static', UNWANTED_COURSE_FIELD . $coursesid, '',
                '<a onclick="toggleUnwanted(\''.$coursesid.'\', true)" href="javascript:void(0)">'.
                get_string('all').'</a>/<a onclick="toggleUnwanted(\''.
                $coursesid.'\', false)" href="javascript:void(0)">'.get_string('none').'</a>');

            uasort($courses_sections, "cmpSemester");

            foreach($courses_sections as $sectionsid => $fullname){

                $section = $sections[$sectionsid];
                $semester = $semester_cache[$section->semestersid];

                // Build information to be substituted in listing
                $a = new stdClass();
                $a->semester_year = $semester->getYear();
                $a->semester_name = $semester->getName();
                $a->section_number = $section->section_number;

                $form->addElement('checkbox', UNWANTED_SECTION_FIELD . $sectionsid 
                    . '_' . $coursesid, '<span class="choiceindenter">',
                    get_string('unwanted_label_section', 'block_courseprefs', $a));
            }
        }

        // Adding submit and cancel buttons to form
        $buttons = array();
        $buttons[] = &$form->createElement('submit', 'submitbutton', get_string('savechanges'));
        $buttons[] = &$form->createElement('cancel');
        $form->addGroup($buttons, 'buttonar', '', array(' '), false);
        $form->closeHeaderBefore('buttonar');
    }

    function validation($data) {
        global $USER;

        $errorlog = array();
        $form_errorlog = $this->_form->_errors;

        // Lookup necessary objects related to the user and his/her courses ans sections
        $user = CoursePrefsUser::findByUnique($USER->username);
        $sections = $user->getSectionsInfoAsPrimaryTeacher();

        // Parse form submission and glean necessary information
        $this->selected_sections = array();

        foreach ($data as $key => $value) {

            $matches = array();

            if (preg_match(UNWANTED_SECTION_PATTERN, $key, $matches)) {
                $sectionsid = $matches[1]; 
                $coursesid =  $matches[2];

                $section = $sections[$sectionsid];

                // Associated section with its course
                if (!$this->selected_sections[$coursesid]) {
                    $this->selected_sections[$coursesid] = array();
                }

                $this->selected_sections[$coursesid][$section->id] = $section;
            }
        }

        // Iterate over new unwanted courses and remove new unwanted sections
        if (!empty($this->selected_courses)) {
            foreach ($this->selected_courses as $coursesid => $boolean) {

                if (isset($this->selected_sections[$coursesid])) {
                    unset($this->selected_sections[$coursesid]);
                }
            }
        }
    }
}

?>
