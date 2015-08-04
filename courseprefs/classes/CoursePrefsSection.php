<?php

require_once('CoursePrefsBase.php');
require_once('CoursePrefsTeacher.php');

/**
 * Class representation of the mdl_blocks_courseprefs_sections db table
 */
class CoursePrefsSection implements CoursePrefsBase {

    const STATUS_PENDING = 'pending';
    const STATUS_COMPLETED = 'completed';
    const STATUS_REQUESTED = 'requested';
    const STATUS_UNWANT = 'unwant';
    const STATUS_UNWANTED = 'unwanted';

    private $id;
    private $semestersid;
    private $coursesid;
    private $section_number;
    private $idnumber;
    private $status;
    private $unwanted;
    private $teachers;
    private $timestamp;

    /**
     * Construtor
     */
    function __construct($semestersid, $coursesid, $section_number, $status=self::STATUS_PENDING, 
            $id = null, $idnumber = null, $timestamp=null) {
        $this->id = $id;
        $this->semestersid = $semestersid;
        $this->coursesid = $coursesid;
        $this->section_number = $section_number;
        $this->idnumber = $idnumber;
        $this->status = $status;
        $this->timestamp = $timestamp;
    }

    /**
     * Getters
     */
    function getCoursesId() {
        return $this->coursesid;
    }

    function getId() {
        return $this->id;
    }

    function getIdNumber() {
        return $this->idnumber;
    }

    function getSectionNumber() {
        return $this->section_number;
    }

    function getSemestersId() {
        return $this->semestersid;
    }

    function getStatus() {
        return $this->status;
    }

    function getTimeStamp() {
        return $this->timestamp;
    }

    /**
     * Setters
     */
    function setCoursesId($coursesid) {
        $this->coursesid = $coursesid;
    }

    function setId($id) {
        $this->id = $id;
    }

    function setIdNumber($idnumber) {
        $this->idnumber = $idnumber;
    }

    function setSectionNumber($section_number) {
        $this->section_number = $section_number;
    }

    function setSemestersId($semestersid) {
        $this->semestersid = $semestersid;
    }

    function setStatus($status) {
        $this->status = $status;
    }

    function setTimeStamp($timestamp) {
        $this->timestamp = $timestamp;
    }

    /**
     * if null is null, the object is stored in the database; otherwise existing entry is updated
     */
    function save() {

        $record = new stdClass;
        $record->semestersid = $this->semestersid;
        $record->coursesid = $this->coursesid;
        $record->section_number = $this->section_number;
        $record->idnumber = $this->idnumber;
        $record->status = $this->status;
        $record->timestamp = $this->timestamp;

        if (!$this->id) {

            $this->id = insert_record('block_courseprefs_sections', $record, true);

            if (!$this->id) {
                throw new Exception('Unable to create new courseprefs section within database');
            }

        } else {

            $record->id = $this->id;

            if (!update_record('block_courseprefs_sections', $record)) {
                throw new Exception('Unable to update new courseprefs section within database');
            }
        }
    }

    /**
     * Finds and removed an entry in the database based on the id provided
     */
    static function deleteById($id){
        delete_records('block_courseprefs_sections', 'id', $id);
    }

    /**
     * Finds and instantiates an object based on the id provided
     */
    static function findById($id) {

        $result = get_record('block_courseprefs_sections', 'id', $id);

        if (!$result) {
            return null;
        }

        return new CoursePrefsSection($result->semestersid, $result->coursesid, $result->section_number,
            $result->status, $result->id, $result->idnumber, $result->timestamp);
    }

    static function findSectionInfo($sectionsid) {
        global $CFG;

        $sql = "SELECT sec.*, cou.department, cou.course_number,
                       sem.name, sem.year
                    FROM {$CFG->prefix}block_courseprefs_sections sec,
                         {$CFG->prefix}block_courseprefs_courses cou,
                         {$CFG->prefix}block_courseprefs_semesters sem
                    WHERE sec.id = {$sectionsid}
                      AND cou.id = sec.coursesid
                      AND sem.id = sec.semestersid";

        $result = get_record_sql($sql);
        return $result;
    }

    /**
     * Finds and instantiates an object based on the semestersid, coursesid, and section number provided
     */
    static function findByUnique($semestersid, $coursesid, $section_number) {

        $result = get_record('block_courseprefs_sections', 'semestersid', $semestersid,
            'coursesid', $coursesid, 'section_number', $section_number);

        if (!$result) {
            return null;
        }

        return new CoursePrefsSection($result->semestersid, $result->coursesid, $result->section_number,
            $result->status, $result->id, $result->idnumber, $result->timestamp);
    }

    /**
     * Generate full display name of a section including course and semester information.
     * Example output: 2008 Fall CSC 1001 001
     */
    static function generateFullname($semester_year, $semester_name, $course_department,
            $course_number, $section_number) {

        return $semester_year . ' ' . $semester_name . ' ' . $course_department . ' ' . $course_number . ' ' . $section_number;
    }

    /**
     * Generate full display name of a section given a section's ID.  Returns the same output as the
     * generateFullname() method.
     * Example output: 2008 Fall CSC 1001 001
     */
    static function generateFullnameById($sectionsid) {

        $section = self::findById($sectionsid);

        if (!$section) {
            return null;
        }

        $course = CoursePrefsCourse::findById($section->getCoursesId());
        $semester = CoursePrefsSemester::findById($section->getSemestersId());

        return self::generateFullname($semester->getYear(), $semester->getName(),
            $course->getDepartment(), $course->getCourseNumber(), $section->getSectionNumber());
    }
}

?>
