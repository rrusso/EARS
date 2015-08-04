<?php

/*
 * @author: Adam C. Zapletal
 */

require_once('CoursePrefsBase.php');
require_once('CoursePrefsTeacher.php');
require_once('CoursePrefsLog.php');

/**
 * Class representation of mdl_blocks_courseprefs_teamteach
 **/
class CoursePrefsTeamTeach implements CoursePrefsBase {

    private $id;
    private $usersid;
    private $sectionsid;
    private $tt_sectionsid;
    private $approval_flag;
    private $status;

    /**
     * Constructor
     */
    function __construct($usersid, $sectionsid, $tt_sectionsid, $status='todo', $approval_flag = 0, $id = null) {
        $this->id = $id;
        $this->usersid = $usersid;
        $this->sectionsid = $sectionsid;
        $this->tt_sectionsid = $tt_sectionsid;
        $this->approval_flag = $approval_flag;
        $this->status = $status;
    }

    /**
     * Getters
     */
    function getUsersId() {
        return $this->usersid;
    }

    function getSectionsId() {
        return $this->sectionsid;
    }

     function getTtSectionsId() {
        return $this->tt_sectionsid;
     }

    function getApprovalFlag() {
        return $this->approval_flag;
    }

    function getId() {
        return $this->id;
    }

    function getStatus() {
        return $this->status;
    }

    /**
     * Convenience method for obtaining the teacher of the tt_section
     */
    function getTtUser(){

        $teachers = CoursePrefsTeacher::findBySectionId($this->tt_sectionsid);

        foreach ($teachers as $teacher) {

            if ($teacher->getPrimaryFlag()) {
                return CoursePrefsUser::findById($teacher->getUsersId());
            }
        } 
    }

    /**
     * Convenience method for obtaining the teacher of the section
     */
    function getSectionsUser() {

        $teachers = CoursePrefsTeacher::findBySectionId($this->sectionsid);

        foreach ($teachers as $teacher) {

            if ($teacher->getPrimaryFlag()) {
                return CoursePrefsUser::findById($teacher->getUsersId());
            }
        }
    }

    /**
     * Setters
     */
    function setUsersId($usersid) {
        $this->usersid = $usersid;
    }

    function setSectionsId($sectionsid) {
        $this->sectionsid = $sectionsid;
    }

    function setTtSectionsId($tt_sectionsid) {
        $this->tt_sectionsid = $tt_sectionsid;
    }

    function setApprovalFlag($approval_flag) {
        $this->approval_flag = $approval_flag;
    }

    function setId($id) {
        $this->id = $id;
    }

    function setStatus($status) {
        $this->status = $status;
    }
    /**
     * Stores an object in the database if the id is null; otherwise existing entry is updated
     */
    function save() {

        $record = new stdClass;
        $record->usersid = $this->usersid;
        $record->sectionsid = $this->sectionsid;
        $record->tt_sectionsid = $this->tt_sectionsid;
        $record->approval_flag = $this->approval_flag;
        $record->status = $this->status;

        if (!$this->id) {

            $this->id = insert_record('block_courseprefs_teamteach', $record, true);

            if (!$this->id) {
                throw new Exception('Unable to create new courseprefs team teach entry within database');
            }

        } else {

            $record->id = $this->id;

            if (!update_record('block_courseprefs_teamteach', $record)) {
                throw new Exception('Unable to update new courseprefs team teach entry within database');
            }
        }

        CoursePrefsLog::add_to_log($this->usersid, $this->sectionsid, time(), 'teamteach', $this->status);
    }

    /**
     * Finds and instantiates an object based on the id provided
     */
    static function findById($id) {

        $result = get_record('block_courseprefs_teamteach', 'id', $id);

        if (!$result) {
            return null;
        }

        return new CoursePrefsTeamTeach($result->usersid, $result->sectionsid, $result->tt_sectionsid,
            $result->status, $result->approval_flag, $result->id);
    }
    
    /**
    * Find and instantiates an object based on the usersid, sectionsid, and tt_sectionsid provided
    */
    static function findByUnique($usersid, $sectionsid, $tt_sectionsid) {
        global $CFG;
        
        $sql = "SELECT * FROM {$CFG->prefix}block_courseprefs_teamteach
                WHERE usersid       = {$usersid}
                  AND sectionsid    = {$sectionsid}
                  AND tt_sectionsid = {$tt_sectionsid}
                  AND status       != 'undo'";

        $result = get_record_sql($sql);
        
        if (!$result) {
            return null;
        }

        return new CoursePrefsTeamTeach($result->usersid, $result->sectionsid, $result->tt_sectionsid,
            $result->status, $result->approval_flag, $result->id);
    }

    /**
     * Finds and removes an entry in the database based on the id provided
     */
    static function deleteById($id) {
        $tt = get_record('block_courseprefs_teamteach', 'id', $id);

        // Add to the logs
        CoursePrefsLog::add_to_log($tt->usersid, $tt->sectionsid, time(), 'reset', 'Teamteach reset');
        delete_records('block_courseprefs_teamteach', 'id', $id);
    }
}

?>
