<?php

ini_set('memory_limit', '2048M');
ini_set('max_execution_time', '14400');

require_once($CFG->dirroot . '/blocks/courseprefs/lib.php');
require_once($CFG->dirroot . '/blocks/moodleblock.class.php');
require_once($CFG->dirroot . '/blocks/courseprefs/classes/CoursePrefsStudent.php');
require_once($CFG->dirroot . '/blocks/courseprefs/classes/CoursePrefsTeacher.php');
require_once($CFG->dirroot . '/blocks/courseprefs/classes/CoursePrefsUser.php');
require_once($CFG->dirroot . '/blocks/courseprefs/classes/CoursePrefsConfig.php');
require_once($CFG->dirroot . '/blocks/courseprefs/classes/CoursePrefsLog.php');

/**
 * Course Preferences allows teachers to manage the creation of their courses and course sections.
 *
 * @Original author Andrew R. Feller
 * Edited by: Philip Cali
 *
 **/
class block_courseprefs extends block_list {
    /**
     * Initialization method of block setting title, version, and number of seconds between cron runs.
     **/
    function init() {
        $this->title = get_string('blockname', 'block_courseprefs');
        $this->version = 2011062600;
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
        
        $user = CoursePrefsUser::findByUnique($USER->username);
        $this->content->icons = array();
        $this->content->footer = '';
        $this->content->items = array();
     
        $context = get_context_instance(CONTEXT_SYSTEM);
        if (has_capability('block/courseprefs:viewdata', $context)) {
            $this->content->items[] = '<a href="'.$CFG->wwwroot . '/blocks/courseprefs/content_viewer.php">'.
                                      get_string('content_viewer', 'block_courseprefs').'</a>';
        }
 
        if (!$user || !$user->isTeacher()) {
            $this->content->items[]= get_string('block_request_link', 'block_courseprefs') . 
                " <a href=\"{$CFG->wwwroot}/blocks/courseprefs/request.php\">here</a> to".
                ' request a course';
            return $this->content;
        }
        
        // Removing Links that might break current courses        
        $this->content->items[]= '<a href="' . $CFG->wwwroot  . '/blocks/courseprefs/creation_enrol.php">' .
            get_string('creation_link', 'block_courseprefs')  . '</a>';
        $this->content->items[]= '<a href="' . $CFG->wwwroot  . '/blocks/courseprefs/split.php">' .
            get_string('split_link', 'block_courseprefs')  . '</a>';
        $this->content->items[]= '<a href="' . $CFG->wwwroot  . '/blocks/courseprefs/crosslist.php">' .
            get_string('crosslist_link', 'block_courseprefs')  . '</a>'; 
        $this->content->items[]= '<a href="' . $CFG->wwwroot  . '/blocks/courseprefs/teamteach.php">' .
            get_string('teamteach_link', 'block_courseprefs')  . '</a>';
        $this->content->items[]= '<a href="' . $CFG->wwwroot  . '/blocks/courseprefs/materials.php">' .
            get_string('materials_link', 'block_courseprefs')  . '</a>';
        $this->content->items[]= '<a href="' . $CFG->wwwroot  . '/blocks/courseprefs/unwanted.php">' .
            get_string('unwanted_link', 'block_courseprefs')  . '</a>';

        return $this->content;
    }

} 
?>
