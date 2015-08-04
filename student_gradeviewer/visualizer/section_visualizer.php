<?php

class referral_section_visualizer extends referral_visualizer {
    function create_obj($id) {
        global $CFG;

        return get_record_sql("SELECT sec.*, cou.course_number, cou.department,
                                      sem.name, sem.year, sem.campus
                                    FROM {$CFG->prefix}block_courseprefs_sections sec,
                                         {$CFG->prefix}block_courseprefs_courses cou,
                                         {$CFG->prefix}block_courseprefs_semesters sem
                                    WHERE sec.coursesid = cou.id
                                      AND sec.semestersid = sem.id
                                      AND sec.id = {$id}");
    }

    function heading() {
        return get_string('format_section', 'block_courseprefs', $this->obj). ' in '.
               get_string('format_semester', 'block_student_gradeviewer', $this->obj);
    }
    
    function extra_navigation() {
        return array('name' => get_string('format_section', 
                                          'block_courseprefs', $this->obj),
                     'link' => '', 'type' => 'title');
    }
   
    function perform_prune() {
        $this->prune_keys(array(1, 5));
    }

    function is_capable() {
        return true;
    }

    function prune_filters($filter) {
        return $filter != "section";
    }
}
?>
