<?php

require_once('lib.php');

// This is the page for assigning mentors of either sports or academincs to students
class admin_person extends admin_page {
    function init() {
        global $CFG;

        // Get user capabilities
        $cap_sql = "SELECT rc.id, rc.capability
                        FROM {$CFG->prefix}role_assignments ra,
                             {$CFG->prefix}role_capabilities rc
                        WHERE ra.userid = {$this->userid}
                          AND ra.roleid = rc.roleid
                          AND rc.capability IN ('".implode("','", $this->user_caps)."')";
        $capabilities = get_records_sql_menu($cap_sql);

        // Get all the internal assignments
        $map = create_function('$field', '
                return "SELECT DISTINCT(u.id), u.firstname, u.lastname
                    FROM '.$CFG->prefix.'user u,
                         '.$CFG->prefix.'block_student_{$field} a,
                         '.$CFG->prefix.'role_assignments ra,
                         '.$CFG->prefix.'role_capabilities rc
                    WHERE u.id = a.usersid
                      AND ra.userid = u.id
                      AND ra.roleid = rc.roleid
                      AND rc.capability IN (\''.implode("','", array_values($capabilities)).'\')
                    GROUP BY u.id";');
        $sql = implode(') UNION (', array_map($map, array('sports', 'academics', 'person')));

        $this->mentors = array_map('flatten_mentors', get_records_sql('('. $sql. ') ORDER BY lastname'));
    }

    function __construct($userid) {
        $this->name = get_string('admin_person', 'block_student_gradeviewer');
        $this->userid = $userid;
        $this->type = 'person';
        $this->debug = get_config('', 'block_student_gradeviewer_debug');
        $this->capabilities = array('block/student_gradeviewer:sportsadmin',
                                    'block/student_gradeviewer:academicadmin');
        $this->user_caps = array('block/student_gradeviewer:viewgrades',
                                 'block/student_gradeviewer:sportsviewgrades');

        $this->path = optional_param('path', 0);
    }

    function print_header() {
        popup_form('admin.php?type='.$this->type.'&amp;path=', $this->mentors, 'mentor_selector',
                   $this->path, 'choose', '', '', false, 'self',
                   get_string('person_assign', 'block_student_gradeviewer'));
        $this->print_errors();
        
        if(isset($this->mentors[$this->path])) {
            $a->person = $this->mentors[$this->path];
        }
        $header = ($this->path == 0) ? get_string('person_select', 
                  'block_student_gradeviewer') : 
                  get_string('person_mentors', 'block_student_gradeviewer', $a);
        
        $this->header_string($header);
    }

    function perform_add($user) {
        // Blindly add the assignment at this point
        $assign = new stdClass;
        $assign->usersid = $this->path;
        $assign->path = $user;

        $this->insert_assignment($assign);
    }

    function perform_remove($user) {
        // Blindly remove the entry at this point
        if($assign = get_record('block_student_person', 'usersid', $this->path,
                                'path', $user)) {
            $this->remove_assignment($assign);
        }
    }

    function get_sql() {
        return "SELECT u.*, mu.email ";
    }

    function get_everyone_sql() {
        global $CFG;
        
        $everyone = "FROM {$CFG->prefix}block_courseprefs_users u,
                          {$CFG->prefix}user mu
                     WHERE mu.id = u.moodleid";

        return $everyone;
    }

    function get_context_user_sql() {
        global $CFG;

        $sql = "FROM {$CFG->prefix}block_courseprefs_users u,
                     {$CFG->prefix}user mu,
                     {$CFG->prefix}block_student_person a
                WHERE mu.id = u.moodleid
                  AND a.path = u.id
                  AND a.usersid = {$this->path} ";

        return $sql;
    }

    function get_exclusive_sql($sub_sql) {
        global $CFG;

        // We don't want any mentors
        $mentor_sql = " AND (u.id NOT IN (SELECT u.id $sub_sql) 
                        AND mu.id NOT IN (SELECT DISTINCT(a.usersid) 
                                         FROM {$CFG->prefix}block_student_person a)
                        AND mu.id NOT IN (".$this->admin_sub_sql()."))";
        return $mentor_sql;
    }

    function validate_data($data) {
        // Make sure that the selected mentor is STILL a mentor
        if($this->path == 0) {
            $this->errors['mentors'] = get_string('person_error_please', 
                                                  'block_student_gradeviewer');
            return false;
        }
        if(!isset($this->mentors[$this->path])) {
            $this->errors['mentors'] = get_string('person_error_exists', 
                                                  'block_student_gradeviewer');
            return false;
        }
        return true;
    }
}

function flatten_mentors($mentor) {
    return fullname($mentor);
}

?>
