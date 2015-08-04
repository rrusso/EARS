<?php

class CoursePrefsLog implements CoursePrefsBase {

    private $id;
    private $action;
    private $timestamp;
    private $info;
    private $usersid;
    private $sectionsid;

    public function __construct($usersid=0, $sectionsid=0, $timestamp=0, 
                                $action='', $info='', $id=null) {
        $this->id = $id;
        $this->timestamp = $timestamp;
        $this->usersid = $usersid;
        $this->sectionsid = $sectionsid;
        $this->info = $info;
        $this->action = $action;
    }

    public function getId() {
        return $this->id;
    }

    public function getAction() {
        return $this->action;
    }

    public function getInfo() {
        return $this->info;
    }

    public function setId($id) {
        $this->id = $id;
    }

    public function save() {
        $record = new stdClass;
        $record->info = $this->info;
        $record->timestamp = $this->timestamp;
        $record->usersid = $this->usersid;
        $record->sectionsid = $this->sectionsid;
        $record->action = $this->action;
        $record->id = $this->id;        

        if (!$this->id) {
            $this->id = insert_record('block_courseprefs_logs', $record, true);
        } else {
            update_record('block_courseprefs_logs', $record);
        }
        return $this->id;
    }

    static public function add_to_log($usersid=0, $sectionsid=0, $timestamp=0,
                                    $action='', $info='') {
        $log = new CoursePrefsLog($usersid, $sectionsid, $timestamp, $action, $info);
        $log->save();
    }

    static public function findById($id) {
        $record = get_record('block_courseprefs_logs', 'id', $id);
        if (!$record) {
            return null;
        }

        return new CoursePrefsLog($record->usersid, $record->sectionsid,
                $record->timestamp, $record->action, $record->info, $record->id);
    }

    static public function deleteById($id) {
        return delete_records('block_courseprefs_logs', 'id', $id);
    }
}

?>
