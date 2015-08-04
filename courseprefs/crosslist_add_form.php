<?php

/**
 * Form for allowing instructors to cros list sections they own
 * @Original author: Philip Cali
 */

require_once($CFG->libdir . '/formslib.php');
require_once('classes/CoursePrefsCourse.php');
require_once('classes/CoursePrefsSection.php');
require_once('classes/CoursePrefsUser.php');
require_once('classes/CoursePrefsSemester.php');
require_once('lib.php');

// Constants defining the form elements that are to be rendered
define('SECTIONSID_FIELD', 'sectionsid');
define('CR_SECTIONSID_FIELD', 'cr_sectionsid');

// Regular expressions used to validate form elements
define('SECTIONSID_PATTERN', '/^\d+$/');

/**
* Crosslisting page adds entries for courses to be crosslisted.  the following validations must occur before two sections
* are legitimatly crosslisted:  the primary section must crosslist with a unique secondary, and primary and secondary sesmesters
* MUST be the same. For example:
* Diagram NOTE: A(Fall) -> B(Fall) = A crosslisted B
*
* The following logic will NOT be valid:
* A -> A: cannot crosslist with itself
* A -> B, B -> A: crosslist is not reflexive
* A -> B, B -> C: rather than assuming A->C, it's throws a validation error
* A -> B, A -> B: entries must be unique
* A(Fall) -> B(Spring): crosslist must be of the same semester
*/

class crosslist_add_form extends moodleform {

    function definition() {

        global $CFG, $USER;

        $form =& $this->_form;
        $user = CoursePrefsUser::findByUnique($USER->username);

        // Generate listing of sections the user is the primary teacher
        $dropdown_items = array();
        $sections = $user->getSectionsAsPrimaryTeacher();

        foreach ($sections as $section) {

            // Skip section if the user isn't the primary teacher of it
            if (!$user->isPrimaryTeacher($section)) {
                continue;
            }

            $dropdown_items[$section->getId()] = CoursePrefsSection::generateFullnameById($section->getId());
        }

        // Sort listing of sections by semesters and prepend the default "NONE" option
        uasort($dropdown_items, "cmpSemester");
        $dropdown_items = array("NONE" => "NONE") + $dropdown_items;

        // Adding cross listing fieldset
        $form->addElement('header', 'add_crosslist', get_string('header_crosslist_add', 'block_courseprefs'));
        $form->setHelpButton('add_crosslist', array('crosslist_add',
            get_string('crosslist_add_help', 'block_courseprefs'), 'block_courseprefs', true));

        // Register regular expression rules for form elements
        $form->registerRule('valid_sectionsid', 'regex', SECTIONSID_PATTERN);

        // Adding dropdown element for user-owner sections
        $form->addElement('select', SECTIONSID_FIELD, get_string('crosslist_primary_section',
            'block_courseprefs'), $dropdown_items);
        $form->addRule(SECTIONSID_FIELD, get_string('err_select_section', 'block_courseprefs'),
            'valid_sectionsid');

        // Adding dropdown element for cross listed section
        $form->addElement('select', CR_SECTIONSID_FIELD,
            get_string('crosslist_secondary_section', 'block_courseprefs'), $dropdown_items);
        $form->addRule(CR_SECTIONSID_FIELD, get_string('err_select_section', 'block_courseprefs'),
            'valid_sectionsid');

        // Add submit button to form
        $form->addElement('submit', 'submit', get_string('add_entry', 'block_courseprefs'));
    }

    function validation($data){

        global $USER;

        $form_errorlog = $this->_form->_errors;
        $errorlog = array();

        // Prematurely end form submission validation if any errors were encountered during submission
        if (array_key_exists(SECTIONSID_FIELD, $form_errorlog) || array_key_exists(CR_SECTIONSID_FIELD, $form_errorlog)) {
            return $errorlog;
        }

        $user = CoursePrefsUser::findByUnique($USER->username);
        $sections = $user->getSectionsAsPrimaryTeacher();

        // Lookup selected sections from lookup of sections the user teaches
        $sectionsid = $data[SECTIONSID_FIELD];
        $cr_sectionsid = $data[CR_SECTIONSID_FIELD];
        $section = $sections[$sectionsid];
        $cr_section = $sections[$cr_sectionsid];

        // Log an error if the user isn't the primary instructor of the selected section
        if (!($section && $user->isPrimaryTeacher($section))) {
            $errorlog[SECTIONSID_FIELD] = get_string('err_crosslist_not_primary', 'block_courseprefs');
        }

        // Log an error if the user isn't the primary instructor of the selected section to be cross listed
        if (!($cr_section && $user->isPrimaryTeacher($cr_section))) {
            $errorlog[CR_SECTIONSID_FIELD] = get_string('err_crosslist_not_primary', 'block_courseprefs');
        }

        // Prematurely end form submission validation if any errors were encountered so far
        if ($errorlog) {
            return $errorlog;
        }

        // Log an error if the selected sections are the same
        // Log an error if the selected sections are not part of the same semester
        if ($sectionsid == $cr_sectionsid) {
            $errorlog[CR_SECTIONSID_FIELD] = get_string('err_crosslist_same_section', 'block_courseprefs');
        } else if ($section->getSemestersId() != $cr_section->getSemestersId()) {
            $errorlog[CR_SECTIONSID_FIELD] = get_string('err_crosslist_different_semester', 'block_courseprefs');
        }

        // Prematurely end form submission validation if any errors were encountered so far
        if ($errorlog) {
            return $errorlog;
        }

        // Validate form submission against existing cross listing entries
        $crosslists = $user->getCrosslists();

        foreach ($crosslists as $id => $crosslist) {

            // Log an error if the primary section selected is already cross listed as a secondary section
            if ($sectionsid == $crosslist->getCrSectionsId()) {
                $errorlog[SECTIONSID_FIELD] = get_string('err_crosslist_section_is_a_secondary', 'block_courseprefs');
            }

            // Log an error if the secondary section selected is already cross listed as a primary section
            if ($cr_sectionsid == $crosslist->getSectionsId()) {
                $errorlog[CR_SECTIONSID_FIELD] = get_string('err_crosslist_section_is_a_primary', 'block_courseprefs');
            }

            // Log an error if the user has previously selected the primary and secondary sections
            // Log an error if the secondary section selected is already cross listed as a secondary section
            if ($sectionsid == $crosslist->getSectionsId() && $cr_sectionsid == $crosslist->getCrSectionsId()) {
                $errorlog[CR_SECTIONSID_FIELD] = get_string('err_crosslist_not_unique', 'block_courseprefs');
            } else if ($cr_sectionsid == $crosslist->getCrSectionsId()) {
                $errorlog[CR_SECTIONSID_FIELD] = get_string('err_crosslist_section_is_a_secondary', 'block_courseprefs');
            }
        }

        return $errorlog;
    }
}

?>
