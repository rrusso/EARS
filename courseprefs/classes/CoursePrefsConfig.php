<?php

require_once('CoursePrefsBase.php');

/**
 * Class representation of a config entry from the config db table
 **/
class CoursePrefsConfig implements CoursePrefsBase {

    /**
     * Private properties 
     */
    private $id;
    private $name;
    private $value;

    /**
     * Constructor
     * If id is null, when save() is called on the object, a new record
     * is created, else the record with that id is updated
     */
    function __construct($name, $value, $id = null) {
        $this->id = $id;
        $this->name = $name;
        $this->value = $value;
    }

    /**
     * Property Getters
     */
    function getValue() {
        return $this->value;
    }

    function getName() {
        return $this->name;
    }

    function getId() {
        return $this->id;
    }

    /**
     * Property Setters
     */
    function setValue($value) {
        $this->value = $value;
    }

    function setName($name) {
        $this->name = $name;
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
        $record->name = $this->name;
        $record->value = $this->value;

        if (!$this->id) {

            $this->id = insert_record('block_courseprefs_config', $record, true);

            if (!$this->id) {
                throw new Exception('Unable to create new courseprefs config within database');
            }

        } else {

            $record->id = $this->id;

            if (!update_record('block_courseprefs_config', $record)) {
                throw new Exception('Unable to update a courseprefs config record within the database');
            }
        }
    }

    /**
     * Class method to find all the records in the config table
     */
    static function findAll() {
        $configs = array();

        $results = array();
        $results = get_records('block_courseprefs_config');
        
        foreach ($results as $result) {
            $configs[$result->name] = new CoursePrefsConfig($result->name, $result->value, $result->id);
        }
                
        return $configs;
    }    

    /**
     * Class method that finds, instantiates, and returns a config entry 
     * based on the id provided
     */
    static function findById($id) {

        $result = get_record('block_courseprefs_config', 'id', $id);

        if (!$result) {
            return null;
        }

        $config = new CoursePrefsConfig($result->name, $result->value, $result->id);

        return $config;
    }

    /**
     * Class method that finds, instantiates, and returns a config record based on 
     * it's unique properties, in this case, name
     */
    static function findByUnique($name) {

        $result = get_record('block_courseprefs_config', 'name', $name);

        if (!$result) {
            return null;
        }

        return new CoursePrefsConfig($result->name, $result->value,
            $result->id);
    }

    static function getNamedValue($name) {
        return CoursePrefsConfig::findByUnique($name)->getValue();
    }

    /**
    * Class method that finds and removes config entries from the database
    * based on the id provided
    */
    static function deleteById($id){
        delete_records('block_courseprefs_config', 'id', $id);
    }
}

?>
