<?php

require_once($CFG->libdir . '/formslib.php');
require_once('classes/CoursePrefsCourse.php');
require_once('classes/CoursePrefsUser.php');
require_once('classes/CoursePrefsSection.php');
require_once('classes/CoursePrefsSemester.php');
require_once('lib.php');

define('TEXTBOX_GROUP', 'textboxgroup');
define('SECTIONSID_FIELD', 'sectionfield');
define('DEPARTMENT_FIELD', 'department');
define('COURSE_NUMBER_FIELD', 'coursenumber');
define('SECTION_NUMBER_FIELD', 'sectionnumber');

define('SECTIONSID_PATTERN', '/^\d+$/');
define('COURSE_NUMBER_PATTERN', '/^\d{4}$/');
define('SECTION_NUMBER_PATTERN', '/^\d{3}$/');

define('SECTION_DEFAULT', 'SECTION #');
define('DEPARTMENT_DEFAULT', 'DEPT');
define('COURSE_DEFAULT', 'COURSE #');

/**
* This form sets up adding, accepting, rejecting, and removing temateach requests. In order for an entry
* to be valid, the primary section must be team taught with a valid secondary section, meaning that the
* course must exists and taught by a primary instructor that is not himself. For example:
* A (user1) -> B (user2) = Teacher user1 requests to teamteach section A with Teacher user2 from section B
*
* Not valid entries:
* A (user1) -> B (user1) = cannot teamteach with himself
* A (user1) -> B (!user2) = cannot teamteach a section with no primary instructor
* A (user1) -> B (user2), A (user1) -> B (user2) = cannot duplicate entry
*/

class teamteach_add_form extends moodleform {

    public $section_selections;
    public $secondary_teamteaches;

    function definition() {

        global $USER;
        $form =& $this->_form;

        $user = CoursePrefsUser::findByUnique($USER->username);

        // prepare a 2D array of courseids and their sections
        $sections = $user->getTeamteachableSections();

        //Builds the dropdown item array
        $dropdown_items = array();

        foreach ($sections as $sid => $section) {

             $dropdown_items[$sid] = CoursePrefsSection::generateFullname($section->year,
                $section->name, $section->department, $section->course_number,
                $section->section_number);
        }

        // Sort listing of invitations by semesters and prepend the default "NONE" option
        uasort($dropdown_items, "cmpSemester");
        $dropdown_items = array("NONE" => "NONE") + $dropdown_items;

        // Adding team teach fieldset
        $form->addElement('header', 'add_teamteach', get_string('header_teamteach_add', 'block_courseprefs'));
        $form->setHelpButton('add_teamteach', array('teamteach_add',
            get_string('teamteach_add_help', 'block_courseprefs'), 'block_courseprefs', true));

        // Register regular expression rules for form elements
        $form->registerRule('valid_sectionsid', 'regex', SECTIONSID_PATTERN);
        $form->registerRule('valid_course_number', 'regex', COURSE_NUMBER_PATTERN);
        $form->registerRule('valid_section_number', 'regex', SECTION_NUMBER_PATTERN);

        // Adding dropdown element for user-owner sections
        $form->addElement('select', SECTIONSID_FIELD, get_string('teamteach_selected_courses', 'block_courseprefs'),
            $dropdown_items);
        $form->addRule(SECTIONSID_FIELD, get_string('err_select_section', 'block_courseprefs'), 'valid_sectionsid');
        $form->addRule(SECTIONSID_FIELD, get_string('err_select_section', 'block_courseprefs'), 'required');

        // Adding textbox elements to search for foreign section
        $textboxes = array();
        $textboxes[] = &$form->createElement('text', DEPARTMENT_FIELD, null, array('value' => DEPARTMENT_DEFAULT));
        $textboxes[] = &$form->createElement('text', COURSE_NUMBER_FIELD, null, array('value' => COURSE_DEFAULT));
        $textboxes[] = &$form->createElement('text', SECTION_NUMBER_FIELD, null, array('value' => SECTION_DEFAULT));

        $form->addGroup($textboxes, TEXTBOX_GROUP, get_string('teamteach_related_course', 'block_courseprefs'), '', false);
        $form->addGroupRule(TEXTBOX_GROUP, array(
            DEPARTMENT_FIELD => array(
                array(get_string('err_missing_department', 'block_courseprefs'), 'required'),
                ),
            COURSE_NUMBER_FIELD => array(
                array(get_string('err_missing_course_number', 'block_courseprefs'), 'required'),
                array(get_string('err_invalid_course_number', 'block_courseprefs'), 'valid_course_number'),
                ),
            SECTION_NUMBER_FIELD => array(
                array(get_string('err_missing_section_number', 'block_courseprefs'), 'required'),
                array(get_string('err_invalid_section_number', 'block_courseprefs'), 'valid_section_number'),
                ),
            )
        );

        // Adding submit button to form
        $form->addElement('submit', 'submit', get_string('add_entry', 'block_courseprefs'));
    }

    function validation($data) {

        global $USER;

        $errorlog = array();
        $form_errorlog = $this->_form->_errors;

        // Skip processing group if there were errors with any of the related group fields
        if ($form_errorlog[TEXTBOX_GROUP] || $form_errorlog[SECTIONSID_FIELD]) {
            return $errorlog;
        }

        // Parse form submission and glean necessary information
        $section_id = $data[SECTIONSID_FIELD];
        $department = $data[DEPARTMENT_FIELD];
        $course_number = $data[COURSE_NUMBER_FIELD];
        $section_number = $data[SECTION_NUMBER_FIELD];

        // Build sections ID to courses ID lookup for data validation
        $user = CoursePrefsUser::findByUnique($USER->username);
        $sections = $user->getSectionsAsTeacher();

        // Log an error if the user isn't the primary teacher of the section selected
        if (!($sections[$section_id] && $user->isPrimaryTeacher($sections[$section_id]))) {
            $errorlog[TEXTBOX_GROUP] = get_string('err_not_primary', 'block_courseprefs');
            return $errorlog;
        }

        // Log an error if the section selected doesn't exist
        $users_section = $sections[$section_id];

        if (!$users_section) {
            $errorlog[TEXTBOX_GROUP] = get_string('err_unknown_section', 'block_courseprefs');
            return $errorlog;
        }

        // Retrieve course related to critera and log an error if course doesn't exist
        $course = CoursePrefsCourse::findByUnique($department, $course_number);

        if (!$course) {
            $errorlog[TEXTBOX_GROUP] = get_string('err_unknown_course', 'block_courseprefs');
            return $errorlog;
        }

        // Retrieve section related to critera and log an error if course doesn't exist
        $section = CoursePrefsSection::findByUnique($users_section->getSemestersId(), $course->getId(), $section_number);

        if (!$section) {
            $errorlog[TEXTBOX_GROUP] = get_string('err_unknown_section', 'block_courseprefs');
            return $errorlog;
        }

        // Log an error if the user has already selected these sections to be team taught
        if (CoursePrefsTeamTeach::findByUnique($user->getId(), $users_section->getId(), $section->getId())) {
            $errorlog[TEXTBOX_GROUP] = get_string('err_same_teamteach', 'block_courseprefs');
            return $errorlog;
        }

        // Determine the primary teacher of the foreign section
        $teachers = CoursePrefsTeacher::findBySectionId($section->getId());
        $teacher_primary = null;

        foreach ($teachers as $teacher) {

            // Skip if teacher is not the primary teacher
            if (!$teacher->getPrimaryFlag()) {
                continue;
            }

            $teacher_primary = $teacher;
            break;
        }

        // Log an error if there isn't a primary teacher to notify or if the user teaches both courses.
        // Otherwise, store the ids of the sections selected for team teaching
        if (!$teacher_primary) {
            $errorlog[TEXTBOX_GROUP] = get_string('no_instructor', 'block_courseprefs');
        } else if ($teacher_primary->getUsersId() == $user->getId()) {
            $errorlog[TEXTBOX_GROUP] = get_string('same_teacher', 'block_courseprefs');
        } else {
            $this->section_selections = $section_id;
            $this->secondary_teamteaches = $section->getId();
        }

        return $errorlog;
    }
}

?>
