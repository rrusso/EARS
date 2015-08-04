<?php

class referral_semester_visualizer extends referral_visualizer {
    function create_obj($id) {
        return get_record('block_courseprefs_semesters', 'id', $id);
    }

    function is_capable() {
        return ($this->cps_user and 
                $this->cps_user->getSectionsForMoodleCourse()) || 
                parent::is_capable();
    }

    function heading() {
        return get_string('format_semester', 'block_student_gradeviewer', $this->obj);
    }

    function extra_navigation() {
        return array('name' => get_string('format_semester', 
                                          'block_student_gradeviewer', $this->obj),
                     'link' => '', 'type' => 'title');
    }

    function perform_prune() {
        $this->prune_keys(array(5));
    }
}

?>
