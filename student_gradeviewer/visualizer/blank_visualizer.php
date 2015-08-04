<?php

class referral_blank_visualizer extends referral_visualizer {
    function create_obj() {
        global $USER;
        return $USER;
    }
    function heading() {
        global $USER;
        return fullname($USER);
    }

    function exportable() {
        return false;
    }

    function is_capable() {
        return ($this->cps_user and 
                $this->cps_user->getSectionsForMoodleCourse(null, true)) || 
                parent::is_capable();
    }

    function perform_prune() {
        $this->prune_keys(array(0, 1, 2, 3, 6));
        unset($this->fields['date_referred']);
        $this->keys = array_reverse($this->keys);
    }

    function prune_filters($filter) {
        return false;
    }

    function referral_process() {
        if(!$this->exporting) echo '<span class="semesterchooser">'.get_string('semesterchooser', 'block_student_gradeviewer').'</span>';
        return $this->get_semesters();
    }

    function get_semesters() {
        global $CFG;
        
        $sql = "SELECT id as semestersid, name, year, campus, class_start AS date_referred 
                    FROM {$CFG->prefix}block_courseprefs_semesters";

        return get_records_sql($sql);
    }

    function referral_where($where) {
        global $CFG;
        
        return "FROM {$CFG->prefix}block_courseprefs_semesters ref";
    }
}

?>
