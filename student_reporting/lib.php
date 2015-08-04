<?php

require_once($CFG->dirroot . '/blocks/student_gradeviewer/report/lib.php');

function get_referred_students($section) {
    global $CFG;
    
    $sql = "SELECT ref.*, cpsu.firstname, cpsu.lastname
                FROM {$CFG->prefix}block_courseprefs_users cpsu,
                     {$CFG->prefix}block_courseprefs_students cpsst,
                     {$CFG->prefix}block_student_referrals ref
                WHERE ref.sectionsid = {$section->id}
                  AND ref.usersid = cpsst.usersid
                  AND cpsst.sectionsid = {$section->id}
                  AND cpsst.status = 'enrolled'
                  AND cpsu.id = ref.usersid
                GROUP BY ref.usersid, ref.source";

    return get_records_sql($sql);
}
?>
