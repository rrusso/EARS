<?php

class referral_student_visualizer extends referral_visualizer {

    function create_obj($id) {
        return get_record('block_courseprefs_users', 'id', $id);
    }

    function heading() {
        return fullname($this->obj);
    }
    
    function extra_navigation() {
        return array('name' => fullname($this->obj), 'link' => '', 'type' => 'title');
    }

    function perform_prune() {
       // $this->prune_keys(array(0));
        $this->protected['section'] = 'sectionsid';
    }

    function prune_filters($filter) {
        return false;
    }

    function is_capable() {
        global $USER;
        $mentors = lookup_mentors($this->obj);

        return in_array($USER->id, array_keys($mentors)) || parent::is_capable();
    }

    function referral_process() {
        if(!$this->exporting) {
            $times = times_student_referred($this->obj);

            echo '<ul class="student_list">
                 '.array_reduce($times, array($this, 'reduce_times')).'
                  </ul>';
        }

        return parent::referral_process();
    }

    function reduce_times($in, $time) {
        $inter = (empty($in)) ? '' : $in;
        $str = get_string('time', 'block_student_gradeviewer');
        return $inter . '<li>' .get_string('referred', 'block_student_gradeviewer', $time) .
                                (($time->count > 1) ? $str . 's' : $str) . ' in ' .
                              get_string('format_semester', 'block_student_gradeviewer', $time).
                        '</li>';
    }
}

function times_student_referred($student) {
    global $CFG;
    
    $sql = "SELECT sem.id, COUNT(ref.id) as count, sem.name, sem.year, sem.campus 
                FROM {$CFG->prefix}block_student_referrals ref,
                     {$CFG->prefix}block_courseprefs_semesters sem
                WHERE sem.id = ref.semestersid
                  AND ref.usersid = {$student->id}
                GROUP BY ref.semestersid
                ORDER BY ref.date_referred DESC";

    return get_records_sql($sql);
}
?>
