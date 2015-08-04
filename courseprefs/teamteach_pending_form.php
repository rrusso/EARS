<?php

require_once($CFG->libdir . '/formslib.php');
require_once('classes/CoursePrefsCourse.php');
require_once('classes/CoursePrefsUser.php');
require_once('classes/CoursePrefsSection.php');
require_once('classes/CoursePrefsTeamTeach.php');
require_once('lib.php');

define('PENDING_WAITING', 'teamteach_waiting');

class teamteach_pending_form extends moodleform {

    function definition() {

        global $USER;
        $form =& $this->_form;

        $user = CoursePrefsUser::findByUnique($USER->username);
        $teamteaches = $user->getRequestedTeamTeaches();

        // Pending team teaching invitations header
        $form->addElement('header', 'pending_teamteach', get_string('header_teamteach_pending', 'block_courseprefs'));
        $form->setHelpButton('pending_teamteach', array('teamteach_pending',
            get_string('teamteach_pending_help', 'block_courseprefs'), 'block_courseprefs', true));

        // Generate a listing of team teaching invitation the user has made of other teachers which
        // need to be approved or rejected
        $display = array();

        foreach ($teamteaches as $teamteach) {

            // Skip the team teaching invitation if it doesn't need to be approved
            if (!$teamteach->getApprovalFlag()) {
                continue;
            }

            // Lookup information about the requesting user and user's section
            $teamteach_user = $teamteach->getTtUser();
            $display[$teamteach->getId()] = CoursePrefsSection::generateFullnameById($teamteach->getTtSectionsId()) . 
                ' with ' . $teamteach_user->getLastname() . ' ' . $teamteach_user->getFirstname();
        }

        // Skip the remaining form definition if no invitations were found and display a message to the user
        if (!$display) {
            $form->addElement('static', null, null, get_string('no_requests', 'block_courseprefs'));
            return;
        }

        // Sort listing of invitations by semesters
        uasort($display, "cmpSemester");

        // Add static elements for each team teach invitiation that hasn't been accepted or rejected yet
        foreach ($display as $key => $teamteach) {
            $form->addElement('static', null, null, $teamteach);
        } 
    }
}

?>
