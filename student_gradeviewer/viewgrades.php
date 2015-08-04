<?php

require_once('../../config.php');
require_once('lib.php');
require_once($CFG->dirroot . '/blocks/courseprefs/ui/lib.php');
require_once($CFG->libdir . '/grade/grade_item.php');
require_once($CFG->libdir . '/grade/grade_grade.php');
require_once($CFG->libdir . '/gradelib.php');
require_once($CFG->dirroot . '/grade/report/simple_grader/lib/simple_grade_hook.php');

require_login();

// Sports drop down appended to the components
class sports_drop_down extends drop_down {
    function __construct($value) {
        parent::__construct('sports', $value);
    }
 
    function get_options() {
        return array($this->default_value() => $this->default_value()) + pull_sports();
    }

    function where() {
        return "spo.id = '".addslashes($this->value)."'";
    }
}

// Results per page added to the components
class results_drop_down extends drop_down {
    function __construct($value) {
        parent::__construct('per_page', $value);
    }

    function get_options() {
        $options = array(/*0 => get_string('showall'),*/ 10 => 10, 20 => 20,50 => 50, 100 => 100);
        return $options;
    }

    function default_value() {
        return get_string('results_perpage', 'block_student_gradeviewer');
    }

    // This component doesn't add sql to the where clause
    function where_eligible() {
        return false;
    }
}

$context = get_context_instance(CONTEXT_SYSTEM);

$viewsports = has_capability('block/student_gradeviewer:sportsviewgrades', $context);
$viewgrades = has_capability('block/student_gradeviewer:viewgrades', $context);

// The user doesn't have permission to use the app
if (!$viewsports && !$viewgrades) {
    error(get_string('no_permission', 'block_student_gradeviewer'));
}

// We want to make sure that the course preference block is installed
// because we depend on those tables
if (!get_field('block', 'id', 'name', 'courseprefs')) {
    error(get_string('install_cps', 'block_student_gradeviewer'));
}

$blockname = get_string('blockname', 'block_student_gradeviewer');
$heading_main = get_string('view_grades', 'block_student_gradeviewer');
$navigation = array(
              array('name' => $blockname, 'link' => '', 'type' => 'title'),
              array('name' => $heading_main, 'link' => '', 'type' => 'title')
              );

// Some javascript
require_js($CFG->wwwroot . '/blocks/student_gradeviewer/functions.js');

// Start of the form output
print_header_simple($heading_main, '', build_navigation($navigation));

// An empty prequery and no admin means that they have no association
list($table, $prequery) = get_pre_query($USER->id);

if(empty($prequery) && 
   !has_capability('block/student_gradeviewer:academicadmin', $context) &&
   !has_capability('block/student_gradeviewer:sportsadmin', $context)) {
    echo '<div class="results">'.get_string('no_mentees', 'block_student_gradeviewer').'<div>';
    print_footer();
    die();
}

// Used for paging the results
$per_page = optional_param('per_page', 10, PARAM_INT);
$page = optional_param('page', 0, PARAM_INT);

$components = cps_user_component::build_components();

if($viewsports) {
    $components->components[] = new sports_drop_down(cps_transform_filter('sports'));
}

// Per page selector
$components->components[] = new results_drop_down($per_page);

$components->display_as_table($heading_main);

$where = $components->where_clause();

// Do a prequery to figure out if the table doesn't just print out
$threshold = get_config('', 'block_student_gradeviewer_threshold');

//Running a count on the pre query info
if (!empty($prequery) && empty($where)) {
    $sql = "SELECT COUNT(u.id) FROM {$CFG->prefix}block_courseprefs_users u {$table} WHERE ". $prequery;
    
    $count = count_records_sql($sql);
} else {
    $count = $threshold;
}

// Print table if they submitted data
if(!empty($where) || ($count < $threshold)) {
    // build table
    build_table($components, $where, $page, $per_page);
}

// Tag the inputs with our special javascript
echo '<script type="text/javascript">tag_inputs();</script>';

print_footer();

function build_table($components, $where, $page, $per_page) {
    global $CFG, $USER;
 
    $defaults = $components->as_dict();

    list($table, $prequery) = get_pre_query($USER->id);
    // if they have the ability to view a particular sport then
    // we have to do some joins in our lookup
    $viewsports = (isset($where['sports']) or !empty($table));

    if($viewsports) {
        $extra_values = ", spo.code AS sports, spo.name ";
        $sports_tables = ", {$CFG->prefix}block_courseprefs_sports spo,
                     {$CFG->prefix}block_courseprefs_sportusers spu ";
        $sports_where = " u.id = spu.usersid 
                          AND spu.sportsid = spo.id
                          AND ";
    } else {
        $extra_values = '';
        $sports_tables = '';
        $sports_where = ''; 
    }

    $count_sql = "SELECT COUNT(u.id) FROM {$CFG->prefix}block_courseprefs_users u ";
    $sql = "SELECT u.* $extra_values
              FROM {$CFG->prefix}block_courseprefs_users u ";
    // Do a prequery for those who have student gradeviewer assignments
    if($prequery) {
        $where[] = $prequery;
    }

    $limit = $offset = '';
    if(!empty($per_page)) {
        $limit = " LIMIT " . $per_page;
        $offset = " OFFSET " . ($per_page * $page);
    }

    $where_sql = "WHERE " . $sports_where . implode(' AND ', $where);

    // Get our users, hopefully!
    $users = get_records_sql($sql . $sports_tables . $where_sql . $limit . $offset);
    $count = count_records_sql($count_sql . $sports_tables . $where_sql);

    if(!$viewsports) {
        array_pop($defaults);
    }

    // pop per page selector
    array_pop($defaults);
    
    $defaults['grades'] = get_string("grades");

    $keys = array_keys($defaults);

    if (empty($users)) {
        echo '<div class = "results">'.
             get_string('content_no_results', 'block_courseprefs').'</div>';
    } else {
        // Finding where eligible components to preserve lookup
        $where_eligible_components = array_filter($components->components,
            create_function('$c', '
                return $c->where_eligible();
            ')
        );

        // Building param map from eligible components
        $html_params = implode('&amp;', array_map(create_function('$c', 
            'return "$c->key=$c->value";'), $where_eligible_components)). 
            '&amp;per_page='.$per_page.'&amp;';
    
        // The html to do the paging
        $bar = print_paging_bar($count, $page, $per_page, 'viewgrades.php?'.$html_params, 'page', false, true);

        echo $bar;
        echo '<div class = "results">'. $count. 
             get_string('content_results', 'block_courseprefs').'</div>';
        
        // print the results table
        $table = new stdClass;

        $table->head = $defaults;
        $table->data = array();
        foreach($users as $user) {
            // pull the courses where the user is enrolled as a student
            $course_grades = pull_course_grades($user); 
          
            // Boolean if they have or don't have courses 
            $no_courses = empty($course_grades);
            
            $line = array();
            foreach($keys as $key) {

                if($viewsports && $key == 'sports') {
                    $value = flatten_sport($user);
                } else if($key=='username') {
                    $value = ($no_courses) ? $user->username : '<a href="'.$CFG->wwwroot.
                             '/blocks/student_gradeviewer/mentee.php?id='.
                             $user->id.'">'.$user->username.'</a>';
                } else if($key == 'grades') {
                    $value = ($no_courses) ? 'NA' : implode('<br/>', $course_grades);
                } else {
                    $value = $user->{$key};
                }
                $line[] = $value;
            }
            $table->data[] = $line;
        }
        print_table($table);
        echo $bar;
    }
}

?>
