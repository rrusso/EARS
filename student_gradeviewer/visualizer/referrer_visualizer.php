<?php

require_once($CFG->dirroot. '/blocks/student_gradeviewer/visualizer/student_visualizer.php');

class referral_referrer_visualizer extends referral_student_visualizer {
    function create_obj($id) {
        if ($id == 1) {
            $a->firstname = get_string('anon_report', 'block_student_gradeviewer');
            return $a;
        } else {
            return get_record('user', 'id', $id);
        }
    }

    function referral_process() {
        $sql = $this->referral_select() . $this->build_where() . $this->referral_limit();
        return get_records_sql($sql);
    }

    function perform_prune() {
        $this->prune_keys(array(2));
    }

    function prune_filters($filter) {
        return true;
    }

    function is_capable() {
        global $USER;
        return $USER->id == $this->id || parent::is_capable();
    }
}

?>
