<?php

$block_student_gradeviewer_capabilities = array(
    'block/student_gradeviewer:viewgrades' => array(
        'captype' => 'read',
        'contextlevel' => CONTEXT_SYSTEM,
        'legacy' => array (
            'admin' => CAP_ALLOW,
        )
    ),
    'block/student_gradeviewer:sportsviewgrades' => array(
        'captype' => 'read',
        'contextlevel' => CONTEXT_SYSTEM,
        'legacy' => array (
            'admin' => CAP_ALLOW,
        )
    ),
    'block/student_gradeviewer:sportsadmin' => array(
        'captype' => 'write',
        'contextlevel' => CONTEXT_SYSTEM,
        'legacy' => array (
            'admin' => CAP_ALLOW,
        )
    ),
    'block/student_gradeviewer:academicadmin' => array(
        'captype' => 'write',
        'contextlevel' => CONTEXT_SYSTEM,
        'legacy' => array (
            'admin' => CAP_ALLOW,
        )
    ),
);

?>
