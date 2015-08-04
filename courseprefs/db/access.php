<?php

$block_courseprefs_capabilities = array(
    'block/courseprefs:viewdata' => array(
        'captype' => 'read',
        'contextlevel' => CONTEXT_BLOCK,
        'legacy' => array(
            'admin' => CAP_ALLOW,
            'coursecreator' => CAP_ALLOW
        )
    )
);
?>
