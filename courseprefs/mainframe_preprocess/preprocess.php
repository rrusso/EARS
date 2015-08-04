<?php

/**
 * Nightly script that processes student, teacher, and course information downloaded from the
 * mainframe and stores them into tables used in the Moodle Course Preference block.
 *
 * @author Adam C. Zapletal (azaple1)
 * @author Andrew R. Feller (afelle1)
 */

//Changing the settings, because the file can take longer than 30 seconds to run
ini_set('max_execution_time','7200');
ini_set('memory_limit','768M');

require_once('../../../config.php');
require_once($CFG->libdir . '/dmllib.php');
require_once($CFG->dirroot . '/blocks/courseprefs/mainframe_preprocess/lib.php');
require_once($CFG->dirroot . '/blocks/courseprefs/classes/CoursePrefsCourse.php');
require_once($CFG->dirroot . '/blocks/courseprefs/classes/CoursePrefsStudent.php');
require_once($CFG->dirroot . '/blocks/courseprefs/classes/CoursePrefsTeacher.php');
require_once($CFG->dirroot . '/blocks/courseprefs/classes/CoursePrefsSemester.php');
require_once($CFG->dirroot . '/blocks/courseprefs/classes/CoursePrefsSection.php');
require_once($CFG->dirroot . '/blocks/courseprefs/classes/CoursePrefsUser.php');
require_once($CFG->dirroot . '/blocks/courseprefs/classes/CoursePrefsConfig.php');
require_once($CFG->dirroot . '/blocks/courseprefs/classes/CoursePrefsLog.php');
require_once($CFG->dirroot . '/blocks/courseprefs/classes/CoursePrefsSport.php');

// This script is being called via the web, so check the password if there is one.
if (!empty($CFG->cronremotepassword)) {
   $pass = optional_param('password', '', PARAM_RAW);
   if($pass != $CFG->cronremotepassword) {
     // wrong password.
     print_error('cronerrorpassword', 'admin');
       exit;
   }
}

/**
 * Script constants
 *
 * FROM_DESCRIPTION - email addressed notified of any issues during processing
 * INPUT_PATH - path to mainframe data files to process
 */
define('FROM_DESCRIPTION', 'Course Preferences Mainframe Processor');

//Get the file locations from the config table
/*
 * $user_file - fully qualified path to user data file
 * $date_file - fully qualified path to semester data file
 * $course_file - fully qualified path to course/section/instructor data file
 * $enroll_file - fully qualified path to student enrollment data file
 */

$iteration = array('inputpath'  => 'base root path',
                   'userfile'   => 'user filename',
                   'coursefile' => 'course filename',
                   'enrollfile' => 'enrollment filename',
                   'datesfile'  => 'dates filename',
                   'sportsfile' => 'sports filename'
            );

$preprocess = new FilePreprocess();

try {
    $preprocess->setFiles($iteration);
    $preprocess->process(time());
} catch (Exception $e) {
    $preprocess->errorlog[] = $e->getMessage();
}

report_errors($preprocess->errorlog, __FILE__, FROM_DESCRIPTION, 
        'File preprocessor errors');
