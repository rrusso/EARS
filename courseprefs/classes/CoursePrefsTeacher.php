<?php

require_once('CoursePrefsBase.php');

/**
 * Class representation of the mdl_blocks_courseprefs_teacher db table
 */
class CoursePrefsTeacher implements CoursePrefsBase {

    /**
     *
     */
    const STATUS_ENROLLED = 'enrolled';
    const STATUS_ENROLL = 'enroll';
    const STATUS_UNENROLLED = 'unenrolled';
    const STATUS_UNENROLL = 'unenroll';

    /**
     * Object Properties
     */
    private $id;
    private $sectionsid;
    private $status;
    private $usersid;
    private $primary_flag;
    private $timestamp;

    /**
     * Constructor
     */
    function __construct($sectionsid, $usersid, $primary_flag = false, $status = self::STATUS_ENROLL, $timestamp=null, $id = null) {
        $this->id = $id;
        $this->sectionsid = $sectionsid;
        $this->setStatus($status);
        $this->usersid = $usersid;
        $this->primary_flag = $primary_flag;
        $this->status = $status;
        $this->timestamp = $timestamp;
    }

    /**
     * Getters
     */
    function getId() {
        return $this->id;
    }

    function getPrimaryFlag() {
        return $this->primary_flag;
    }

    function getSectionsId() {
        return $this->sectionsid;
    }

    function getStatus() {
        return $this->status;
    }

    function getUsersId() {
        return $this->usersid;
    }

    function getTimeStamp() {
        return $this->timestamp;
    }

    /**
     * Setters
     */
    function setId($id) {
        $this->id = $id;
    }

    function setPrimaryFlag($primary_flag) {
        $this->primary_flag = $primary_flag;
    }

    function setSectionsId($sectionsid) {
        $this->sectionsid = $sectionsid;
    }

    function setStatus($status) {
        $this->status = $status;
    }

    function setUsersId($usersid) {
        $this->usersid = $usersid;
    }

    function setTimeStamp($timestamp) {
        $this->timestamp = $timestamp;
    }
    /**
     * Stores the instance in the database if the id is null; otherwise updates existing entry in the database
     */
    function save() {

        $record = new stdClass;
		$record->primary_flag = ($this->primary_flag ? 1 : 0);
		$record->sectionsid = $this->sectionsid;
		$record->status = $this->status;
		$record->usersid = $this->usersid;
        $record->timestamp = $this->timestamp;

        if (!$this->id) {

            $this->id = insert_record('block_courseprefs_teachers', $record, true);

            if (!$this->id) {
                throw new Exception('Unable to create new courseprefs teacher within database');
            }

        } else {

            $record->id = $this->id;

            if (!update_record('block_courseprefs_teachers', $record)) {
                throw new Exception('Unable to update new courseprefs teacher within database');
            }
        }
    }

    /**
     * Finds and instanitates a Teacher object based on the id provided
     */
    static function findById($id) {

        $result = get_record('block_courseprefs_teachers', 'id', $id);

        if (!$result) {
            return null;
        }

        return new CoursePrefsTeacher($result->sectionsid, $result->usersid, $result->primary_flag,
            $result->status, $result->timestamp, $result->id);
    }

    /**
     * Find and instaniates a Teacher object based on the usersid and sectionsid provided.
     * Plays a big part in teamteach
     */
    static function findByUnique($usersid, $sectionsid, $primary_flag) {

        $result = get_record('block_courseprefs_teachers', 
                             'usersid', $usersid, 
                             'sectionsid', $sectionsid,
                             'primary_flag', $primary_flag);

        if (!$result) {
            return null;
        }

        return new CoursePrefsTeacher($result->sectionsid, $result->usersid, $result->primary_flag,
            $result->status, $result->timestamp, $result->id);

    }

    /**
     * Finds and removes a Teacher entry from the database
     */
    static function deleteById($id){
        delete_records('block_courseprefs_teachers', 'id', $id);
    }

    /**
     * Retrieve an array of teachers associated with a Section.
     */
    static function findBySectionId($sectionsid) {

        $teachers = array();
        $results = get_records('block_courseprefs_teachers', 'sectionsid', $sectionsid);

        foreach ($results as $result) {
            $teachers[$result->id] = new CoursePrefsTeacher($result->sectionsid, $result->usersid,
                $result->primary_flag, $result->status, $result->timestamp, $result->id);
        }
 
        return $teachers;
    }

    /*
    static function findEnrolledBySectionId($section_id) {

        $teachers = array();
        $results = self::findBySectionId($section_id);

        // Iterate over teachers and remove non-enrolled teachers from list
        foreach ($results as $id => $result) {

            // Skip anyone who isn't enrolled
            if ($result->status != 'enrolled') {
                continue;
            }

            $teachers[$id] = $result;
        }

        return $teachers; 
    }*/
}

?>
