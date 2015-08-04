<?php

require_once('CoursePrefsBase.php');
 
class CoursePrefsHook implements CoursePrefsBase {
    private $id;
    private $name;
    private $tpe;

    /**
     * Override these function in Moodle extension for CPS flexibility
     */
    public function userDeleted($usersid) {}
    public function cleanup($semestersid) {}
    public function studentUnenroll($studentsid) {}
    public function teacherUnenroll($teachersid) {}
    public function courseUnenroll($sectionsid) {}
    public function teacherDrop($teachersid) {}
    public function courseCreate($sectionsid) {}

    public function __construct($name, $type='block', $id=null) {
        $this->name = $name;
        $this->tpe = $type;
        $this->id = $id;
    }

    public function getId() {
        return $this->id;
    }

    public function getName() {
        return $this->name;
    }

    public function getType() {
        return $this->tpe;
    }

    public function setId($id) {
        $this->id = $id;
    }

    public function setName($name) {
        $this->name = $name;
    }
    
    public function setType($type) {
        $this->tpe = $type;
    }

    public function save() {
        $record = new stdClass;
        $record->name = $this->name;
        $record->type = $this->tpe;
        $record->id = $this->id;

        if($this->id) {
            update_record('block_courseprefs_hooks', $record);
        } else {
            $this->id = insert_record('block_courseprefs_hooks', $record, true);
        }
    }

    public function hook() {
        global $CFG;

        $lib = $CFG->dirroot . $this->dirs($this->tpe) . '/' .
                     $this->name . '/cps_hook/lib.php';

        if(!file_exists($lib)) return new CoursePrefsHook($this->name, $this->tpe);        

        require_once($lib);
    
        $hook = $this->name . '_cps_hook';
        return new $hook($this->name, $this->tpe);
    }

    static public function dirs($type) {
        switch ($type) {
            case 'block': $path = "/blocks"; break;
            case 'mod': $path = "/mod"; break;
            case 'enrol': $path = "/enrol"; break;
            case 'auth': $path = "/auth"; break;
            case 'report': $path = "/grade/report"; break;
            case 'import': $path = "/grade/import"; break;
            case 'export': $path = "/grade/export"; break;
            case 'user': $path = "/user"; break;
            default: $path = "";
        }
        return $path;
    }

    static public function allHooks($logger = null) {
        $dbhooks = CoursePrefsHook::findAll();

        return new HookCollection(array_map(create_function('$dbhook', 
                         'return $dbhook->hook();'), $dbhooks), $logger);
    }

    static public function findAll() {
        $records = get_records('block_courseprefs_hooks');
        
        $rtn = array();
        foreach($records as $id => $rec) {
            $rtn[$id] = new CoursePrefsHook($rec->name, $rec->type, $rec->id);
        }
        return $rtn;
    }

    public function delete() {
        return $this->deleteById($this->id);
    }

    static public function findByUnique($name) {
        $record = get_record('block_courseprefs_hooks', 'name', $name);
        
        if(!$record) return null;

        return new CoursePrefsHook($record->name, $record->type, $record->id);
    }

    static public function findById($id) {
        $record = get_record('block_courseprefs_hooks', 'id', $id);

        if(!$record) return null;

        return new CoursePrefsHook($record->name, $record->type, $record->id);
    }

    static public function deleteById($id) {
        return delete_records('block_courseprefs_hooks', 'id', $id);
    }

}

class HookCollection {
    private $hooks;
    private $logger;

    function __construct($hooks, $logger = null) {
        $this->hooks = $hooks;
        $this->logger = $logger;
    }

    public function withHooks($fun) {
        if(empty($this->hooks)) return;

        foreach($this->hooks as $hook) {
            $rtn = $fun($hook);
            if($this->logger) $this->logger->log($rtn);
        }
    }

    public function execute($method, $param=null) {
        $this->withHooks(create_function('$hook', '
            $rtn = "Running '.$method.' with parameter '.
                    $param.' on hook {$hook->getName()} of type {$hook->getType()}: ";
            $rtn .= $hook->'.$method.'('.$param.');
            return $rtn;
        '));
    }
}
?>
