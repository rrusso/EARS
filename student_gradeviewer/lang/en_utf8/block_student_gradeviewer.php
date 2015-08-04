<?php //$Id: block_simple_restore.php,v 1.0 2008/04/01 15:52:43 pcali1 Exp $

//Block Strings
$string['blockname'] = 'Student Research System';
$string['view_grades'] = 'Mentees';
$string['admin'] = 'Administration';
$string['options'] = 'Automatic Referral Options';
$string['analysis'] = 'Referral Analysis';
$string['student_gradeviewer:viewgrades'] = 'Allows users to use the Student Grade Viewer';
$string['student_gradeviewer:sportsviewgrades'] = 'Allows users to use the Student Grade Viewer for Sport students';
$string['student_gradeviewer:sportsadmin'] = 'Allows users to add mentors in sports categories';
$string['student_gradeviewer:academicadmin'] = 'Allows users to add mentors in academic categories';

// Viewgrades
$string['results_perpage'] = "Per page ";

// Config page strings
$string['mentee_config'] = 'Mentee Page Configuration';
$string['automatic_config'] = 'Automatic Referral Configuration';
$string['user_threshold'] = 'User Threshold';
$string['debug'] = 'Print performance info';
$string['sports_mentor'] = 'ACSA Mentor'; 
$string['academics_mentor'] = 'CAS Mentor'; 
$string['acsa_admin_mentor'] = 'ACSA Admin'; 
$string['cas_admin_mentor'] = 'CAS Admin'; 
$string['enable_logging'] = 'Enable the system cron job';
$string['default_reporting'] = 'Default CAS Reporting (if enabled)';
$string['lagging_negative_value'] = 'Negative standard deviation grades multiplier';
$string['lagging_positive_value'] = 'Positive standard deviation grades multiplier';
$string['lagging_items'] = 'Multiplier of the deviation for grade items';
$string['days_after'] = 'Days after classes start on $a->time';
$string['days_prior'] = 'Days prior grades are due on $a->time';
$string['cas_email'] = 'Enable CAS Reporting';
$string['acsa_email'] = 'Enable ACSA Reporting';
$string['non_primary_control'] = 'Non-Primary control';
$string['when_to_report'] = 'When to report referrals during ';
$string['student_feedback'] = 'Student feedback (if system and Student reporting enabled)';

// Error Strings
$string['no_mentees'] = 'You have no mentees assigned to you.';
$string['no_permission'] = 'You do not have the correct permissions to view this page. 
                            If you think that you should, please contact our Moodle 
                            administrator.';
$string['install_cps'] = 'This block depends on the Course Preferences block. 
                          Please install the block before continuing.';
$string['dependency'] = 'This block requires that courseprefs and student_gradeviewer
                         is installed.';
$string['bad_user'] = 'This user does not exists in the system.';
$string['no_course'] = 'That course does not exists.';
$string['no_courses'] = 'This user is not enrolled in any courses as a student.';

// Admin strings
$string['admin_person'] = 'Assign Mentees';
$string['admin_sports'] = 'Assign Sport Mentors';
$string['admin_academic'] = 'Assign Course Mentors';

// General Admin page strings
$string['admin_assigning'] = 'Assigning mentors to students';
$string['admin_no_configure'] = 'The admin has not properly configured the role yet.';
$string['admin_mentors'] = 'Mentor Assignment';

// Sports admin page strings
$string['sports_na'] = 'Do Not Assign to Sport';
$string['sports_error'] = 'Selected sport does not exist in the system.';
$string['sports_assign'] = 'Sport to Assign';

// Person admin strings
$string['person_assign'] = 'Assign mentees to';
$string['person_select'] = 'Selector a mentor';
$string['person_mentors'] = 'Assigning mentees to <strong>$a->person</strong>';
$string['person_error_please'] = 'Please choose a mentor first.';
$string['person_error_exists'] = 'Selected mentor does not exists.';

//Academic admin string
$string['academic_current'] = 'Current Assignments';
$string['academic_error'] = '$a->field is not a valid entry for ';
$string['year'] = 'Year to Assign';
$string['college'] = 'College to Assign';
$string['major'] = 'Major to Assign';
$string['year_desc'] = ' enrolled as <strong>Year $a->year</strong> students';
$string['college_desc'] = ' in <strong>$a->college</strong>';
$string['major_desc'] = ' majoring in <strong>$a->major</strong>';
$string['year_desc_text'] = ' enrolled as Year $a->year students';
$string['college_desc_text'] = ' in $a->college';
$string['major_desc_text'] = ' majoring in $a->major';

// Change sports name admin page
$string['admin_name'] = 'Change sports name';
$string['name_code'] = 'Sports code: <strong>$a->code</strong>';
$string['name_select'] = 'All the sports';
$string['name_changing'] = 'Changing the name of the sport <strong>$a->sport</strong>';
$string['name_error'] = 'Invalid entry for <strong>$a->code</strong>: $a->name';
$string['name_save'] = 'Unable to update $a->code';

// Report strings
$string['selected'] = 'Selected Students';
$string['body'] = 'Body of Referral (optional): ';
$string['attachment'] = 'Attachment (optional): ';
$string['anonymous'] = 'Send Anonymously: ';
$string['report'] = 'Submit Referral';
$string['sending'] = 'Sending Referrals';
$string['mentor'] = 'Sending report to <strong>$a->firstname $a->lastname</strong>...';
$string['fail'] = '<span class=\"error\">Failed</span>';
$string['success'] = '<span class=\"success\">Success</span>';
$string['subject_report'] = '$a->course Report for $a->user';
$string['grade_link'] = 'Grades Overview for $a->user: ';
$string['no_mentor'] = 'No mentors found for $a->user';
$string['kudo'] = 'Praise report:';
$string['no_reason'] = 'Referral is invalid without a reason.';
$string['no_insert'] = '<span class=\"error\">Failed to log</span>';
$string['concerned'] = 'Instructor is concerned with student progress.';
$string['praised'] = 'Instructor is pleased with student progress.';
$string['processing'] = 'Processing Referrals';
$string['student_referral'] = 'Processing referral for <strong>$a->fullname</strong>';
$string['referral_subject'] = 'LSU Academic Warning - $a->course_name (High Importance)';
$string['referral_subject_recovered'] = '$a->course_name';
$string['referral_subject_praise'] = '$a->course_name - Congratulations';
$string['athletic_referral_subject'] = 'LSU Academic Warning - $a->course_name (High Importance)';
$string['athletic_referral_subject_praise'] = '$a->course_name - Congratulations';
$string['athletic_referral_subject_recovered'] = '$a->course_name';
$string['referral'] = 'Hi $a->fullname,

You are receiving this email because you have an opportunity to significantly raise your course grade before the semester ends, but you will need to take action now.  Provided is a link to your grade at this point in $a->course_name ($a->grade_link).

It is strongly encouraged that you utilize the following free services provided by the Center for Academic Success to help improve your academic performance. These services have proven very helpful for many LSU students (see http://www.cas.lsu.edu/success-stories), and they can help you also.

Please complete the following on-line:
    1) Go to www.lsu.edu/learn and click LSU Student Entry.
    2) For required group, select Early Academic Referral Student.
    3) Complete the 3 short Learning Style Self Tests.
    4) Complete the workshops on Test Preparation and Time Management.

If you are interested in receiving further assistance to improve your academic performance, you may want to also:
    
    * Visit faculty office hours
    * Attend on-campus workshops scheduled for this semester 
      (see www.cas.lsu.edu/campus-workshops for schedule)
    * Utilize the Create My Plan for Success portion of the CAS web site 
      (see http://www.cas.lsu.edu/my-plan-success)
    * Attend Tutoring
      (see www.cas.lsu.edu/tutoring for locations and times)
    * Attend Supplemental Instruction sessions for select courses
      (see www.cas.lsu.edu/supplemental-instruction for current sessions)
    * Contact your college to inquire about other resources that may be available

Please take advantage of these free resources designed to help you improve your academic performance.

For more information visit the CAS web site: www.cas.lsu.edu.
';
$string['referral_praise'] = 'Hi $a->fullname, 

I am very pleased with your performance thus far in this course.
Provided is a link to your grade at this point in $a->course_name ($a->grade_link).

Please let me know if you might be interested in helping fellow
classmates with their studies.

Keep up the good work,
$a->sender';
$string['referral_recovered'] = 'Hi $a->fullname,

You have recovered from your previous referral.';
$string['athletic_referral'] = 'Dear $a->fullname,
 
You have been recently flagged by the instructor for not making satisfactory progress in $a->course_name. Your current course grades are available at this link: $a->grade_link.
 
It is important for you to know that LSU is committed to your success. Your Cox Communications Academic Center advisor $a->mentor has been notified about your progress, and they will contact you shortly regarding this matter. It is imperative that you to meet with your advisor so that you may receive the necessary assistance and guidance to rectify your status. Contact your advisor immediately at $a->mentor_email or (225) 578-5787. It is also advisable to coordinate a meeting with the course instructor to discuss your status.
 
This approach is important in helping you reach your potential in this course. This email notification is time sensitive so it is important for you to take immediate action. If you have further questions, please direct them to your Cox Communications Academic Center advisor.
 
Thank you';
$string['athletic_referral_praise'] = 'Dear $a->fullname,

Congratulations on your progress in $a->course_name! Your current course grades are available at this link: $a->grade_link.

Your hard work and dedication is evident, and exemplifies the full measure of success of all LSU student-athletes. Keep up the good work and finish strong through the remainder of the semester. If you attain a semester GPA of 3.0 or better you will be added to the \"COX Communications Academic Honor Roll.\" Your ACSA advisor also received this same highlight, and stands ready to provide additional support if it becomes needed. Do not hesitate to contact them for any reason!

The entire Cox Communication Academic Center for Student Athletes Team is very proud if you and your accomplishments. Continue to keep up the good work!

All the best,';
$string['athletic_referral_recovered'] = 'Dear $a->fullname,

You have recovered from your previous referral.';
// Referral Strings
$string['format_semester'] = '$a->year $a->name $a->campus';
$string['firstname'] = 'First name';
$string['lastname'] = 'Surname';
$string['username'] = 'PAWS ID';
$string['idnumber'] = 'LSU ID';
$string['student'] = 'Student';
$string['section'] = 'Section';
$string['referrer'] = 'Referrer';
$string['reason'] = 'Reason';
$string['date_referred'] = 'Date';
$string['semester'] = 'Semester';
$string['primary'] = 'Primary';
$string['non_primary'] = 'Non-Primary';
$string['source'] = 'Source';
$string['failing'] = 'Failing';
$string['lagging'] = 'Lagging Behind';
$string['no_referrals'] = 'No referrals';
$string['referred'] = 'Was referred $a->count';
$string['time'] = ' time';
$string['all_date_format'] = 'm/d/Y (h:i:sa)';
$string['manual'] = '$a->firstname was referred manually.';
$string['automated'] = '$a->firstname was referred automatically.';
$string['kudos'] = '$a->firstname was praised.';
$string['neg'] = 'Negative referrals';
$string['pos'] = 'Positive referrals';
$string['man'] = 'Manual negative referrals';
$string['auto'] = 'Automatic negative referrals';
$string['anon_report'] = 'Anonymous';
$string['semesterchooser'] = 'To get started, select a semester.';
$string['option_saved'] = '<span class=\"success\">$a->section saved with reporting options: $a->options
                      </span>';
$string['option_error'] = '<span class=\"error\">$a->section failed to save options: $a->options
                    </span>';
$string['option_removed'] = '<span class=\"success\">$a->section was removed from the automated 
                      referral system.</span>';

// Automatic Referral strings
$string['auto_no_semester'] = 'No semester in session.';
$string['auto_failing'] = 'Student is failing.';
$string['auto_lagging'] = 'Student is lagging.';
$string['auto_failing_lagging'] = 'Student is failing and lagging.';
$string['auto_praise'] = 'Student is doing really well.';
$string['auto_subject'] = 'Referrals for $a->semester on $a->date';
$string['auto_referred'] = 'Referred students';
$string['auto_negative'] = '$a->negative_count negative';
$string['auto_positive'] = '$a->positive_count positive';
$string['auto_total'] = 'Total referrals: ';
$string['auto_elapsed'] = 'EARS took $a->time seconds to complete.';
$string['auto_not_enabled'] = 'EARS is not enabled.';
$string['auto_options'] = 'Automatic Referral Communication Options';

// Help String
$string['viewgrades_help'] = 'Query Mentees';

// CPS hook strings
$string['cleanup_hook'] = 'Removing all referral logs for $a->campus $a->name $a->year';
?>
