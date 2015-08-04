<?php

/**
 * Constant defining code identifying Fall semester in mainframe preprocessing.
 */
define('SEMESTER_FALL', '1S');

/**
 * Constant defining code identifying Spring semester in mainframe preprocessing.
 */
define('SEMESTER_SPRING', '2S');

/**
 * Constant defining code identifying Summer semester in mainframe preprocessing.
 */
define('SEMESTER_SUMMER', '3S');

/**
 * Constant defining code identifying Winter Intersession semester in mainframe
 * preprocessing.
 */
define('SEMESTER_WINTER_INTERSESSION', '1T');

/**
 * Constant defining code identifying Spring Intersession semester in mainframe
 * preprocessing.
 */
define('SEMESTER_SPRING_INTERSESSION', '2T');

/**
 * Constant defining code identifying Summer Intersession semester in mainframe
 * preprocessing.
 */
define('SEMESTER_SUMMER_INTERSESSION', '3T');

/**
 * Constant defining code identifying a generic error involving courses.
 */
define('ERROR_COURSE', 2000);

/**
 * Constant defining code identifying an error where the user doesn't have any
 * courses.
 */
define('ERROR_COURSE_NONE', 2001);

/**
 * Constant defining code identifying a generic error involving the
 * creation/enrollment page.
 */
define('ERROR_CREATION', 3000);

/**
 * Constant defining code identifying an error where a new course-specific
 * creation/enrollment preference couldn't be inserted.
 */
define('ERROR_CREATION_COURSE_INSERT', 3001);

/**
 * Constant defining code identifying an error where an existing course-specific
 * creation/enrollment preference couldn't be updated.
 */
define('ERROR_CREATION_COURSE_UPDATE', 3002);

/**
 * Constant defining code identifying an error where the user's default
 * creation/enrollment preferences couldn't be updated.
 */
define('ERROR_CREATION_DEFAULT_UPDATE', 3003);

/**
 * Constant defining code identifying a generic error involving the cross
 * listing page.
 */
define('ERROR_CROSSLIST', 4000);

/**
 * Constant defining code identifying a generic error involving the materials
 * page.
 */
define('ERROR_MATERIALS', 5000);

/**
 * Constant defining code identifying a generic error involving the split page.
 */
define('ERROR_SPLIT', 6000);

/**
 * Constant defining code identifying an error where a new course-specific
 * split preference couldn't be inserted.
 */
define('ERROR_SPLIT_COURSE_INSERT', 6001);

/**
 * Constant defining code identifying an error where the user's default split
 * preference couldn't be updated.
 */
define('ERROR_SPLIT_DEFAULT', 6002);

/**
 * Constant defining code identifying a generic error involving the teamteaching
 * page.
 */
define('ERROR_TEAMTEACH', 7000);

/**
 * Constant defining code identifying a generic error involving the unwanted
 * page.
 */
define('ERROR_UNWANTED', 8000);

/**
 * Constant defining code identifying a generic error involving users.
 */
define('ERROR_USER', 1000);

/**
 * Constant defining code identifying an error where the user is missing from
 * the block's database tables.
 */
define('ERROR_USER_MISSING', 1001);

define('ERROR_SPLIT_MISSING', 1002);
define('ERROR_CROSSLIST_MISSING', 1003);
define('ERROR_TEAMTEACH_MISSING', 1004);
define('ERROR_UNWANTED_MISSING', 1005);
/**
 * Below are log levels used in the courseprefs system
 */
define('DEBUG', 0);
define('INFO', 1);
define('WARNING', 2);
define('ERROR', 3);

/**
 *
 */
define('STATUSCODE_TYPE_STUDENT', 'STUDENT');

/**
 *
 */
define('STATUSCODE_TYPE_TEACHER', 'TEACHER');

/**
 * Preference Statuses
 */
define('STATUS_TODO', 'todo');
define('STATUS_RESOLVED', 'resolved');
define('STATUS_UNDO', 'undo');

define('FERPED', 'Y');

/**
 *
 */
function lookup_errorcode($code) {

    $code = (int) $code;

    $rtn->error = get_string('error_unwanted', 'block_courseprefs');
    $rtn->reasons = '';
    switch ($code) {
        case ERROR_USER_MISSING:
            $rtn->error =  get_string('error_user_missing', 'block_courseprefs');
            return $rtn;
        case ERROR_COURSE_NONE:
            $rtn->error =  get_string('error_course_none', 'block_courseprefs');
            return $rtn;
        case ERROR_SPLIT_MISSING:
            $rtn = get_pref_error('split');
            return $rtn;
        case ERROR_CROSSLIST_MISSING:
            $rtn =  get_pref_error('cross-listed');
            return $rtn;
        case ERROR_TEAMTEACH_MISSING:
            $rtn =  get_pref_error('teamtaught');
            return $rtn;
        case ERROR_UNWANTED_MISSING:
            $rtn =  get_pref_error('unwanted');
            return $rtn;
    }

    $category = $code - ($code % 1000);

    switch ($category) {
        case ERROR_USER:
            $rtn->error =  get_string('error_user', 'block_courseprefs');
            return $rtn;
        case ERROR_COURSE:
            $rtn->error =  get_string('error_course', 'block_courseprefs');
            return $rtn;
        case ERROR_CREATION:
            $rtn->error =  get_string('error_creation' , 'block_courseprefs');
            return $rtn;
        case ERROR_CROSSLIST:
            $rtn->error = get_string('error_crosslist', 'block_courseprefs');
            return $rtn;
        case ERROR_MATERIALS:
            $rtn->error = get_string('error_materials', 'block_courseprefs');
            return $rtn;
        case ERROR_SPLIT:
            $rtn->error = get_string('error_split', 'block_courseprefs');
            return $rtn;
        case ERROR_TEAMTEACH:
            $rtn->error =  get_string('error_teamteach', 'block_courseprefs');
            return $rtn;
        case ERROR_UNWANTED:
            $rtn->error =  get_string('error_unwanted', 'block_courseprefs');
            return $rtn;
    }

    return $rtn;
}

function get_pref_error($pref) {
    global $CFG;
    $a->preference = $pref;
    $rtn->reasons = '<div class="cps_reason"><div class="reason_header">'.
                   get_string('cps_reasons', 'block_courseprefs'). ':</div>'.
                   get_string($pref. '_error', 'block_courseprefs', $CFG).
                   get_string('same_error', 'block_courseprefs', $CFG).
                   '</div>';
    $rtn->error = get_string('error_preference_missing', 'block_courseprefs', $a);
    return $rtn;
}

/**
 *
 */
function lookup_semestername($code) {

    if (SEMESTER_FALL == $code) {
        return 'Fall';
    } else if (SEMESTER_SPRING == $code) {
        return 'Spring';
    } else if (SEMESTER_SUMMER == $code) {
        return 'Summer';
    } else if (SEMESTER_WINTER_INTERSESSION == $code) {
        return 'WinterInt';
    } else if (SEMESTER_SPRING_INTERSESSION == $code) {
        return 'SpringInt';
    } else if (SEMESTER_SUMMER_INTERSESSION == $code) {
        return 'SummerInt';
    }

    return false;
}

/**
 *
 */
function lookup_statuscode($code, $type = STATUSCODE_TYPE_TEACHER) {

    if ($type == STATUSCODE_TYPE_TEACHER) {

        if ('ID' == $code) {
            return 'unenroll';
        } else if ('SC' == $code) {
            return 'unenroll';
        } else if ('SD' == $code) {
            return 'unenroll';
        } else if ('IA' == $code) {
            return 'enroll';
        }
    } else if ($type == STATUSCODE_TYPE_STUDENT) {

        if ('DR' == $code) {
            return 'unenroll';
        } else if ('PR' == $code) {
            return 'unenroll';
        } else if ('CN' == $code) {
            return 'unenroll';
        } else if ('AD' == $code) {
            return 'enroll';
        }
    }

    return false;
}

/**
 * Redirects users to the block error page with an error code and optional
 * URL to redirect users to.
 */
function redirect_error($code, $url = null) {

    global $CFG;

    // Set the redirect URL if it wasn't specified as a parameter
    if (!$url) {
        $url = $CFG->wwwroot;
    }

    // URL encode parameters to error page
    $code = urlencode($code);
    $url = urlencode($url);

    // Redirect user to error page with encoded parameters
    redirect($CFG->wwwroot . "/blocks/courseprefs/error.php?error=$code" .
        ($url ? "&url=$url" : ''));
}

/**
 * Callback used in 'register_shutdown_function' function call to check for
 * errors upon script execution.  Email administrators if any errors occurred
 * on execution.
 */
function report_errors($errorlog, $file, $from, $subject, $error=true) {

    global $CFG;

    // Exit function if the errorlog is empty; nothing to report
    if (!$errorlog) {
        return;
    }

    $admin_users = get_admins();
    $messagetext = '';
    if ($error) {
        $messagetext = "Following issues occurred while executing $file\n\n";
    }
    $messagetext .= implode("\n", $errorlog);

    foreach ($admin_users as $admin) {
        email_to_user($admin, $from, $subject, $messagetext);
    }
}

$semesters = array('Spring' => 1, 'SpringInt'=> 2, 'Summer' =>3, 'SummerInt'=>4,
        'Fall'=>5, 'WinterInt'=>6 );

/**
* Function that compares two display strings for year and semester; ex:
* "2007 Fall ... ..."
* a_arr[0] = 2008, a_arr[1] = Fall
*
* The function uses the preordered $semesters array to determine the correct
* order of classes.
*/
function cmpSemester($a, $b) {
   
    global $semesters;

    // Assumption is being made on the comparison where a/b is:
    // YEAR SEMESTER_NAME DEPARTMENT COURSE_NUMBER SECTION_NUMBER
    list($a_year, $a_semester, $a_department, $a_course_number, $a_section_number) = explode(' ', $a);
    list($b_year, $b_semester, $b_department, $b_course_number, $b_section_number) = explode(' ', $b);

    if ($a_year != $b_year) {
        return strcmp($a_year, $b_year);
    } else if ($a_semester != $b_semester) {
        return strcmp($semesters[$a_semester], $semesters[$b_semester]);
    } else if ($a_department != $b_department) {
        return strcmp($a_department, $b_department);
    } else if ($a_course_number != $b_course_number) {
        return strcmp($a_course_number, $b_course_number);
    } else if ($a_section_number != $b_section_number) {
        return strcmp($a_section_number, $b_section_number);
    }

    return 0;
}

/**
 * Courseprefs clean moved to an external function to be appended anywhere as necessary
 */
function courseprefs_cleanup($file='cleanup.php', $semester=0, $logger=null, $web_report=false) {
    global $CFG;

    if (!$logger) {
        $logger = new Logger(true);
    }

    $clean_children = array("block_courseprefs_teamteach",
                            "block_courseprefs_crosslist",
                            "block_courseprefs_split",
                            "block_courseprefs_logs",
                            "block_courseprefs_teachers",
                            "block_courseprefs_students");

    if ($semester == 0) {
        $sql = "SELECT * FROM {$CFG->prefix}block_courseprefs_semesters 
                WHERE grades_due < " . time();
    } else {
        $sql = "SELECT * FROM {$CFG->prefix}block_courseprefs_semesters
                WHERE id = {$semester}";
    }

    require_once($CFG->dirroot . '/blocks/courseprefs/classes/CoursePrefsHook.php');

    $hooks = CoursePrefsHook::allHooks($logger);

    // Parent semesters to remove
    $semesters = get_records_sql($sql);

    // If the result from query is not null, iterate
    if ($semesters) {

        foreach ($semesters as $semester) {
            $hooks->execute('cleanup', $semester->id);
        
            // For processing time's sake, we remove enroll here
            delete_records('block_courseprefs_enroll', 'semestersid', $semester->id);
    
            $str = "Removing {$semester->campus} {$semester->name} {$semester->year}" . 
                " and all associated data.";
            $logger->log($str);
            if ($web_report) {
                mtrace($str);
            }
        }
        $semesterids = implode(',', array_keys($semesters));
        $sql = "SELECT * FROM {$CFG->prefix}block_courseprefs_sections
                  WHERE semestersid IN ({$semesterids})";

        $sections = get_records_sql($sql);
        
        foreach ($sections as $section) {

            // Blow away the 'normal' cases first
            foreach ($clean_children as $table) {
                delete_records($table, 'sectionsid', $section->id);
            }

            // Remove the section entry
            delete_records('block_courseprefs_sections', 'id', $section->id);
            $str = "Removing section {$section->id} {$section->idnumber}";
            $logger->log($str);
        }
        // Remove the semesters
        delete_records_select('block_courseprefs_semesters', 
                'id IN ('. $semesterids .')');
    } else {
        $str = "There are no semesters to remove.";
        $logger->log($str);
        if ($web_report) {
            mtrace($str);
        }
    }

    $header = get_string('cleanup_header', 'block_courseprefs');    
    $logger->reportErrors($file, $header, $header);
}

function reset_prefs($prefs) {
    $now = time();
    foreach ($prefs as $pref) {
        $status = $pref->getStatus();
        switch ($status) {
            case 'todo':
                $pref->deleteById($pref->getId());
                break;
            case 'resolved':
                $pref->setStatus('undo');
                try {
                    $pref->save();
                } catch(Exception $e) {
                    //swallow exception
                }
                break;
        }
    }
}

function build_form($callback, $param, $next="Next", $back='Back', $method="GET", $action="split.php", $script='') {
    global $CFG;
    echo '<form class="mform" '.$script.'  method="'.$method.'" action="'.$action.'">';
    //$html = '<div class="select_splits">';
    $html = '<fieldset>';
    $html .= $callback($param);

    $html .= '</fieldset>';
    //$html .= '</div>';
    echo $html;
    echo '<div class="cps_form_buttons">';
    echo '<a style="text-decoration:none;" href="'.$CFG->wwwroot.'/my">
                     <input type="button" value="'.
                     get_string('home_button', 'block_courseprefs').'"></a>';
    if (!empty($back)) {
        echo '<input class="form_back" type="submit" name="submit" value="'.$back.'"/>';
    }
    echo '<input class="form_next" type="submit" name="submit" value="'.$next.'"/>';
    echo '</div>';
    echo '</form>';
}

function finished_content($params) {
    list($changed_prefs, $new_prefs, $errors) = $params;

    
    // Give user some feedback; maybe something went down
    $html = print_errors($errors);

    if (empty($changed_prefs) && empty($new_prefs)) {
        $html .= '<span class="label">There are no changes</span>';
    } else {
        $html .= print_changes('Change settings:', $changed_prefs);
        $html .= print_changes('New settings:', $new_prefs);
    }

    return $html;
}

function print_errors($errors) {
    $html = '';
    if (!empty($errors)) {
        $html = '<span class="errors">'.get_string('changes_not_saved', 
                                                   'block_courseprefs').'</span>';
        $html .= "<ul>";
        foreach ($errors as $error) {
            $html .= '<li>'.$error.'</li>';
        }
        $html .= "</ul>";
    }
    return $html;
}

function print_changes($label, $the_prefs) {
    $html = '';
    if (!empty($the_prefs)) {
        $html = '<span class="label">'.$label.'</span>';
        $html .= "<ul>";
            foreach ($the_prefs as $bucket => $sections) {
                $html .= '<li>Changes for Course '.$bucket.'</li>';
                $html .= '<ul>';
                foreach ($sections as $section) {
                    $html .= '<li>'.CoursePrefsSection::generateFullname($section->year, 
                                $section->name, $section->department, 
                                $section->course_number, $section->section_number).'</li>';
                }
                $html .= '</ul>';
            }
        $html .= "</ul>";
    }
    return $html;
}

function block_process($current, $timestamp) {
    
    $current_record = get_record('block_courseprefs_config', 'name', $current);

    // Process has never been run
    if (!$current_record) {
        $current_record = new stdClass;
        $current_record->name = $current;
        $current_record->value = 'DONE';
        $current_record->id = insert_record('block_courseprefs_config', $current_record, true);
    }

    // Process is not running
    if ($current_record->value == 'DONE') {
        $current_record->value = $timestamp;
        update_record('block_courseprefs_config', $current_record);
        return false;
    }

    // Process is running
    return (int)$current_record->value;
}

/**
 * Returns the CPS sections based on the current course
 */
function cps_sections($course, $where=null) {
    global $CFG;

    $sql = "SELECT s.*, c.department, c.course_number
                FROM {$CFG->prefix}block_courseprefs_sections s,
                     {$CFG->prefix}block_courseprefs_courses  c
                WHERE s.coursesid = c.id
                  AND s.idnumber = '{$course->idnumber}'
                  ".(($where) ? "AND {$where}" : '')."
                  ORDER BY s.section_number";
    return get_records_sql($sql);
}

function unblock_process($blocker) {
    // We're done with the process
    set_field('block_courseprefs_config', 'value', 'DONE', 'name', $blocker);
}

class Logger {

    private $current_level;
    private $errorlog;

    function __construct($force=false) {
        $current_level =  get_field('block_courseprefs_config', 'value', 'name', 'log_level');
        if (is_null($current_level) || $force) {
            $current_level = DEBUG;
        }
        $this->current_level = $current_level;
        $this->errorlog = array();
    }

    function log($message, $level=ERROR) {
        if ($this->current_level <= $level) {
            $this->errorlog[] = $message;
        }
    }

    function getErrorLog() {
        return $this->errorlog;
    }

    function reportErrors($file, $description, $subject) {
        report_errors($this->errorlog, $file, $description, $subject);
    }
}

?>
