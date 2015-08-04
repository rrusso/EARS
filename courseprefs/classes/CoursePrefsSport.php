<?php

require_once('CoursePrefsBase.php');

class CoursePrefsSport implements CoursePrefsBase {

    private $id;
    private $code;
    private $name;

    function __construct($code, $name, $id = null) {
        $this->id = $id;
        $this->code = $code;
        $this->name = $name;
    }

    function getId() {
        return $this->id;
    }

    function setId($id) {
        $this->id = $id;
    }

    function save() {
        $record = new stdClass;
        $record->code = $this->code;
        $record->name = $this->name;

        if(!$this->id) {
            $this->id = insert_record('block_courseprefs_sports', $record, true);
            
            if (!$this->id) {
                throw new Exception('Unable to create new courseprefs sports within database');
            }
        } else {
            $record->id = $this->id;

            if (!update_record('block_courseprefs_sports', $record)) {
                throw new Exception('Unable to update a courseprefs sports record within the database');
            }
        }
    }

    function addUser($user) {
        // Insert if the record doesn't already exists
        if(!$record = get_record('block_courseprefs_sportusers', 'sportsid', 
                                 $this->sportsid, 'usersid', $user->getId())) {
            $record = new stdClass;
            $record->sportsid = $this->id;
            $record->usersid = $user->getId();
            
            if(!insert_record('block_courseprefs_sportusers', $record)) {
                throw new Exception('Unable to create a sport user relationship');
            }
        }
    }

    static function truncateSportUsers() {
        $tables = array('sportusers');       

        return array_reduce($tables, 'trun_reduce', true);
    }

    static function findAll() {
        $results = array();

        $records = get_records('block_courseprefs_sports');

        foreach ($records as $id => $record) {
            $results[$id] = new CoursePrefsSport($record->code, $record->name, $id);
        }

        return $results;
    }

    static function findById($id) {
        $result = get_record('block_courseprefs_sports', 'id', $id);

        if(!$result) {
            return null;
        }

        return new CoursePrefsSport($result->code, $result->name, $result->id);
    }

    static function findByUnique($code) {
        $record = get_record('block_courseprefs_sports', 'code', $code);

        if(!$record) {
            return null;
        }

        return new CoursePrefsSport($code, $record->name, $record->id);
    }

    static function deleteById($id) {
        delete_records('block_courseprefs_sports', 'id', $id);
    }
}

function trun_reduce($in, $table) {
    global $CFG;
    
    $sql = "TRUNCATE {$CFG->prefix}block_courseprefs_{$table}";
    return $in && execute_sql($sql, false);    
}

?>
