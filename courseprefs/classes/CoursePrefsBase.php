<?php

/**
 * Base class that each CoursePrefs class will implement
 * This class models the most basic database functionality.
 **/
interface CoursePrefsBase {

    public function getId();
    public function setId($id);
    public function save();
    static public function findById($id);
    static public function deleteById($id);
}

?>
