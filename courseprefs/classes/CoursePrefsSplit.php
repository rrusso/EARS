<?php

/*
 * @author: Adam C. Zapletal
 */

require_once('CoursePrefsBase.php');

/**
 * Class representation of a split entry from the splits db table
 **/
class CoursePrefsSplit implements CoursePrefsBase {

    /**
     * Private properties 
     */
    private $id;
    private $usersid;
    private $status;
    private $sectionsid;
    private $groupingsid;
    private $shell_name;

    /**
     * Constructor
     * If id is null, when save() is called on the object, a new record
     * is created, else the record with that id is updated
     */
    function __construct($usersid, $sectionsid, $groupingsid, $shell_name=null, $status='todo', $id = null) {
        $this->id = $id;
        $this->usersid = $usersid;
        $this->sectionsid = $sectionsid;
        $this->groupingsid = $groupingsid;
        $this->status = $status;
        $this->shell_name = $shell_name;
    }

    /**
     * Property Getters
     */
    function getUsersId() {
        return $this->usersid;
    }

    function getId() {
        return $this->id;
    }

    function getSectionsId() {
        return $this->sectionsid;
    }

    function getGroupingsId() {
        return $this->groupingsid;
    }

    function getStatus() {
        return $this->status;
    }

    function getShellName() {
        return $this->shell_name;
    }

    /**
     * Property Setters
     */
    function setUsersId($usersid) {
        $this->usersid = $usersid;
    }

    function setId($id) {
        $this->id = $id;
    }

    function setStatus($status) {
        $this->status = $status;
    }

    function setSectionsId($sectionsid) {
        $this->sectionsid = $sectionsid;
    }

    function setGroupingsId($groupingsid) {
        $this->groupingsid = $groupingsid;
    }

    function setShellName($shell_name) {
        $this->shell_name = $shell_name;
    }

    /**
     *
     * Saves this split instance to the database.
     * Decides to insert or update based on whether or not id is set
     */
    function save() {

        $record = new stdClass;
        $record->usersid = $this->usersid;
        $record->groupingsid = $this->groupingsid;
        $record->sectionsid = $this->sectionsid;
        $record->status = $this->status;
        $record->shell_name = $this->shell_name;

        if (!$this->id) {

            $this->id = insert_record('block_courseprefs_split', $record, true);

            if (!$this->id) {
                throw new Exception('Unable to create new courseprefs split course within database');
            }

        } else {

            $record->id = $this->id;

            if (!update_record('block_courseprefs_split', $record)) {
                throw new Exception('Unable to update new courseprefs split course within database');
            }
        }

        CoursePrefsLog::add_to_log($this->usersid, $this->sectionsid, time(), 'split', $this->status);
    }

    /**
     * Class method that finds, instantiates, and returns a split 
     * based on the id provided
     */
    static function findById($id) {

        $result = get_record('block_courseprefs_split', 'id', $id);

        if (!$result) {
            return null;
        }

        $split = new CoursePrefsSplit($result->usersid, $result->sectionsid,
            $result->groupingsid, $result->shell_name, $result->status, $result->id);

        return $split;
    }

    /**
     * Class method that finds, instantiates, and returns a split based on 
     * it's unique properties, in this case, coursesid
     */
    static function findByUnique($sectionsid, $groupingsid, $status) {

        global $CFG;

        $sql = "SELECT * FROM {$CFG->prefix}block_courseprefs_split
                    WHERE sectionsid={$sectionsid}
                      AND groupingsid={$groupingsid}
                      AND status='{$status}'";
        $result = get_record_sql($sql);

        if (!$result) {
            return null;
        }

        return new CoursePrefsSplit($result->usersid, $result->sectionsid,
            $result->groupingsid, $result->shell_name, $result->status, $result->id);
    }

    /**
     * Class method that finds and removes a split from the database
     * based on the id provided
     */
    static function deleteById($id) {
        $split = get_record('block_courseprefs_split', 'id', $id);

        // Logging that they've removed a split entry
        CoursePrefsLog::add_to_log($split->usersid, $split->sectionsid, time(), 'reset', 'Split reset');
        // ----------------------
        delete_records('block_courseprefs_split', 'id', $id);
    }
}

?>
