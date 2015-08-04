<?php

require_once('../lib.php');

/**
    The library containing only preprocess info / classes
*/

// Base abstract class
abstract class Preprocess {
   
    abstract function process($now);

    abstract function courseprefs_date_to_stamp($datestring);
 
    function process_user($fields, $now) {
        // Lookup user from database; throw an error if found
        $user = CoursePrefsUser::findByUnique($fields[0]);

        if (!$user) {
            $user = CoursePrefsUser::findByIdnumber($fields[3]);
        }
     
        if (!$user) {
            $user = new CoursePrefsUser();
        }

        $user->setMoodleField('firstname', $fields[2]);
        $user->setMoodleField('lastname', $fields[1]);
        $user->setMoodleField('username', $fields[0]);
        $user->setMoodleField('idnumber', $fields[3]);
        $user->setMoodleField('college', trim($fields[4]));
        $user->setMoodleField('year', (empty($fields[5]) ? 0 : $fields[5]));
        $user->setMoodleField('classification', trim($fields[6]));
        $user->setMoodleField('reg_status', $fields[7]);
        $user->setMoodleField('keypadid', trim($fields[8]));
        $user->setMoodleField('degree_candidacy', $fields[9]);
        // $user->setMoodleField('anonymous', $fields[10]);

        // Create user entry and signal error if unable to do so
        try {
    //        $user = new CoursePrefsUser($fields[0], $fields[2], $fields[1], $fields[3]);
            $user->save();
            CoursePrefsLog::add_to_log($user->getId(), 0, $now, 'create', "{$user->getFirstname()} {$user->getLastname()} created in Moodle");
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    function process_course($fields, $now) {
        list($year, $semester_name) = $this->parse_semester($fields[0]);
        $semester = CoursePrefsSemester::findByUnique($year, $semester_name, $fields[2]);

        if (!$semester) {
            throw new Exception(get_string('no_semester', 'block_courseprefs'));
        }

        // Check to see if the course already exists; if not, then create it
        $course = CoursePrefsCourse::findByUnique($fields[3], $fields[4]);

        $first_year = ($fields[12] == 'FY') ? 1 : 0;
    
        if (!$course) {
            $course = new CoursePrefsCourse($fields[3], $fields[4], $fields[6], 
                                            $fields[10], $fields[11], $fields[12]);
        }

        // For updating pruposes
        $course->setCourseType($fields[10]);
        $course->setGradeType($fields[11]);
        $course->setFirstYear($first_year);

        // Log exception if the course doesn't exist and could not be created
        try {
            $course->save();
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }

        $section = CoursePrefsSection::findByUnique($semester->getId(), $course->getId(), $fields[5]);

        if (!$section) {
            $section = new CoursePrefsSection($semester->getId(), $course->getId(), $fields[5]);
        }

        // Log exception if the section doesn't exist and could not be created
        try {
            // Only attempt to save the section if it doesn't have an id; new entry
            if (!$section->getId()) {
                $section->save();
                $name = "{$year} {$semester_name} {$course->getDepartment()} {$course->getCourseNumber()}";
                CoursePrefsLog::add_to_log(0, $section->getId(), $now, 'create', $name);
            }

        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }

        return $section;
    }

    function process_semester($fields) {
        // Lookup the semester from the database
        list($year, $semester_name) = $this->parse_semester($fields[1]);
        $semester = CoursePrefsSemester::findByUnique($year, $semester_name, $fields[0]);

        // Create a new semester entry if the semester doesn't exist within the database
        if (!$semester) {
            $semester = new CoursePrefsSemester($year, $semester_name, $fields[0]);
        }

        // Set when classes start for the semester if available
        if ($fields[2]) {
            $semester->setClassStart($this->courseprefs_date_to_stamp($fields[2]));
        }

        // Set when grades are due for the semester if available
        if ($fields[3]) {
            $semester->setGradesDue($this->courseprefs_date_to_stamp($fields[3]));
        }

        try {
            $semester->save();
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    function process_enrollment($user, $section, $fields) {
        global $CFG;

        $role = (int) $fields[0];
        // Student role=1
        // Else it's a Teacher primary role=2
        if ($role == 1) {
            $status = lookup_statuscode($fields[1], STATUSCODE_TYPE_STUDENT);
            $student = CoursePrefsStudent::findByUnique($section->getId(), $user->getId());
            $credit_hours = $fields[4];
            if (!$student && $status == 'unenroll') {
                throw new Exception(get_string('cant_drop', 'block_courseprefs'));
            } else if(!$student){
                $student = new CoursePrefsStudent($section->getId(), $user->getId(), 
                                                  $status, $credit_hours);
            } else {
                // Recalculate hours
                $student->findHours($status, $credit_hours);
            }

            try {
                $student->save();
            } catch (Exception $e) {
                throw new Exception($e->getMessage());
            }

            // Time to add a log entry for this course
            CoursePrefsLog::add_to_log($user->getId(), 
                $section->getId(), strtotime($fields[2]), 
                $status, strtotime($fields[3]));

        } else {
            $status = lookup_statuscode($fields[1], STATUSCODE_TYPE_TEACHER);
            $primary_flag = ($role == 2) ? 1 : 0;
            $teacher = CoursePrefsTeacher::findByUnique($user->getId(), 
                                                        $section->getId(),
                                                        $primary_flag);

            // In the event that the teacher does not exist, and the mainframe
            // gives us a non-primary teacher drop, then change the info to be a
            // primary teacher drop.
            if (!$teacher && !$primary_flag && $status == 'unenroll') {
                $role = 2;
                $teacher = CoursePrefsTeacher::findByUnique($user->getId(),
                                                    $section->getId(),
                                                    1);
            }

            if (!$teacher) {
                $teacher = new CoursePrefsTeacher($section->getId(), $user->getId(), 
                                                  $primary_flag, $status);
            }

            /*
            $teacher->setPrimaryFlag($primary_flag);
            */
            $teacher->setStatus($status);
            $teacher->setTimeStamp(strtotime($fields[2]));

            try {
                $teacher->save();
                CoursePrefsLog::add_to_log($user->getId(), $section->getId(), 
                    strtotime($fields[2]), $status, 'Teacher event');
            } catch(Exception $e) {
                throw new Exception($e->getMessage());
            }

            // Great job; now if the primary teacher is being dropped, mark the section as pending
            // cron will take care of the rest
            if ($role == 2 && $status == 'unenroll') {
                $section->setStatus('pending');
                $section->save();

                // Clean any preference that was applied
                foreach(array('crosslist', 'split') as $pref) {
                    $sql = "UPDATE {$CFG->prefix}block_courseprefs_{$pref}
                                SET status='todo'
                                WHERE sectionsid={$section->getId()}";
                    execute_sql($sql,false);
                    // delete_records('block_courseprefs_'.$pref, 'sectionsid', $section->getId());
                }
            }
        }    
    }

    function parse_semester($yearname) {
        $year = substr($yearname, 0, 4);
        $semester = substr($yearname, 4);
        $semester_name = lookup_semestername($semester);

        // Roll the year back if Fall or Winter Intersession semesters
        // LSU associates semester with catalog instead of actual year
        if ($semester == SEMESTER_FALL || $semester == SEMESTER_WINTER_INTERSESSION) {
            $year--;
        }

        return array($year, $semester_name);
    }
}

// Mainframe preprocessor
class FilePreprocess extends Preprocess {
    var $errorlog = array();

    private $files;
    private $function_map;

    function setFiles($files) {
        $patharray = array();

        $this->files = array();
        $this->function_map = array();
        foreach ($files as $key => $value) {
            $config = CoursePrefsConfig::findByUnique($key);
            if (!$config) {
                throw new Exception(get_string('not_configured', 'block_courseprefs', $value));
            }
            $patharray[$key] = $config->getValue();
        }

        $input_path = $patharray['inputpath'];
        unset($patharray['inputpath']);
        foreach ($patharray as $key => $value) {
            $this->files[$key] = $input_path . $patharray[$key];
        }

        // Check to make sure that all input data files are available;
        // perform function map hooks
        foreach($this->files as $key => $file){
            if (!file_exists($file)) {
                throw new Exception(get_string('no_file', 'block_courseprefs', $file));
            }
            $this->function_map[$key] = 'parse_' . $key;
        }
         
        $file_time = filemtime($this->files['userfile']);
        $last_run = get_record('block_courseprefs_config', 'name', 'last_run');
        if (!$last_run) {
            $last_run = new stdClass;
            $last_run->value = 'DONE';
            $last_run->name = 'last_run';
            $last_run->id = insert_record('block_courseprefs_config', $last_run, true);
        }

        // Always blank out sports people
        $this->blank_out('sportsfile');

        // This file has never been processed; blank out read lines
        if ($last_run->value != $file_time) {
            foreach ($this->files as $key => $value) {
                $this->blank_out($key);
            }
            $last_run->value = $file_time;
        }
        
        update_record('block_courseprefs_config', $last_run);
    }

    function blank_out($file) {
        $config = CoursePrefsConfig::findByUnique($file . '_lines');
        if (!$config) {
            $config = new CoursePrefsConfig($file. '_lines', 0);
        }
        $config->setValue(0);
        $config->save();
    }

    function get_current_line($file) {
        $config = CoursePrefsConfig::findByUnique($file . '_lines');
        if (!$config) {
            throw new Exception(get_string('data_integrity','block_courseprefs', $file));
        }
        return $config->getValue();
    }

    function parse_file($now, $filekey, $fun) {
        $lines = file($this->files[$filekey]);
        try {
            $lines = $this->get_lines($filekey, $lines);
        } catch (Exception $e) {
            $this->errorlog[] = $e->getMessage();
            return;
        }

        $count = CoursePrefsConfig::findByUnique($filekey . '_lines');
        foreach ($lines as $line) {
            $count->setValue($count->getValue() + 1);
            
            $this->{$fun}($now, $line);

            // Update counter
            $count->save();
        }      
    }

    /**
     * Process Sports file
     * IDNUMBER,CODE
     */
    function parse_sportsfile($now, $line) {
        $fields = array_map('trim', explode(' ', $line));
        
        if(count($fields) != 2) {
            $a->file = $this->files['soprtsfile'];
            $a->line = $line;
            $this->errorlog[] = get_string('malformed', 'block_courseprefs', $a);
            return;
        }
        
        try {
            // Try to get the user
            $user = CoursePrefsUser::findByIdnumber($fields[0]);
            if(!$user) {
                $this->errorlog[] = get_string('no_user', 'block_courseprefs', $line);
                return;
            }

            // Try to get the sport, if it doesn't exists, save it
            $sport = CoursePrefsSport::findByUnique($fields[1]);

            if(!$sport) {
                $sport = new CoursePrefsSport($fields[1], $fields[1]);
               
                $sport->save();
            }

            // Try to add the relationship
            $sport->addUser($user);

        } catch(Exception $e) {
            $this->errorlog[] = $e->getMessage() . ' from ' . $this->files['sportsfile'] . ': ' . $line;
        }
    }

    /**
     * PROCESS USERS FILE
     * Expects a file of format: PawsID|Last Name|First Name|LSU ID|COLLEGE|YEAR|MAJOR|REG DATE|KEYPADID|DEGREE CAN|Anon
     */
    function parse_userfile($now, $line) {
        $fields = array_map('trim', explode('|', $line));
        
        // process only if we have the correct number of fields
        if (count($fields) != 11) {
            $a->file = $this->files['userfile'];
            $a->line = $line;
            $this->errorlog[] = get_string('malformed', 'block_courseprefs', $a);
            return;
        }

        try {
            $fields[] = null;
            $this->process_user($fields, $now);
        }catch (Exception $e) {
            $this->errorlog[] = $e->getMessage() . ' from ' . $this->files['userfile'] . ': ' . $line; 
        }
    }

    /**
     * PROCESS SEMESTERS FILE
     * Expects a file of format: Campus|Semester|First DayOf Classes|Final Grades Due
     */
    function parse_datesfile($now, $line) {
        $fields = array_map('addslashes', array_map('trim', explode('|', $line)));
            
        // process only if we have the correct number of fields
        if (count($fields) != 4) {
            $a->file = $this->files['datesfile'];
            $a->line = $line;
            $this->errorlog[] = get_string('malformed', 'block_courseprefs', $a);
            return;
        }

        try {
            $this->process_semester($fields);
        } catch (Exception $e) {
            $this->errorlog[] = $e->getMessage() . ' from ' . $this->files['datesfile'] . ': ' . $line;
        }
    }

    /**
     * PROCESS COURSES FILE
     * Expects a file of format: Semester|Instructor PawsID|Campus|DEPT|Course #|Section|Course Title|is Primary|Date|Status Code|Course Type|Grading Type|First Year
     */
    function parse_coursefile($now, $line) {
        global $CFG;

        $fields = array_map('addslashes', array_map('trim', explode('|', $line)));

        // Log exception if the incorrect number of fields was found
        if (count($fields) != 13) {
            $a->file = $this->files['coursefile'];
            $a->line = $line;
            $this->errorlog[] = get_string('malformed', 'block_courseprefs', $a);
            return;
        }

        try {
            $section = $this->process_course($fields, $now);

            // SPECIAL CASE: Section drop, get rid of everyone!
            if ($fields[9] == 'SC' || $fields[9] == 'SD') {
                // Sett all teachers of this section to be unenrolled
                $sql = "UPDATE {$CFG->prefix}block_courseprefs_teachers 
                            SET status='unenroll' 
                        WHERE sectionsid={$section->getId()}";
                execute_sql($sql, false);
                // Pending section says to remove students
                $section->setStatus('pending');
                $section->save();
                
                // Clean any preference that was applied
                foreach(array('crosslist', 'split') as $pref) {
                    $sql = "UPDATE {$CFG->prefix}block_courseprefs_{$pref}
                                SET status='todo'
                                WHERE sectionsid={$section->getId()}";
                    execute_sql($sql, false);
                    // delete_records('block_courseprefs_'.$pref, 'sectionsid', $section->getId());
                }
                return;
            }

            // Lookup teacher's user information from the database; throw an error if not available
            $user = CoursePrefsUser::findByUnique($fields[1]);
            if (!$user) {
                $this->errorlog[] = 'Unable to lookup teacher\'s user information from ' . $this->files['coursefile'] . ': ' . $line;
                return;
            }

            $new_fields = array(($fields[7] == 'Y') ? 2 : 3, $fields[9], $fields[8]);

            if($new_fields[1] == 'IM') {
                // Handle the add, or "modify" into new role
                $new_fields[1] = 'IA';
                $this->process_enrollment($user, $section, $new_fields);

                // Now handle the drop: easiest after an appropriate add is in place
                $new_fields[1] = 'ID';
                $new_fields[0] = ($fields[7] == 'Y') ? 3 : 2;
            }

            $this->process_enrollment($user, $section, $new_fields);
            
        } catch(Exception $e) {
            $this->errorlog[] = $e->getMessage() . ' from ' .  $this->files['coursefile'] . ': ' . $line;
        }
    }


    /**
     * PROCESS ENROLLMENT FILE
     * Expects a file of format: Semester|Student PawsID|Campus|Department|Course #|Section #|Status Code|HRS|Real Date|EFFECTIVE DATE|FERP
     */
    function parse_enrollfile($now, $line) {
        $fields = array_map('addslashes', array_map('trim', explode('|', $line)));

        // Process only if we have the correct number of fields
        if (count($fields) != 11) {
            $a->file = $this->files['enrollfile'];
            $a->line = $line;
            $this->errorlog[] = get_string('malformed', 'block_courseprefs', $a);
            return;
        }
            
        // Lookup necessary foreign keys for enrollment lookup; throw exception if any are missing
        list($year, $semester_name) = $this->parse_semester($fields[0]);
        $semester = CoursePrefsSemester::findByUnique($year, $semester_name, $fields[2]);

        if (!$semester) {
            $this->errorlog[] = 'Unable to lookup semester entry for enrollment in ' . $this->files['enrollfile'] . ': ' . $line;
            return;
        }

        $user = CoursePrefsUser::findByUnique($fields[1]);

        if (!$user) {
            $this->errorlog[] = 'Unable to lookup user entry for enrollment in ' . $this->files['enrollfile'] . ': ' . $line;
            return;
        }

        $course = CoursePrefsCourse::findByUnique($fields[3], $fields[4]);

        if (!$course) {
            $this->errorlog[] = 'Unable to lookup course entry for enrollment in ' . $this->files['enrollfile'] . ': ' . $line;
            return;
        }

        $section = CoursePrefsSection::findByUnique($semester->getId(), $course->getId(), $fields[5]);

        if (!$section) {
            $this->errorlog[] = 'Unable to lookup section entry for enrollment in ' . $this->files['enrollfile'] . ': ' . $line;
            return;
        }

        try {
            $new_fields = array(1, $fields[6], $fields[8], $fields[9], $fields[7]); 
            $this->process_enrollment($user, $section, $new_fields);
        } catch(Exception $e) {
            $this->errorlog[] = $e->getMessage() . ' from ' . $this->files['enrollfile'] . ': ' . $line;
        }

        $user->setFerpa($fields[10]);
        try {
            $user->save();
        }catch (Exception $e) {
            $this->errorlog[] = $e->getMessage() . ' from ' . $this->files['enrollfile'] . ': '
            . $line;
        }
    }

    function get_lines($file, $lines) {
        $last_read = $this->get_current_line($file);
        if ($last_read != count($lines)) {
            return array_slice($lines, $last_read);
        }
        throw new Exception("Already read {$file}");
    }

    // Now consumes the files appropriately
    function process($now) {

        $start_time = explode(" ", microtime());
        $start_time = $start_time[1] + $start_time[0];

        // Start the run process
        $this->start_run($now);

        // Before we start processing, we have to truncate the sport users
        CoursePrefsSport::truncateSportUsers();

        // Parse the files
        foreach($this->files as $key => $file) {
            $this->parse_file($now, $key, $this->function_map[$key]);
        }

        // Clear the run flag
        unblock_process('running');

        // Check the line numbers processed
        if ($this->empty_files($now)) {
            // Houston, we have a problem
            // Send an email to our moodle admin friends
            report_errors(array('The Mainframe sent empty files.',
                                'This is not a weekend.'),
                          __FILE__, 'Course Preferences Mainframe Processor', 
                          'EMERGENCY Read Me');
        }

        $end_time = explode(" ", microtime());
        $end_time = $end_time[1] + $end_time[0];

        //Force an email to be sent
        $this->errorlog[] = 'Preprocessor finished.';
        $this->errorlog[] = 'Clocked in at: ' . ($end_time - $start_time) . ' seconds';
    }

    function start_run($now) {
        // Sleep anywhere from 1 to 5 seconds
        usleep(rand(100000, 500000));

        if (block_process('running', $now)) {
            throw new Exception(get_string('blocked', 'block_courseprefs'));
        } 
    }

    function courseprefs_date_to_stamp($datestring) {
        $parts = explode('/', $datestring);
        return mktime(0, 0, 0, $parts[0], $parts[1], $parts[2]);
    }

    /**
     * Check the time with lines processed
     * Log an error if it's not a weekend
     */
    function empty_files($now) {
        global $CFG;
        // If we're in a weekend, it doesn't matter
        $weekend = array(6,7);
        $current_day = date("N", $now);
        if (in_array($current_day, $weekend)) {
            return false;
        }

        $sql = "SELECT name, value 
                FROM {$CFG->prefix}block_courseprefs_config
                WHERE name LIKE '%_lines'";

        $lines = get_records_sql_menu($sql);
        return array_sum(array_values($lines)) == 0;
    }
}

// Web service preprocess
class XmlPreprocess extends Preprocess {
    var $errorlog = array();    
    private $xml;

    function courseprefs_date_to_stamp($datestring) {
        $parts = explode('-', $datestring);
        return mktime(0, 0, 0, $parts[1], $parts[2], $parts[0]);
    }

    function setURL($url, $option, $web) {
        $this->xml = new SimpleXMLElement($url, $option, $web);
    }

    function process($now) {
        foreach ($this->xml->children() as $name => $child) {
            switch ($name) {
                case "Courses":
                    $this->parse_course($child, $now);
                    unset($child);
                    break;
                case "Semesters":
                    $this->parse_semesters($child);
                    unset($child);
                    break;
                case "Users":
                    $this->parse_user($child, $now);
                    unset($child);
                    break;
            }
        }
    }

    function parse_course($xml, $now) {
        if (empty($xml)) {
            return;
        }
       
        /* Expects a file of format: Semester|Instructor PawsID|Campus|DEPT|Course #|Section|Course Title|is Primary|Date|Status Code|Course Type|Grading Type|First Year*/
        foreach($xml->children() as $child) {
            $department = current($child->department);
            $campus = ($department != 'LAW') ? 'LSU' : 'LAW';
            $fields = array(current($child->semesterCode), null, 
                            $campus, $department, current($child->courseNumber), 
                            current($child->sectionNumber), current($child->description));
            try {
                $section = $this->process_course($fields, $now);
                
                if (isset($child->Enrollments)) {
                    $this->parse_enrollment(current($child->Enrollments), $section);
                }
            } catch (Exception $e){
                $this->errorlog[] = '';
            }


            unset($child);
        } 
    }

    function parse_semesters($xml) {
        if (empty($xml)) {
            return;
        }

        foreach ($xml->children() as $child) {
            try {
                $new_fields = array(current($child->campus), 
                                    current($child->semesterCode),
                                    current($child->startDate),
                                    current($child->endDate));
                $this->process_semester($new_fields);
            } catch (Exception $e) {
                $this->errorlog[] = $e->getMessage();
            }
        }
    }

    function parse_enrollment($xml, $section) {
        if (empty($xml)) {
            return;
        }

        foreach ($xml as $child) {
            $user = CoursePrefsUser::findByIdnumber(current($child->idNumber));
            if (!$user) {
                continue;
            }

            try {
                $fields = array(current($child->role), current($child->action),
                               current($child->realTimeStamp), 
                               current($child->effectiveDate), current($child->creditHours));
                
                $this->process_enrollment($user, $section, $fields);
            } catch (Exception $e) {
                $this->errorlog[] = $e->getMessage();
            }
        }
    }

    function parse_user($xml, $now) {
        if(empty($xml)) {
            return;
        }

        foreach ($xml->children() as $child) {
            $fields = array(
                        current($child->userId),
                        current($child->lastName),
                        current($child->firstName),
                        current($child->idNumber),
                        current($child->college),
                        current($child->year),
                        current($child->major),
                        current($child->regDate),
                        current($child->keyPadId),
                        current($child->degreeCandidacy),
                        current($child->privacy)
                        );
            try {
                $this->process_user($fields, $now);
            } catch (Exception $e) {
            }
        }
    }
}

?>
