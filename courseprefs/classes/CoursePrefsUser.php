<?php

// May want to look optimizing things

require_once('CoursePrefsBase.php');
require_once('CoursePrefsSection.php');
require_once('CoursePrefsCourse.php');
require_once('CoursePrefsMaterial.php');
require_once('CoursePrefsSplit.php');
require_once('CoursePrefsEnroll.php');
require_once('CoursePrefsCrosslist.php');
require_once('CoursePrefsTeamTeach.php');

/**
 * Class Representation of a user from the {$CFG->prefix}blocks_courseprefs_users db table
 **/
class CoursePrefsUser implements CoursePrefsBase {

    const update_flag = true;

    /**
     * Private properties 
     */
    private $id;
    private $username;
    private $firstname;
    private $lastname;
    private $update_flag = self::update_flag;
    private $idnumber;
    private $year;
    private $college;
    private $reg_status;
    private $classification;
    private $keypadid;
    private $ferpa;
    private $degree_candidacy;
    private $anonymous;
    private $hidden;
    private $cr_delete;
    private $format;
    private $numsections;
    private $moodleid;

    /**
     * Private properties to be filled by methods
     */
    private $sections_student;
    private $sections_teacher;
    private $sections_primary;
    private $courses_teacher;
    private $courses_materials;
    private $courses_split;
    private $courses_splittable;
    private $courses_crosslists;
    private $courses_crosslistable;
    private $courses_teamteach;
    private $unwanted;
    private $courses_valid;

    /**
     *
     */
    function CoursePrefsUser() {}

    /**
     * Constructor
     * If id is null, a new record is saved, else a record is updated. The defaults for
     * course_create_days and course_enroll_days are the same as the database defaults.
     */
    function __construct($username='', $firstname='', $lastname='', $idnumber='', $year=0, 
                         $college=null, $reg_status=null, $classification=null, $keypadid='',
                         $ferpa='', $degree_candidacy='', $anonymous='', $format=null, 
                         $numsections=null, $hidden=null, $cr_delete=null, $id = null, 
                         $update_flag = self::update_flag, $moodleid=null) {

        $this->id = $id;
        $this->username = $username;
        $this->firstname = $firstname;
        $this->lastname = $lastname;
        $this->idnumber = $idnumber;
        $this->update_flag = $update_flag;
        $this->year = $year;
        $this->college = $college;
        $this->reg_status = $reg_status;
        $this->classification = $classification;
        $this->keypadid = $keypadid;
        $this->ferpa = $ferpa;
        $this->degree_candidacy = $degree_candidacy;
        $this->anonymous = $anonymous;
        $this->numsections = $numsections;
        $this->format = $format;
        $this->hidden = $hidden;
        $this->cr_delete = $cr_delete;
        $this->moodleid = $moodleid;
    }


    /**
     * Property Getters
     */
    function getCourseCreateDays() {
        return $this->course_create_days;
    }

    function getCourseEnrollDays() {
        return $this->course_enroll_days;
    }

    function getFirstname() {
        return $this->firstname;
    }

    function getId() {
        return $this->id;
    }

    function getLastname() {
        return $this->lastname;
    }

    function getSplitCourses() {
        return $this->split_courses;
    }

    function getUpdateFlag() {
        return $this->update_flag;
    }

    function getUsername() {
        return $this->username;
    }

    function getIdNumber() {
        return $this->idnumber;
    }

    function getYear() {
        return $this->year;
    }

    function getCollege() {
        return $this->college;
    }

    function getRegStatus() {
        return $this->reg_status;
    }

    function getClassification() {
        return $this->classification;
    }

    function getKeypadId() {
        return $this->keypadid;
    }

    function getFerpa() {
        return $this->ferpa;
    }

    function getDegreeCandidacy() {
        return $this->degree_candidacy;
    }

    function getAnonymous() {
        return $this->anonymous;
    }

    function getFormat() {
        return $this->format;
    }

    function getNumsections() {
        return $this->numsections;
    }

    function getVisible() {
        return $this->hidden;
    }

    function getCrDelete() {
        return $this->cr_delete;
    }

    function getMoodleId() {
        return $this->moodleid;
    }

    /**
     * Property Setters
     */
    function setCourseCreateDays($course_create_days) {
        $this->course_create_days = $course_create_days;
    }

    function setCourseEnrollDays($course_enroll_days) {
        $this->course_enroll_days = $course_enroll_days;
    }

    function setMoodleField($field, $value) {
        if ($this->{$field} != $value) {
            $this->{$field} = $value;
            $this->setUpdateFlag(true);
        }
    }

    function setId($id) {
        $this->id = $id;
    }

    function setSplitCourses($split_courses) {
        $this->split_courses = $split_courses;
    }

    function setUpdateFlag($update_flag) {
        $this->update_flag = $update_flag;
    }

    function setYear($year) {
        $this->year = $year;
    }

    function setCollege($college) {
        $this->college = $college;
    }

    function setRegStatus($reg_status) {
        $this->reg_status = $reg_status;
    }

    function setClassification($classification) {
        $this->classification = $classification;
    }

    function setKeypadId($keypadid) {
        $this->keypadid = $keypadid;
    }

    function setFerpa($ferpa) {
        $this->ferpa = $ferpa;
    }

    function setDegreeCandidacy($degree_candidacy) {
        $this->degree_candidacy = $degree_candidacy;
    }

    function setAnonymous($anonymous) {
        $this->anonymous = $anonymous;
    }

    function setVisible($visible) {
        $this->hidden = $visible;
    }

    function setFormat($format) {
        $this->format = $format;
    }

    function setNumsections($numsections) {
        $this->numsections = $numsections;
    }

    function setCrDelete($cr_delete) {
        $this->cr_delete = $cr_delete;
    }

    function setMoodleId($moodleid) {
        $this->moodleid = $moodleid;
    }

    /**
     * Obtains the the sections that the the Sections that the user is a teacher of
     */
    function getSectionsAsTeacher($refresh = false) {

        if (!$refresh && $this->sections_teacher) {
            return $this->sections_teacher;
        }

        global $CFG;

        $sql = "SELECT s.*, t.primary_flag
                  FROM {$CFG->prefix}block_courseprefs_sections s,
                       {$CFG->prefix}block_courseprefs_teachers t,
                       {$CFG->prefix}block_courseprefs_users u
                 WHERE s.id = t.sectionsid
                   AND u.id = t.usersid
                   AND (t.status = 'enroll' OR t.status = 'enrolled')
                   AND u.id = " . addslashes($this->id);

        $results = get_records_sql($sql);
        $this->sections_teacher = array();
        $this->sections_primary = array();

        foreach ($results as $result) {
            $this->sections_teacher[$result->id] = new CoursePrefsSection($result->semestersid,
                $result->coursesid, $result->section_number, $result->status, 
                $result->id, $result->idnumber);
            $this->sections_primary[$result->id] = ($result->primary_flag ? true : false);
        }

        return $this->sections_teacher;
    }

    function getSectionsInfoAsPrimaryTeacher($refresh = false, $course) {
        global $CFG;
        
        return $this->getSectionsInfoAsTeacher(true, $course, $refresh);
    }

    function getSectionsInfoAsTeacher($primary = false, $course = null, $refresh = false) {
        global $CFG;

        if (!$refresh && $this->sections_teacher) {
            return $this->sections_teacher;
        }

        $working = "";
        if ($primary) {
            $working = " AND t.primary_flag = 1 ";
        }

        if($course) {
            $working .= " AND sec.idnumber = '{$course->idnumber}' ";
        }

        $sql = "SELECT sec.*, sem.year, sem.name, cou.department, cou.course_number
                    FROM {$CFG->prefix}block_courseprefs_sections sec,
                         {$CFG->prefix}block_courseprefs_courses  cou,
                         {$CFG->prefix}block_courseprefs_semesters sem,
                         {$CFG->prefix}block_courseprefs_teachers t
                    WHERE t.usersid = {$this->id}
                      AND t.sectionsid = sec.id
                      AND (t.status = 'enroll' OR t.status = 'enrolled')
                      AND cou.id = sec.coursesid
                      AND sem.id = sec.semestersid
                      {$working}";

        $results = get_records_sql($sql);
        $this->sections_teacher = $results;
        return $this->sections_teacher;
    }

    function transform_course($course) {
        global $CFG;

        $sql = "SELECT DISTINCT (sec.id), sec. * , 
                       cou.department, cou.course_number, 
                       sem.year, sem.name, sem.campus
                    FROM {$CFG->prefix}block_courseprefs_sections sec, 
                         {$CFG->prefix}block_courseprefs_courses cou, 
                         {$CFG->prefix}block_courseprefs_semesters sem, 
                         {$CFG->prefix}block_courseprefs_users u, 
                         {$CFG->prefix}block_courseprefs_students stu, 
                         {$CFG->prefix}groups_members gm
                    WHERE gm.groupid IN (
                        SELECT g.id
                            FROM {$CFG->prefix}groups g, 
                                 {$CFG->prefix}groups_members gm, 
                                 {$CFG->prefix}context con, 
                                 {$CFG->prefix}role_capabilities rc, mdl_role_assignments ra
                            WHERE g.courseid = {$course->id}
                              AND con.contextlevel =50
                              AND con.instanceid = g.courseid
                              AND rc.capability =  'moodle/user:viewdetails'
                              AND rc.roleid = ra.roleid
                              AND ra.userid = gm.userid
                              AND ra.contextid = con.id
                              AND gm.groupid = g.id
                              AND gm.userid = {$this->moodleid}
                        )
                      AND gm.userid = u.moodleid
                      AND stu.usersid = u.id
                      AND stu.sectionsid = sec.id
                      AND stu.status =  'enrolled'
                      AND sec.coursesid = cou.id
                      AND sec.semestersid = sem.id
                      AND sec.idnumber = '{$course->idnumber}'";

        return $sql;
    }

    function getSectionsForMoodleCourse($course=null, $use_cps=false, $primary=true, $refresh=false) {
        global $CFG, $SESSION;

        // First try to see if they're a CPS teacher, if you want
        if($use_cps and $sections = $this->getSectionsInfoAsTeacher($primary, $course, $refresh)) {
            return $sections;
        }

        // If the have sections for a script, return that
        if(!$refresh and $this->sections_teacher) {
            return $this->sections_teacher;
        }

        // If they have sections from they're courses in the SESSION
        // return that
        if(!$course and !$use_cps and isset($SESSION->cps_moodle_sections)) {
            $this->sections_teacher = $SESSION->cps_moodle_sections;
            return $SESSION->cps_moodle_sections;
        }

        // Get all their courses
        if(!$course) {
            $courses = get_my_courses($this->moodleid);
            $courses = array_filter($courses, create_function('$a', 'return !empty($a->idnumber);'));
        } else {
            $courses = array($course);
        }
       
        $sql = '(' . implode(') UNION (', array_map(array($this, 
               'transform_course'), $courses)) . ')';        

        $this->sections_teacher = get_records_sql($sql);
        asort($this->sections_teacher);

        if(!$course and !isset($SESSION->cps_moodle_sections)) {
            $SESSION->cps_moodle_sections = $this->sections_teacher;
        }

        return $this->sections_teacher;  
    }

    function getSectionsForCourse($semesterid, $courseid, $showunenroll=true) {
        global $CFG;

        $inject = (!$showunenroll) ? "AND t.status != 'unenrolled'" : "";

        $sql = "SELECT sec.*
                    FROM {$CFG->prefix}block_courseprefs_sections sec,
                         {$CFG->prefix}block_courseprefs_teachers t
                    WHERE t.usersid = ". addslashes($this->id) . "
                      AND t.sectionsid = sec.id
                      AND t.primary_flag = 1
                      {$inject}
                      AND sec.status != 'unwanted'
                      AND sec.status != 'unwant'
                      AND sec.coursesid = ". addslashes($courseid) . "
                      AND sec.semestersid = ". addslashes($semesterid) . "
                    ORDER BY sec.section_number ASC";

        $results = get_records_sql($sql);
        $sections = array();
        foreach ($results as $result) {
            $sections[$result->id] = new CoursePrefsSection($result->semestersid,
                $result->coursesid, $result->section_number, $result->status,
                $result->id, $result->idnumber);
        }
        return $sections;
    }

    /**
     * Obtains the courses that the user is a teacher of
     */
    function getCoursesAsTeacher($refresh = false) {

        if (!$refresh && $this->courses_teacher) {
            return $this->courses_teacher;
        }

        global $CFG;

        $sql = "SELECT DISTINCT c.*
                  FROM {$CFG->prefix}block_courseprefs_courses c,
                       {$CFG->prefix}block_courseprefs_sections s,
                       {$CFG->prefix}block_courseprefs_teachers t,
                       {$CFG->prefix}block_courseprefs_users u
                 WHERE c.id = s.coursesid
                   AND s.id = t.sectionsid
                   AND u.id = t.usersid
                   AND (t.status = 'enroll' OR t.status = 'enrolled')
                   AND u.id = " . addslashes($this->id) . "
              ORDER BY c.department, c.course_number ASC";

        $results = get_records_sql($sql);
        $this->courses_teacher = array();

        if ($results){
            foreach ($results as $result) {
                $this->courses_teacher[$result->id] = new CoursePrefsCourse($result->department,
                    $result->course_number, $result->fullname, $result->course_type, 
                    $result->grade_type, $result->first_year,
                    $result->exception, $result->legal_writing, $result->id);
            }
        }
        return $this->courses_teacher;
    }

    function findTotalGroupCount($semestersid, $coursesid) {
        global $CFG;

        $courses = implode(',', $coursesid);

        $sql = "SELECT sec.coursesid, COUNT(sec.id) as count FROM
                    {$CFG->prefix}block_courseprefs_sections sec,
                    {$CFG->prefix}block_courseprefs_teachers t
                    WHERE sec.coursesid in ({$courses})
                      AND t.sectionsid = sec.id
                      AND semestersid = {$semestersid}
                      AND t.usersid = {$this->id}
                    GROUP BY sec.coursesid
                    ORDER BY count ASC";

        $results = get_records_sql($sql);
        if (!$results) {
            return 0;
        }

        $total = 0;
        $last = 0;
        foreach ($results as $result) {
            $total += $result->count;
            $last = $result->count;
        }

        return (int)min($total / 2, $total - $last);
    }

    function getValidSections($filters=array(), $apply_count= false, $generate_keys=false, $limiter=0, $apply_semester_filter= false) {
        global $CFG;
        
        $count ='';
        $group ='';
        $order ='cou.department, cou.course_number ASC';

        $sql = "SELECT sec.*, sem.year, sem.name, cou.department, cou.course_number, 1 AS count
                FROM {$CFG->prefix}block_courseprefs_sections sec,
                     {$CFG->prefix}block_courseprefs_courses cou,
                     {$CFG->prefix}block_courseprefs_semesters sem, 
                     {$CFG->prefix}block_courseprefs_teachers t
                WHERE t.sectionsid = sec.id 
                  AND t.usersid = {$this->id} 
                  AND sec.semestersid = sem.id 
                  AND sec.coursesid = cou.id
                  AND t.primary_flag = 1
                  AND (t.status = 'enroll' OR t.status = 'enrolled')
                  AND sec.status != 'unwant' 
                  AND sec.status != 'unwanted'
                ORDER BY {$order}";

        $results = get_records_sql($sql);
        $this->courses_valid = array();

        if ($apply_count) {
            $temp = array();
            $key = '';
            foreach($results as $sectionsid => $section) {
                $new_key = "{$section->semestersid}|{$section->coursesid}";
                if ($key != $new_key) {
                    $key = $new_key;
                    $temp[$new_key] = $section;
                } else {
                    $temp[$new_key]->count = 1 + $temp[$new_key]->count;
                }
            }
            $results = $temp;
        }

        if ($apply_semester_filter) {
            $temp = array();
            $semesters = $this->getCoursesPerSemester();
            foreach($results as $sectionsid => $section) {
                if ($semesters[$section->semestersid] <= 1) {
                    continue;
                }
                $temp[] = $section;
            }
            $results = $temp;
        }

        foreach ($results as $sectionsid => $section) {
            if ($apply_count && $section->count <= $limiter) {
                continue;
            }

            if (!empty($filters)) {
                foreach ($filters as $filter) {
                    //$where = "WHERE f.sectionsid = {$section->id} ";
                    //$course = '';
                    //if ($apply_count) {
                        $course = ", {$CFG->prefix}block_courseprefs_sections sec";
                        $where = "WHERE f.sectionsid = sec.id
                                    AND sec.coursesid = {$section->coursesid}
                                    AND sec.semestersid = {$section->semestersid}";
                    //}

                    // Fix this
                    $sql = "SELECT * FROM 
                                {$CFG->prefix}block_courseprefs_{$filter} f
                                $course
                            $where
                              AND f.status != 'undo'
                              AND f.usersid = {$this->id}";
                    if (get_records_sql($sql)) {
                        continue 2;
                    }
                }
            }

            if ($generate_keys) {
                if(!array_key_exists($section->semestersid, $this->courses_valid)) {
                    $this->courses_valid[$section->semestersid] = array();
                }

                $this->courses_valid[$section->semestersid][$section->coursesid] = $section;
            } else {
                $this->courses_valid[$section->id] = $section;
            }
        }
        return $this->courses_valid;
    }

    function getCoursesPerSemester($semester = null) {
        global $CFG;
        
        $sem = "";
        if (is_array($semester)) {
            $sem = "AND sec.semestersid IN (".implode(',', $semester).")";
        } else if(is_numeric($semester)){
            $sem = "AND sec.semestersid = {$semester}";
        }
        
        $sql = "SELECT sec.semestersid, COUNT(cou.id) as count
                    FROM {$CFG->prefix}block_courseprefs_sections sec,
                         {$CFG->prefix}block_courseprefs_courses cou,
                         {$CFG->prefix}block_courseprefs_teachers t
                    WHERE sec.coursesid = cou.id
                      AND t.sectionsid = sec.id
                      AND t.usersid = {$this->id}
                      AND t.primary_flag = 1
                      AND (t.status = 'enroll' OR t.status = 'enrolled')
                      AND sec.status != 'unwanted'
                      {$sem}
                 GROUP BY sec.semestersid";
        $results = get_records_sql_menu($sql);
        return $results;
    }

    function getCrosslistableSections($refresh = false, $generate_keys=false) {

        if (!$refresh && $this->courses_crosslistable) {
            return $this->courses_crosslistable;
        }

        $filters = array('split', 'teamteach');
        $this->courses_crosslistable = $this->getValidSections($filters, true, $generate_keys, 0, true);
        return $this->courses_crosslistable;
    }

    function getTeamteachableSections($refresh = false) {
        
        if (!$refresh && $this->courses_teamteach) {
            return $this->courses_teamteach;
        }
    
        $filters = array('split', 'crosslist');
        $this->courses_teamteach = $this->getValidSections($filters);
        return $this->courses_teamteach;
    }

    /**
     * We only want the courses that we can split with. A teacher is unable to split a course
     * where this is only one section.
     */
    function getSplittableCourses($refresh = false) {

        if (!$refresh && $this->courses_splittable) {
            return $this->courses_splittable;
        }

        $filters = array('crosslist', 'teamteach');
        $this->courses_splittable = $this->getValidSections($filters, true, true, 1);
        return $this->courses_splittable;
    }

    /**
     * Obtains the courses that the user is a teacher of
     */
    function getCoursesAsPrimaryTeacher($refresh = false) {

        if (!$refresh && $this->courses_teacher) {
            return $this->courses_teacher;
        }

        global $CFG;

        $sql = "SELECT DISTINCT c.*
                  FROM {$CFG->prefix}block_courseprefs_courses c,
                       {$CFG->prefix}block_courseprefs_sections s,
                       {$CFG->prefix}block_courseprefs_teachers t,
                       {$CFG->prefix}block_courseprefs_users u
                 WHERE c.id = s.coursesid
                   AND s.id = t.sectionsid
                   AND u.id = t.usersid
                   AND t.primary_flag = 1
                   AND (t.status = 'enroll' OR t.status = 'enrolled')
                   AND u.id = " . addslashes($this->id) . "
              ORDER BY c.department, c.course_number ASC";

        $results = get_records_sql($sql);
        $this->courses_teacher = array();

        foreach ($results as $result) {
            $this->courses_teacher[$result->id] = new CoursePrefsCourse($result->department,
                $result->course_number, $result->fullname, $result->course_type,
                $result->grade_type, $result->first_year,
                $result->exception, $result->legal_writing, $result->id);
        }

        return $this->courses_teacher;
    }


    /**
     * Obtains material courses that the user created
     */ 
    function getMaterials($refresh = false) {

        if (!$refresh && $this->courses_materials) {
            return $this->courses_materials;
        }

        $results = get_records('block_courseprefs_materials', 'usersid', $this->id);
        $this->courses_materials = array();

        if (!empty($results)) {        
            foreach ($results as $result) {
                $this->courses_materials[$result->id] = new CoursePrefsMaterial($result->coursesid,
                    $result->usersid, $result->create_flag, $result->id);
            }
        }
        return $this->courses_materials;
    }

    /**
     * Obtains split courses that the user have created
     */
    function getSplits($refresh = false) {
        global $CFG;

        if (!$refresh && $this->courses_split) {
            return $this->courses_split;
        }

        $sql = "SELECT spl.*, sec.semestersid, sec.coursesid
                    FROM {$CFG->prefix}block_courseprefs_split spl,
                         {$CFG->prefix}block_courseprefs_sections sec
                    WHERE spl.usersid = {$this->id}
                      AND sec.id = spl.sectionsid
                      AND spl.status != 'undo'
                    ORDER BY groupingsid";

        $results = get_records_sql($sql);
        $this->courses_split = array();

        if (!$results) {
            return array();
        }

        foreach ($results as $result) {
            if (!array_key_exists($result->semestersid, $this->courses_split)) {
                $this->courses_split[$result->semestersid] = array();
            }
            if (!array_key_exists($result->coursesid, $this->courses_split[$result->semestersid])) {
                $this->courses_split[$result->semestersid][$result->coursesid] = array();
            }
            $this->courses_split[$result->semestersid][$result->coursesid][$result->sectionsid] = new CoursePrefsSplit(
                $result->usersid, $result->sectionsid, $result->groupingsid, 
                $result->shell_name, $result->status, $result->id);
        }

        return $this->courses_split;
    }

    function getSplitsForCourse($courseid) {
        global $CFG;

        if ($this->courses_split) {
            return $this->courses_split[$courseid];
        }

        $sql = "SELECT spl.*, sec.semestersid, sec.coursesid
                    FROM {$CFG->prefix}block_courseprefs_split spl,
                         {$CFG->prefix}block_courseprefs_sections sec,
                    WHERE spl.usersid = {$this->id}
                      AND sec.id = spl.sectionsid
                      AND sec.coursesid = {$courseid}";

        $results = get_records_sql($sql);

        $splits = array();

        foreach ($results as $result) {
            $splits[$result->id] = new CoursePrefsSplit($result->usersid,
                $result->sectionsid, $result->groupingsid, $result->shell_name,
                $result->status, $result->id);
        }

        return $splits;
    }

    function getSectionsByGroupingsId($semestersid, $courseid, $groupingsid) {
        global $CFG;
        
        $sql = "SELECT sec.*
                    FROM {$CFG->prefix}block_courseprefs_sections sec,
                         {$CFG->prefix}block_courseprefs_split spl
                    WHERE spl.sectionsid = sec.id
                      AND sec.coursesid  = {$courseid}
                      AND spl.usersid    = {$this->id}
                      AND spl.groupingsid= {$groupingsid}
                      AND sec.semestersid= {$semestersid}
                      AND spl.status    != 'undo'";

        $results =  get_records_sql($sql);
        $return_arr = array();
        if ($results) {
            foreach ($results as $result) {
                $return_arr[$result->id] = new CoursePrefsSection($result->semestersid,
                $result->coursesid, $result->section_number, $result->status,
                $result->id, $result->idnumber);
            }
        }
        return $return_arr;
    }

    function getSplitGroupnumber($courseid) {
        global $CFG;
        
        $sql = "SELECT groupingsid
                    FROM {$CFG->prefix}block_courseprefs_split spl,
                         {$CFG->prefix}block_courseprefs_sections sec
                        WHERE spl.usersid={$this->id}
                          AND sec.id = spl.sectionsid
                          AND sec.coursesid={$courseid}
                          AND spl.status != 'undo'
                        ORDER BY spl.groupingsid DESC";
        $record = get_record_sql($sql, true);
        return $record->groupingsid;
    }

    /**
     * Obtains unwanted courses that the user no longer wishes to teach
     */
    function getUnwanted($refresh = false) {
        global $CFG;

        if (!$refresh && $this->unwanted) {
            return $this->unwanted;
        }

        $sql = "SELECT sec.* from {$CFG->prefix}block_courseprefs_sections sec,
                                  {$CFG->prefix}block_courseprefs_teachers t
                WHERE sec.id = t.sectionsid
                  AND t.usersid = {$this->id}
                  AND t.primary_flag = 1
                  AND (sec.status = 'unwanted' 
                    OR sec.status = 'unwant')";

        $results = get_records_sql($sql);
        $this->unwanted = array();

        if (!empty($results)) {
            foreach ($results as $result) {
                $this->unwanted[$result->id] = new CoursePrefsSection($result->semestersid, 
                    $result->coursesid, $result->section_number, $result->status, 
                    $result->id, $result->idnumber);
            }
        }
        return $this->unwanted;
    }

    /**
     * Obtains sections that have been crosslisted
     */
    function getCrosslists($refresh = false, $semester_keys=false) {
        global $CFG;

        if (!$refresh && $this->courses_crosslists) {
            return $this->courses_crosslists;
        }

        $sql = "SELECT cr.*, sec.semestersid, sec.coursesid, cou.department, cou.course_number,
                    sec.section_number
                    FROM {$CFG->prefix}block_courseprefs_sections sec,
                         {$CFG->prefix}block_courseprefs_courses cou,
                         {$CFG->prefix}block_courseprefs_crosslist cr
                WHERE cr.usersid = {$this->id}
                  AND cr.sectionsid = sec.id
                  AND sec.coursesid = cou.id
                  AND cr.status != 'undo'";


        $results = get_records_sql($sql);
        $this->courses_crosslists = array();

        if(!$results) {
            return array();
        }

        foreach ($results as $result) {
            if ($semester_keys){
                $this->courses_crosslists[$result->semestersid][$result->coursesid][$result->sectionsid] = $result; 
            } else {
                $this->courses_crosslists[$result->sectionsid] = new CoursePrefsCrosslist($result->usersid,
                    $result->sectionsid, $result->status, $result->shell_name, $result->idnumber,
                    $result->id);
            }
        }

        return $this->courses_crosslists;
    }

    function getCrosslistsByClNumber($semester, $number) {
        global $CFG;

        $semester_name = "{$semester->year}{$semester->name}";
        $idnumber = "{$semester_name}{$this->username}cl{$number}";
        
        $sql = "SELECT sec.*, sem.year, sem.name, cou.department, cou.course_number,
                      cr.shell_name, cr.idnumber AS cr_id
                    FROM {$CFG->prefix}block_courseprefs_crosslist cr,
                         {$CFG->prefix}block_courseprefs_sections sec,
                         {$CFG->prefix}block_courseprefs_courses  cou,
                         {$CFG->prefix}block_courseprefs_semesters sem
                    WHERE cr.sectionsid = sec.id
                      AND sec.coursesid = cou.id
                      AND sec.semestersid = sem.id
                      AND cr.status != 'undo'
                      AND cr.idnumber = '{$idnumber}'";
        $results = get_records_sql($sql);

        $return = array();
        if (!empty($results)) {
            foreach ($results as $result) {
                $return[$result->cr_id][$result->id] = $result;
            }
        }
        return $return;
    }

    function findCrosslistNumber($semestersid, $cr_courses, $last=false) {
        global $CFG;
        
        $filter = '';
        if (!$last) {
            $courses = implode(',', $cr_courses);
            $filter = "AND sec.coursesid IN ({$courses})";
        }

        $sql = "SELECT COUNT(DISTINCT(cr.idnumber))
                    FROM {$CFG->prefix}block_courseprefs_crosslist cr,
                         {$CFG->prefix}block_courseprefs_sections sec,
                         {$CFG->prefix}block_courseprefs_teachers t
                    WHERE sec.id = cr.sectionsid
                      AND t.sectionsid = sec.id
                      AND t.usersid = {$this->id}
                      $filter
                      AND sec.semestersid = {$semestersid}";

        return count_records_sql($sql);
    }

    function findClNumbers($semestersid, $courses=null) {
        global $CFG;

        $inner = '';
        if ($courses != null && is_array($courses)) {
            $courseids = implode(',', $courses);
            $inner = "AND sec.coursesid IN ({$courseids})";
        }

        $sql = "SELECT DISTINCT(cr.idnumber)
                    FROM {$CFG->prefix}block_courseprefs_crosslist cr,
                         {$CFG->prefix}block_courseprefs_sections sec
                    WHERE sec.id = cr.sectionsid
                      AND sec.semestersid = {$semestersid}
                      {$inner} 
                      AND cr.usersid = {$this->id}
                    ORDER BY cr.idnumber";

        $results = get_records_sql($sql);

        // Crosslist doesn't exist
        if (!$results) {
            return array();
        }

        $idnumbers = array();
        foreach ($results as $result) {
            $idnumbers[] = $result->idnumber[strlen($result->idnumber) - 1];
        }

        return $idnumbers;
    }

    function getTeamTeaches($request = true) {
        global $CFG;
        
        if ($request) {
            $sql_join = ", {$CFG->prefix}block_courseprefs_teachers teach";
            $sql_on   = "AND team.tt_sectionsid  = teach.sectionsid
                         AND teach.primary_flag  = 1
                         AND teach.approval_flag = 1";
        }

        $sql = "SELECT * FROM
                    {$CFG->prefix}block_courseprefs_teamteach team
                    {$sql_join}
                WHERE team.usersid = {$this->id}
                    {$sql_on}
                  AND team.status != 'undo'";
        
        $results = get_records_sql($sql);
        $teamteaches = array();
        
        if ($results) {
            foreach ($results as $result) {
                $teamteaches[$result->id] = new CoursePrefsTeamTeach($result->usersid, 
                    $result->sectionsid, $result->tt_sectionsid, $result->status,
                    $result->approval_flag, $result->id);
            }
        }
        return $teamteaches;
    }

    /**
     * Returns an array of CoursePrefsTeamTeach objects the user has created.  These objects
     * are regardless of whether the team teaching invitation needs to be approved or not.
     */
    function getRequestedTeamTeaches() {
        global $CFG;

        $sql = "SELECT * FROM
                    {$CFG->prefix}block_courseprefs_teamteach
                WHERE usersid  = {$this->id}
                  AND status  != 'undo'";

        $results = get_records_sql($sql);
        $teamteaches = array();

        // Return an empty array if there are no records to process
        if (!$results) {
            return $teamteaches;
        }

        foreach ($results as $result) {
            $teamteaches[$result->id] = new CoursePrefsTeamTeach($result->usersid, 
                $result->sectionsid, $result->tt_sectionsid, $result->status,
                $result->approval_flag, $result->id);
        }
        return $teamteaches;
    }

    /**
     * Returns an array of CoursePrefsTeamTeach objects where the user is the primary teacher of the
     * sections which need to accept or reject a team teaching invitation.
     */
    function getDecisionTeamTeaches() {

        global $CFG;

        $sql = "SELECT team.*
                  FROM {$CFG->prefix}block_courseprefs_teamteach team,
                       {$CFG->prefix}block_courseprefs_teachers teach
                 WHERE team.tt_sectionsid = teach.sectionsid
                   AND teach.usersid = " . $this->id . "
                   AND teach.primary_flag = 1
                   AND team.approval_flag = 1
                   AND team.status = 'todo'";

        $results = get_records_sql($sql);
        $teamteaches = array();

        // Return an empty array if there are no records to process
        if (!$results) {
            return $teamteaches;
        }

        foreach ($results as $result) {
            $teamteaches[$result->id] = new CoursePrefsTeamTeach($result->usersid, 
                $result->sectionsid, $result->tt_sectionsid, $result->status,
                $result->approval_flag, $result->id);
        }

        return $teamteaches;
    }

    /**
     * Obtains course creation and enrollment information that the user has customized
     */
    function getEnrolls() {
        $results = get_records('block_courseprefs_enroll', 'usersid', $this->id);
        $enrolls = array();

        if (!empty($results)) {
            foreach ($results as $result) {
                $enrolls[$result->id] = new CoursePrefsEnroll($result->semestersid, 
                    $result->coursesid, $result->usersid, $result->course_create_days, 
                    $result->course_enroll_days, $result->id);
            }
        }
        return $enrolls;
    }

    /**
     * Returns true if the user is a primary instructor of that section
     */
    function isPrimaryTeacher(CoursePrefsSection $section) {
        return $this->sections_primary[$section->getId()];
    }

    function isTeacher($idnumber=null) {
        global $CFG;

        if (!$this->id) {
            return false;
        }

        $filter = '';
        $join = '';
        if ($idnumber) {
            $join = ", {$CFG->prefix}block_courseprefs_sections sec ";
            $filter = " AND sec.id = t.sectionsid AND sec.idnumber='{$idnumber}'";
        }
        $sql = "SELECT COUNT(t.id) FROM {$CFG->prefix}block_courseprefs_teachers t
                        {$join}
                WHERE t.usersid={$this->id}
                   {$filter}
                   AND (t.status = 'enrolled' OR t.status = 'enroll')";
        return (count_records_sql($sql) > 0 ? true : false);
    }

    /**
     * Stores this instance in a database; if id exists, it updates the entry in the database
     */
    function save() {

        $record = new stdClass;
        $record->username = $this->username;
        $record->firstname = addslashes($this->firstname);
        $record->lastname = addslashes($this->lastname);
        $record->idnumber = $this->idnumber;
        $record->update_flag = $this->update_flag;
        $record->reg_status = $this->reg_status;
        $record->college = $this->college;
        $record->year = $this->year;
        $record->classification = $this->classification;
        $record->keypadid = $this->keypadid;
        $record->ferpa= $this->ferpa;
        $record->anonymous = $this->anonymous;
        $record->degree_candidacy = $this->degree_candidacy;

        if ($this->hidden != null) {
            $record->hidden = $this->hidden;
        }
        if ($this->numsections != null) {
            $record->numsections = $this->numsections;
        }
        if ($this->format != null) {
            $record->format= $this->format;
        }
        if ($this->cr_delete != null) {
            $record->cr_delete = $this->cr_delete;
        }
        if ($this->moodleid != null) {
            $record->moodleid = $this->moodleid;
        }

        if (!$this->id) {

            $this->id = insert_record('block_courseprefs_users', $record, true);

            if (!$this->id) {
                throw new Exception('Unable to create new courseprefs user within database');
            }

        } else {

            $record->id = $this->id;

            if (!update_record('block_courseprefs_users', $record)) {
                throw new Exception('Unable to update existing courseprefs user within database');
            }
        }
    }

    /**
     * Class method that finds and instaniates a user based on the id provided
     */ 
    static function findById($id) {

        $result = get_record('block_courseprefs_users', 'id', $id);

        if (!$result) {
            return null;
        }

        return new CoursePrefsUser($result->username, $result->firstname, $result->lastname,
            $result->idnumber, $result->year, $result->college, $result->reg_status, 
            $result->classification, $result->keypadid, $result->ferpa, 
            $result->degree_candidacy, $result->anonymous, $result->format, 
            $result->numsections, $result->hidden, $result->cr_delete, $result->id,
            $result->update_flag, $result->moodleid);
    }

    /**
     * Class method that finds and instantiates a user based on the username provided
     */
    static function findByUnique($username) {

        $result = get_record('block_courseprefs_users', 'username', $username);

        if (!$result) {
            return null;
        }

        return new CoursePrefsUser($result->username, $result->firstname, $result->lastname,
            $result->idnumber, $result->year, $result->college, $result->reg_status, 
            $result->classification, $result->keypadid, $result->ferpa, 
            $result->degree_candidacy, $result->anonymous, $result->format, 
            $result->numsections, $result->hidden, $result->cr_delete, $result->id, 
            $result->update_flag, $result->moodleid);
    }

    /**
     * Class method that finds and instantiates a user based on the username provided
     */
    static function findByIdnumber($idnumber) {

        $result = get_record('block_courseprefs_users', 'idnumber', $idnumber);

        if (!$result) {
            return null;
        }

        return new CoursePrefsUser($result->username, $result->firstname, $result->lastname,
            $result->idnumber, $result->year, $result->college, $result->reg_status, 
            $result->classification, $result->keypadid, $result->ferpa, 
            $result->degree_candidacy, $result->anonymous, $result->format, 
            $result->numsections, $result->hidden, $result->cr_delete, $result->id, 
            $result->update_flag, $result->moodleid);
    }

    /**
     * Class Method that finds and removes the user entry from the database
     */
    static function deleteById($id) {
        delete_records('block_courseprefs_users', 'id', $id);
    }

    /**
     * Deprecated
     */
    static function findByUsername($username) {
        return CoursePrefsUser::findByUnique($username);
    }
}

?>
