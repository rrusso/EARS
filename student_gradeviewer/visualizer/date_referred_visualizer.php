<?php

class referral_date_referred_visualizer extends referral_visualizer {
    function create_obj($id) {
        return $id;
    }

    function heading() {
        return date(get_string('all_date_format', 'block_student_gradeviewer'),
                   $this->obj);
    }

    function is_capable() {
        return true;
    }

    function perform_prune() {
        $this->prune_keys(array(4));
    }

    function prune_filters($filter) {
        return $filter != 'date_referred';
    }

    function extra_navigation() {
        return array('name' => $this->heading(), 'link' => '', 'type' => 'title');
    }
}

?>
