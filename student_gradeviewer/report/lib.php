<?php

require_once($CFG->dirroot. '/blocks/student_gradeviewer/lib.php');
require_once($CFG->libdir . '/uploadlib.php');

/**
 * The Reporting form is used to send reports to mentors
 * example use case to send a report to all the mentors of students
 * in a particular moodle course:
 * ex: 
 * $message = new stdClass;
 * $message->report = "Anything extra you'd like to say";
 * $message->anonymous = true // boolean whether or not to send anonymously
 * $report = new report_form($course->id);
 * $report->users = get_all_students($course);
 * //$message can be null
 * $report->process($message);
 * //$report->process();
 */
class report_form {
    var $users;
    var $courseid;
    var $submit;

    var $reason;
    var $usehtmleditor;
    var $defaultformat;

    // Constructor can take in post data for user selection or a courseid
    // or nothing which means the developer will configure its setup.
    function __construct($courseid = null, $submitted_data = null) {
        // Not tied to a course
        if($courseid) $this->courseid = $courseid;

        // Strip users from that data, if available
        if($submitted_data) {
            $ids = $this->get_user_data($submitted_data);
            $users = ($ids) ? get_records_select('block_courseprefs_users',
                     'id IN ('.implode(',', $ids).')') : array();
            $this->users =  $this->get_section_users($submitted_data) +
                            $users;
        }

        // Only here if the developers want a customizeed email.
        if($this->usehtmleditor = can_use_richtext_editor()) {
            $this->defaultformat = FORMAT_HTML;
        } else {
            $this->defaultformat = FORMAT_MOODLE;
        }
    
        // Users coming here one way or the other.
        $this->submit = (optional_param('form_submit')) ? true : false;

        if($this->submitted()) $this->setup_referral($submitted_data);
    }

    // Initiate the referral reason/timestamp
    function setup_referral($data) {
        global $CFG, $USER;

        $source = (isset($data->source)) ? $data->source : 1;

        $report = (isset($data->report) and !empty($data->report)) ? $data->report : 
                  (($source == 3) ? get_string('praised', 'block_student_gradeviewer') : 
                                   get_string('concerned', 'block_student_gradeviewer'));

        $this->reason = new stdClass;
        // A reason can't be null, thus a manual one is auto filled in
        $this->reason->body = $report;
        $this->reason->time = time();
        $this->reason->referrer = (isset($data->anonymous)) ? 1 : $USER->id;
        $this->reason->source = $source;

        $this->user = ($this->referrer != 1) ? get_record('user', 'id', $this->referrer) : 
                      get_string('noreplyname');
    }

    function process_referrals($reason=null) {
        if($reason and !$this->reason) $this->reason = $reason;
        if(!$reason and !$this->reason) error(get_string('no_reason', 
                                                         'block_student_gradeviewer'));
        global $CFG;

        $course = get_record('course', 'id', $this->courseid);

        mtrace('<h2>'.get_string('processing', 'block_student_gradeviewer').'</h2>');
        mtrace('<ul class="student_list">');
        // For every user selected, log referral, and send email
        foreach($this->users as $user) {
            $section = lookup_section($user, $course);

            $referral = new stdClass;
            $referral->usersid = $user->id;
            $referral->sectionsid = $section->id;
            $referral->referrerid = $this->reason->referrer;
            $referral->reason = $this->reason->body;
            $referral->date_referred = $this->reason->time;
            $referral->semestersid = $section->semestersid;
            $referral->source = $this->reason->source;

            $a->fullname = fullname($user);
            $a->course_name = $course->fullname;
            $a->grade_link = $CFG->wwwroot . '/grade/report/user/index.php?id='.
                             $this->courseid.'&amp;userid='.$user->moodleid;
            mtrace('<li>'.get_string('student_referral', 
                                     'block_student_gradeviewer', $a));

            if(!insert_record('block_student_referrals', $referral)) {
                mtrace(get_string('no_insert', 'block_student_gradeviewer'));
            }

            mtrace('...');

            $this->notify_student($user, $a, $this->reason);

            mtrace('</li>');
        }
        mtrace('</ul>');

        return true;
    }

    function map_mentor_names($mentor) {
        return fullname($mentor);
    }

    function map_mentor_email($mentor) {
        return $mentor->email;
    }

    function format_mentors($mentors, $function='names') {
        $count_mentors = count($mentors);

        if($count_mentors == 1) {
            return $this->{'map_mentor_'.$function}(current($mentors));
        } else if($count_mentors == 2) {
            return implode(' and ', array_map(
                                   array($this, 'map_mentor_'. $function), $mentors));
            
        } else if($count_mentors >= 3) {
            $tran = array_map(array($this, 'map_mentor_'. $function), $mentors);
            $mentor = end($tran);
            $mentor = 'and '. $mentor;
            $tran[end(array_keys($tran))] = $mentor;
            return implode(', ', $tran);
        }
        return '';
    }

    function notify_student($user, $a, $referral=null) {
        if(!$referral) $referral = $this->reason;

        // Is this a praise?
        if($referral->source == 3) {
            $suffix = '_praise';
        } else if($referral->source == 4) {
            $suffix = '_recovered';
        } else {
            $suffix = '';
        }


        // If the student is an athlete
        $athletic = '';
        $athlete = is_student_athlete($user);
        if($athlete) {
            $athletic = 'athletic_';
            $mentors = lookup_athletic_mentors($user);
            $a->mentor = $this->format_mentors($mentors, 'names');
            $a->mentor_email = $this->format_mentors($mentors, 'email');
        }
      
        $a->sender = fullname($this->user);
 
        $moodle_user = get_record('user', 'id', $user->moodleid);

        $subject = get_string($athletic . 'referral_subject'. $suffix, 
                              'block_student_gradeviewer', $a);
        $body = get_string($athletic. 'referral'. $suffix, 
                              'block_student_gradeviewer', $a);

        $code = email_to_user($moodle_user, $this->user, $subject, $body);
        $code = ($code) ? 'success' : 'fail';

        //mtrace(get_string($code, 'block_student_gradeviewer'));
    }

    function print_heading($heading = null) {
        if(!$heading) $heading = get_string('report', 'block_student_gradeviewer');
        print_heading_with_help($heading, 'report', 'block_student_gradeviewer');
    }

    // Getter for if the POST is can the report form or something else.
    // Only usefull if making a report interface, not a cron job
    function submitted() {
        return $this->submit;
    }
    
    // Convenience method for printing out all the users selected for
    // reporting
    function print_selected_users() {
        echo '
            <h2 class="section_header">'.get_string('selected', 
                                                    'block_student_gradeviewer').'</h2>
            <ul class="student_list">
               '.array_reduce($this->users, array($this, 'reduce_user')).'
            </ul>';
    }

    // Prints out the form which contains a extra report details, an attachment, and 
    // an option whether or not to send this report anonymously to the mentors.
    function print_form() {
        global $CFG;

        // If the report form has a course. then we'll allow attachments.
        $special_inputs = '';
        if($this->courseid) {
            $course = get_record('course', 'id', $this->courseid);
            $special_inputs = '<input type="hidden" name="id" value="'.$this->courseid.'">
                               <input type="hidden" name="MAX_FILE_SIZE" value="'.
                            get_max_upload_file_size($CFG->maxbytes, $course->maxbytes).'">
                              '.get_string('attachment', 'block_student_gradeviewer').'
                               <input type="file" name="attach" size="45"><br/>';
        }

        echo '<form enctype="multipart/form-data" method="POST">
                <div class="report_wrap">
                   '.print_box_start('generalbox', true).'
                    <div class="report_text" style="width: 65%; margin: auto auto;">
                    <span>'.get_string('body', 'block_student_gradeviewer').'</span><br/>
                    '.print_textarea($this->usehtmleditor, 20, 90, null, null, 
                                     'report', '', $this->courseid, true).'
                        <br/>
                        <input type="hidden" name="form_submit" value="1">
                        '.array_reduce($this->users, array($this, 'reduce_ids')).
                        get_string('kudo', 'block_student_gradeviewer').
                        print_checkbox('source', 3, false, '', '', '', true). '<br/>'.
                        $special_inputs . 
                        get_string('anonymous', 'block_student_gradeviewer') . 
                         print_checkbox('anonymous', 1, false, '', '', '', true).'<br/>
                        <input type="submit" value="'.get_string('report', 
                                                             'block_student_gradeviewer').'">
                    </div>
                    '.print_box_end(true).'
                </div>
              </form>';

        if($this->usehtmleditor) {
            use_html_editor('report');
        }

    }

    // The bulk of the reporting process: with message data send an email formatted
    // like so to the mentors:
    // Subject: DEPT 1001 - 001 Report for Mentee
    // Body: $data->body + 
    //       Grades Overview for Mentee: (link to mentee report)
    // Attachment if any
    // reply to will be teacher unless anonymous is true
    function process($data) {
        global $CFG, $USER;

        $this->process_referrals();

        // Process Attachment
        $course = get_record('course', 'id', $this->courseid);

        list($attachmentname, $attachment) = $this->process_attachment($course);

        // Outputing info as mtrace in case developers want to report
        // as Moodle cron
        mtrace('<div class="report_wrap">
                <h2 class="">'.get_string('sending', 
                                          'block_student_gradeviewer').'</h2>
                <ul class="student_list">');

        // Set the from here; If the teacher sends anonymously,
        // then send with a no reply
        $from = (isset($data->anonymous)) ? get_string('noreplyname') : $USER;
        // Foreach user, send report to mentor
        foreach ($this->users as $id => $user) {
            // Find the section the user is enrolled in
            $section = lookup_section($user, $course);
            // Format section: DEPT 1001 - 001
            $a->course = get_string('format_section', 'block_courseprefs', $section);
            // Format User's fullname: Firstname Lastname
            $a->user = fullname($user);

            // DEPT 1001 - 001 Report For Firstname Lastname
            $subject = get_string('subject_report', 'block_student_gradeviewer', $a);
            // Mentee link in student_grade viewer
            $grade_link = $CFG->wwwroot . 
                          '/blocks/student_gradeviewer/mentee.php?id='.$id.
                          (($this->courseid) ? '&amp;courseid='.$this->courseid : '');
            // Grades Overview for Firstname Lastname:
            $mentee = get_string('grade_link', 'block_student_gradeviewer',$a);
            
            $body = $data->report;

            mtrace('<li><h2>'.$a->user.'</h2>
                    <ul class="student_list">');

            // Get this user's mentors to email, if any at all
            $mentors = lookup_mentors($user);
            if(!$mentors) {
                mtrace('<li>' . get_string('no_mentor', 
                       'block_student_gradeviewer', $a). '</li></ul></li>');
                continue;
            }

            // If any mentors email them what the teacher said
            foreach($mentors as $mentor) {
                $code = 'success';
                mtrace('<li>' . get_string('mentor', 
                                         'block_student_gradeviewer', $mentor));

                $result = email_to_user($mentor, $from, $subject, 
                              format_text_email($body . "\n" . $mentee . $grade_link, 1),
                              format_text($body . "<br/>" . $mentee .
                              '<a href="'.$grade_link.'">'.$grade_link.'</a>', 1),
                              $attachment, $attachmentname);
                // Email failed
                if(!$result) $code = 'fail';
                mtrace(get_string($code, 'block_student_gradeviewer') . '</li>');
            }
            mtrace('</ul></li>');
        }

        mtrace('  </ul>');
        if($this->courseid) print_continue($CFG->wwwroot . '/course/view.php?id='. 
                                           $this->courseid);
        mtrace('</div>');
    }

    // Process upload and return attachment tuple (attachment name, attachment loc)
    function process_attachment($course = null) {
        if(!$course) $course = get_record('course', 'id', $this->courseid);
        $rtn = array();

        $um = new upload_manager('attach', false, true, $course, false, 0, true);
        if($um->process_file_uploads('temp/block_student_gradeviewer')) {
            $rtn[] = $um->get_new_filename(); 
            $rtn[] = 'temp/block_student_gradeviewer/'.$um->get_new_filename();
        } else {
            $rtn = array('', '');
        }
        return $rtn;
    }

    // Pulls user_id id from POST data and returns a list of ids
    function get_user_data($data) {
        return array_map(array($this, 'transform_user'), 
                    array_filter(
                        array_keys(get_object_vars($data)), array($this, 'filter_user')
                    )
               );
    }

    // Pulls section_id id from POST and flattens all users associated with that section
    function get_section_users($data) {
        $rtn = array_reduce(
                    array_filter(
                        array_keys(get_object_vars($data)), array($this, 'filter_section')
                    ),
               array($this, 'reduce_section')
               );
        return (empty($rtn)) ? array() : $rtn; 
    }

    // Reduce function to build user list
    function reduce_user($in, $user) {
        $inter = (empty($in)) ? '' : $in;
        return $in . '<li>'.fullname($user).'</li>';
    }

    // Reduce function to inject hidden form fields
    function reduce_ids($in, $user) {
        $inter = (empty($in)) ? '' : $in;
        return $in . '<input type="hidden" name="user_'.$user->id.'" value="1">';
    }
    // Reduce function that collects all users from a soecific section
    function reduce_section($in, $section) {
        global $CFG;

        list($section, $id) = explode('_',$section);

        $a->id = $id;
        $rtn = get_students($a);        

        $inter = (empty($in)) ? array() : $in;
        return array_merge($inter, ($rtn) ? $rtn : array());
    }

    // Filter function that finds elements that start with 'section_'
    function filter_section($value) {
        return preg_match('/^section_/', $value);
    }

    // Filter function that finds elements that start with 'user_'
    function filter_user($value) {
        return preg_match('/^user_/', $value);
    }

    // Map function that takes 'user_id' and returns 'id'
    function transform_user($user_id) {
        list($user, $id) = explode('_', $user_id);
        return $id;
    }
}


function print_source($referral) {
    global $CFG;

    switch($referral->source) {
        case 1 : $source = 'manual'; break;
        case 2 : $source = 'automated'; break;
        case 3 : $source = 'kudos'; break;
    }

    $title = get_string($source, 'block_student_gradeviewer', $referral);

    $html = '<img title="'.$title.'" src="'.$CFG->wwwroot.
            '/blocks/student_gradeviewer/images/'. $source .'_referral.gif"/>';

    return $html;
}

/**
 * Restrict access for the reporting tool based on a course.
 * Dependencies are checked here as well.
 */
function has_reporting_permission($courseid) {
    $course = get_record('course', 'id', $courseid);

    // Course exists?
    if(!$course) {
        error(get_string('no_course', 'block_student_gradeviewer'));
    }

    // Permission?
    $context = get_context_instance(CONTEXT_COURSE, $courseid);
    if(!has_capability('moodle/user:viewdetails', $context)) {
        error(get_string('no_permission', 'block_student_gradeviewer'));
    }

    // This block depends on courseprefs and student_gradeviewer
    $ids = get_records_select('block', 
                              "name IN ('courseprefs', 'student_gradeviewer')");
    if(!$ids || count($ids) < 2) {
        error(get_string('dependency', 'block_student_gradeviewer'));
    }
   
    return $course; 
}
?>
