<?php

require_once('CoursePrefsBase.php');
require_once('CoursePrefsLog.php');

/**
 * Class representation of a materials entry from the materials db table
 **/
class CoursePrefsMaterial implements CoursePrefsBase {

    /**
     * Private properties 
     */
    private $id;
    private $coursesid;
    private $usersid;
    private $createflag;

    /**
     * Constructor
     * If id is null, when save() is called on the object, a new record
     * is created, else the record with that id is updated
     */
    function __construct($coursesid, $usersid, $createflag=1, $id = null) {
        $this->id = $id;
        $this->coursesid = $coursesid;
        $this->usersid = $usersid;
        $this->createflag = $createflag;
    }

    /**
     * Property Getters
     */
    function getUsersId() {
        return $this->usersid;
    }

    function getCoursesId() {
        return $this->coursesid;
    }

    function getCreateFlag() {
        return $this->createflag;
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

    function setCoursesId($coursesid) {
        $this->coursesid = $coursesid;
    }

    function setCreateFlag($createflag) {
        $this->createflag = $createflag;
    }

    function setId($id) {
        $this->id = $id;
    }

    /**
     * Saves this materials instance to the database.
     * Decides to insert or update based on whether or not id is set
     */
    function save() {

        $record = new stdClass;
        $record->coursesid = $this->coursesid;
        $record->usersid = $this->usersid;
        $record->createflag = $this->createflag;

        if (!$this->id) {

            $this->id = insert_record('block_courseprefs_materials', $record, true);

            if (!$this->id) {
                throw new Exception('Unable to create new courseprefs course within database');
            }

        } else {

            $record->id = $this->id;

            if (!update_record('block_courseprefs_materials', $record)) {
                throw new Exception('Unable to update new courseprefs course within database');
            }
        }
        
    }

    /**
     * Class method that finds, instantiates, and returns a materials entry 
     * based on the id provided
     */
    static function findById($id) {

        $result = get_record('block_courseprefs_materials', 'id', $id);

        if (!$result) {
            return null;
        }

        $material = new CoursePrefsMaterial($result->coursesid, $result->usersid, $result->createflag,
            $result->id);

        return $material;
    }

    /**
     * Class method that finds, instantiates, and returns a material based on 
     * it's unique properties, in this case, coursesid
     */
    static function findByUnique($coursesid) {

        $result = get_record('block_courseprefs_materials', 'coursesid', $coursesid);

        if (!$result) {
            return null;
        }

        return new CoursePrefsMaterial($result->coursesid, $result->usersid,
            $result->create_flag, $result->id);
    }

    /**
    * Class method that finds and removes a materials entry from the database
    * based on the id provided
    */
    static function deleteById($id){
        delete_records('block_courseprefs_materials', 'id', $id);
    }
}

?>
