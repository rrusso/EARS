<?php

/**
* Form for removing cross listed courses
* @Original author: Philip Cali and Adam Zapetal
*/

require_once($CFG->libdir . '/formslib.php');
require_once('classes/CoursePrefsCourse.php');
require_once('classes/CoursePrefsSection.php');
require_once('classes/CoursePrefsUser.php');

//definition to be used for the regex
define('CROSSLIST_DELETE_GROUP', 'crosslist_delete');
define('CROSSLIST_DELETE_BUTTON', 'crosslist_delete_button');

//Perl regex expressions used to pull out section 
define('CROSSLIST_DELETE_PATTERN', '/^' . CROSSLIST_DELETE_GROUP.  '(\d+)$/');

class crosslist_remove_form extends moodleform {

    public $selected_crosslists;

    function definition() {

        global $CFG, $USER;
        $form =& $this->_form;

        $user = CoursePrefsUser::findByUnique($USER->username);

        // Remove cross listing header
        $form->addElement('header', 'remove_crosslist', get_string('header_crosslist_remove', 'block_courseprefs'));
        $form->setHelpButton('remove_crosslist', array('crosslist_remove',
            get_string('crosslist_remove_help', 'block_courseprefs'), 'block_courseprefs', true));

        // Generate a listing of existing cross listing entries to delete
        $crosslists = $user->getCrosslists();
        $display = array();
        $display_secondary = array();

        foreach ($crosslists as $crosslist) {

            $sectionsid = $crosslist->getSectionsId();
            $display[$sectionsid] = CoursePrefsSection::generateFullnameById($sectionsid);

            // Create array for secondary sections if it hasn't been created yet
            if (!$display_secondary[$sectionsid]) {
                $display_secondary[$sectionsid] = array();
            }

            // Associate secondary section with primary section
            $display_secondary[$sectionsid][$crosslist->getId()] = CoursePrefsSection::generateFullnameById($crosslist->getCrSectionsId());
        }

        // Skip the remaining form definition if no cross listing entries were found and display a message to the user
        if (!$display) {
            $form->addElement('static', null, null, get_string('no_cl_deletes', 'block_courseprefs'));
            return;
        }

        // Sort listing of cross listing entries by semesters
        uasort($display, "cmpSemester");

        // Iterate over primary sections of cross listed sections to generate form elements
        foreach ($display as $key => $value) {

            // Sort listing of secondary sections this section is cross listed with
            uasort($display_secondary[$key], "cmpSemester");

            // Iterate over secondary sections and generate necessary form elements
            foreach ($display_secondary[$key] as $id => $value_secondary) {

                // Build information to be substituted in listing
                $a = new stdClass();
                $a->primary_section = $value;
                $a->secondary_section = $value_secondary;

                $form->addElement('checkbox', CROSSLIST_DELETE_GROUP . $id, null,
                    get_string('crosslist_with', 'block_courseprefs', $a));
            }
        }

        $form->addElement('submit', 'delete_submit', get_string('remove', 'block_courseprefs'));
    }

    function validation($data) {

        global $USER;

        $errorlog = array();

        $user = CoursePrefsUser::findByUnique($USER->username);
        $crosslists = $user->getCrosslists();

        // Parse form submission and glean necessary information
        $this->selected_crosslists = array();

        foreach ($data as $key => $value) {

            $matches = array();

            // Add the ID of the cross list entry to the list of selections if the user owns it
            if (preg_match(CROSSLIST_DELETE_PATTERN, $key, $matches) && $crosslists[$matches[1]]) {
                $this->selected_crosslists[] = $matches[1];
            }
        }

        return $errorlog;
    }
}

?>
