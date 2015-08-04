<?php

require_once($CFG->libdir . '/formslib.php');
require_once('classes/CoursePrefsCourse.php');
require_once('classes/CoursePrefsUser.php');
require_once('classes/CoursePrefsSection.php');
require_once('classes/CoursePrefsTeamTeach.php');
require_once('lib.php');

define('PENDING_CHECKBOX', 'teamteach_decision');
define('PENDING_ACCEPT_BUTTON', 'teamteach_accept');
define('PENDING_REJECT_BUTTON','teamteach_reject');

define('PENDING_CHECKBOX_PATTERN', '/^' . PENDING_CHECKBOX . '(\d+)$/');

class teamteach_accept_form extends moodleform {

    public $selected_teamteaches;

    function definition() {

        global $USER;
        $form =& $this->_form;

        // Lookup necessary information for generating the listing of invitations
        $user = CoursePrefsUser::findByUnique($USER->username);
        $teamteaches = $user->getDecisionTeamTeaches();

        // Pending team teaching invitations header
        $form->addElement('header', 'accept_teamteach', get_string('header_teamteach_accept', 'block_courseprefs'));
        $form->setHelpButton('accept_teamteach', array('teamteach_accept',
            get_string('teamteach_accept_help', 'block_courseprefs'), 'block_courseprefs', true));

        // Generate a listing of team teaching invitations the user needs to accept or reject
        $display = array();

        foreach ($teamteaches as $teamteach) {
            $teamteach_user = $teamteach->getSectionsUser();
            $display[$teamteach->getId()] = CoursePrefsSection::generateFullnameById($teamteach->getSectionsId()) . 
                ' with '. $teamteach_user->getLastname(). ' ' .$teamteach_user->getFirstname(); 
        }

        // Sort listing of invitations by semesters
        uasort($display, "cmpSemester");

        // Skip the remaining form definition if no invitations were found and display a message to the user
        if (!$display) {
            $form->addElement('static', null, null, get_string('no_accepts', 'block_courseprefs'));
            return;
        }

        // List the invitations for the user to accept or reject
        foreach ($display as $key => $teamteach) {
            $form->addElement('checkbox', PENDING_CHECKBOX . $key, null, $teamteach);
        } 

        // Add submit and cancellation buttons
        $row = array();
        $row[] = &$form->createElement('submit', PENDING_ACCEPT_BUTTON, get_string('accept', 'block_courseprefs'));
        $row[] = &$form->createElement('submit', PENDING_REJECT_BUTTON, get_string('reject', 'block_courseprefs'));
        $form->addGroup($row, 'buttonarr', null, '', false);
    }

    function validation($data) {

        global $USER;

        $errorlog = array();

        $user = CoursePrefsUser::findByUnique($USER->username);
        $teamteaches = $user->getDecisionTeamTeaches();

        // Parse form submission and glean necessary information
        $this->selected_teamteaches = array();

        foreach ($data as $key => $value) {

            $matches = array();

            // Add the ID of the team teach entry to the list of selections if the user owns it
            if (preg_match(PENDING_CHECKBOX_PATTERN, $key, $matches) && $teamteaches[$matches[1]]) {
                $this->selected_teamteaches[] = $matches[1];
            }
        }

        return $errorlog;
    }
}

?>
