<?php

require_once('CoursePrefsBase.php');
require_once('CoursePrefsLog.php');

/**
 * Class representation of a crosslist from the crosslist db table
 **/
class CoursePrefsCrosslist implements CoursePrefsBase {

    /*
     * Private properties
     */
    private $id;
    private $usersid;
    private $sectionsid;
    private $status;
    private $shell_name;
    private $idnumber;

    /**
     * Constructor
     * If id is null, when save() is called on the object, a new record
     * is created, else the record with that id is updated
     */
    function __construct($usersid, $sectionsid, $status='todo', $shell_name=null, $idnumber=null, $id = null) {
        $this->id = $id;
        $this->usersid = $usersid;
        $this->sectionsid = $sectionsid;
        $this->status = $status;
        $this->shell_name = $shell_name;
        $this->idnumber = $idnumber;
    }

    /**
     * Property Getters
     */
    function getUsersId() {
        return $this->usersid;
    }

    function getSectionsId() {
        return $this->sectionsid;
    }

    function getId() {
        return $this->id;
    }

    function getStatus() {
        return $this->status;
    }

    function getIdnumber() {
        return $this->idnumber;
    }

    function getShellName() {
        return $this->shell_name;
    }

    /**
     * Property Settters 
     */
    function setUsersId($usersid) {
        $this->usersid = $usersid;
    }

    function setSectionsId($sectionsid) {
        $this->sectionsid = $sectionsid;
    }

    function setId($id) {
        $this->id = $id;
    }

    function setStatus($status) {
        $this->status = $status;
    }

    function setIdnumber($idnumber) {
        $this->idnumber = $idnumber;
    }

    function setShellName($shell_name) {
        $this->shell_name = $shell_name;
    }

    /**
     * Saves this crosslilst instance to the database.
     * Decides to insert or update based on whether of not id is set
     */
    function save() {

        $record = new stdClass;
        $record->usersid = $this->usersid;
        $record->sectionsid = $this->sectionsid;
        $record->status = $this->status;
        $record->shell_name = $this->shell_name;
        $record->idnumber = $this->idnumber;

        if (!$this->id) {

            $this->id = insert_record('block_courseprefs_crosslist', $record, true);

            if (!$this->id) {
                throw new Exception('Unable to create new courseprefs crosslist within database');
            }

        } else {

            $record->id = $this->id;

            if (!update_record('block_courseprefs_crosslist', $record)) {
                throw new Exception('Unable to update new courseprefs crosslist within database');
            }
        }

        CoursePrefsLog::add_to_log($this->usersid, $this->sectionsid, time(), 'crosslist', $this->status);
    }

    /**
     * Class method that finds, instantiates, and returns a crosslist
     * based on the id provided
     */
    static function findById($id) {

        $result = get_record('block_courseprefs_crosslist', 'id', $id);

        if (!$result) {
            return null;
        }

        $crosslist = new CoursePrefsCrosslist($result->usersid, $result->sectionsid,
            $result->status, $result->shell_name, $result->idnumber, $result->id);

        return $crosslist;
    }
    
    /**
     * Find and instantiates an object based on the usersid, sectonsid, and cr_sectionsid provided
     */
    static function findByUnique($usersid, $sectionsid, $status) {

        $result = get_record('block_courseprefs_crosslist', 'usersid', $usersid,
            'sectionsid', $sectionsid, 'status', $status);

        if (!$result) {
            return null;
        }

        return new CoursePrefsCrosslist($result->usersid, $result->sectionsid,
            $result->status, $result->shell_name, $result->idnumber, $result->id);
    }

    static function findByNumber($idnumber) {
        $results = get_records('block_courseprefs_crosslist', 'idnumber', $idnumber);
        
        $rtn = array();
        if (!$results) {
            return $rtn;
        }
        
        foreach($results as $result) {
            $rtn[$result->id] = new CoursePrefsCrosslist($result->usersid, 
                $result->sectionsid, $result->status, $result->shell_name,
                $result->idnumber, $result->id);
        }
        return $rtn;
    }
    
    /**
    * Class method that finds and removes a crosslist from the database
    * based on the id provided
    */
    static function deleteById($id){
        $cr = get_record('block_courseprefs_crosslist', 'id', $id);

        // Add to the logs
        CoursePrefsLog::add_to_log($cr->usersid, $cr->sectionsid, time(), 'reset', 'Crosslist reset');
        delete_records('block_courseprefs_crosslist', 'id', $id);
    }
}

?>
