<?php

class referral_course_visualizer extends referral_visualizer {
    function create_obj($id) {
        return get_record('course', 'id', $id);
    }

    function heading() {
        return $this->obj->fullname;
    }

    function is_capable() {
        return true;
    }

    function extra_navigation() {
        return array('name' => $this->obj->fullname, 'link' => '', 'type' => 'title');
    }

}

?>
