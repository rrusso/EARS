<?php

function xmldb_block_student_gradeviewer_upgrade($oldversion = 0) {
    global $CFG, $THEME, $db;

    $result = true;

    if($result && $oldversion < 2010070912) {
        /// Define field opt to be dropped from block_teacher_referral_opt
        $table = new XMLDBTable('block_teacher_referral_opt');
        
        // First let's drop the index
        $index = new XMLDBIndex('bloc_tea_usesec_uix');
        $index->setAttributes(XMLDB_INDEX_UNIQUE, array('usersid', 'sectionsid'));

        $failing_field = new XMLDBField('failing');
        $lagging_field = new XMLDBField('lagging');
        $usersid_field = new XMLDBField('usersid');

        /// Launch drop field opt
        $result = $result && 
                  drop_index($table, $index) &&
                  drop_field($table, $usersid_field) &&
                  drop_field($table, $lagging_field) &&
                  drop_field($table, $failing_field);

        // Add the following fields
        $fields = array('sectionsid', 'primary_instructor', 'non_primary_instructor', 
                       'student', 'non_primary_control');

        foreach(range(1, count($fields) - 1) as $index) {
            $field = new XMLDBField($fields[$index]);
            $field->setAttributes(XMLDB_TYPE_INTEGER, '1', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null, '0', $fields[$index-1]);

            $result = $result && add_field($table, $field);
        }

        $index = new XMLDBIndex('bloc_tea_usesec_uix');
        $index->setAttributes(XMLDB_INDEX_UNIQUE, array('sectionsid'));
        
        $result = $result && add_index($table, $index);
    }

    return $result;
}

