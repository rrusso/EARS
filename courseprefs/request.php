<?php

require_once('../../config.php');
require_once("classes/CoursePrefsUser.php");
require_once("lib.php");

require_login();

if ($user=CoursePrefsUser::findByUnique($USER->username)) {
    error("You already exist in the system!");
}

$action = optional_param('action', '', PARAM_ACTION);

$strcourseprefs = get_string('blockname', 'block_courseprefs');
$navigation = array(
    array('name' => $strcourseprefs, 'link'=>'', 'type'=>'title'),
    );
print_header_simple($strcourseprefs, '', build_navigation($navigation));


switch ($action) {
    case "success":
        print_heading(get_string('request_email', 'block_courseprefs'));

        $user = new CoursePrefsUser($USER->username, $USER->firstname, 
                    $USER->lastname, '');

        $message = array();

        $message[] = get_string('request_email_message', 'block_courseprefs');
        $message[] = "Fullname: {$user->getFirstname()} {$user->getLastname()}";
        $message[] = "LogonID: {$user->getUsername()}";
        $message[] = "E-mail: {$USER->email}";

        try{
            $user->save();
        } catch (Exception $e) {
            $message[] = get_string('request_error', 'block_courseprefs');    
        }
        
        report_errors($message, __FILE__, "Course Request Service", 
             'Course Request @ ' . date("F j, Y, g:i a"), false);

        redirect($CFG->wwwroot, get_string('request_thank_you', 'block_courseprefs'));
        break;
    default:
        print_heading('Request a Course');
        notice_yesno(get_string('request_warning', 'block_courseprefs'), "request.php?action=success", $CFG->wwwroot);
        break;
}

print_footer();

?>
