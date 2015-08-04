<?php

/*
 * @author: Adam C. Zapletal
 */

require_once('CoursePrefsBase.php');

/**
 * Class representation of an enrollment preferences entry from the enroll db table
 **/
class CoursePrefsEnroll implements CoursePrefsBase {

    /**
     * Private properties
     */
    private $id;
    private $semestersid;
    private $coursesid;
    private $usersid;
    private $course_create_days;
    private $course_enroll_days;

    /**
     * Constructor
     * If id is null, when save() is called on the object, a new record
     * is created, else the record with that id is updated
     */
    function __construct($semestersid, $coursesid, $usersid, $course_create_days, $course_enroll_days, $id = null) {
        $this->id = $id;
        $this->semestersid = $semestersid;
        $this->coursesid = $coursesid;
        $this->usersid = $usersid;
        $this->course_create_days = $course_create_days;
        $this->course_enroll_days = $course_enroll_days;
    }

    /**
     * Property Getters
     */
    function getUsersId() {
        return $this->usersid;
    }

    function getSemestersId() {
        return $this->semestersid;
    }
    
    function getCoursesId() {
        return $this->coursesid;
    }

    function getCourseCreateDays() {
        return $this->course_create_days;
    }

    function getCourseEnrollDays() {
        return $this->course_enroll_days;
    }

    function getId() {
        return $this->id;
    }

    /**
     * Property Setters
     */
    function setUsersId($usersid) {
        $this->usersid = $usersid;
    }

    function setSemestersId($semestersid) {
        $this->semestersid = $semestersid;
    }

    function setCoursesId($coursesid) {
        $this->coursesid = $coursesid;
    }

    function setCourseCreateDays($course_create_days) {
        $this->course_create_days = $course_create_days;
    }

    function setCourseEnrollDays($Course_enroll_days) {
        $this->course_enroll_days = $course_enroll_days;
    }

    function setId($id) {
        $this->id = $id;
    }

    /**
     * Saves this enroll instance to the database.
     * Decides to insert or update based on whether or not id is set
     */
    function save() {

        $record = new stdClass;
        $record->semestersid = $this->semestersid;
        $record->coursesid = $this->coursesid;
        $record->usersid = $this->usersid;
        $record->course_create_days = $this->course_create_days;
        $record->course_enroll_days = $this->course_enroll_days;

        if (!$this->id) {

            $this->id = insert_record('block_courseprefs_enroll', $record, true);

            if (!$this->id) {
                throw new Exception('Unable to create new courseprefs creation enroll entry within database');
            }

        } else {

            $record->id = $this->id;

            if (!update_record('block_courseprefs_enroll', $record)) {
                throw new Exception('Unable to update courseprefs creation enroll entry within database');
            }
        }
    }

    /**
     * Class method that finds, instantiates, and returns an enroll based on 
     * the id provided
     */
    static function findById($id) {

        $result = get_record('block_courseprefs_enroll', 'id', $id);

        if (!$result) {
            return null;
        }

        $enroll = new CoursePrefsEnroll($result->semestersid, $result->coursesid, $result->usersid, $result->id, 
            $result->course_create_days, $result->course_enroll_days);

        return $enroll;
    }

    /**
     * Class method that finds, instantiates, and returns an enroll based on 
     * its unique properties, in this case, semestersid, coursesid, and usersid
     */
    static function findByUnique($semestersid, $coursesid, $usersid) {

        $result = get_record('block_courseprefs_enroll', 'semestersid', $semestersid, 'coursesid',
            $coursesid, 'usersid', $usersid);

        if (!$result) {
            return null;
        }

        return new CoursePrefsEnroll($result->semestersid, $result->coursesid, $result->usersid,
            $result->course_create_days, $result->course_enroll_days, $result->id);
    }

    /**
    * Class method that finds and removes an enroll from the database
    * based on the id provided
    */
    static function deleteById($id) {
        delete_records('block_courseprefs_enroll', 'id', $id);
    }
}

?>
