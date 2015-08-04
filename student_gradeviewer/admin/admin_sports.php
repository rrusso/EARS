<?php

require_once('lib.php');

// Admin page for sport mentor assignment
class admin_sports extends admin_page {
    function init() {
        $this->sports = array('NA' => get_string('sports_na', 
                              'block_student_gradeviewer')) + pull_sports();
    }

    function __construct($userid) {
        $this->name = get_string('admin_sports', 'block_student_gradeviewer');
        $this->userid = $userid;
        $this->type = 'sports';

        // Assign the path here
        $this->path = optional_param('path', 'NA');

        $this->capabilities = array('block/student_gradeviewer:sportsadmin');
    }

    function print_header() {
        global $CFG;

        popup_form('admin.php?type='.$this->type.'&amp;path=', $this->sports, 'sport_selector',
                   $this->path, '', '', '', false, 'self',
                   get_string('sports_assign', 'block_student_gradeviewer'));

        if ($this->path != 'NA') {
            echo ' <a href="admin.php?type=name&amp;path='.$this->path.'"><img src="'.
             $CFG->pixpath.'/i/edit.gif"/></a>';
        }
        // Any validation errors should be printed first
        $this->print_errors();
        $this->header_string(get_string('admin_assigning', 
                             'block_student_gradeviewer') . $this->get_sport());
    }

    function get_sport() {
        $sport = $this->sports[$this->path];
        return ($this->path == 'NA') ? '' : ' in <strong>'.$sport.'</strong>';
    }

    function validate_data($data) {
        // Path chosen, doesn't exist in the database
        if(!isset($this->sports[$this->path])) {
            $this->errors['sports'] = get_string('sports_error', 'block_student_gradeviewer');
            return false;
        }
        return true;
    }
}
?>
