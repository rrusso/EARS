<?php

class student_gradeviewer_cps_hook extends CoursePrefsHook {
    public function cleanup($semestersid) {
        // Clean up All the referral logs
        $semester = get_record('block_courseprefs_semesters', 'id', $semestersid);

        delete_records('block_student_referrals', 'semestersid', $semestersid);

        // Clean up all the teacher referral options on a section
        $sections = get_records('block_courseprefs_sections', 'semestersid', $semestersid);
        
        delete_records_select('block_teacher_referral_opt', 'sectionsid IN ('.
                              implode(',', array_keys($sections)).')');
        
        return get_string('cleanup_hook', 'block_student_gradeviewer', $semester);
    }
}

?>
