<?php

require_once($CFG->dirroot . '/blocks/moodleblock.class.php');
require_once($CFG->dirroot . '/blocks/courseprefs/hooks/lib.php');
require_once($CFG->dirroot . '/blocks/courseprefs/classes/CoursePrefsUser.php');
require_once($CFG->dirroot . '/blocks/student_gradeviewer/automaticlib.php');
require_once($CFG->dirroot . '/blocks/student_gradeviewer/lib.php');
require_once($CFG->dirroot . '/blocks/student_gradeviewer/report/lib.php');

define('DAY_IN_SECONDS', 86400);

/**
 *
 * author: Philip Cali
 *
 **/
class block_student_gradeviewer extends cps_block_list {
    /**
     * Initialization method of block setting title, version, and number of seconds between cron runs.
     **/
    function init() {
        $this->title = get_string('blockname', 'block_student_gradeviewer');
        $this->version = 2010070912;

        // Run cron once a day
        $this->cron = DAY_IN_SECONDS;
    }

    // The block configurable
    function has_config() {
        return true;
    }

    /**
     * Limits where the block can be added.
     **/
    function applicable_formats() {
        return array('site' => true, 'my' => true, 'course' => false);
    }

    /**
     * Content generation method of block; list of page links
     **/
    function get_content() {

        if ($this->content !== NULL) {
            return $this->content;
        }

        global $CFG, $USER;

        $this->content = new stdClass;
        
        $this->content->icons = array();
        $this->content->footer = '';
        $this->content->items = array();
     
        $context = get_context_instance(CONTEXT_SYSTEM);

        $mentor = has_capability('block/student_gradeviewer:viewgrades', $context) ||
                  has_capability('block/student_gradeviewer:sportsviewgrades', $context);

        $admin  = has_capability('block/student_gradeviewer:academicadmin', $context) ||
                  has_capability('block/student_gradeviewer:sportsadmin', $context);
        // If they can't view the grades, then they can't query
        if($mentor) { 
            $this->content->items[] = '<a href="' . $CFG->wwwroot  . '/blocks/student_gradeviewer/viewgrades.php">' .
                get_string('view_grades', 'block_student_gradeviewer')  . '</a>';
        }

        // This is solely for the admins of the systems, or the Moodle admins        
        if($admin) {
            $admin = get_string('admin', 'block_student_gradeviewer');
            $this->content->items[] = '<a href="'.$CFG->wwwroot.'/blocks/student_gradeviewer/admin.php">' .
            $admin . '</a>' . helpbutton('admin', $admin, 'block_student_gradeviewer', true, false, '', true);
        }

        if($mentor || $admin) {
            $this->content->items[] = '<a href="'.$CFG->wwwroot.
                                  '/blocks/student_gradeviewer/analysis.php">
                                  '.get_string('analysis', 'block_student_gradeviewer').'</a>';
        }

        // Primary instructors can configure their digest options
        $cps_user = CoursePrefsUser::findByUnique($USER->username);
        if(!empty($CFG->cas_email) 
           and $cps_user 
           and $cps_user->getSectionsInfoAsTeacher()) {
            $this->content->items[] = '<a href="'.$CFG->wwwroot.
                                      '/blocks/student_gradeviewer/options.php">'.
                                     get_string('options', 'block_student_gradeviewer').'</a>';
        }
        return $this->content;
    }

    // This cron runs every day
    function cron() {
        global $CFG;

        // If the system is disabled, we die right here
        if(empty($CFG->block_student_gradeviewer_enable)) {
            mtrace(get_string('auto_not_enabled', 'block_student_gradeviewer'));
            return true;
        }

        // Get the current time
        $now = time();

        $start_time = explode(" ", microtime());
        $start_time = $start_time[1] + $start_time[0];

        // Get the current semester or semesters
        $current_semester = current_semester($now);

        // There is no semester to work with
        if(!$current_semester) {
            mtrace(get_string('auto_no_semester', 'block_student_gradeviewer'));
            return true;
        }

        // There may be an instance where we're in two semesters
        foreach($current_semester as $semester) {

            // Is the semester in reporting session?
            // These are admin configurable days
            $in_reporting_session = is_within_timespan($now, $semester);

            // Get valid sections; Valid sections are sections that have been created
            // with a valid idnumber and visiblility is set to 1
            $sections = get_valid_sections($semester);
            if(!$sections) {
                continue;
            }
            
            $processed = 0; 
            foreach($sections as $section) {
                $processed += 1;
                if(($processed % 100) == 0) {
                    mtrace("Processing $processed sections");
                }

                // Determine if the student is doing poorly
                $lagging_algorithm = new lagging_algorithm($section);
                $failing_algorithm = new failing_algorithm($section);
            
                // Skip section, if either algorithm hasn't loaded
                if(!$lagging_algorithm->loaded) {
                    continue;
                }
                
                // Get all the students for the sections
                $students = get_students($section);

                if(!$students) {
                    continue;
                }

                $primary = get_primary($section);

                foreach($students as $student) {
                    $lagging = $lagging_algorithm->process($student);
                    $failing = $failing_algorithm->process($student);

                    // Source is automatic unless otherwise defined
                    $source = AUTOMATIC_REFERRAL_SOURCE;

                    // Surprise! the student is actually doing above the rest
                    if($lagging == EXCEPTIONAL and !$failing) {
                        // Source is praise
                        $source = POSITIVE_REFERRAL_SOURCE;
                        $message = get_string('auto_praise', 'block_student_gradeviewer');
                    } else if($failing and ($lagging == LAGGING)) {
                        $message = get_string('auto_failing_lagging', 
                                              'block_student_gradeviewer');
                    } else if($failing) {
                        $message = get_string('auto_failing', 'block_student_gradeviewer');
                    } else if($lagging == LAGGING) {
                        $message = get_string('auto_lagging', 'block_student_gradeviewer');
                    }

                    // log this student referral
                    if(!empty($message)) {
                        $referral = new stdClass;
                        $referral->usersid = $student->id;
                        $referral->sectionsid = $section->id;
                        $referral->referrerid = $primary->id;
                        $referral->reason = $message;
                        $referral->date_referred = $now;
                        $referral->semestersid = $semester->id;
                        $referral->source = $source;

                        insert_record('block_student_referrals', $referral);
                    }
                    
                    // reset our message variable
                    $message = "";
                }

            }
    
            // Finished processing all the sections in the semester
            mtrace("Finished processing $processed sections in ". 
                    get_string('format_semester', 'block_student_gradeviewer', $semester));


            // After we finish processing the primary, we're done with that teacher
            // we can now submit the first of the email digest, if they want
            if(!empty($CFG->cas_email)) {
                $instructors = get_moodle_instructors($semester);

                foreach($instructors as $instructor) {
                    $digestable_sections = get_digestable_sections($instructor, $semester);
                    // If the instructor has disabled reporting for himself, then don't worry about
                    // even trying to report
                    if(empty($digestable_sections)) {
                        continue;
                    }
                    report_to($instructor, $now, $semester, 
                           'with_instructor', 'format_referrals', $digestable_sections);
                }
            }
 
            // We have finished reporting for primaries, now we report to subsystems
            // We report by enabled reporting in the admin screens
            foreach(array('cas', 'acsa') as $subsystem) {
                if(!empty($CFG->{$subsystem.'_email'})) {
                    $mentors = get_mentors($subsystem);

                    foreach($mentors as $mentor) {
                        report_to($mentor, $now, $semester, 
                                     'with_mentor', 
                                     'format_referrals', $subsystem);
                    }

                    // Get separate referred students
                    $student_function = "get_{$subsystem}_students";
                    $students = $student_function($now);
                    
                    foreach($students as $student) {
                        $digestable_sections = get_all_student_sections($student, $semester);
                        // If they do not have any digestable sections, then skip
                        if(empty($digestable_sections)) {
                            continue;
                        }

                        $referrals = with_student($student, $now, $digestable_sections);
                        foreach($referrals as $referral) {
                            if($in_reporting_session or 
                               !student_was_referred($student, $referral->sectionsid)) {
                                // If in session or the student has never been referrred before
                                notify_student($student, $referral);
                            }
                        }

                        // If student feedback is disabled, then don't worry about feedback
                        if(empty($CFG->block_student_gradeviewer_student_feedback)) {
                            continue;
                        }

                        $yesterday_referrals = with_student($student, $now - DAY_IN_SECONDS, 
                                                            $digestable_sections, 4);

                        $no_longer_referred = no_longer_referred($yesterday_referrals, 
                                                                 $referrals);

                        // They are no longer referred in these sections
                        foreach($no_longer_referred as $referral) {
                            $recovered = true;
                            notify_student($student, $referral, $recovered);
                        }
                    }

                    // The above work does count for students who were referred a previous
                    // run, but no longer referred. We want these too.
                    if(!empty($CFG->block_student_gradeviewer_student_feedback)) {
                        $yesterdays_students = $student_function($now - DAY_IN_SECONDS, 4);

                        // Gets all students who were referred yesterday and no longer, today
                        $students_no_longer_referred = no_longer_referred($yesterdays_students,
                                                                          $students,
                                                                          'student_comp');
                        foreach($students_no_longer_referred as $student) {
                            // Still adhere to notifications
                            $digestable_sections = get_all_student_sections($student, 
                                                                            $semester);
                            if(empty($digestable_sections)) {
                                continue;
                            }

                            // Get yesterdays referral for this student
                            $yesterdays_referrals = with_student($student, 
                                                                 $now - DAY_IN_SECONDS, 
                                                                 $digestable_sections, 4);

                            foreach($yesterdays_referrals as $referral) {
                                $recovered = true;
                                notify_student($student, $referral, $recovered);
                            }
                        }
                    }

                    $admins = get_early_warning_admins($subsystem);

                    report_to($admins, $now, $semester,
                            "with_{$subsystem}_admin",
                            "format_referrals");
                }
            }
        }

        $end_time = explode(" ", microtime());
        $end_time = $end_time[1] + $end_time[0];

        $a->time = $end_time - $start_time;
        mtrace(get_string('auto_elapsed', 'block_student_gradeviewer', $a));

        return true;
        // Finished the automatic referral process
    }
} 
?>
