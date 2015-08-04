<?php

/*
 * This file keeps track of upgrades to the search block.  Sometimes, changes between versions
 * involve alterations to database structures and other major things that may break installations.
 * The upgrade function in this file will attempt to perform all the necessary actions to upgrade
 * your older installtion to the current version.  If there's something it cannot do itself, it will
 * tell you what you need to do.  The commands in here will all be database-neutral, using the
 * functions defined in lib/ddllib.php.
 */
function xmldb_block_courseprefs_upgrade($oldversion = 0) {

    global $CFG, $THEME, $db;

    $result = true;

    /*
     * And upgrade begins here. For each one, you'll need one block of code similar to the next one.
     * Please, delete this comment lines once this file start handling proper upgrade code.
     *
     * if ($result && $oldversion < YYYYMMDD00) { //New version in version.php
     *     $result = result of "/lib/ddllib.php" function calls
     * }
     *
     */    

    if ($result && $oldversion < 2008011700) {

        // Adding block_courseprefs_students.status field to table
        $table = new XMLDBTable('block_courseprefs_students');
        $field = new XMLDBField('status');
        $field->setAttributes(XMLDB_TYPE_CHAR, '10', null, XMLDB_NOTNULL, null, XMLDB_ENUM,
            array('pending', 'enrolled'), 'pending', 'sectionsid');
        $result = $result && add_field($table, $field);

        // Adding block_courseprefs_teachers.status field to table
        $table = new XMLDBTable('block_courseprefs_teachers');
        $field = new XMLDBField('status');
        $field->setAttributes(XMLDB_TYPE_CHAR, '10', null, XMLDB_NOTNULL, null, XMLDB_ENUM,
            array('enrolled', 'pending'), 'pending', 'primary_flag');
        $result = $result && add_field($table, $field);
    }

    if ($result && $oldversion < 2008021400) {

        $table = new XMLDBTable('block_courseprefs_enroll');

        // Adding block_courseprefs_enroll.sectionsid field to table
        $field = new XMLDBField('semestersid');
        $field->setAttributes(XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL,
            null, null, null, null, 'usersid');
        $result = $result && add_field($table, $field);

        // Dropping unique index on usersid and coursesid
        $index = new XMLDBIndex('blocouenr-usecou-uk');
        $index->setAttributes(XMLDB_INDEX_UNIQUE, array('usersid', 'coursesid'));
        $result = $result && drop_index($table, $index);

        // Delete table entries as users will fall back on users' defaults
        delete_records('block_courseprefs_enroll');

        // Adding unique index on usersid, semestersid, and coursesid
        $index = new XMLDBIndex('blocouenr-usesemcou-uix');
        $index->setAttributes(XMLDB_INDEX_UNIQUE, array('usersid', 'semestersid', 'coursesid'));
        $result = $result && add_index($table, $index);
    }

    if ($result && $oldversion < 2008032400) {
        //Upgrade existing users table
        $user_table = new XMLDBTable('block_courseprefs_users');
        $field = new XMLDBField('idnumber');
        $field->setAttributes(XMLDB_TYPE_CHAR, '11', null, XMLDB_NOTNULL, null, null, null, null, 'split_courses');

        /// Define table block_courseprefs_config to be created
        $table = new XMLDBTable('block_courseprefs_config');

        /// Adding fields to table block_courseprefs_config
        $table->addFieldInfo('id', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, XMLDB_SEQUENCE, null, null, null);
        $table->addFieldInfo('name', XMLDB_TYPE_CHAR, '128', null, XMLDB_NOTNULL, null, null, null, null);
        $table->addFieldInfo('value', XMLDB_TYPE_TEXT, 'small', null, XMLDB_NOTNULL, null, null, null, null);

        /// Adding keys to table block_courseprefs_config
        $table->addKeyInfo('primary', XMLDB_KEY_PRIMARY, array('id'));

        /// Adding indexes to table block_courseprefs_config
        $table->addIndexInfo('blocoucon-nam-uix', XMLDB_INDEX_UNIQUE, array('name'));

        /// Launch create table for block_courseprefs_config
        $result = $result && create_table($table) && add_field($user_table, $field);
    }

    if ($result && $oldversion < 2008061102){ 
        // Upgrade existing updates table
        $updates_table = new XMLDBTable('block_courseprefs_updates');
        $field = new XMLDBField('action');
       
        $result = $result && drop_field($updates_table, $field);
 
        $field->setAttributes(XMLDB_TYPE_CHAR, '30', null, null, null, XMLDB_ENUM, 
                    array('drop_instructor', 'drop_student', 'enroll_student', 
                          'enroll_instructor', 'enroll_primary_instructor'), null, 'usersid');

        /// Launch change of list of values for field action
        $result = $result && add_field($updates_table, $field);
	
	/// Define index blocouupd-secuseact-uix (unique) to be dropped form block_courseprefs_updates
        $table = new XMLDBTable('block_courseprefs_updates');
        $index = new XMLDBIndex('blocouupd-secuseact-uix');
        $index->setAttributes(XMLDB_INDEX_UNIQUE, array('sectionsid', 'usersid'));

    /// Launch drop index blocouupd-secuseact-uix
        $result = $result && drop_index($table, $index); 
    }

    if ($result && $oldversion < 2008111200) {

        $enrollments = array('students', 'teachers');

       //-------Changing of the users table ------------//

        /// Define field college to be dropped from block_courseprefs_users
        $table = new XMLDBTable('block_courseprefs_users');
        $field = new XMLDBField('course_create_days');

    /// Launch drop field college
        $result = $result && drop_field($table, $field);

        /// Define field college to be dropped from block_courseprefs_users
        $field = new XMLDBField('split_courses');

    /// Launch drop field college
        $result = $result && drop_field($table, $field);

        /// Define field college to be dropped from block_courseprefs_users
        $field = new XMLDBField('course_enroll_days');

    /// Launch drop field college
        $result = $result && drop_field($table, $field);
       
        $field = new XMLDBField('year');
        $field->setAttributes(XMLDB_TYPE_INTEGER, '1', XMLDB_UNSIGNED, null, null, null, null, null, 'idnumber');

    /// Launch add field year
        $result = $result && add_field($table, $field);

       /// Define field college to be added to block_courseprefs_users
        $field = new XMLDBField('college');
        $field->setAttributes(XMLDB_TYPE_CHAR, '10', null, null, null, null, null, null, 'year');

    /// Launch add field college
        $result = $result && add_field($table, $field);

        /// Define field reg_status to be added to block_courseprefs_users
        $field = new XMLDBField('reg_status');
        $field->setAttributes(XMLDB_TYPE_CHAR, '20', null, null, null, null, null, null, 'college');

    /// Launch add field reg_status
        $result = $result && add_field($table, $field);  

        /// Define field classification to be added to block_courseprefs_users
        $field = new XMLDBField('classification');
        $field->setAttributes(XMLDB_TYPE_CHAR, '15', null, null, null, null, null, null, 'reg_status');

    /// Launch add field classification
        $result = $result && add_field($table, $field);

        /// Define field keypadid to be added to block_courseprefs_users
        $field = new XMLDBField('keypadid');
        $field->setAttributes(XMLDB_TYPE_CHAR, '20', null, null, null, null, null, null, 'classification');

    /// Launch add field keypadid
        $result = $result && add_field($table, $field);

        /// Define field moodleid to be added to block_courseprefs_users
        $field = new XMLDBField('moodleid');
        $field->setAttributes(XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, null, null, null, null, null, 'keypadid');

    /// Launch add field moodleid
        $result = $result && add_field($table, $field);

        /// Define field degree_candidacy to be added to block_courseprefs_users
        $field = new XMLDBField('degree_candidacy');
        $field->setAttributes(XMLDB_TYPE_CHAR, '1', null, XMLDB_NOTNULL, null, null, null, 'N', 'moodleid');

    /// Launch add field degree_candidacy
        $result = $result && add_field($table, $field);

        /// Define field anonymous to be added to block_courseprefs_users
        $field = new XMLDBField('anonymous');
        $field->setAttributes(XMLDB_TYPE_CHAR, '20', null, null, null, null, null, null, 'degree_candidacy');

    /// Launch add field anonymous
        $result = $result && add_field($table, $field);

        /// Define field anonymous to be added to block_courseprefs_users
        $field = new XMLDBField('ferpa');
        $field->setAttributes(XMLDB_TYPE_CHAR, '1', null, null, null, null, null, null, 'anonymous');

    /// Launch add field anonymous
        $result = $result && add_field($table, $field);

        /// Define field format to be added to block_courseprefs_users
        $field = new XMLDBField('format');
        $field->setAttributes(XMLDB_TYPE_CHAR, '50', null, XMLDB_NOTNULL, null, null, null, 'topics', 'ferpa');

    /// Launch add field format
        $result = $result && add_field($table, $field);

        /// Define field numsections to be added to block_courseprefs_users
        $field = new XMLDBField('numsections');
        $field->setAttributes(XMLDB_TYPE_INTEGER, '3', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null, '17', 'format');

    /// Launch add field numsections
        $result = $result && add_field($table, $field);

        /// Define field hidden to be added to block_courseprefs_users
        $field = new XMLDBField('hidden');
        $field->setAttributes(XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, null, null, '1', 'numsections');

    /// Launch add field hidden
        $result = $result && add_field($table, $field);

        $field = new XMLDBField('cr_delete');
        $field->setAttributes(XMLDB_TYPE_INTEGER, '1', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null, '1', 'hidden');

    /// Launch add field cr_delete
        $result = $result && add_field($table, $field);

        // Make moodleid valid
        $sql = "UPDATE {$CFG->prefix}user mu, 
                       {$CFG->prefix}block_courseprefs_users u 
                    SET u.moodleid = mu.id 
                    WHERE u.username=mu.username";
        execute_sql($sql);

       //-------Changing of the sections table ---------//

       /// Define field status to be added to block_courseprefs_sections
        $table = new XMLDBTable('block_courseprefs_sections');
        $field = new XMLDBField('status');
        $field->setAttributes(XMLDB_TYPE_CHAR, '10', null, XMLDB_NOTNULL, null, XMLDB_ENUM, 
                array('pending', 'completed', 'unwant', 'unwanted'), 'pending', 'idnumber');

    /// Launch add field status
        $result = $result && add_field($table, $field);
        
        // Now we update the db to reflect the new schema
        $sql = "UPDATE {$CFG->prefix}block_courseprefs_sections 
                SET status='completed'
                WHERE idnumber IS NOT NULL";
        execute_sql($sql);

        /// Define field timestamp to be added to block_courseprefs_sections
        $table = new XMLDBTable('block_courseprefs_sections');
        $field = new XMLDBField('timestamp');
        $field->setAttributes(XMLDB_TYPE_CHAR, '20', null, null, null, null, null, null, 'status');

    /// Launch add field timestamp
        $result = $result && add_field($table, $field);

    /// Define field credit_hours to be added to block_courseprefs_students
        $table = new XMLDBTable('block_courseprefs_students');
        $field = new XMLDBField('credit_hours');
        $field->setAttributes(XMLDB_TYPE_INTEGER, '2', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null, '3', 'status');

    /// Launch add field credit_hours
        $result = $result && add_field($table, $field);

        /// Define field timestamp to be added to block_courseprefs_teachers
        $table = new XMLDBTable('block_courseprefs_teachers');
        $field = new XMLDBField('timestamp');
        $field->setAttributes(XMLDB_TYPE_CHAR, '20', null, null, null, null, null, null, 'status');

        $index = new XMLDBIndex('blocoutea-usesec-uix');
        $index->setAttributes(XMLDB_INDEX_UNIQUE, array('usersid', 'sectionsid'));

    /// Launch add field timestamp
        $result = $result && add_field($table, $field) && drop_index($table, $index);

        // Add unique index
        $index = new XMLDBIndex('blocoutea_usesec_ix');
        $index->setAttributes(XMLDB_INDEX_NOTUNIQUE, array('usersid', 'sectionsid'));
        
        $uindex = new XMLDBIndex('blocoutea_usesecpri_uix');
        $uindex->setAttributes(XMLDB_INDEX_UNIQUE, array('usersid', 'sectionsid', 'primary_flag'));

        $result = $result && add_index($table, $index) && add_index($table, $uindex);

       //-------Changing of the enrollments ---------//

        foreach ($enrollments as $enrollment) {
            /// Changing list of values (enum) of field status on table block_courseprefs_students to 'enrolled', 'enroll', 'unenrolled', 'unenroll'
            $table = new XMLDBTable('block_courseprefs_'.$enrollment);
            $field = new XMLDBField('status');
            $field->setAttributes(XMLDB_TYPE_CHAR, '10', null, XMLDB_NOTNULL, null, XMLDB_ENUM, 
                    array('enrolled', 'enroll', 'unenrolled', 'unenroll'), 'enroll');

        /// Launch change of list of values for field status
            $result = $result && change_field_type($table, $field);

            //Change 'pending' values
            $sql = "UPDATE {$CFG->prefix}block_courseprefs_$enrollment 
                        SET status='enroll' 
                        WHERE status!='enrolled'";
            $result = $result && execute_sql($sql);
        }

        //--------Changing of the split table ----------//

        //Caching off the splits in record now
        $splits = get_records('block_courseprefs_split');

        $table = new XMLDBTable('block_courseprefs_split');
        $index = new XMLDBIndex('blocouspl-usecou-uk');
        $index->setAttributes(XMLDB_INDEX_UNIQUE, array('usersid', 'coursesid'));

        $result = $result && drop_index($table, $index);

        /// Define field coursesid to be dropped from block_courseprefs_split
        $table = new XMLDBTable('block_courseprefs_split');
        $field = new XMLDBField('coursesid');

    /// Launch drop field coursesid
        $result = $result && drop_field($table, $field);

        /// Define field sectionsid to be added to block_courseprefs_split
        $table = new XMLDBTable('block_courseprefs_split');
        $field = new XMLDBField('sectionsid');
        $field->setAttributes(XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, null, null, null, null, null, 'usersid');

    /// Launch add field sectionsid
        $result = $result && add_field($table, $field);

        /// Define field groupingsid to be added to block_courseprefs_split
        $table = new XMLDBTable('block_courseprefs_split');
        $field = new XMLDBField('groupingsid');
        $field->setAttributes(XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, null, null, null, null, null, 'sectionsid');

    /// Launch add field groupingsid
        $result = $result && add_field($table, $field);

        /// Define field status to be added to block_courseprefs_split
        $table = new XMLDBTable('block_courseprefs_split');
        $field = new XMLDBField('status');
        $field->setAttributes(XMLDB_TYPE_CHAR, '10', null, XMLDB_NOTNULL, null, XMLDB_ENUM, array('todo', 'resolved', 'undo'), 'resolved', 'groupingsid');

    /// Launch add field status
        $result = $result && add_field($table, $field);

        /// Define field shell_name to be added to block_courseprefs_split
        $table = new XMLDBTable('block_courseprefs_split');
        $field = new XMLDBField('shell_name');
        $field->setAttributes(XMLDB_TYPE_CHAR, '255', null, null, null, null, null, null, 'status');

    /// Launch add field shell_name
        $result = $result && add_field($table, $field);

        /// Define index blocouspl-usecousec-uk (unique) to be dropped form block_courseprefs_split
        $new_index = new XMLDBIndex('blocouspl-usesecgrosta-uk');
        $new_index->setAttributes(XMLDB_INDEX_UNIQUE, array('usersid', 'sectionsid', 'groupingsid', 'status'));

    /// Launch drop previous index and add blocouspl-usecousecgrosta-uk
        $result = $result && add_index($table, $new_index);

        // Now convert old split data to match the new one
        foreach ($splits as $split) {
            $sql = "SELECT sec.* FROM {$CFG->prefix}block_courseprefs_sections sec,
                    {$CFG->prefix}block_courseprefs_teachers t
                    WHERE t.usersid   = {$split->usersid}
                    AND t.sectionsid  = sec.id
                    AND sec.coursesid = {$split->coursesid}";
            $sections = get_records_sql($sql);
            $count = 0;
            foreach ($sections as $section) {
                $new_split = new stdClass;
                $new_split->usersid = $split->usersid;
                $new_split->sectionsid = $section->id;
                $new_split->status = 'resolved';
                $new_split->groupingsid = ++$count;
                $new_split->shell_name = 'Section '. $section->section_number;

                $result = insert_record('block_courseprefs_split', $new_split);
           }
           $result = delete_records('block_courseprefs_split', 'id', $split->id);
        }

        //---------Changing of the crosslist table --------//

        $crosslists = get_records('block_courseprefs_crosslist');

        /// Define field status to be added to block_courseprefs_crosslist
        $table = new XMLDBTable('block_courseprefs_crosslist');
        $field = new XMLDBField('status');
        $field->setAttributes(XMLDB_TYPE_CHAR, '10', null, XMLDB_NOTNULL, null, XMLDB_ENUM, array('todo', 'resolved', 'undo'), 'resolved', 'cr_sectionsid');

    /// Launch add field status
        $result = $result && add_field($table, $field);

        /// Define field shell_name to be added to block_courseprefs_crosslist
        $table = new XMLDBTable('block_courseprefs_crosslist');
        $field = new XMLDBField('shell_name');
        $field->setAttributes(XMLDB_TYPE_CHAR, '100', null, null, null, null, null, null, 'status');

    /// Launch add field shell_name
        $result = $result && add_field($table, $field);

        /// Define field idnumber to be added to block_courseprefs_crosslist
        $table = new XMLDBTable('block_courseprefs_crosslist');
        $field = new XMLDBField('idnumber');
        $field->setAttributes(XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null, null, null, 'shell_name');

    /// Launch add field idnumber
        $result = $result && add_field($table, $field);

        /// Define field cr_sectionsid to be dropped from block_courseprefs_crosslist
        $table = new XMLDBTable('block_courseprefs_crosslist');
        $field = new XMLDBField('cr_sectionsid');

    /// Launch drop field cr_sectionsid
        $result = $result && drop_field($table, $field);

        // Time to change the existing records
        foreach ($crosslists as $crosslist) {
            $sections = array_map('find_section', array($crosslist->sectionsid, $crosslist->cr_sectionsid));
         
            $shell_name = "{$sections[0]->department} {$sections[0]->course_number} / {$sections[1]->department} {$sections[1]->course_number}";
            $idnumber = $sections[0]->idnumber;

            foreach($sections as $section) {
                $new_crosslist = new stdClass;
                $new_crosslist->usersid = $crosslist->usersid;
                $new_crosslist->sectionsid = $section->id;
                $new_crosslist->shell_name = $shell_name;
                $new_crosslist->idnumber = $idnumber;
                $new_crosslist->status = 'resolved';
                insert_record('block_courseprefs_crosslist', $new_crosslist);
            }
            
            delete_records('block_courseprefs_crosslist', 'id', $crosslist->id);
        }

        //----------Changing of the teamteach table --------//
    
        /// Define field status to be added to block_courseprefs_teamteach
        $table = new XMLDBTable('block_courseprefs_teamteach');
        $field = new XMLDBField('status');
        $field->setAttributes(XMLDB_TYPE_CHAR, '10', null, XMLDB_NOTNULL, null, XMLDB_ENUM, array('todo', 'resolved', 'undo'), 'resolved', 'approval_flag');

    /// Launch add field status
        $result = $result && add_field($table, $field);

        //-----------Dropping the updates table -----------//

        /// Define table block_courseprefs_updates to be dropped
        $table = new XMLDBTable('block_courseprefs_updates');

    /// Launch drop table for block_courseprefs_updates
        $result = $result && drop_table($table);

        //-----------Dropping the unwanted table ----------//

        $unwanteds = get_records('block_courseprefs_unwanted');
        foreach ($unwanteds as $unwanted) {
            if ($unwanted->sectionsid) {
                // There should only be one
                $sections = get_records('block_courseprefs_sections', 'id', $unwanted->sectionsid);
            } else {
                $sql = "SELECT * FROM {$CFG->prefix}block_courseprefs_sections
                            WHERE coursesid={$unwanted->coursesid}";
                $sections = get_records_sql($sql);
            }

            foreach ($sections as $section) {
                $section->status = 'unwanted';
                $result = update_record('block_courseprefs_sections', $section);
            }
        }

        /// Define table block_courseprefs_unwanted to be dropped
        $table = new XMLDBTable('block_courseprefs_unwanted');

    /// Launch drop table for block_courseprefs_unwanted
        $result = $result && drop_table($table);

        /// Define table block_courseprefs_logs to be created
        $table = new XMLDBTable('block_courseprefs_logs');

    /// Adding fields to table block_courseprefs_logs
        $table->addFieldInfo('id', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, XMLDB_SEQUENCE, null, null, null);
        $table->addFieldInfo('timestamp', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null, '0');
        $table->addFieldInfo('usersid', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null, '0');
        $table->addFieldInfo('sectionsid', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null, '0');
        $table->addFieldInfo('action', XMLDB_TYPE_CHAR, '40', null, XMLDB_NOTNULL, null, null, null, null);
        $table->addFieldInfo('info', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null, null, null);

    /// Adding keys to table block_courseprefs_logs
        $table->addKeyInfo('primary', XMLDB_KEY_PRIMARY, array('id'));

    /// Adding indexes to table block_courseprefs_logs
        $table->addIndexInfo('bloclogs_tim_ix', XMLDB_INDEX_NOTUNIQUE, array('timestamp'));
        $table->addIndexInfo('bloclogs_act_ix', XMLDB_INDEX_NOTUNIQUE, array('action'));
        $table->addIndexInfo('bloclogs_usesec_ix', XMLDB_INDEX_NOTUNIQUE, array('usersid', 'sectionsid'));
        $table->addIndexInfo('bloclogs_usesecact_ix', XMLDB_INDEX_NOTUNIQUE, array('usersid', 'sectionsid', 'action'));

    /// Launch create table for block_courseprefs_logs
        $result = $result && create_table($table);
    }

    if ($result && $oldversion < 2010010500) {

    //TODO: make this not tied to mysql
    /// Changing list of values (enum) of field status on table block_courseprefs_sections to 
    /// 'pending', 'completed', 'unwant', 'unwanted', and requested
        $sql = "ALTER TABLE {$CFG->prefix}block_courseprefs_sections 
                CHANGE `status` `status` 
                ENUM( 'pending', 'completed', 'unwant', 'unwanted', 'requested' ) 
                CHARACTER SET utf8 
                COLLATE utf8_general_ci NOT NULL DEFAULT 'pending'";
        execute_sql($sql);

    }

    // Adding the sport code, and user relationship tables
    if ($result && $oldversion < 2010030500) {
    /// Define table blocks_courseprefs_sports to be created
        $table = new XMLDBTable('block_courseprefs_sports');

    /// Adding fields to table blocks_courseprefs_sports
        $table->addFieldInfo('id', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, XMLDB_SEQUENCE, null, null, null);
        $table->addFieldInfo('code', XMLDB_TYPE_CHAR, '10', null, XMLDB_NOTNULL, null, null, null, null);
        $table->addFieldInfo('name', XMLDB_TYPE_CHAR, '100', null, XMLDB_NOTNULL, null, null, null, null);

    /// Adding keys to table blocks_courseprefs_sports
        $table->addKeyInfo('primary', XMLDB_KEY_PRIMARY, array('id'));

    /// Adding indexes to table blocks_courseprefs_sports
        $table->addIndexInfo('blocouspo-nam-uix', XMLDB_INDEX_UNIQUE, array('name'));

    /// Launch create table for blocks_courseprefs_sports
        $result = $result && create_table($table);

    /// Define table blocks_courseprefs_sportuser to be created
        $table = new XMLDBTable('block_courseprefs_sportusers');

    /// Adding fields to table blocks_courseprefs_sportuser
        $table->addFieldInfo('id', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, XMLDB_SEQUENCE, null, null, null);
        $table->addFieldInfo('sportsid', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null, null);
        $table->addFieldInfo('usersid', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null, null);

    /// Adding keys to table blocks_courseprefs_sportuser
        $table->addKeyInfo('primary', XMLDB_KEY_PRIMARY, array('id'));

    /// Adding indexes to table blocks_courseprefs_sportuser
        $table->addIndexInfo('blocouspouse-spouse-ix', XMLDB_INDEX_NOTUNIQUE, array('sportsid', 'usersid'));

    /// Launch create table for blocks_courseprefs_sportuser
        $result = $result && create_table($table);
    }

    if($result && $oldversion < 2010050513) {

        $table = new XMLDBTable('block_courseprefs_hooks');

        /// Adding fields to table block_courseprefs_hooks
        $table->addFieldInfo('id', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, 
                                   XMLDB_NOTNULL, XMLDB_SEQUENCE, null, null, null);
        $table->addFieldInfo('name', XMLDB_TYPE_CHAR, '255', null, 
                                   XMLDB_NOTNULL, null, null, null, null);
        $table->addFieldInfo('type', XMLDB_TYPE_CHAR, '100', null, 
                                   XMLDB_NOTNULL, null, XMLDB_ENUM, array('block', 
                                   'mod', 'enrol', 'auth', 'report', 'import', 
                                   'export', 'user'), 'block');

        /// Adding keys to table block_courseprefs_hooks
        $table->addKeyInfo('primary', XMLDB_KEY_PRIMARY, array('id'));
        $table->addIndexInfo('bloc_couhoonam', XMLDB_INDEX_UNIQUE, array('name'));

        $result = $result && create_table($table); 
    }

    if($result && $oldversion < 2011030912) {
        $table = new XMLDBTable('block_courseprefs_courses');

        $course_type_field = new XMLDBField('course_type');
        $course_type_field->setAttributes(XMLDB_TYPE_CHAR, '3', null, null, null, XMLDB_ENUM, array('CLI', 'IND', 'LEC', 'RES', 'SEM'), null, 'fullname');

        $grade_type_field= new XMLDBField('grade_type');
        $grade_type_field->setAttributes(XMLDB_TYPE_CHAR, '2', null, XMLDB_NOTNULL, null, XMLDB_ENUM, array('LP', 'N', 'L'), 'L', 'course_type');

        $first_year_field = new XMLDBField('first_year');
        $first_year_field->setAttributes(XMLDB_TYPE_INTEGER, '1', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null, '0', 'grade_type');

        $exception_field = new XMLDBField('exception');
        $exception_field->setAttributes(XMLDB_TYPE_INTEGER, '1', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null, '0', 'first_year');

        /// Launch add field course_type
        $result = $result && add_field($table, $course_type_field) && 
                             add_field($table, $grade_type_field) && 
                             add_field($table, $first_year_field) && 
                             add_field($table, $exception_field);
    } 

    if ($result && $oldversion < 2011062600) {

    /// Define field legal_writing to be added to block_courseprefs_courses
        $table = new XMLDBTable('block_courseprefs_courses');
        $field = new XMLDBField('legal_writing');
        $field->setAttributes(XMLDB_TYPE_INTEGER, '1', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null, '0', 'exception');

    /// Launch add field legal_writing
        $result = $result && add_field($table, $field);
    }

    return $result;
}

function find_section($sectionsid) {
    global $CFG;

    $sql = "SELECT sec.*, cou.course_number, cou.department
                   sem.year, sem.name
                FROM {$CFG->prefix}block_courseprefs_sections sec,
                     {$CFG->prefix}block_courseprefs_courses cou,
                     {$CFG->prefix}block_courseprefs_semesters sem
                WHERE sec.id = {$sectionsid}
                  AND sec.semestersid = sem.id
                  AND sec.coursesid = cou.id";
    $section = get_record_sql($sql);
    return $section;
}

?>
