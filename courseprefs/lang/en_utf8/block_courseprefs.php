<?php

/**
 * Language entries for Course Preferences block
 *
 * @author Andrew R. Feller
 * @package courseprefs
 **/

// Strings for block
$string['blockname'] = 'Course Preferences';
$string['cps_error'] = ' Error';

// Strings for capabilities
$string['courseprefs:canconfig'] = 'Course Preferences Settings';
$string['courseprefs:viewdata'] = 'Permission to query CPS user table';

// Strings for block links
$string['creation_link'] = 'Creation and Enrollment';
$string['crosslist_link'] = 'Cross-Listing';
$string['materials_link'] = 'Master Courses';
$string['split_link'] = 'Splitting Courses';
$string['teamteach_link'] = 'Team-Teaching';
$string['unwanted_link'] = 'Unwanted Courses';
$string['settings_link'] = 'Settings...';
$string['block_request_link'] = 'If you do not see a course you are supposed to be teaching click';
$string['home_button'] = 'Home';

// Strings for header elements
$string['default_settings'] = 'Default Settings';
$string['course_settings'] = 'Course-Specific Settings';

// Strings for page headings
$string['creation_heading'] = 'Course Creation and Enrollment: Preferences ';
$string['crosslist_heading'] = 'Cross-Listing Preferences ';
$string['materials_heading'] = 'Master Courses Preferences ';
$string['split_heading'] = 'Splitting Courses Preferences ';
$string['teamteach_heading'] = 'Team-Teaching Preferences ';
$string['unwanted_heading'] = 'Unwanted Courses Preferences ';

//Strings for config page
$string['input_path'] = 'Base input path:';
$string['user_file'] = 'Users file name:';
$string['course_file'] = 'Course file name:';
$string['enrol_file'] = 'Enrollment file name:';
$string['date_file'] = 'Dates file name:';
$string['sports_file'] = 'Sports file name:';
$string['no_permission'] = 'You do not have the correct permission to access this page.';
$string['err_nofile'] = 'Specified file does not exists on the server.';
$string['err_nopath'] = 'Base path does not exists on the server.';
$string['missing_users'] = 'Get missing users';
$string['orphaned_users'] = 'Get orphaned users';
$string['log_level'] = 'Log Level:';
$string['missing_header'] = 'Invalid Students';
$string['enroll_header'] = 'System Enroll Settings';
$string['debug_header'] = 'Debug Settings';
$string['rolemapping_header'] = 'Enrollment Mapping';
$string['perform_cleanup'] = 'Perform CPS cleanup, right now!';
$string['cleanup_header'] = 'Cleanup Services';
$string['perform_old'] = 'Remove all old semesters';
$string['cleanup'] = 'Cleanup';
$string['course_creation_threshold'] = 'Do not auto-create courses with this course number or more';

// Strings for materials preference tab
$string['materials_courses'] = 'Create master courses';
$string['materials_courses_help'] = 'master courses';

// Strings for unwanted preference tab
$string['unwanted_help'] = 'unwanted courses';

// $a->course_department = Department of the course
// $a->course_number = Call number of the course
$string['unwanted_label_fieldset'] = '$a->course_department $a->course_number';
$string['unwanted_label_all'] = 'All sections';

// $a->semester_year = Year of semester
// $a->semester_name = Name of semester
// $a-section_number = Number of section
$string['unwanted_label_section'] = '$a->semester_year $a->semester_name Section $a->section_number';

// Strings for creation and enrollment preferences tab
$string['course_create_days'] = 'Days before classes begin to create courses';
$string['course_enroll_days'] = 'Days before classes begin to enroll students';
$string['creation_enrol_default_help'] = 'default preferences';
$string['creation_enrol_semester_help'] = 'semester preferences';
$string['err_create_enroll_compare'] = 'Course must be created before students are enrolled';
$string['creation_days'] = 'Creation days';
$string['enroll_days'] = 'Enroll days';
$string['format'] = 'Course format';
$string['numberweeks'] = 'Number of topics / weeks';
$string['availability'] = 'Course availability';
$string['delete_option'] = 'Delete my courses option';
$string['delete_choice_0'] = 'Delete courses after semester';
$string['delete_choice_1'] = 'Delete courses when enrollment equal zero';
$string['err_days_invalid'] = '$a->name days must be greater than 0.';

$string['form_marked'] = 'marked to be ';

// Strings for cross-listing preferences tab
$string['crosslist_add_help'] = 'cross-listing courses';
$string['crosslist_primary_section'] = 'Select primary section';
$string['crosslist_remove_help'] = 'removing cross-listing from courses';
$string['crosslist_secondary_section'] = 'Select secondary section';
$string['crosslist_with'] = '$a->primary_section is cross-listed with $a->secondary_section';
$string['no_cl_deletes'] = 'There are no cross-listed courses to delete';
$string['err_invalid_bucket'] = 'Course shell with name <strong>$a->name</strong> was not created, because it had less that <strong>$a->number</strong> section entries.';
$string['err_invalid_section'] = 'Section with id of <strong>$a->sectionsid</strong> does not exist.';
$string['header_crosslist_add'] = 'Cross-list courses';
$string['header_crosslist_remove'] = 'Remove cross-listing from existing courses';
$string['crosslist_select'] = 'Courses capable of being cross-listed:';
$string['crosslist_option_taken'] = ' * Cross-list option taken';
$string['crosslist_selected'] = 'You have selected to cross-list:';
$string['crosslist_course_bank'] = 'Your Sections:';
$string['crosslist_current'] = 'Current course cross-list status:';
$string['crosslist'] = 'cross-listed';

// Strings for splitting preferences tab
$string['split_selected_courses'] = 'Split all courses into individual Moodle courses';
$string['split_default_help'] = 'default preferences';
$string['split_course_help'] = 'course preferences';
$string['please_select'] = 'Select a course to split:';
$string['split_option_taken'] = ' * Split option taken';
$string['split_selected'] = 'You have selected to split:';
$string['split_how_many'] = 'How many separate course shells would you like to have created? ';
$string['form_customize'] = 'Customize name';
$string['split_current'] = 'Current course split status';
$string['form_reset'] = 'Unsplit these courses? ';
$string['form_regroup'] = 'Rearrange sections?';
$string['form_regroup_add'] = 'Rearrange sections, but add $a->html more course shell(s)?';
$string['form_current_num'] = 'There are currently <span class=\"error\">$a->groups separate courses that will be DELETED</span> and  <span class=\"error\">one new course</span> shell will be created in their place.';
$string['form_reset_warning'] = 'You are about to reset $a->name to its original state.';

// Strings for team-teaching preferences tab
$string['teamteach_accept_help'] = 'accepting or reject a team-teaching invitation';
$string['teamteach_add_help'] = 'sending a team-teaching invitation';
$string['teamteach_pending_help'] = 'viewing pending team-teaching invitations';
$string['teamteach_remove_help'] = 'revoking team-teaching invitation';
$string['teamteach_selected_courses'] = 'Select section to team-teach';
$string['teamteach_related_course'] = 'Section to invite';
$string['err_invalid_course_number'] = 'Course number must be a 4-digit number; e.g. 1001';
$string['err_invalid_section_number'] = 'Section number must be a 3-digit number; e.g. 001';
$string['err_missing_department'] = 'Missing department of section';
$string['err_missing_course_number'] = 'Missing course number of section';
$string['err_missing_section_number'] = 'Missing section number of section';
$string['err_not_primary'] = 'Unable to team-teach section as you are not the primary instructor';
$string['err_select_section'] = 'You must select a section';
$string['err_same_teamteach'] = 'Team-Teach already requested';
$string['err_unknown_course'] = 'Unable to find course matching these criteria';
$string['err_unknown_section'] = 'Unable to find section matching these criteria';
$string['email_sent'] = 'An email has been sent to the primary instructor';
$string['no_accepts'] = 'No team-teaching invitations to accept or reject';
$string['no_requests'] = 'No team-teaching invitations pending';
$string['no_deletes'] = 'No team-teach invitations to revoke';
$string['no_instructor'] = 'There is no primary instructor for this course. Please contact the Department regarding this issue';
$string['same_teacher'] = 'Cannot team-teach section as you are its primary instructor; try cross-listing';
$string['awaiting'] = 'Awaiting Response...';
$string['accept'] = 'Accept';
$string['reject'] = 'Reject';
$string['header_teamteach_accept'] = 'Accept/reject team-teaching invitations';
$string['header_teamteach_add'] = 'Send a team-teaching invitation';
$string['header_teamteach_pending'] = 'View pending team-teaching invitations';
$string['header_teamteach_remove'] = 'Revoke team-teaching invitations';

// Strings for team-teaching preference tab emails

// Invite email placeholders:
// $a->to_name = Recepient's fullname
// $a->to_section = Recepient's section name
// $a->from_name = Requester's fullname
// $a->from_section = Requester's section name
// $a->link = Link to page where recepient can accept or reject this invitation
$string['invite_email_subject'] = 'Moodle Team-Teaching Request';
$string['invite_email'] = '
$a->to_name,

$a->from_name has invited you and your students from your $a->to_section course to participate in a team-taught course with his/her $a->from_section course.  If you accept this invitation, you and your students will be added within an hour and you will be made a non-primary instructor.

Please click the following link to accept or reject $a->from_name\'s request: $a->link';

// Reject email placeholders:
// $a->to_name = Recepient's fullname
// $a->to_section = Recepient's section name
// $a->from_name = Requester's fullname
// $a->from_section = Requester's section name
$string['reject_email_subject'] = 'Moodle Team-Teaching Request Rejected';
$string['reject_email'] = '
$a->to_name,

$a->from_name has rejected your invitation to team-teach your $a->to_section course with his/her $a->from_section course.';

// Accept email placeholders:
// $a->to_name = Recepient's fullname
// $a->to_section = Recepient's section name
// $a->from_name = Requester's fullname
// $a->from_section = Requester's section name
$string['accept_email_subject'] = 'Moodle Team-Teaching Request Accepted';
$string['accept_email'] = '
$a->to_name,

$a->from_name has accepted your invitation to team-teach your $a->to_section course with his/her $a->from_section course.  All instructors and students of $a->from_section will be enrolled within your $a->to_section course within an hour.';

$string['cps_reasons'] = 'There are two possible reasons for this error';
$string['split_error'] = '
    <ul>
        <li>You are only teaching one section of any course per semester.</li>
        <li>
            <ul>
                <li><a onclick=\"this.target=\'popup\'; return openpopup(\'/help.php?module=block_courseprefs&file=split.html&forcelang=#restrictions\', \'popup\', \'menubar=0,location=0,scrollbars,resizable,width=500,height=400\', 0);\" href=\"\">
                    <img class=\"iconhelp\" src=\"$CFG->wwwroot/theme/lsu/pix/help.gif\"/></a>
                You can only split courses that contain more than one section.</li>
            </ul>
        </li>
';
$string['cross-listed_error'] = '
    <ul>
        <li>You are only teaching one course this semester.</li>
        <li>
            <ul>
                <li><a onclick=\"this.target=\'popup\'; return openpopup(\'/help.php?module=block_courseprefs&file=split.html&forcelang=#restrictions\', \'popup\', \'menubar=0,location=0,scrollbars,resizable,width=500,height=400\', 0);\" href=\"\">
                    <img class=\"iconhelp\" src=\"$CFG->wwwroot/theme/lsu/pix/help.gif\"/></a>
           You can only cross-list courses when you have more than one course per semester.</li>
            </ul>
        </li>
';
$string['same_error'] = '
        <li>You have already applied another preference to your second course.</li>
        <li>
            <ul>
                <li><a onclick=\"this.target=\'popup\'; return openpopup(\'/help.php?module=block_courseprefs&file=split.html&forcelang=#restrictions\', \'popup\', \'menubar=0,location=0,scrollbars,resizable,width=500,height=400\', 0);\" href=\"\">
                    <img class=\"iconhelp\" src=\"$CFG->wwwroot/theme/lsu/pix/help.gif\"/></a>
                You can not split courses that have another preference applied.</li>
            </ul>
        </li>
    </ul>
';
$string['unwanted_error'] = '
    <ul>
       <li><a onclick=\"this.target=\'popup\'; return openpopup(\'/help.php?module=block_courseprefs&file=unwanted.html&forcelang=\', \'popup\', \'menubar=0,location=0,scrollbars,resizable,width=500,height=400\', 0);\" href=\"\">
               <img class=\"iconhelp\" src=\"$CFG->wwwroot/theme/lsu/pix/help.gif\"/></a>
        
        You are not the primary instructor of any of your courses.</li>
';
$string['teamtaught_error'] = '
    <ul>
       <li><a onclick=\"this.target=\'popup\'; return openpopup(\'/help.php?module=block_courseprefs&file=teamteach.html&forcelang=#restrictions\', \'popup\', \'menubar=0,location=0,scrollbars,resizable,width=500,height=400\', 0);\" href=\"\">
               <img class=\"iconhelp\" src=\"$CFG->wwwroot/theme/lsu/pix/help.gif\"/></a>
        You are not the primary instructor of any of your courses.</li>
';
// Remove email placeholders:
// $a->to_name = Recepient's fullname
// $a->to_section = Recepient's section name
// $a->from_name = Requester's fullname
// $a->from_section = Requester's section name
$string['remove_email_subject'] = 'Moodle Team-Teaching Request Revoked';
$string['remove_email'] = '
$a->to_name,

$a->from_name has revoked the invitation to team-teach your $a->to_section course with his/her $a->from_section course.  All instructors and students from your $a->to_section course will be unenrolled from $a->from_section within an hour.';

// Miscellaneous strings
$string['changes_not_saved'] = 'Changes not saved due to issues';
$string['remove'] = 'Remove';
$string['add_entry'] = 'Add Entry';
$string['add'] = 'Add';
$string['err_negative_number'] = 'You must enter a number greater than or less than 0 here.';


//Request a Course strings
$string['request_email'] = 'An email has been sent.';
$string['request_email_message'] = 'The following user has requested a course to be manually created:';
$string['request_exists'] = 'You already exist in the system!';
$string['request_thank_you'] = 'Thank you for your patience.';
$string['request_warning'] = 'Warning: By clicking \'Yes\' and email will be sent'. 
                    ' to the Administrator, requesting a course. Continue?';
$string['request_error'] = 'CPS was unable to add this user.';
$string['request_request'] = 'Request a Course';

// Errors
$string['error_preference_missing'] = 'You have no courses capable of being $a->preference.';
$string['error_user_missing'] = 'Your PAWS ID was not found within the Course Preferences module';
$string['error_course_none'] = 'You were not listed as teaching any courses within the Course Preferences module';
$string['error_user'] = 'Unknown user-related error';
$string['error_course'] = 'Unknown course-related error';
$string['error_creation'] = 'Unknown creation/enrollment page error';
$string['error_crosslist'] = 'Unknown cross-list page error';
$string['error_materials'] = 'Unknown master page error';
$string['error_split'] = 'Unknown split page error';
$string['error_teamteach'] = 'Unknown team-teaching page error';
$string['error_unwanted'] = 'Unknown unwanted page error';
$string['error_unknown'] = 'Unknown error';
$string['error_preference'] = 'Completing this process will delete ';
$string['error_preference_may'] = 'Completing this process may delete ';
$string['error_preference_any'] = ' any selected shells.';
$string['error_preference_sub'] = 'If you have added materials to this course, please backup and download a copy to your desktop before proceeding.';

$string['content_viewer'] = 'User Data Viewer';
$string['content_no_results'] = 'No results.';
$string['content_results'] = ' user(s) found matching the current required search criteria.';

// User strings
$string['reg_status'] = 'Registration';
$string['degree_candidacy'] = 'Degree';
$string['nr'] = ' N/R';
$string['firstname'] = 'First name';
$string['lastname'] = 'Surname';
$string['username'] = 'PAWS ID';
$string['idnumber'] = 'LSU ID';
$string['hours'] = 'Hours';
$string['year'] = 'Year';
$string['classification'] = 'Major';
$string['college'] = 'College';
$string['cps_people:viewferpa'] = 'View Buckley hold students';
$string['anonymous'] = 'Anonymous Number';
$string['keypadid'] = 'KeypadID';
$string['sports'] = 'Sport';
$string['export_roster'] = 'Export Roster';
$string['not'] = 'Not ';
$string['degree_candidacying'] = 'Graduating';
$string['reg_statusing'] = 'Registred';
$string['reprocess_course'] = 'Reprocess Course';
$string['format_section'] = '$a->department $a->course_number - $a->section_number';
$string['sections'] = 'Sections ';

// Preprocess Strings
$string['cant_drop'] = 'Can not insert a student drop record before enrollment';
$string['no_semester'] = 'Unable to lookup semester entry for courses';
$string['not_configured'] = 'Unable to run script due to the $a not being configured.';
$string['no_file'] = 'Unable to run script due to missing input file: $a';
$string['data_integrity'] = 'Date integrity issues have occurred: Trying to pull $a lines';
$string['malformed'] = 'Malformed line in $a->file: $a->line';
$string['no_user'] = 'User does not exists in CPS: $a';
$string['blocked'] = 'ERROR: The mainframe process is being blocked.';

// Hook strings
$string['hook_install'] = 'Installing CPS hook: $a->name of type $a->type';
$string['hook_delete'] = 'Uninstalling CPS hook: $a->name of type $a->type';
?>
