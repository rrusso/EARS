<?php

// Referral Source Constants
define('AUTOMATIC_REFERRAL_SOURCE', 2);
define('POSITIVE_REFERRAL_SOURCE', 3);

// Lagging constants
define('NOT_LAGGING', 0);
define('LAGGING', 1);
define('EXCEPTIONAL', 2);

/**
 * Function returns a list of current CPS semesters.
 * There's an optional param stating if you all semesters
 * whose start time is less than the time given... the future.
 */
function current_semester($time, $future = false) {
    global $CFG;

    $sql = "SELECT * FROM {$CFG->prefix}block_courseprefs_semesters
            WHERE grades_due > {$time} ".
              ((!$future) ? "AND class_start < {$time}" : '') .
            " AND campus = 'LSU'";

    return get_records_sql($sql);
}

/**
 * Function to obtain all the primary instructors for a given
 * semester. 
 */
function get_primary($section) {
    global $CFG;
    
    $sql = "SELECT mu.*, tea.usersid
            FROM {$CFG->prefix}user mu,
                 {$CFG->prefix}block_courseprefs_users cpsu,
                 {$CFG->prefix}block_courseprefs_sections sec,
                 {$CFG->prefix}block_courseprefs_teachers tea
            WHERE cpsu.moodleid = mu.id
              AND sec.id = tea.sectionsid
              AND tea.primary_flag = 1
              AND cpsu.id = tea.usersid
              AND sec.id = {$section->id}";

    return get_record_sql($sql);
}

function get_moodle_instructors($semester) {
    global $CFG;
    
    $sql = "SELECT mu.*, tea.usersid
            FROM {$CFG->prefix}user mu,
                 {$CFG->prefix}block_courseprefs_users cpsu,
                 {$CFG->prefix}block_courseprefs_teachers tea,
                 {$CFG->prefix}block_courseprefs_sections sec,
                 {$CFG->prefix}course cou
            WHERE tea.status = 'enrolled'
              AND tea.usersid = cpsu.id
              AND mu.id = cpsu.moodleid
              AND tea.sectionsid = sec.id
              AND cou.visible = 1
              AND sec.idnumber = cou.idnumber
              AND sec.semestersid = {$semester->id}
              AND sec.status = 'completed'
            GROUP BY mu.id";

    return get_records_sql($sql);
}

/**
 * Function to retrieve mentors from the student grade research
 * system. An optional subsystem param.
 */
function get_mentors($subsystem = 'cas') {
    global $CFG;

    $stable = ($subsystem == 'cas') ? 'academics' : 'sports';

    $sql = "SELECT mu.*
                FROM {$CFG->prefix}user mu,
                     {$CFG->prefix}block_student_{$stable} t
                WHERE t.usersid = mu.id
                GROUP BY mu.id";
    
    return get_records_sql($sql);
}

function find_referral_duration($when, $duration) {
    if(!empty($duration)) {
        // Have a half duration in hours for more lenient grab
        $sub = ($duration / 2) * 3600;
        $when_sql = " AND ref.date_referred <= " . ($when + $sub) .
                    " AND ref.date_referred >= " . ($when - $sub) .
                    " AND ref.source != 3";
    } else {
        $when_sql = " AND ref.date_referred = {$when}";
    }

    return $when_sql;
}

/**
 * Returns referred students who are NOT athletes.
 */
function get_cas_students($when, $duration = 0) {
    global $CFG;

    $when_sql = find_referral_duration($when, $duration);

    $sql = "SELECT cpsu.*, ref.usersid, ref.source
                FROM {$CFG->prefix}block_student_referrals ref,
                     {$CFG->prefix}block_courseprefs_users cpsu
                WHERE ref.usersid = cpsu.id
                  {$when_sql}
                  AND cpsu.id NOT IN (
                SELECT usersid FROM {$CFG->prefix}block_courseprefs_sportusers
                )
                GROUP BY ref.usersid";
   
    return get_records_sql($sql);
}

/**
 * Returns referred students who ARE athletes.
 */
function get_acsa_students($when, $duration = 0) {
    global $CFG;

    $when_sql = find_referral_duration($when, $duration);

    $sql = "SELECT cpsu.*, ref.usersid, ref.source
                FROM {$CFG->prefix}block_courseprefs_users cpsu,
                     {$CFG->prefix}block_courseprefs_sportusers spu,
                     {$CFG->prefix}block_student_referrals ref
                WHERE ref.usersid = cpsu.id
                  {$when_sql}
                  AND cpsu.id = spu.usersid
                GROUP BY ref.usersid";

    return get_records_sql($sql);
}

/**
 * Returns subsytems admins, along with regular Moodle admins
 */
function get_early_warning_admins($subsystem) {
    global $CFG;

    $roleid = $CFG->{'block_student_gradeviewer_'.$subsystem.'_admin'};
    $context = get_context_instance(CONTEXT_SYSTEM);
    $admins = get_role_users($roleid, $context);
    
    return get_admins() + $admins;
}

/**
 * Returns all the created sections with idnumbers, for a semester
 */ 
function get_valid_sections($semester) {
    global $CFG;
    
    $sql = "SELECT sec.*
            FROM {$CFG->prefix}block_courseprefs_sections sec,
                 {$CFG->prefix}course cou
            WHERE sec.semestersid = {$semester->id}
              AND sec.idnumber = cou.idnumber
              AND cou.visible = 1
              AND sec.idnumber IS NOT NULL
              AND sec.status = 'completed'";

    return get_records_sql($sql);
}

/**
 * For each semester there are eight optional time periods to declare
 * valid run times for the automatic reporting portion.
 * There are four possible times after classes begin
 * and four possible times prior to grade dues
 */
function is_within_timespan($time, $semester) {
    $range = range(1,4);

    foreach(array('after'=>$semester->class_start,
                  'prior'=>$semester->grades_due) as $key => $stime) {
        $is_within = array_reduce($range, create_function('$in,$number', '
                global $CFG;
                $name = "block_student_gradeviewer_reporting_'.
                        $key.'_'.$semester->id.'_$number";

                // No value set is to return false for that number
                if(empty($CFG->{$name})) return $in || false;

                $value= $CFG->{$name};

                $mult = ("'.$key.'" == "prior") ? -1 : 1;
                $start = '.$stime.' + ($mult * ($value * 60 * 60 * 24));
                $end = $start + 86400;

                return $in || ('.$time.' > $start && '.$time.' < $end);
              '));

        if($is_within) {
            return true;
        }
    }

    return false;
}

/**
 * Get all digestable sections for a primary
 * Instructors have the final say whether or not a student in
 * their course will be notified. Look at defaults, and compare
 * their selections if that exists.
 */
function get_digestable_sections($instructor, $semester) {
    global $CFG;

    $sql = "SELECT sec.*, tea.primary_flag
                FROM {$CFG->prefix}block_courseprefs_sections sec,
                     {$CFG->prefix}block_courseprefs_teachers tea
                WHERE tea.sectionsid = sec.id
                  AND tea.status = 'enrolled'
                  AND tea.usersid = {$instructor->usersid}
                  AND sec.semestersid = {$semester->id}";

    $temp = get_records_sql($sql);
    $sectionsids = ($temp) ? array_keys($temp) : array();

    $sql = "SELECT opt.sectionsid, opt.primary_instructor, opt.non_primary_instructor
                FROM {$CFG->prefix}block_teacher_referral_opt opt
                WHERE opt.sectionsid IN (".implode(',', $sectionsids).")";

    $db_digest = get_records_sql($sql);
    $db_digest = (!$db_digest) ? array() : $db_digest;

    $rtn = array();
    foreach(array('primary', 'non_primary') as $teacher_flag) {

        $default = $CFG->{'cas_reporting_'.$teacher_flag};
        if(!empty($default)) {
            $only_not_teacher = array_filter($db_digest, 'filter_not_'.$teacher_flag);
            $rtn += array_diff(array_keys(array_filter($temp, 'filter_'.$teacher_flag)), 
                               array_keys($only_not_teacher));
        } else {
            $only_teacher = array_filter($db_digest, 'filter_'.$teacher_flag);
            $rtn += array_keys($only_teacher);
        }
    }

    return $rtn;
}

/**
 * Get all the digestable sections for a student.
 * Instructor have the final say whether or not a student in
 * their course will be notified.
 */
function get_all_student_sections($student, $semester) {
    global $CFG;

    $sql = "SELECT sec.*
                FROM {$CFG->prefix}block_courseprefs_students stu,
                     {$CFG->prefix}block_courseprefs_sections sec
                WHERE stu.status = 'enrolled'
                  AND sec.id = stu.sectionsid
                  AND stu.usersid = {$student->usersid}
                  AND sec.semestersid = {$semester->id}";

    $temp = get_records_sql($sql);
    $sectionids = ($temp) ? array_keys($temp) : array();

    // Only get section marked for 'student' or 'all', otherwise we get everything
    $sql = "SELECT opt.sectionsid, opt.student
                FROM {$CFG->prefix}block_teacher_referral_opt opt
                WHERE opt.sectionsid IN (".implode(',', $sectionids).")";

    $db_digest = get_records_sql($sql);
    $db_digest = ($db_digest) ? $db_digest : array();

    // Grab admin default
    $default = $CFG->cas_reporting_student;
    if(!empty($default)) {
        $only_student = array_filter($db_digest, 'filter_not_student');
        return array_diff($sectionids, array_keys($only_student));
    } else {
        $only_none = array_filter($db_digest, 'filter_student');
        return array_keys($only_none);
    }
}

// Filter functions for the digestable options below
function filter_primary($option) {
    return !empty($option->primary_instructor) || !empty($option->primary_flag);
}

function filter_not_primary($option) {
    return empty($option->primary_instructor);
}

function filter_non_primary($option) {
    return !empty($option->non_primary_instructor) || 
           (isset($option->primary_flag) && empty($option->primary_flag));
}

function filter_not_non_primary($option) {
    return empty($option->non_primary_instructor);
}

function filter_student($option) {
    return !empty($option->student);
}

function filter_not_student($option) {
    return empty($option->student);
}
// End digestable functions

/**
 * The report back function send a specially formatted referrals to the specified
 * user.
 */
function report_to($user, $when, $semester, $pull_referrals_with_filter, $format, $extra=null) {
    if(is_array($user)) {
        $referrals = $pull_referrals_with_filter($when, $extra);
    } else {
        $referrals = $pull_referrals_with_filter($user, $when, $extra);
        $user = array($user);
    }

    // If no one was referred in this run, that they are capable of
    // seeing, then don't even bother with the email
    if(empty($referrals)) {
        return false;
    }
    $formatted = $format($referrals);

    $a->semester = get_string('format semester', 'block_student_gradeviewer', $semester);

    $a->date = date('m/d/Y', $when);

    $subject = get_string('auto_subject', 'block_student_gradeviewer', $a);
    foreach($user as $u) {
        email_to_user($user, '', $subject, $formatted);
    }
}

/**
 * Pull automatic referrals for primaries.
 */
function with_instructor($primary, $when, $digestable_sections) {
    $referrals = array_map(create_function('$id', '
        global $CFG;

        $sql = "SELECT ref.*
                    FROM {$CFG->prefix}block_student_referrals ref
                    WHERE ref.sectionsid = {$id}
                      AND ref.date_referred = '.$when.'";

        $referrals = get_records_sql($sql);

        list($negative, $positive) = array_partition($referrals, "filter_negative");
        
        $rtn = new stdClass;
        $rtn->negative_count = count($negative);
        $rtn->positive_count = count($positive);

        $sql = "SELECT sec.id, sec.section_number, cou.course_number, cou.department
                    FROM {$CFG->prefix}block_courseprefs_sections sec,
                         {$CFG->prefix}block_courseprefs_courses cou
                    WHERE cou.id = sec.coursesid
                      AND sec.id = $id";

        $section = get_record_sql($sql);

        $rtn->header = get_string("format_section", "block_courseprefs", $section);

        return $rtn;
    '), $digestable_sections);

    return array_filter($referrals, 'filter_zeros');
}

/**
 * Pull automatic referrals for mentors of a subsystem
 */
function with_mentor($mentor, $when, $subsystem) {
    // With a mentor, we need to know their assignments, and run a count 
    // on that
    global $CFG;
   
    $type = ($subsystem == 'cas') ? 'academics' : 'sports';

    $sql = "SELECT t.path, t.path AS real_path FROM
                {$CFG->prefix}block_student_{$type} t
                WHERE t.usersid = {$mentor->id}
                  AND t.path != 'NA'";
    
    // Can't trust get_records_sql 
    $temp = get_records_sql($sql);
    $academic_assignments = ($temp) ? $temp : array();

    $with_function = "with_{$type}_assignments";

    return array_merge($with_function($academic_assignments, $when), 
                       with_person_mentor($mentor, $when));   
}

/**
 * Pull automatic referral for sport mentors
 */
function with_sports_assignments($assigns, $when) {
    $assignments = array_map(create_function('$assignment', '
            global $CFG;
        
            $rtn = new stdClass;

            $sql = "SELECT ref.*
                        FROM {$CFG->prefix}block_student_referrals ref,
                             {$CFG->prefix}block_courseprefs_sportusers spu
                        WHERE spu.usersid = ref.usersid
                          AND spu.sportsid = {$assignment->real_path}
                          AND ref.date_referred = '.$when.'";

            $records = get_records_sql($sql);
            
            $rtn->header = get_field("block_courseprefs_sports", "name", 
                                     "id", $assignment->real_path);

            list($negative, $positive) = array_partition($records, "filter_negative");
            $rtn->negative_count = count($negative);
            $rtn->positive_count = count($positive);

            return $rtn;
            '), $assigns);

    $assignments = array_filter($assignments, 'filter_zeros');

    return $assignments;
}

/**
 * Pull automatic referrals for academic mentors
 */
function with_academics_assignments($assigns, $when) {
    $assignments = array_map(create_function('$assignment', '
                global $CFG;

                $rtn = new stdClass;

                list($year, $college, $major) = explode("/", $assignment->real_path);
                $fields = array("year" => $year, 
                                "college" => $college, 
                                "major"=>$major);

                $sql = "SELECT ref.*
                            FROM {$CFG->prefix}block_student_referrals ref,
                                 {$CFG->prefix}block_courseprefs_users cpsu
                            WHERE cpsu.id = ref.usersid
                              AND ref.date_referred = '.$when.'";
                $rtn->header = "";
                $referred = get_string("auto_referred", "block_student_gradeviewer");
                foreach($fields as $field => $value) {
                    if($value != "NA") {
                        $a->{$field} = $value;
                        $rtn->header .= $referred . get_string($field."_desc_text", 
                                        "block_student_gradeviewer", $a);
                        if($field == "major") $field = "classification";
                        $sql .= " AND cpsu.{$field} = \"$value\" ";
                    }
                }

                $records = get_records_sql($sql);
                list($negative, $positive) = array_partition($records, "filter_negative");
                $rtn->negative_count = count($negative);
                $rtn->positive_count = count($positive);

                return $rtn;
               '), $assigns);

    $assignments = array_filter($assignments, 'filter_zeros');
   
    return $assignments;
}

/**
 * Pull automatic referrals for individual mentors
 */
function with_person_mentor($mentor, $when) {
    global $CFG;
    
    $sql = "SELECT CONCAT(cpsu.firstname, ' ', cpsu.lastname), t.path, cpsu.firstname,
                         cpsu.lastname
                FROM {$CFG->prefix}block_student_person t,
                     {$CFG->prefix}block_courseprefs_users cpsu
                WHERE cpsu.id = t.path
                  AND t.usersid = {$mentor->id}";
    
    $temp = get_records_sql($sql);
    $person_assignments = ($temp)? $temp: array();

    $assignments = array_map(create_function('$assignment', '
                global $CFG;
    
                $rtn = new stdClass;

                $sql = "SELECT ref.*
                            FROM {$CFG->prefix}block_student_referrals ref
                            WHERE ref.usersid = {$assignment->path}
                              AND ref.date_referred = '.$when.'";

                $records = get_records_sql($sql);
                list($negative, $positive) = array_partition($records, "filter_negative");
                $rtn->negative_count = count($negative);
                $rtn->positive_count = count($positive);

                $rtn->header = $assignment->firstname . " " .$assignment->lastname;
                return $rtn;
           '), $person_assignments);

    $assignments = array_filter($assignments, 'filter_zeros');
    return $assignments;
}

// Filter and reduce functions for pulling referrals
function filter_zeros($referral) {
    return $referral->negative_count > 0 or $referral->positive_count;
}

function reduce_counts($in, $referral) {
    return $in + ($referral->negative_count + $referral->positive_count);
}
// End auxillary referral pulling functions


/**
 * Format the referrals pulled for a specified user. Format for emailing
 */
function format_referrals($referrals) {

    $text = array_reduce($referrals, create_function('$in, $ref', '
                $inter = empty($in) ? "" : $in;
                return $inter . "\n\t" . $ref->header . ": " . 
                       get_string("auto_negative", "block_student_gradeviewer", $ref) ." | " .
                       get_string("auto_positive", "block_student_gradeviewer", $ref);
           '));

    $total = array_reduce($referrals, 'reduce_counts');
    $text .= "\n\n" . get_string('auto_total', 'block_student_gradeviewer') . $total;

    return $text;
}

/**
 * Pull referrals for CAS admins
 */
function with_cas_admin($when) {
    global $CFG;

    $sql = "SELECT id, CONCAT('NA/NA/', classification) as real_path
                FROM {$CFG->prefix}block_courseprefs_users
                WHERE classification != ''
                GROUP BY classification
                ORDER BY classification ASC";

    $paths = get_records_sql($sql);
 
    return with_academics_assignments($paths, $when); 
}

/**
 * Pull referrals for ACSA admins
 */
function with_acsa_admin($when) {
    global $CFG;

    $sql = "SELECT id, id AS real_path
                FROM {$CFG->prefix}block_courseprefs_sports";
    
    $paths = get_records_sql($sql);

    return with_sports_assignments($paths, $when);
}

/**
 * Pull referrals for a specific student
 */
function with_student($student, $when, $digestable_sections, $duration=0) {
    global $CFG;
    
    $when_sql = find_referral_duration($when, $duration);

    $sectionids = implode(',', $digestable_sections);

    $sql = "SELECT ref.*, c.fullname, c.id AS courseid
                FROM {$CFG->prefix}block_student_referrals ref,
                     {$CFG->prefix}block_courseprefs_sections sec,
                     {$CFG->prefix}course c
                WHERE c.idnumber = sec.idnumber
                  AND sec.id = ref.sectionsid
                  AND ref.sectionsid IN ({$sectionids})
                  AND ref.usersid = {$student->usersid}
                  {$when_sql}";

    return get_records_sql($sql);
}

/**
 * Send a report to the student
 */
function notify_student($student, $referral, $recovered = false) {
    global $CFG;

    $referral->source = ($recovered) ? 4 : $referral->source;

    $a->fullname = fullname($student);
    $a->course_name = $referral->fullname;
    $a->grade_link = $CFG->wwwroot . '/grade/report/user/index.php?id='.
                     $referral->courseid.'&userid='.$student->moodleid;

    $report_form = new report_form();

    $report_form->user = get_record('user', 'id', $referral->referrerid);
    $report_form->notify_student($student, $a, $referral);
}

/**
 * Boolean function to see if a student has been referred
 */
function student_was_referred($student, $sectionsid) {
    global $CFG;

    $sql = "SELECT COUNT(ref.id)
                FROM {$CFG->prefix}block_student_referrals ref
                WHERE ref.usersid = {$student->id}
                  AND ref.sectionsid = {$sectionsid}
                GROUP BY ref.usersid";

    $count = count_records_sql($sql);

    return $count > 1;
}

function referral_comp($ref_a, $ref_b) {
    if($ref_a->sectionsid === $ref_b->sectionsid) {
        return 0;
    }
    return ($ref_a->sectionsid > $ref_b->sectionsid) ? 1 : -1; 
}

function student_comp($ref_a, $ref_b) {
    if($ref_a->id === $ref_b->id) {
        return 0;
    }
    return ($ref_a->id > $ref_b->id) ? 1 : -1;
}

function no_longer_referred($yesterdays_referrals, $current_referrals, $func='referral_comp') {
    return array_udiff($yesterdays_referrals, $current_referrals, $func);
}

// The algorithms to determine if the student is a problem
class reporting_algorithm {
    var $course;
    var $loaded;
    var $section;
    var $course_item;

    function __construct($section) {
        // Find a Moodle course for this section
        $this->course = get_record('course', 'idnumber', $section->idnumber);
        $this->course_item = get_record('grade_items', 'courseid', 
                                  $this->course->id, 'itemtype' , 'course');

        // debug condition
        $this->debug_condition = false;

        $this->section = $section;
        $this->loaded = false;

        // If the course is invisible, or neither pre condition is met, then return
        if(!$this->course->visible == 1 or !$this->pre_condition() or !$this->pre_process()) {
            return;
        }

        $this->loaded = true;
    }

    function pre_process() {
        return true;
    }

    // do the specific logic here
    function process($student) {}
}

class lagging_algorithm extends reporting_algorithm {
    var $negative_value;
    var $positive_value;
    var $items;

    // Loads the lagging algorithm from the admin config
    function pre_condition() {
        global $CFG;

        foreach(array('negative_value', 'positive_value', 'items') as $val) {
            $named_param = 'block_student_gradeviewer_lagging_'. $val;
            $value = isset($CFG->{$named_param}) ? $CFG->{$named_param} : 1;
            $this->{$val} = empty($value) ? 1 : $value; 
        }

        return true;
    }

    function debug($what) {
        if($this->debug_condition) print_r($what);
    }

    function pre_process() {
        global $CFG;

        // Get our population total
        
        $sql = "SELECT COUNT(stu.id)
                    FROM {$CFG->prefix}block_courseprefs_students stu
                    WHERE stu.sectionsid = {$this->section->id}
                      AND stu.status = 'enrolled'";

        $total = count_records_sql($sql);
       
        // Time to calculate the means of completed items
        $sql = "SELECT stu.id, COUNT(g.id) as count
                    FROM {$CFG->prefix}grade_grades g,
                         {$CFG->prefix}block_courseprefs_students stu,
                         {$CFG->prefix}block_courseprefs_users cpsu,
                         {$CFG->prefix}grade_items gr
                    WHERE stu.sectionsid = {$this->section->id}
                      AND stu.status = 'enrolled'
                      AND stu.usersid = cpsu.id
                      AND cpsu.moodleid = g.userid
                      AND gr.courseid = {$this->course->id}
                      AND gr.itemtype != 'course'
                      AND gr.itemtype != 'category'
                      AND gr.aggregationcoef > 0.00000
                      AND g.itemid = gr.id
                      AND (g.finalgrade IS NOT NULL OR g.excluded != 0)
                    GROUP BY g.userid";

        $items = get_records_sql($sql);

        $this->grade_item_mean = ($items) ?
            (array_reduce($items, array($this, 'reduce_grade_item')) / $total) :
            0;

        // No Items in the course means skipping for the course
        if($this->grade_item_mean == 0) {
            return false;
        }

        // Grab the deviation of completed items
        $this->item_deviation = empty($this->grade_item_mean) ? 0 :
        sqrt(array_reduce($items, array($this, 'reduce_item_deviation')) / $total);

        // Compute the mean of the final grade
        $sql = "SELECT g.*
                    FROM {$CFG->prefix}grade_grades g,
                         {$CFG->prefix}block_courseprefs_students stu,
                         {$CFG->prefix}block_courseprefs_users cpsu
                    WHERE g.itemid = {$this->course_item->id}
                      AND g.finalgrade IS NOT NULL
                      AND g.userid = cpsu.moodleid
                      AND stu.usersid = cpsu.id
                      AND stu.sectionsid = {$this->section->id}
                      AND stu.status = 'enrolled'";
 
        $grades = get_records_sql($sql);
        $g_count = count($grades);

        $this->grade_value_mean = ($grades) ?
              (array_reduce($grades, array($this, 'reduce_grade_value')) / $g_count) : 
              0;
   
        // We can get the deviation here
        $this->grade_deviation = empty($this->grade_value_mean) ? 0 :
        sqrt(array_reduce($grades, array($this, 'reduce_grade_deviation')) / $g_count);

        // The threshold for logging
        $this->problem_threshold = ($this->grade_value_mean - 
                                   ($this->negative_value * $this->grade_deviation));

        $this->debug("Problem threshold: {$this->problem_threshold} \n");
        $this->kudos_threshold = ($this->grade_value_mean +
                                 ($this->positive_value * $this->grade_deviation));
        
        $this->debug("Kudos threshold: {$this->kudos_threshold} \n");

        $this->item_threshold = ($this->grade_item_mean - 
                                ($this->items * $this->item_deviation));
        $this->debug("Item threshold : {$this->item_threshold} \n");
        
        return true;
    }

    function reduce_grade_item($in, $item) {
        return $in + $item->count;
    }

    function reduce_grade_value($in, $grade) {
        return $in + $grade->finalgrade;
    }

    function reduce_grade_deviation($in, $grade) {
        return $in + pow($grade->finalgrade - $this->grade_value_mean, 2);
    }

    function reduce_item_deviation($in, $item) {
        return $in + pow($item->count - $this->grade_item_mean, 2);
    }

    function process($student) {
        if(!$this->loaded) {
            return NOT_LAGGING;
        }

        global $CFG;
        
        // Get the number of completed grades for the student
        $sql = "SELECT COUNT(gg.id)
                    FROM {$CFG->prefix}grade_grades gg,
                         {$CFG->prefix}grade_items gr
                    WHERE gg.userid = {$student->moodleid}
                      AND gr.id = gg.itemid
                      AND gr.itemtype != 'course'
                      AND gr.itemtype != 'category'
                      AND gr.aggregationcoef > 0.00000
                      AND gr.courseid = {$this->course->id}
                      AND (gg.finalgrade IS NOT NULL OR gg.excluded != 0)";

        $item_count = count_records_sql($sql);

        // If they're item count is less than the threshold, then they are lagging
        if($item_count < $this->item_threshold) {
            return LAGGING;
        }

        // If they have an above average item count, then we check they're grade
        // value threshold
        $grade = get_record('grade_grades', 'userid', 
                            $student->moodleid, 'itemid', $this->course_item->id);

        // They don't have a final grade in the class, skip it
        if(!$grade or is_null($grade->finalgrade)) {
            return NOT_LAGGING;
        }

        if($grade->finalgrade <= $this->problem_threshold) {
            return LAGGING;
        }

        // Well, alright; if they doing really well, report that
        if($grade->finalgrade >= $this->kudos_threshold) {
            return EXCEPTIONAL;
        }

        return NOT_LAGGING;
    }

    function student_is_doing_well($lagging) {
        return $lagging == 2;
    }
}

class failing_algorithm extends reporting_algorithm {
    // This algorithm only applies if this one precondition is met
    function pre_condition() {
        return !empty($this->course_item->gradepass);
    }

    function process($student) {
        if(!$this->loaded) {
            return false;
        }

        
        // Attempt to get the grade grade for the student
        $grade = get_record('grade_grades', 'userid', $student->moodleid,
                            'itemid', $this->course_item->id);

        if(!$grade or is_null($grade->finalgrade)) {
            return false;
        }

        if($grade->finalgrade < $this->course_item->gradepass){
            return true;
        }
        
        return false;
    }
}

// php should have an array partition method. Not sure why they don't
function filter_negative($referral) {
    return $referral->source == 2;
}

function array_partition($collection, $predicate) {
    $true = array();
    $false = array();

    if($collection) {
        foreach($collection as $key => $elem) {
            if($predicate($elem)) $true[$key] = $elem;
            else $false[$key] = $elem;
        }
    }

    return array($true, $false);
}

?>
