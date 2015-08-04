<?php

require_once('CoursePrefsBase.php');

/**
 * Class representation of the mdl_blocks_courseprefs_semesters db table
 */
class CoursePrefsSemester implements CoursePrefsBase {

    /**
     * Object Properties
     */
    private $id;
    private $year;
    private $name;
    private $campus;
    private $class_start;
    private $grades_due;

    /**
     * Constructor
     */
    function __construct($year, $name, $campus, $id = null, $class_start = null, $grades_due = null) {
        $this->id = $id;
        $this->year = $year;
        $this->name = $name;
        $this->campus = $campus;
        $this->class_start = $class_start;
        $this->grades_due = $grades_due;
    }

    /**
     * Getters
     */
    function getCampus() {
        return $this->campus;
    }

    function getClassStart() {
        return $this->class_start;
    }

    function getGradesDue() {
        return $this->grades_due;
    }

    function getId() {
        return $this->id;
    }

    function getName() {
        return $this->name;
    }

    function getYear() {
        return $this->year;
    }

    /**
     * Setters
     */
    function setCampus($campus) {
        $this->campus = $campus;
    }

    function setClassStart($class_start) {
        $this->class_start = $class_start;
    }

    function setGradesDue($grades_due) {
        $this->grades_due = $grades_due;
    }

    function setId($id) {
        $this->id = $id;
    }

    function setName($name) {
        $this->name = $name;
    }

    function setYear($year) {
        $this->year = $year;
    }

    /**
     * Stores an instance of the object in the database if the id is null; otherwise updates existing entry
     */
    function save() {

        $record = new stdClass;
		$record->year = $this->year;
		$record->name = $this->name;
		$record->campus = $this->campus;
		$record->class_start = $this->class_start;
		$record->grades_due = $this->grades_due;

        if (!$this->id) {

            $this->id = insert_record('block_courseprefs_semesters', $record, true);

            if (!$this->id) {
                throw new Exception('Unable to create new courseprefs semester within database');
            }

        } else {

            $record->id = $this->id;

            if (!update_record('block_courseprefs_semesters', $record)) {
                throw new Exception('Unable to update new courseprefs semester within database');
            }
        }
    }

    /**
     * Finds and instantiates object based on the id provided
     */
    static function findById($id) {

        $result = get_record('block_courseprefs_semesters', 'id', $id);

        if (!$result) {
            return null;
        }

        return new CoursePrefsSemester($result->year, $result->name, $result->campus,
            $result->id, $result->class_start, $result->grades_due);
    }

    /**
     * Finds and instantiates object based on the year, name, and campus provided
     */
    static function findByUnique($year, $name, $campus) {

        $result = get_record('block_courseprefs_semesters', 'year', $year, 'name', $name, 'campus', $campus);

        if (!$result) {
            return null;
        }

        return new CoursePrefsSemester($result->year, $result->name, $result->campus,
            $result->id, $result->class_start, $result->grades_due);
    }

    /**
     * Finds and removes an entry in the database where id provided
     */
    static function deleteById($id){
        delete_records('block_courseprefs_semesters', 'id', $id);
    }
}

?>
