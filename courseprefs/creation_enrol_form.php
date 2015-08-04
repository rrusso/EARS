<?php

/**
 * Form allowing teachers to set preferences related to the creation of courses and enrollment
 * of students.  Used by creation_enrol.php, this form presents users with two types of
 * settings: default and course-specific.  Default settings are applied to all courses while
 * course-specific settings are applied to all sections of a course the teacher teaches.
 *
 * @Original author Andrew Feller
 */
require_once($CFG->libdir . '/formslib.php');
require_once('classes/CoursePrefsCourse.php');
require_once('classes/CoursePrefsUser.php');
require_once('classes/CoursePrefsSemester.php');
require_once('classes/CoursePrefsConfig.php');
require_once('lib.php');

define('CREATE_DAYS_DEFAULT', 30);
define('ENROLL_DAYS_DEFAULT', 14);

define('CREATE_DAYS_FIELD', 'coursecreate');
define('ENROLL_DAYS_FIELD', 'courseenroll');
define('COURSE_GROUP', 'coursegroup');

define('CREATE_DAYS_PATTERN', '/(\d+)'. CREATE_DAYS_FIELD . '(\d+)$/');
define('ENROLL_DAYS_PATTERN', '/(\d+)'. ENROLL_DAYS_FIELD . '(\d+)$/');

define('VALID_DAYS_PATTERN', '/^\d+$/');

class creation_enrol_form extends moodleform {

    public $course_create;
    public $course_enroll;
    public $semester_to_courses;

    function definition() {

        global $CFG, $USER;

        $form =& $this->_form;
        $user = CoursePrefsUser::findByUnique($USER->username);

        // Register regular expression rules for form elements
        $form->registerRule('valid_days', 'regex', VALID_DAYS_PATTERN);

        // Default settings fieldset
        $form->addElement('header', 'default_settings', get_string('default_settings', 'block_courseprefs'));
        $form->setHelpButton('default_settings', array('creation_enrol_default',
            get_string('creation_enrol_default_help', 'block_courseprefs'), 'block_courseprefs', true));

        $form->addElement('static', 'course_create_days', get_string('course_create_days', 'block_courseprefs'),
                CoursePrefsConfig::getNamedValue('course_create_days'));
        $form->addElement('static', 'course_enroll_days', get_string('course_enroll_days', 'block_courseprefs'),
                CoursePrefsConfig::getNamedValue('course_enroll_days'));
        
        $courseformats = mr_get_list_of_plugins('course/format');
        $formcourseformats = array();
        foreach ($courseformats as $courseformat) {
            $formcourseformats["$courseformat"] = get_string("format$courseformat","format_$courseformat");
            if($formcourseformats["$courseformat"]=="[[format$courseformat]]") {
                $formcourseformats["$courseformat"] = get_string("format$courseformat");
            }
        }

        $form->addElement('select', 'format', get_string('format', 'block_courseprefs'), $formcourseformats);

        $numsections = array();
        foreach (range(1,52) as $i) {
            $numsections[$i] = $i;
        }

        $form->addElement('select', 'numsections', get_string('numberweeks', 'block_courseprefs'), $numsections);
       
        $choices = array();
        $choices['0'] = get_string('courseavailablenot');
        $choices['1'] = get_string('courseavailable');
        $form->addElement('select', 'visible', get_string('availability', 'block_courseprefs'), $choices);

        /*
        $choices = array();
        $choices['0'] = get_string('delete_choice_0', 'block_courseprefs');
        $choices['1'] = get_string('delete_choice_1', 'block_courseprefs');
        $form->addElement('select', 'delete', get_string('delete_option', 'block_courseprefs'), $choices);
        */

        /*
        // Adding textbox for user's default for course creation
        $form->addElement('text', 'course_create_days', get_string('course_create_days', 'block_courseprefs'));
        $form->setDefault('course_create_days', $user->getCourseCreateDays());
        $form->addRule('course_create_days', get_string('err_required', 'form'), 'required');
        $form->addRule('course_create_days', get_string('err_negative_number', 'block_courseprefs'), 'valid_days');

        // Adding textbox for user's default for course enrollment
        $form->addElement('text', 'course_enroll_days', get_string('course_enroll_days', 'block_courseprefs'));
        $form->setDefault('course_enroll_days', $user->getCourseEnrollDays());
        $form->addRule('course_enroll_days', get_string('err_required', 'form'), 'required');
        $form->addRule('course_enroll_days', get_string('err_negative_number', 'block_courseprefs'), 'valid_days');
        $form->addRule(array('course_create_days', 'course_enroll_days'),
            get_string('err_create_enroll_compare', 'block_courseprefs'), 'compare', 'gte');
        */

        // Lookup necessary information to build form elements
        $user = CoursePrefsUser::findByUnique($USER->username); 
        $courses = $user->getCoursesAsTeacher();
        $sections = $user->getSectionsAsTeacher();

        // Build lookup of semesters to the courses the user teaches
        $semester_lookup = array();
        $semester_to_courses = array();
        $semester_cache = array();

        foreach ($sections as $section) {

            $semestersid = $section->getSemestersId();

            // Check semester cache for object and look it up if not found
            if (!array_key_exists($semestersid, $semester_cache)) {
                $semester_cache[$semestersid] = CoursePrefsSemester::findById($semestersid);
                $semester_to_courses[$semestersid] = array();
            }

            $semester = $semester_cache[$section->getSemestersId()];

            // Build semester name for fieldsets and associate course with semester
            $semester_lookup[$semester->getId()] = $semester->getYear() . ' '. $semester->getName();
            $semester_to_courses[$semester->getId()][$section->getCoursesId()] = $courses[$section->getCoursesId()];

            // Resort semester's courses and maintain the array indexes
            asort($semester_to_courses[$semester->getId()]);
        }

        // Sort semesters
        uasort($semester_lookup, "cmpSemester");

        // Generate semester-specific field sets containing a list of courses the user teaches
        foreach ($semester_lookup as $semestersid => $semesterheader) {

            $form->addElement('header', 'year_semester' . $semestersid, $semesterheader);
            $form->setHelpButton('year_semester'. $semestersid, array('creation_enrol_semester',
                get_string('creation_enrol_semester_help', 'block_courseprefs'), 'block_courseprefs', true));
            
            $statics = array();
            $statics[] = &$form->createElement('static', 'create_heading', '', 
                        '<span style="margin:0px 30px 0px 35px; font-weight: bold">' .
                        get_string('creation_days', 'block_courseprefs'). '</span>');
            $statics[] = &$form->createElement('static', 'enroll_heading', '', 
                        '<span style="margin:0px 35px 0px 70px; font-weight: bold">' .
                        get_string('enroll_days', 'block_courseprefs'). '</span>');
            $form->addGroup($statics, 'static_groupings', false);

            foreach ($semester_to_courses[$semestersid] as $course) {        

                // Build textbox group to hold course-specific creation/enrollment settings
                $textboxes = array();
                $textboxes[] = &$form->createElement('text', $semestersid . CREATE_DAYS_FIELD . $course->getId(), 
                     null /*,array('value' => $user->getCourseCreateDays())*/);
                $textboxes[] = &$form->createElement('text', $semestersid . ENROLL_DAYS_FIELD . $course->getId(),
                     null /*,array('value' => $user->getCourseEnrollDays())*/);

                // Add form elements for course
                $form->addGroup($textboxes, $semestersid. COURSE_GROUP . $course->getId(),
                    $course->getDepartment() . ' ' . $course->getCourseNumber(), '&nbsp;', false);
                /*$form->addGroupRule($semestersid . COURSE_GROUP . $course->getId(), array(
                    $semestersid . CREATE_DAYS_FIELD . $course->getId() => array(
                        array(get_string('err_negative_number', 'block_courseprefs'), 'compare', 'neq', 0),
                    ),
                    $semestersid . ENROLL_DAYS_FIELD . $course->getId() => array(
                        array(get_string('err_negative_number', 'block_courseprefs'), 'compare', 'neq', 0),
                    ),
                ));*/
            }
        }

        // Adding submit and cancel buttons to form
        $buttonarray = array();
        $buttonarray[] = &$form->createElement('submit', 'submitbutton', get_string('savechanges'));
        $buttonarray[] = &$form->createElement('cancel');
        $form->addGroup($buttonarray, 'buttonar', '', array(' '), false);
        $form->closeHeaderBefore('buttonar');
    }

    function validation($data) {

        global $USER;

        $errorlog = array();
        $form_errorlog = $this->_form->_errors;

        $user = CoursePrefsUser::findByUnique($USER->username);
        $courses = $user->getCoursesAsTeacher();

        $this->course_create = array();
        $this->course_enroll = array();

        $enroll_default = CoursePrefsConfig::getNamedValue('course_enroll_days');
        $create_default = CoursePrefsConfig::getNamedValue('course_create_days');

        // Parse form submission and glean necessary information
        foreach ($data as $key => $value) {

            $matches = array();

            if (preg_match(CREATE_DAYS_PATTERN, $key, $matches) && $courses[$matches[2]]) {

                if (!$this->course_create[$matches[1]]) {
                    $this->course_create[$matches[1]] = array();
                }

                $this->course_create[$matches[1]][$matches[2]] = $value;

            } else if (preg_match(ENROLL_DAYS_PATTERN, $key, $matches) && $courses[$matches[2]]) {

                if (!$this->course_enroll[$matches[1]]) {
                    $this->course_enroll[$matches[1]] = array();
                }

                $this->course_enroll[$matches[1]][$matches[2]] = $value;
            }
        }

        // Iterate over semesters and check course-specific settings are valid
        foreach ($this->course_create as $semestersid => $courses) {

            // Check whether the courses' creation value is greater than or equal to its enrollment value
            foreach ($courses as $coursesid => $value) {

                /*// Skip course if either of its create or enroll fields weren't submitted properly
                if ($form_errorlog[$semestersid . CREATE_DAYS_FIELD . $coursesid] ||
                    $form_errorlog[$semestersid . ENROLL_DAYS_FIELD . $coursesid]) {
                    continue;
                }*/

                $course_create_days = $this->course_create[$semestersid][$coursesid];
                $course_enroll_days = $this->course_enroll[$semestersid][$coursesid];

                if ($course_create_days === '0' || $course_enroll_days === '0') {
                    unset($this->course_enroll[$semestersid][$coursesid]);
                    unset($this->course_create[$semestersid][$coursesid]);
                    $errorlog[$semestersid . COURSE_GROUP . $coursesid] = get_string('err_negative_number', 'block_courseprefs');
                    continue;
                }

                if ($course_create_days == '') {
                    $this->course_create[$semestersid][$coursesid] = $create_default;
                }

                if ($course_enroll_days == '') {
                    $this->course_enroll[$semestersid][$coursesid] = $enroll_default;
                }

                /*
                if ($course_create_days == '' && $course_enroll_days != '') {
                    $a->name = 'Create';
                    $errorlog[$semestersid . COURSE_GROUP . $coursesid] = get_string('err_days_invalid', 'block_courseprefs', $a);
                } else if ($course_enroll_days == '' && $course_create_days!='') {
                    $a->name = 'Enroll';
                    $errorlog[$semestersid . COURSE_GROUP . $coursesid] = get_string('err_days_invalid', 'block_courseprefs', $a);
                }
                */
             
                // Log an error if the course's enrollment value is greater than the creation value
                if ($course_create_days < $course_enroll_days) {
                    $errorlog[$semestersid . COURSE_GROUP . $coursesid] = get_string('err_create_enroll_compare', 'block_courseprefs');
                }
            }

        }

        return $errorlog;
    }
}

?>
