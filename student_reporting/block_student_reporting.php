<?php

require_once($CFG->dirroot . '/blocks/student_reporting/lib.php');
require_once($CFG->dirroot . '/blocks/courseprefs/classes/CoursePrefsUser.php');

class block_student_reporting extends block_list {

    function init() {
        $this->title = get_string('blockname', 'block_student_reporting');
        $this->version = 2010042308;
    }

    function applicable_formats() {
        return array('course' =>true);
    }

    function get_content() {
        if($this->content !== NULL) {
            return $this->content;
        }

        global $CFG, $COURSE, $USER;

        $this->content = new stdClass;
        $this->content->icons = array();
        $this->content->items = array();
        $this->content->footer = '';
        
        list($source, $cap) = $this->permissions($COURSE);

        if(!$cap) {
            return $this->content;
        }

        //We're in a global section, so we change our links
        $global = $source == 'system';

        $cps_user = CoursePrefsUser::findByUnique($USER->username);

        // If the cps user is valid, and they are in the teaching role in their course
        $can_report = (($cps_user and
                       $cps_user->getSectionsForMoodleCourse($COURSE))
                       or is_siteadmin($USER->id));

        if(!$global and $can_report) {
            $this->content->icons[] = '<img src="'.$CFG->pixpath.'/i/email.gif"/>';
            $this->content->items[] = '<a href="'.$CFG->wwwroot.
                                      '/blocks/student_reporting/select.php?id='.$COURSE->id.'">'.
                                      get_string('select', 'block_student_reporting').'</a>';
        }

        $this->content->icons[] = '';
        $this->content->items[] = '<a href="'.$CFG->wwwroot.
                                  '/blocks/student_gradeviewer/analysis.php'.(($global) ? 
                                  '' : '?id='.$COURSE->id).'">'.
                                  get_string('analysis', 'block_student_gradeviewer').'</a>';

        if(!empty($CFG->cas_email) 
           and $cps_user 
           and $cps_user->getSectionsInfoAsTeacher()) {
            $this->content->items[] = '<a href="'.$CFG->wwwroot.
                               '/blocks/student_gradeviewer/options.php?id='.$COURSE->id.'">'.
                               get_string('options', 'block_student_gradeviewer').'</a>';
        }

        return $this->content;
    }

    function permissions($course) {
        if($course->id == 1) {
            $context = get_context_instance(CONTEXT_SYSTEM);
            return array('system',
                         has_capability('block/student_gradeviewer:viewgrades', $context) ||
                         has_capability('block/student_gradeviewer:sportsviewgrades', $context));
        } else {
            $context = get_context_instance(CONTEXT_COURSE, $course->id);
            return array('course', 
                        has_capability('moodle/user:viewdetails', $context));
        }
    }
}

?>
