<?php

/**
 * Author: Adam Zapletal
 * Edited by: Philip Cali
 * Hacked by: Robert Russo
 */

require_once ('../../config.php');

require_login();

//Getting missing or orphened users
$action = optional_param('action', '', PARAM_ACTION);
$semester = optional_param('semester');

if (!is_siteadmin($USER->id)) {
	die('Only admins can access this page');
}

set_time_limit(300);

if (!$semester) {
    $header = get_string('missing_header', 'block_courseprefs');
    $navigation = array(
                    array('name' => get_string('enrolname', 'enrol_cpsenrollment'),
                   'link' => $CFG->wwwroot . '/admin/enrol_config.php?enrol=cpsenrollment',
                   'type' => 'title'),
                    array('name' => $header, 'link' => '', 'type'=>'title'),
                  );
    print_header_simple($header, $header, build_navigation($navigation));
    print_heading($header);
    $semesters = get_records('block_courseprefs_semesters', 'campus', 'LSU');
    echo '<form method="GET" action="missing_users.php">
            <div class="cps_missing_selector">
                <input type="radio" name="action" checked="checked" value="missing"/>Missing Users
                <input type="radio" name="action" value="orphan"/>Orphaned Users
            </div>
            <div class="cps_missing_sections_selector">
                <select name="semester">';
    foreach($semesters as $semester) {
            echo '  <option value="'. $semester->year . $semester->name. '">'.
                   $semester->year.$semester->name . '</option>';
    }
    echo '      </select>
            </div>
            <input type="submit" value="'.get_string('submit').'"/>
          </form>';
    print_footer();
    die();
}

// Get all students in all courses on moodle
$sql = "SELECT DISTINCT concat(usr.idnumber, '|', c.shortname, '|', usr.username)
        FROM {$CFG->prefix}course AS c
            INNER JOIN {$CFG->prefix}context AS cx ON c.id = cx.instanceid AND cx.contextlevel = '50'
            INNER JOIN {$CFG->prefix}role_assignments AS ra ON cx.id = ra.contextid
            INNER JOIN {$CFG->prefix}role AS r ON ra.roleid = r.id
            INNER JOIN {$CFG->prefix}user AS usr ON ra.userid = usr.id
        WHERE (r.name = 'Student')
        AND c.shortname LIKE '{$semester}%'";

$moodle_students = array_keys(get_records_sql($sql));

// Get all students and all courses in courseprefs
$sql = "SELECT DISTINCT concat(cpu.idnumber, '|', cps.idnumber, '|', cpu.username)
        FROM {$CFG->prefix}block_courseprefs_users AS cpu
            INNER JOIN {$CFG->prefix}block_courseprefs_students AS cpstu ON cpstu.usersid = cpu.id
            INNER JOIN {$CFG->prefix}block_courseprefs_sections AS cps ON cps.id = cpstu.sectionsid
        WHERE cps.idnumber IS NOT NULL
        AND cps.status = 'completed'".
        (($action == 'missing') ? " AND cpstu.status = 'enrolled' " : "") . "
        AND cps.idnumber LIKE '{$semester}%'";

$cps_students = array_keys(get_records_sql($sql));

$students = array();

//We explicitly want orphened users
if ($action == 'orphan'){
    $array_master = array_diff($moodle_students, $cps_students);
} else {
    $array_master = array_diff($cps_students, $moodle_students);
}

foreach ($array_master as $m) {
    $students[] = 'del, student, ' . implode(', ', explode('|', $m));
}

header("Content-type: text/plain");
header("Content-Disposition: attachment; filename=missing_students.txt");

echo implode("\n", $students);

?>
