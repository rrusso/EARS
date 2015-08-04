<?php

require_once($CFG->libdir . '/formslib.php');
require_once('classes/CoursePrefsCourse.php');
require_once('classes/CoursePrefsUser.php');
require_once('classes/CoursePrefsSection.php');
require_once('lib.php');

define('DELETE_GROUP', 'deletegroup');
define('DELETE_BUTTON', 'deletebutton');

define('DELETE_GROUP_PATTERN', '/^' . DELETE_GROUP . '(\d+)$/');
define('DELETE_BUTTON_PATTERN', '/^' . DELETE_BUTTON . '(\d+)$/');

class teamteach_remove_form extends moodleform {

    public $selected_teamteaches;

    function definition() {

        global $USER;
        $form =& $this->_form;

        $user = CoursePrefsUser::findByUnique($USER->username);

        // Remove team teaching invitations header
        $form->addElement('header', 'remove_teamteach', get_string('header_teamteach_remove', 'block_courseprefs'));    
        $form->setHelpButton('remove_teamteach', array('teamteach_remove',
            get_string('teamteach_remove_help', 'block_courseprefs'), 'block_courseprefs', true));

        // Generate a listing of team teaching invitations
        $teamteaches = $user->getRequestedTeamTeaches();
        $display = array();

        foreach ($teamteaches as $teamteach) {
            $display[$teamteach->getId()] = CoursePrefsSection::generateFullnameById($teamteach->getSectionsId()) .
                ' with ' . CoursePrefsSection::generateFullnameById($teamteach->getTtSectionsId());
        }

        // Skip the remaining form definition if no invitations were found and display a message to the user
        if (!$display) {
            $form->addElement('static', null, null, get_string('no_deletes', 'block_courseprefs'));
            return;
        }

        // Sort listing of invitations by semesters
        uasort($display, "cmpSemester");

        // Add checkbox elements for each team teach invitation the user can remove
        foreach ($display as $key => $teamteach) {
            $form->addElement('checkbox', DELETE_GROUP . $key, null, $teamteach);
        }

        $form->addElement('submit', 'delete_submit', get_string('remove', 'block_courseprefs'));
    }

    function validation($data) {

        global $USER;

        $errorlog = array();

        $user = CoursePrefsUser::findByUnique($USER->username);
        $teamteaches = $user->getRequestedTeamTeaches();

        // Parse form submission and glean necessary information
        $this->selected_teamteaches = array();

        foreach ($data as $key => $value) {

            $matches = array();

            // Add the ID of the team teach entry to the list of selections if the user owns it
            if (preg_match(DELETE_GROUP_PATTERN, $key, $matches) && $teamteaches[$matches[1]]) {
                $this->selected_teamteaches[] = $matches[1];
            }
        }

        return $errorlog;
    }
}

?>
