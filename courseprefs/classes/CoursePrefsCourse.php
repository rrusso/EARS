<?php

require_once('CoursePrefsBase.php');

/**
 * Class representation of a course from the courses db table
 **/
class CoursePrefsCourse implements CoursePrefsBase {

    /**
     * Private properties 
     */
    private $id;
    private $department;
    private $course_number;
    private $fullname;
    private $course_type;
    private $grade_type;
    private $first_year;
    private $exception;
    private $legal_writing;

    /**
     * Constructor
     * If id is null, when save() is called on the object, a new record
     * is created, else the record with that id is updated
     */
    function __construct($department, $course_number, $fullname, 
                         $course_type="LEC", 
                         $grade_type="N", 
                         $first_year=0, $exception=0, $legal_writing=0, $id = null) {
        $this->id = $id;
        $this->department = $department;
        $this->course_number = $course_number;
        $this->fullname = $fullname;
        $this->course_type = $course_type;
        $this->grade_type = $grade_type;
        $this->first_year = $first_year; 
        $this->exception = $exception;
        $this->legal_writing = $legal_writing;
    }

    /**
     * Property Getters
     */
    function getCourseNumber() {
        return $this->course_number;
    }

    function getDepartment() {
        return $this->department;
    }

    function getFullname() {
        return $this->fullname;
    }

    function getCourseType() {
        return $this->course_type;
    }

    function getGradeType() {
        return $this->grade_type;
    }

    function getFirstYear() {
        return $this->first_year;
    }

    function getException() {
        return $this->exception;
    }

    function getLegalWriting() {
        return $this->legal_writing;
    }

    function getId() {
        return $this->id;
    }

    /**
     * Property Setters
     */
    function setCourseNumber($course_number) {
        $this->course_number = $course_number;
    }

    function setDepartment($department) {
        $this->department = $department;
    }

    function setFullname($fullname) {
        $this->fullname = $fullname;
    }

    function setCourseType($course_type) {
        $this->course_type = $course_type;
    }

    function setGradeType($grade_type) {
        $this->grade_type = $grade_type;
    }

    function setFirstYear($first_year) {
        $this->first_year = $first_year;
    }

    function setException($exception) {
        $this->exception = $exception;
    }

    function setLegalWriting($legal_writing) {
        $this->legal_writing = $legal_writing;
    }

    function setId($id) {
        $this->id = $id;
    }

    /**
     * Saves this course instance to the database.
     * Decides to insert or update based on whether or not id is set
     */
    function save() {

        $record = new stdClass;
        $record->department = $this->department;
        $record->course_number = $this->course_number;
        $record->fullname = $this->fullname;
        $record->course_type = $this->course_type;
        $record->grade_type = $this->grade_type;
        $record->first_year = $this->first_year;
        $record->exception = $this->exception;
        $record->legal_writing = $this->legal_writing;

        if (!$this->id) {

            $this->id = insert_record('block_courseprefs_courses', $record, true);

            if (!$this->id) {
                throw new Exception('Unable to create new courseprefs course within database');
            }

        } else {

            $record->id = $this->id;

            // #202: Escape apostrophe's in fullname
            $record->fullname = addslashes($this->fullname);

            if (!update_record('block_courseprefs_courses', $record)) {
                throw new Exception('Unable to update new courseprefs course within database');
            }
        }
    }

    /**
     * Class method that finds, instantiates, and returns a course 
     * based on the id provided
     */
    static function findById($id) {

        $result = get_record('block_courseprefs_courses', 'id', $id);

        if (!$result) {
            return null;
        }

        return new CoursePrefsCourse($result->department, $result->course_number, $result->fullname,
            $result->course_type, $result->grade_type, $result->first_year, $result->exception,
            $result->legal_writing, $result->id);
    }

    /**
     * Class method that finds, instantiates, and returns a course based on 
     * it's unique properties, in this case, department and course_number
     */
    static function findByUnique($department, $course_number) {

        $result = get_record('block_courseprefs_courses', 'department', $department, 'course_number', $course_number);

        if (!$result) {
            return null;
        }

        return new CoursePrefsCourse($result->department, $result->course_number, $result->fullname,
            $result->course_type, $result->grade_type, $result->first_year, $result->exception,
            $result->legal_writing, $result->id);
    }

    /**
    * Class method that finds and removes a course from the database
    * based on the id provided
    */
    static function deleteById($id){
        delete_records('block_courseprefs_courses', 'id', $id);
    }

}

?>
