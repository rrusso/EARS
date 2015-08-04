<?php

/**
 * The admin page baseclass has common code
 * that all the sub classes needs
 */
class admin_page {
    var $name;
    var $capabilities;
    var $userid;
    var $type;
    var $path;
    var $errors = array();

    function print_heading() {
        print_heading_with_help($this->get_name(), 'admin_page_'.$this->type,
                                'block_student_gradeviewer');
    }

    // Basically wrap a the header string around a div class
    function header_string($string) {
        echo '<div class="header_string">
                '.$string.'.
              </div>';
    }

    // If there were any errors, print them out around div classes
    function print_errors() {
        if(!empty($this->errors)) {
            foreach($this->errors as $key => $message) {
               echo '<div class="error">
                        '.$message.'
                     </div>'; 
            }
        }
    }

    // Get the name of the admin page
    function get_name() {
        return $this->name;
    }

    // Can the user use this particular admin page
    function can_use() {
        $bool =  array_reduce($this->capabilities, 'student_gradeviewer_with_capability', false);
        return $bool;
    }

    // Get the type of the admin page
    function get_type() {
        return $this->type;
    }

    // Method to add users into an assignment.
    // Override this method if you need some specific behavior.
    function perform_add($userid) {
        global $CFG;

        // Try to add necessary role
        $context = get_context_instance(CONTEXT_SYSTEM);
        $roleid = $this->check_role();

        // If they were able to obtain the correct Moodle role, then 
        // we will assign them the correct filter role
        if(role_assign($roleid, $userid, 0, $context->id)) {
            $assign = new stdClass;
            $assign->type = $this->type;
            $assign->path = $this->path;
            $assign->usersid = $userid;

            $this->insert_assignment($assign);
        }
    }

    // Method to remove users from an assignment
    // Override this method if you need some specific behavior
    function perform_remove($userid) {
        // Try to remove the necessary role
        $context = get_context_instance(CONTEXT_SYSTEM);
        $roleid = $this->check_role();

        // Regardless if the user has the role or not, remove the assignment
        if($assign = get_record('block_student_' . $this->type,
                             'path', $this->path, 'usersid', $userid)) {

            $this->remove_assignment($assign);
        }

        $count = count_records('block_student_' . $this->type, 'usersid', $userid) + 
                 count_records('block_student_person', 'usersid', $userid);
        // If they don't have any mentor assignments, then remove them from that role
        if($count == 0) {
            role_unassign($roleid, $userid, 0, $context->id);
        }
    }

    function insert_assignment($assign) {
        if($assign->id) {
            update_record('block_student_' . $this->type, $assign);
        } else {
            insert_record('block_student_' . $this->type, $assign);
        }
    }

    function remove_assignment($assign) {
        if($assign) {
            delete_records('block_student_' . $this->type, 'id', $assign->id);
        }
    }

    // If there is a role for this admin page, then check that it exists
    function check_role() {
        $roleid = get_config('', 'block_student_gradeviewer_' . $this->type);

        // Make sure the role exists
        if(!get_field('role', 'id', 'id', $roleid)) {
            error(get_string('admin_no_configure', 'block_student_gradeviewer'));
        }

        return $roleid;
    }

    function process_data($data) {
        // Validate data
        if(!$this->validate_data($data)) {
            return;
        }

        $add = optional_param('add', 0, PARAM_BOOL);
        $remove = optional_param('remove', 0, PARAM_BOOL);
        
        if($add and !empty($data->addselect) and confirm_sesskey()) {
            foreach($data->addselect as $adduser) {
                if(!$adduser = clean_param($adduser, PARAM_INT)) {
                    continue;
                }
                $this->perform_add($adduser);
            }
        } else if ($remove and !empty($data->removeselect) and confirm_sesskey()) {
            foreach($data->removeselect as $removeuser) {
                if(!$removeuser = clean_param($removeuser, PARAM_INT)) {
                    continue;
                }
                $this->perform_remove($removeuser);
            }
        }
    }

    function print_form() {
        global $THEME, $CFG;

        // get the search string, if it exists
        $searchtext = optional_param('searchtext', '', PARAM_RAW);
        $previoussearch = optional_param('previoussearch', 0, PARAM_BOOL);
        $showall = optional_param('showall', 0, PARAM_BOOL);

        $strshowall = get_string('showall');
        $strsearchresults = get_string('searchresults');
        
        $previoussearch = ($searchtext != '') or ($previoussearch) ? 1:0;
       
        // Showing all means that we need to clear out the search string 
        if($showall) {
            $searchtext = '';
            $previoussearch = 0;
        }

        $searchtext = trim($searchtext);
        if($searchtext !== '') {
            $LIKE      = sql_ilike();

            $selectsql = " AND (CONCAT(u.firstname, ' ', u.lastname) 
                           $LIKE '%$searchtext%' OR email $LIKE '%$searchtext%') ";
        } else {
            $selectsql = '';
            $previoussearch = 0;
        }

        define('MAX_USERS_PER_PAGE', 5000);
        define('MAX_USERS_TO_LIST_PER_ROLE', 20);
 
        //Find all the users that are assigned this role
        $sql = $this->get_sql();

        $sub_sql = $this->get_context_user_sql();

        $everyone = $this->get_everyone_sql();

        $excsql = $this->get_exclusive_sql($sub_sql);

        // These are the people who are assigned
        $contextusers = get_records_sql($sql . $sub_sql);

        if (!$contextusers) {
            $contextusers = array();
        }

        // These are people who can be potentially assigned
        $availableusers = get_recordset_sql($sql . $everyone . $selectsql . $excsql);
        $usercount = $availableusers->_numOfRows;

        $strsearch = get_string('search');
        print_box_start();
        include('assign.html');
        print_box_end();
    }

    // Internal sql function below that can be overridden for special behavior
    function get_sql() {
        return "SELECT u.* ";
    }

    function get_everyone_sql() {
        global $CFG;
        
        $everyone = "FROM {$CFG->prefix}user u
                     WHERE username != 'guest' ";

        return $everyone;
    }

    function get_context_user_sql() {
        global $CFG;

        $sub_sql = "FROM {$CFG->prefix}user u,
                         {$CFG->prefix}block_student_{$this->type} a
                    WHERE a.usersid = u.id
                      AND a.path = '{$this->path}'";

        return $sub_sql;
    }

    function admin_sub_sql() {
        global $CFG;
        
        $sql_caps = implode("','", $this->capabilities);

        $sql = "SELECT u.id
                    FROM {$CFG->prefix}user u,
                         {$CFG->prefix}role_assignments ra,
                         {$CFG->prefix}role_capabilities rc
                    WHERE u.id = ra.userid
                      AND rc.roleid = ra.roleid
                      AND rc.contextid = ra.contextid
                      AND rc.capability IN ('{$sql_caps}')";
        return $sql;
    }

    function get_exclusive_sql($sub_sql) {
        return " AND u.id NOT IN (".$this->admin_sub_sql().")";
    }

    // Type selector for all admin pages.
    static function type_selector($current, $classes = null, $return = false) {
        $option_classes = (!$classes) ? student_gradeviewer_get_classes() : $classes;
        $options = student_gradeviewer_get_options($classes);
        return popup_form('admin.php?type=', $options, 'type', 
                          $current, '', '', '', $return, 'self',
                          get_string('admin_mentors', 'block_student_gradeviewer'));
    }

}

// Function used as a reduce callback; is the user capable in all
// these roles
function student_gradeviewer_with_capability($in, $cap) {
    return $in || has_capability($cap, get_context_instance(CONTEXT_SYSTEM));
}

function student_gradeviewer_get_classes() {
    // All the files in this directory that have 'admin_' in their name
    $admin_classes = array_filter(scandir(dirname(__FILE__)), 'student_gradeviewer_filter_admin');

    // Instaniate all the classes the user is capable of using
    $classes = array_filter(array_map('student_gradeviewer_class_type', $admin_classes), 
                                      'student_gradeviewer_filter_type');
    // Get an array of all the type from these classes
    $types = array_map('student_gradeviewer_type', $classes);

    // The types become the keys for the classes
    return array_combine($types, $classes);
}

function student_gradeviewer_get_options($classes) {
    $types = array_keys($classes);

    // The types are now keyed up with the name of the page
    $merged = array_combine($types, array_map('student_gradeviewer_string_type', $classes));
    return $merged;
}

// Below are all the utility callbacks used for array_filter and array_map
function student_gradeviewer_filter_admin($file) {
    return preg_match('/^admin_/', $file);
}

function student_gradeviewer_filter_type($class) {
    return $class->can_use();
}

function student_gradeviewer_type($class) {
    return $class->get_type();
}

function student_gradeviewer_string_type($type) {
    return $type->get_name();
}

function student_gradeviewer_class_type($file) {
    global $CFG, $USER;

    $sys_file = $CFG->dirroot . '/blocks/student_gradeviewer/admin/'. $file;
    require_once($sys_file);

    list($class, $php) = explode('.', $file);
    $con = new $class($USER->id);
    return $con;
}
?>
