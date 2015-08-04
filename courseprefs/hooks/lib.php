<?php

require_once($CFG->dirroot . '/blocks/moodleblock.class.php');
require_once($CFG->dirroot . '/blocks/courseprefs/classes/CoursePrefsHook.php');

class cps_block_list extends block_list {
    
    function cpsName() {
        return preg_replace('/^block_/', '', get_class($this));
    }

    function after_install() {
        $name = $this->cpsName();
        if(!$hook = CoursePrefsHook::findByUnique($name)) {
            $hook = new CoursePrefsHook($name, 'block');
        }
        
        try {
            $hook->save();
            $a->name = $hook->getName();
            $a->type = $hook->getType();
            mtrace(get_string('hook_install', 'block_courseprefs', $a));
        } catch(Exception $e) {
            // TODO: log it or something
        }
    }

    function before_delete() {
        $hook = CoursePrefsHook::findByUnique($this->cpsName());
        if($hook && $hook->delete()) {
            $a->name = $hook->getName();
            $a->type = $hook->getType();
            mtrace(get_string('hook_delete', 'block_courseprefs', $a));
        }
    }
}

?>
