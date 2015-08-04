<?php

require_once('CoursePrefsBase.php');

/**
 * Class representation of the mdl_blocks_courseprefs_students db table
 */
class CoursePrefsStudent implements CoursePrefsBase {

    /**
     *
     */
    const STATUS_ENROLLED = 'enrolled';
    const STATUS_ENROLL = 'enroll';
    const STATUS_UNENROLLED = 'unenrolled';
    const STATUS_UNENROLL = 'unenroll';

    /**
     *
     */
    private $id;
    private $sectionsid;
    private $usersid;
    private $credit_hours;
    private $status;

    /**
     * Constructor
     */
    function __construct($sectionsid, $usersid, $status = self::STATUS_ENROLL, $credit_hours=3.00, $id = null) {
        $this->id = $id;
        $this->sectionsid = $sectionsid;
        $this->status = $status;
        $this->usersid = $usersid;
        $this->credit_hours = $credit_hours;
    }

    /**
     * Getters
     */
    function getId() {
        return $this->id;
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

    function getCreditHours() {
        return $this->credit_hours;
    }

    /**
     * Setters
     */
    function setId($id) {
        $this->id = $id;
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

    function setCreditHours($credit_hours) {
        $this->credit_hours = $credit_hours;
    }

    function findHours($status, $hours) {
        $unenrolling = $status == self::STATUS_UNENROLL;
        $hours = ($unenrolling) ? $hours * - 1 : $hours;
        $this->credit_hours += $hours;
        if (empty($hours)) {
            $this->credit_hours = 0.00;
        }

        if ($this->credit_hours <= 0 && $unenrolling) {
            $this->credit_hours = 0.00;
            $this->status = self::STATUS_UNENROLL;
        } else {
            $this->status = self::STATUS_ENROLL;
        }
    }    

    /**
     * if id is null object is stored in the database; otherwise existing entry is updated
     */
    function save() {

        $record = new stdClass;
		$record->sectionsid = $this->sectionsid;
		$record->status = $this->status;
		$record->usersid = $this->usersid;
        $record->credit_hours = $this->credit_hours;

        if (!$this->id) {

            $this->id = insert_record('block_courseprefs_students', $record, true);

            if (!$this->id) {
                throw new Exception('Unable to create new courseprefs student within database');
            }

        } else {

            $record->id = $this->id;

            if (!update_record('block_courseprefs_students', $record)) {
                throw new Exception('Unable to update new courseprefs student within database');
            }
        }
    }

    /**
     * Finds and instantiates an object based on the id provided
     */
    static function findById($id) {

        $result = get_record('block_courseprefs_students', 'id', $id);

        if (!$result) {
            return null;
        }

        return new CoursePrefsStudent($result->sectionsid, $result->usersid, $result->status, 
            $result->credit_hours, $result->id);
    }

    /**
     * Finds and instantiates an dobject based on the sectionsid and userid provided
     */
    static function findByUnique($sectionsid, $usersid) {

        $result = get_record('block_courseprefs_students', 'sectionsid', $sectionsid, 'usersid', $usersid);

        if (!$result) {
            return null;
        }

        return new CoursePrefsStudent($result->sectionsid, $result->usersid, $result->status, 
            $result->credit_hours, $result->id);
    }

    /**
     * Finds and removes an entry based on the id provided
     */
    static function deleteById($id){
        delete_records('block_courseprefs_students', 'id', $id);
    }
}

?>
