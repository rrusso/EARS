<?php

/**
 * The nature of this app is to get a what I call a pre query, or 
 * a select that narrows the list of users they are capable of seeing
 * based on internal role assignments
 */ 
function get_pre_query($usersid) {
    global $CFG;

    $context = get_context_instance(CONTEXT_SYSTEM);
    $academic_admin = has_capability('block/student_gradeviewer:academicadmin', $context);
    $sports_admin = has_capability('block/student_gradeviewer:sportsadmin', $context);
    //$sports_viewer = has_capability('block/student_gradeviewer:sportsviewgrades', $context);

    $table = ($sports_admin) ? ",{$CFG->prefix}block_courseprefs_sportusers spu" : '';
    // If the user has the admin of academics, then he can see everyone
    // other wise we make a check to see if the user is a sports admin
    if($academic_admin) {
        return array('', '');
    } else if($sports_admin) {
        // Sports admins see all the sport users
        return array($table, " u.id = spu.usersid");
    }

    $rtn_sql = array();
    // Now that admins are out of the way, we look at individual assignments
    // People assignments happen last
    $fields = array('academics', 'sports', 'person');
    foreach ($fields as $field) {
        $sql = "SELECT id, path FROM {$CFG->prefix}block_student_{$field}
                            WHERE (path != 'NA' OR path != 0)
                              AND usersid = {$usersid}";
        $assignments = get_records_sql_menu($sql);    

        if($assignments) {
            $function = 'handle_' . $field;
            $rtn_sql[] = $function($assignments, $usersid);
            return array($table, implode(" OR ", $rtn_sql));
        }
    }

    return array($table, implode(" OR ", $rtn_sql));
}

function handle_individual($usersid) {
    global $CFG;
    
    $sql = "SELECT id, path FROM {$CFG->prefix}block_student_person
                        WHERE (path != 'NA' OR path != 0)
                          AND usersid = {$usersid}";

    $assignments = get_records_sql_menu($sql);

    if($assignments) {
        return handle_person($assignments);
    }

    return '';
}

function handle_academics($assignments, $usersid) {
    global $CFG;

    $rtn_sql = array();
    foreach($assignments as $as) {
        list($year, $college, $classification) = explode('/', $as);
        $where = array();
        foreach(array('year', 'college', 'classification') as $field) {
            $value = ${$field};
            if($value != 'NA') {
                $where[] = " u.$field = '{$value}' ";
            }
        }
        $rtn_sql[] = " u.id in (SELECT u.id 
                    FROM {$CFG->prefix}block_courseprefs_users u
                    WHERE " . implode(' AND ', $where) . ') ';
    }

    $individual = handle_individual($usersid);
    if(!empty($individual)) {
        $individual = ' OR ' . $individual;
    }

    return '(' . implode(' OR ', $rtn_sql) . ')' . $individual;
}

function handle_sports($assignments, $usersid) {
    global $CFG;

    $individual = handle_individual($usersid);
    if(!empty($individual)) {
        $individual = " UNION SELECT u.id 
                        FROM {$CFG->prefix}block_courseprefs_users u WHERE " . $individual;
    }

    $rtn_sql = '';
    $in = implode(',', array_values($assignments));
    $rtn_sql .= " u.id in (SELECT spu.usersid 
                            FROM {$CFG->prefix}block_courseprefs_sportusers spu 
                           WHERE spu.sportsid IN (".$in.")" . $individual .")";
    return $rtn_sql;
}

function handle_person($assignments, $usersid) {
    global $CFG;

    $rtn_sql = '';
    $in = implode(',', array_values($assignments));
    $rtn_sql .= " u.id IN (".$in.")";
    return $rtn_sql;
}

// Function to get the total number of users
function get_total_users($courseid) {
    global $CFG;

    $context = get_context_instance(CONTEXT_COURSE, $courseid);

    $parentcontexts = '';
    $parentcontexts = substr($context->path, 1); // kill leading slash
    $parentcontexts = str_replace('/', ',', $parentcontexts);
    if ($parentcontexts !== '') {
        $parentcontexts = ' OR r.contextid IN ('.$parentcontexts.' )';
    }

   //Checking to see if the person can view hidden role assignments. If not, then omit any hidden roles from the number of users in a course
    $canseehidden = has_capability('moodle/role:viewhiddenassigns', $context);
    $hidden = '';
    if (!$canseehidden) {
        $hidden = ' AND r.hidden = 0 ';
    }

    // Counts the sql for gradeable users in the course
    $sql = "SELECT count(u.id)
              FROM {$CFG->prefix}role_assignments r
                  JOIN {$CFG->prefix}user u 
                  ON u.id = r.userid
              WHERE (r.contextid = $context->id $parentcontexts)
                  $hidden
                  AND r.roleid IN ({$CFG->gradebookroles})
                  AND u.deleted = 0";
    $numusers = count_records_sql($sql);

    return $numusers;
}

// Function to get the rank of a student in a class, provided
// they externally provide the total number of users and context
function get_rank($grade, $numusers, $context) {
    global $CFG;

    if(!isset($grade->finalgrade)) {
        return '-/'.$numusers;
    }

    $sql = "SELECT COUNT(DISTINCT(g.userid))
              FROM {$CFG->prefix}grade_grades g
                INNER JOIN {$CFG->prefix}role_assignments r
                  ON r.userid = g.userid
             WHERE finalgrade IS NOT NULL AND finalgrade > {$grade->finalgrade}
                AND itemid = {$grade->sql_grade_item->id}
                AND (r.contextid = {$context->id})
                AND r.roleid IN ({$CFG->gradebookroles})";

    $rank = count_records_sql($sql) + 1;

    return $rank . '/'. $numusers;
}

// Transform a Moodle grade seq to a list of specialized grade_grade objects
function pull_grade_iterator($userid, $courseid) {
    global $CFG;

    $seq = new grade_seq($courseid, true);
 
    return array_map(create_function('$item', '
            $grade = grade_grade::fetch(array("userid" => '.$userid.',
                                              "itemid" => $item->id));
            if (!$grade) {
                $grade = new grade_grade();
                //$grade->finalgrade = 0.0000;
            }
            $grade->sql_grade_item = $item;
            return $grade;
    '), $seq->items);
}

// Pull courses, with or without grades
function pull_courses($user, $grades=false) {
    global $CFG;
    
    $sql = "SELECT c.id, c.shortname, c.fullname, ra.userid, c.visible,
                   {$user->id} AS cps_userid
                FROM {$CFG->prefix}course c, 
                     {$CFG->prefix}role_assignments ra, 
                     {$CFG->prefix}context ctx 
                WHERE c.id = ctx.instanceid 
                  AND ra.contextid=ctx.id 
                  AND ra.roleid IN ({$CFG->gradebookroles}) 
                  AND ra.userid = {$user->moodleid}";

    $courses = get_records_sql($sql);

    if(!$courses) $courses = array();

    if (!$grades) {
        return $courses;
    } else {
        return pull_grades_courses($courses);
    }
}

// This gets all the courses a user is enrolled in as a 
// gradeable student role
function pull_course_grades($user) {
    return array_map('get_course_grade', pull_courses($user));
}

// Provided that they have the courses, get teh grades for the courses
function pull_grades_courses($courses) {
    return array_map('get_course_grade', $courses);
}

// Pull all the sports in the CPS that they can see
function pull_sports() {
    global $CFG, $USER;
    $context = get_context_instance(CONTEXT_SYSTEM);

    if (has_capability('block/student_gradeviewer:sportsadmin', $context)) {
        $sports = get_records('block_courseprefs_sports');
    } else {
        $sql = "SELECT spo.* FROM {$CFG->prefix}block_courseprefs_sports spo,
                                  {$CFG->prefix}block_student_sports spm
                    WHERE spm.usersid = {$USER->id}
                      AND spm.path = spo.id";
        $sports = get_records_sql($sql);
    }

    if(!$sports) {
        $sports = array();
    }

    return array_map('flatten_sport', $sports);
}

// Function used in array_map to flatten the sports name
function flatten_sport($sport) {
    $name = trim($sport->name);

    return ($name == '') ? $sport->code : $name;
}

// Pulled from grades at a glance
function get_course_grade($course) {
    $course_total_item = grade_item::fetch_course_item($course->id);

    /*
    We don't need to hide the final from the mentors
    if ($course_total_item->hidden) {
        return get_string('hidden', 'grades');
    }
    */

    $grade_grade_params = array('itemid' => $course_total_item->id, 
                                'userid' => $course->userid);

    $user_grade_grade = new grade_grade($grade_grade_params);

    if (!$user_grade_grade->finalgrade) {
        $finalgrade = '-';
    } else {
        $finalgrade = simple_grade_format_gradevalue($user_grade_grade->finalgrade, $course_total_item, true);
    }

    // If grade to pass is set
    $class = passing_grade($course_total_item, $user_grade_grade);

    $formatted = format_coursename($course->fullname) . ' <span class="'.$class.'">' . 
                 $finalgrade . '</span>';

    $hidden = (!$course->visible) ? ' class="course_hidden" ' : '';

    return '<a '.$hidden.' href="mentee.php?id='.$course->cps_userid.
           '&amp;courseid='.$course->id.'">'.$formatted.'</a>';
}

function passing_grade($course_item, $user_grade) {
    $class = '';

    $topass = (isset($course_item->gradepass)) ? $course_item->gradepass : 0;
    $finalgrade = (isset($user_grade->finalgrade)) ? $user_grade->finalgrade : NULL; 

    if($topass != 0) {
        if($finalgrade && 
           $finalgrade < $topass) {
            $class='block_student_gradeviewer_failing';
        } else if($finalgrade &&
                  $finalgrade >= $topass) {
            $class='block_student_gradeviewer_passing';
        }
    }

    return $class;
}

// Pulled from grades at a glance
function format_coursename($fullname) {
    $matches = array();

    preg_match('/(.+) for/', $fullname, $matches);

    $out = $matches[1];

    if ($out == ' ') {
        return $fullname;
    } else {
        return $out;
    }
}

// External functions used by reporting tools based on this block's data
/**
 * Returns the number of atheltes in a Moodle course
 */
function get_athelete_count($course) {
    global $CFG;

    $sql = "SELECT COUNT(cpsst.usersid)
                FROM {$CFG->prefix}block_courseprefs_students cpsst,
                     {$CFG->prefix}block_courseprefs_sportusers cpsspou,
                     {$CFG->prefix}block_courseprefs_sections sec
                WHERE sec.idnumber = '{$course->idnumber}'
                  AND cpsst.sectionsid = sec.id
                  AND cpsst.usersid = cpsspou.usersid
                  AND cpsst.status = 'enrolled'";

    return count_records_sql($sql);
}

/**
 * Returns an array of CPS users that are athletes in a CPS section
 */
function get_atheletes($section) {
    global $CFG;
    
    $sql = "SELECT cpsu.*
                FROM {$CFG->prefix}block_courseprefs_users cpsu,
                     {$CFG->prefix}block_courseprefs_students cpsst,
                     {$CFG->prefix}block_courseprefs_sportusers cpsspou
                WHERE cpsu.id = cpsst.usersid
                  AND cpsu.id = cpsspou.usersid
                  AND cpsst.sectionsid = {$section->id}
                  AND cpsst.status = 'enrolled'
                ORDER BY cpsu.lastname, cpsu.firstname ASC";

    return get_records_sql($sql);
}

function is_student_athlete($user) {
    return get_field('block_courseprefs_sportusers', 'id', 'usersid', $user->id);
}

function get_students($section, $where = null) {
    global $CFG;

    $sql = "SELECT cpsu.*
                FROM {$CFG->prefix}block_courseprefs_users cpsu,
                     {$CFG->prefix}block_courseprefs_students cpsst
                WHERE cpsst.sectionsid = {$section->id}
                  AND cpsst.status = 'enrolled'
                  AND cpsu.id = cpsst.usersid
                  ". (($where) ? 'AND '. implode(" AND ", $where) : '') ."
                ORDER BY cpsu.lastname, cpsu.firstname ASC";
    return get_records_sql($sql);
}

function get_all_students($course) {
    global $CFG;
    
    $sql = "SELECT cpsu.*
                FROM {$CFG->prefix}block_courseprefs_users cpsu,
                     {$CFG->prefix}block_courseprefs_students cpsst,
                     {$CFG->prefix}block_courseprefs_sections sec
                WHERE cpsst.sectionsid = sec.id
                  AND cpsst.status = 'enrolled'
                  AND sec.idnumber = '{$course->idnumber}'
                  AND cpsu.id = cpsst.usersid
                ORDER BY cpsu.lastname, cpsu.lastname ASC";

    return get_records_sql($sql);
}

/**
 * Returns the section that a CPS student is enrolled in
 */
function lookup_section($user, $course) {
    global $CFG;
    
    $sql = "SELECT sec.*, c.course_number, c.department
                FROM {$CFG->prefix}block_courseprefs_sections sec,
                     {$CFG->prefix}block_courseprefs_courses c,
                     {$CFG->prefix}block_courseprefs_students cpsst
                WHERE sec.idnumber = '{$course->idnumber}'
                  AND cpsst.sectionsid = sec.id
                  AND sec.coursesid = c.id
                  AND cpsst.status = 'enrolled'
                  AND cpsst.usersid = {$user->id}";

    return get_record_sql($sql);
}

/**
 * Returns mentors for athletes
 */
function lookup_athletic_mentors($mentee) {
    global $CFG;

    $admin_role = get_config('', 'block_student_gradeviewer_acsa_admin');

    // ACSA admin, unioned with sports mentors, unioned with individual mentors
    $sql = get_mentor_sql($admin_role, $mentee) . "
            (SELECT u.* 
                FROM {$CFG->prefix}user u,
                     {$CFG->prefix}block_courseprefs_sportusers spou,
                     {$CFG->prefix}block_student_sports spoa
                WHERE u.id = spoa.usersid
                  AND spou.usersid = {$mentee->id}
                  AND spou.sportsid = spoa.path)";

    return get_records_sql($sql);
}

/**
 * Returns mentors for a student
 */
function lookup_mentors($mentee) {
    // First see if the user is an athlete; if they are,
    // then return athletic mentors
    if(count_records('block_courseprefs_sportusers', 'usersid', $mentee->id) >= 1) {
        return lookup_athletic_mentors($mentee);
    }

    global $CFG;
    
    $admin_role = get_config('', 'block_student_gradeviewer_cas_admin');

    $path_perm = array("$mentee->year/NA/NA",
                       "$mentee->year/$mentee->college/NA",
                       "$mentee->year/$mentee->college/$mentee->classification",
                       "$mentee->year/NA/$mentee->classification",
                       "NA/$mentee->college/$mentee->classification",
                       "NA/$mentee->college/NA",
                       "NA/NA/$mentee->classification");

    $sql = get_mentor_sql($admin_role, $mentee) . "
            (SELECT u.*
                FROM {$CFG->prefix}user u,
                     {$CFG->prefix}block_student_academics aa
                WHERE u.id = aa.usersid
                  AND aa.path IN ('".implode("','", $path_perm)."'))";

    return get_records_sql($sql);
}

function get_mentor_sql($admin_role, $mentee) {
    global $CFG;

    $sql = "(SELECT u.*
                FROM {$CFG->prefix}user u,
                     {$CFG->prefix}role_assignments ra
                WHERE ra.roleid = {$admin_role}
                  AND ra.userid = u.id)
            UNION
            (SELECT u.*
                FROM {$CFG->prefix}user u,
                     {$CFG->prefix}block_student_person pa
                WHERE u.id = pa.usersid
                  AND pa.path = {$mentee->id})
            UNION ";
   
    return $sql;
}

?>
