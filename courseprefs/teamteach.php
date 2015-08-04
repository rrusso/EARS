<?php

/**
 * Script rendering and processing form for teachers' preferences on team teaching courses.
 *
 * @author Adam Zapletal and Philip Cali
 */

require_once('../../config.php');
require_once('lib.php');
require_once('teamteach_add_form.php');
require_once('teamteach_accept_form.php');
require_once('teamteach_pending_form.php');
require_once('teamteach_remove_form.php');
require_once('classes/CoursePrefsCourse.php');
require_once('classes/CoursePrefsUser.php');
require_once('classes/CoursePrefsTeamTeach.php');
require_once('classes/CoursePrefsSection.php');

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

$sections = $user->getTeamteachableSections();
if (!$sections) {
    redirect_error(ERROR_TEAMTEACH_MISSING);
}

$add_form = new teamteach_add_form();
$accept_form = new teamteach_accept_form();
$pending_form = new teamteach_pending_form();
$remove_form = new teamteach_remove_form();

$heading = null;

if ($data = $add_form->get_data()) {

    try {

        // Process the validated data for adding a new team teaching entry
        $new_teamteach = new CoursePrefsTeamTeach($user->getId(), $add_form->section_selections, 
            $add_form->secondary_teamteaches, 'todo', 1);
        $new_teamteach->save();
        $heading = get_string('email_sent', 'block_courseprefs');  

        // Send off email informing primary teacher of foreign section of team teaching invitation
        teamteach_email($new_teamteach, $user);

        // Refresh add, pending, and remove forms due to new information
        $add_form = new teamteach_add_form();
        $pending_form = new teamteach_pending_form();
        $remove_form = new teamteach_remove_form();

    } catch (Exception $ex) {            
        $heading = get_string('changes_not_saved', 'block_courseprefs');
        add_to_log(SITEID, 'courseprefs', 'update', 'blocks/courseprefs/teamteach.php',
            'Unable to save teamteach object');
    }

} else if ($data = $accept_form->get_data()) {

    // Prematurely set heading that the form submission was processed and necessary emails were sent
    $heading = get_string('email_sent', 'block_courseprefs');

    // Iterate over selected team teach invitations and process accordingly
    foreach ($accept_form->selected_teamteaches as $id) {

        $teamteach = CoursePrefsTeamTeach::findById($id);

        // Accepted invitations need to have their approval flag updated
        // Rejected invitations will be deleted from the database
        // Requesting teacher should be notified of accepted/rejected invitations
        if ($data->{PENDING_ACCEPT_BUTTON}) {

            try{
                $teamteach->setApprovalFlag(false);
                $teamteach->save();
                accept_reject_email($teamteach, $user, true);
            } catch (Exception $e) {
                $heading = get_string('changes_not_saved', 'block_courseprefs');
                 add_to_log(SITEID, 'courseprefs', 'update', 'blocks/courseprefs/teamteach.php',
                    'Unable to save teamteach object');
            }
            
        } else if ($data->{PENDING_REJECT_BUTTON}) {
            CoursePrefsTeamTeach::deleteById($id);
            accept_reject_email($teamteach, $user, false);
        }
    }

    // Refresh accept and remove forms due to new information
    $accept_form = new teamteach_accept_form();
    $remove_form = new teamteach_remove_form();

} else if ($data = $remove_form->get_data()) {

    // Prematurely set heading that the form submission was processed and necessary emails were sent
    $heading = get_string('email_sent', 'block_courseprefs');
    $teamteaches = array();    

    // Iterate over selected team teach requests and delete them accordingly
    foreach ($remove_form->selected_teamteaches as $id) {
        $teamteaches[$id] = CoursePrefsTeamTeach::findById($id);
        delete_email($teamteaches[$id], $user);
    } 

    reset_prefs($teamteaches);

    // Refresh remove form due to new information
    $remove_form = new teamteach_remove_form();
}

// Render the page headers and forms
$heading_main = get_string('teamteach_heading', 'block_courseprefs');
$navigation = array(
    array('name' => get_string('blockname', 'block_courseprefs'), 'link' => '', 'type' => 'title'),
    array('name' => $heading_main, 'link' => '', 'type' => 'title'),
    );

print_header_simple($heading_main, '', build_navigation($navigation));
print_heading_with_help($heading_main, 'teamteach', 'block_courseprefs');

if ($heading){
    print_heading($heading, 'center', 3);
}

// Determined teachers should see this regardless
if ($user->getCrDelete()) {
    echo '<div class="cps_error">'.get_string('error_preference', 'block_courseprefs').
                                   get_string('error_preference_any', 'block_courseprefs');
    echo '<p class="cps_smallerror">' . get_string('error_preference_sub', 'block_courseprefs') . '</p>';
    echo '</div>';
}

$add_form->display();
$accept_form->display();
$pending_form->display();
$remove_form->display();

print_footer();

/**
 * Send an email with an invitation to the teacher of the section selected for team teaching
 */
function teamteach_email($teamteach, $current_user){

    global $CFG;

    $to_user = $teamteach->getTtUser();
    $to = get_record('user', 'username', $to_user->getUsername());
    $from = get_record('user', 'username', $current_user->getUsername());

    // Build information to be substituted in email
    $a = new stdClass();
    $a->to_name = $to_user->getFirstname() . ' ' . $to_user->getLastname();
    $a->to_section = CoursePrefsSection::generateFullnameById($teamteach->getTtSectionsId());
    $a->from_name = $current_user->getFirstname() . ' '. $current_user->getLastname();
    $a->from_section = CoursePrefsSection::generateFullnameById($teamteach->getSectionsId());
    $a->link = $CFG->wwwroot . '/blocks/courseprefs/teamteach.php';

    // Send invitation email to user with necessary placeholders substituted
    email_to_user($to, $from->email, get_string('invite_email_subject', 'block_courseprefs'),
        get_string('invite_email', 'block_courseprefs', $a));
}

/**
 * Send an email with the user's response to a team teaching invitation to the team teach requester
 */
function accept_reject_email($teamteach, $current_user, $invite_approved = false) {

    $to_user = $teamteach->getSectionsUser();
    $to = get_record('user', 'username', $to_user->getUsername());
    $from = get_record('user', 'username', $current_user->getUsername());

    // Build information to be substituted in email
    $a = new stdClass();
    $a->to_name = $to_user->getFirstname() . ' ' . $to_user->getLastname();
    $a->to_section = CoursePrefsSection::generateFullnameById($teamteach->getSectionsId());
    $a->from_name = $current_user->getFirstname() . ' '. $current_user->getLastname();
    $a->from_section = CoursePrefsSection::generateFullnameById($teamteach->getTtSectionsId());

    // Generate the email subject and message based on either the invitation was approved or rejected
    if ($invite_approved) {
        $subject = get_string('accept_email_subject', 'block_courseprefs');
        $message = get_string('accept_email', 'block_courseprefs', $a);
    } else {
        $subject = get_string('reject_email_subject', 'block_courseprefs');
        $message = get_string('reject_email', 'block_courseprefs', $a);

    }

    // Send acceptance / rejection email to user
    email_to_user($to, $from->email, $subject, $message);
}

/**
 * Send an email announcing that a teacher is revoking his/her team teaching invitation
 */
function delete_email($teamteach, $current_user) {

    $to_user = $teamteach->getTtUser();
    $to = get_record('user', 'username', $to_user->getUsername());
    $from = get_record('user', 'username', $current_user->getUsername());

    // Build information to be substituted in email
    $a = new stdClass();
    $a->to_name = $to_user->getFirstname() . ' ' . $to_user->getLastname();
    $a->to_section = CoursePrefsSection::generateFullnameById($teamteach->getTtSectionsId());
    $a->from_name = $current_user->getFirstname() . ' '. $current_user->getLastname();
    $a->from_section = CoursePrefsSection::generateFullnameById($teamteach->getSectionsId());

    // Send revocation email to user
    email_to_user($to, $from->email, get_string('remove_email_subject', 'block_courseprefs'),
        get_string('remove_email', 'block_courseprefs', $a));
}

?>
