<?php // $Id: config_form.php,v 2.5 2008/02/15 09:53:43 pcali1 Exp $

/**
 *   Author: Philip Cali
 *   Date: 2/15/08
 *   Louisiana State University
 *   
 *   Moodle form for configuring the Courseprefs block
 */

require_once($CFG->libdir . '/formslib.php');
require_once('lib.php');

define('BASE_PATH', 'inputpath');
define('USER_FILE', 'userfile');
define('COURSE_FILE', 'coursefile');
define('ENROLL_FILE', 'enrollfile');
define('DATE_FILE', 'datesfile');
define('LOG_SELECT', 'log_level');
define('CREATE_DAYS', 'course_create_days');
define('ENROLL_DAYS', 'course_enroll_days');

class config_form extends moodleform {
    
    function definition() {
        global $CFG, $USER;

        $form=&$this->_form;

        $role_options = get_records_menu("role", '', '' , '', "id, name");
        $role_options = array(0 => get_string('none')) + $role_options;

        $cps_role_name = array(1 => "Student", 2 => "Primary Instructor", 3 => "Secondary Instructor");

        $log_options = array(DEBUG => 'Debug', 
                             INFO => 'Info', 
                             WARNING =>'Warning', 
                             ERROR =>'Error');

        $form->registerRule('valid_days', 'regex', '/^\d+$/');

        $form->addElement('header', 'settings_header',get_string('courseprefs:canconfig', 'block_courseprefs'));
        $form->addElement('text', BASE_PATH, get_string('input_path', 'block_courseprefs'), array('size' => 50));
        $form->addElement('text', USER_FILE, get_string('user_file', 'block_courseprefs'));
        $form->addElement('text', COURSE_FILE, get_string('course_file', 'block_courseprefs'));
        $form->addElement('text', ENROLL_FILE, get_string('enrol_file', 'block_courseprefs'));
        $form->addElement('text', DATE_FILE, get_string('date_file', 'block_courseprefs'));
       
        $form->addElement('header', 'enroll_header',get_string('enroll_header', 'block_courseprefs'));
        $form->addElement('text', CREATE_DAYS, get_string(CREATE_DAYS, 'block_courseprefs'));
        $form->addRule(CREATE_DAYS, get_string('err_negative_number', 'block_courseprefs'), 'valid_days');
        $form->addElement('text', ENROLL_DAYS, get_string(ENROLL_DAYS, 'block_courseprefs'));
        $form->addRule(ENROLL_DAYS, get_string('err_negative_number', 'block_courseprefs'), 'valid_days');
        $form->addRule(array(CREATE_DAYS, ENROLL_DAYS),
            get_string('err_create_enroll_compare', 'block_courseprefs'), 'compare', 'gte');
        
        $form->addElement('header', 'debug_header',get_string('debug_header', 'block_courseprefs')); 
        $form->addElement('select', LOG_SELECT, get_string(LOG_SELECT, 'block_courseprefs'), $log_options);
        
	    $s = get_string('missing_users', 'block_courseprefs');
    	$form->addElement('static', 'missing_users', '', '<a href = "missing_users.php">' . $s . '</a>');
	    $s = get_string('orphaned_users', 'block_courseprefs');
    	$form->addElement('static', 'orphen_users', '', '<a href = "missing_users.php?action=orphen">' . $s . '</a>');

        $form->addElement('header', 'rolemapping_header', 'Enrollment Mappings');
        foreach ($cps_role_name as $i => $name) {
            $form->addElement('select', 'role_'. $i, "Role 0{$i} ({$name})", $role_options);
        }

        $form->addRule(BASE_PATH, null, 'required', null, 'client');
        $form->addRule(USER_FILE, null, 'required', null, 'client');
        $form->addRule(COURSE_FILE, null, 'required', null, 'client');
        $form->addRule(ENROLL_FILE, null, 'required', null, 'client');
        $form->addRule(DATE_FILE, null, 'required', null, 'client');

        $buttonarray = array();
        $buttonarray[] = &$form->createElement('submit', 'submitbutton', 
                                 get_string('savechanges'));
        $buttonarray[] = &$form->createElement('cancel');
        $form->addGroup($buttonarray, 'buttonarr', '', array(' '), false);
        $form->closeHeaderBefore('buttonarr');
    }

    function validation($data) {
        global $CFG;
        
        $errors = $this->_form->_errors;

        $file_arr = array(0 => USER_FILE,
                          1 => COURSE_FILE,
                          2 => ENROLL_FILE,
                          3 => DATE_FILE
                         );

        $this->form_values = array();

        $base_dir = $data[BASE_PATH];

        if (is_dir($base_dir)) {
            if ($base_dir[strlen($base_dir)-1] != '/') {
                $base_dir .= '/';
            }
            $this->form_values[BASE_PATH] = $base_dir;

            foreach ($file_arr as $filename) {
                if (!is_file($base_dir . $data[$filename])) {
                    $errors[$filename] = get_string('err_nofile', 'block_courseprefs');
                    continue;
                }
                $this->form_values[$filename] = $data[$filename];
            }
        } else {
            $errors[BASE_PATH] = get_string('err_nopath', 'block_courseprefs');
        }

        $this->form_values[LOG_SELECT] = $data[LOG_SELECT];
        $this->form_values[CREATE_DAYS] = $data[CREATE_DAYS];
        $this->form_values[ENROLL_DAYS] = $data[ENROLL_DAYS];
        foreach (range(1,3) as $i) {
            $this->form_values['role_'.$i] = $data['role_'.$i];
        }

        return $errors; 
    }
}

?>
