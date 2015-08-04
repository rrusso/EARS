<?php

require_once('lib.php');

class admin_name extends admin_page {
    function init() {
        $this->sql_sports = get_records('block_courseprefs_sports');

        if(!$this->sql_sports) $this->sql_sports = array();

        $this->sports = array('all' => get_string('showall')) +
                        array_map('flatten_sport', $this->sql_sports);

    }

    function __construct($userid) {
        $this->name = get_string('admin_name', 'block_student_gradeviewer');
        $this->userid = $userid;
        $this->type = 'name';
        
        $this->path = optional_param('path', 'all');
        $this->capabilities = arraY('block/student_gradeviewer:sportsadmin');
    }

    function print_header() {
        popup_form('admin.php?type='.$this->type.'&amp;path=', $this->sports, 'sport_selector',
                   $this->path, '', '', '', false, 'self',
                   get_string('admin_name', 'block_student_gradeviewer'));
        $this->print_errors();
        $this->header_string($this->get_sport());
    }

    function get_sport() {
        if($this->path == 'all') {
            return get_string('name_select', 'block_student_gradeviewer');
        } else {
            $a->sport = $this->sports[$this->path];
            return get_string('name_changing', 'block_student_gradeviewer' ,$a);
        }
    }

    function print_form() {
        if ($this->path == 'all') {
            $sports = $this->sql_sports;
        } else {
            $sports = array($this->sql_sports[$this->path]);
        }

        print_box_start();
        echo '<form method="POST">
                '.array_reduce($sports, array($this, 'reduce_sport'), '').'
                <input type="hidden" name="type" value="'.$this->type.'">
                <input type="hidden" name="path" value="'.$this->path.'">
                <input type="hidden" name="userid" value="'.$this->userid.'">
                <input type="submit" value="'.get_string('submit').'">
              </form>';
        print_box_end();
    }

    function reduce_sport($in, $sport) {
        $inter = (empty($in)) ? '' : $in;
        return $inter . '<span>'.get_string('name_code', 
                                         'block_student_gradeviewer', $sport).'</span>
              <input type="hidden" name="sport_'.$sport->id.'" value="'.$sport->id.'">
              <input type="text" name="name_'.$sport->id.'" value="'.$sport->name.'"><br/>';
    }

    function process_data($data) {
        if(!$this->validate_data($data)) {
            return;
        }

        // Try to update sports name
        if($this->path == 'all') {
            foreach($this->sql_sports as $sport) {
                if(!$this->validate_name($data, $sport)) {
                    continue;
                }
                $this->update_sport($sport);
            }
        } else {
            $sport = $this->sql_sports[$this->path];
            if ($this->validate_name($data, $sport)) {
                $this->update_sport($sport);
            }
        }
    }

    function update_sport($sport) {
        $name = optional_param('name_'. $sport->id, '');
        $sport->name = $name;

        if (!update_record('block_courseprefs_sports', $sport)) {
            $this->errors['sports'] = get_string('name_save', 
                                                 'block_student_gradeviewer', $sport);
        } else {
            $sport->name = stripslashes($name);
            $this->sports[$sport->id] = $sport->name;
            $this->sql_sports[$sport->id] = $sport;
        }
    }

    function validate_data($data) {
        if(!isset($this->sports[$this->path])) {
            $this->errors['sports'] = get_string('sports_error', 'block_student_gradeviewer');
        }
        return empty($this->errors);
    }

    function validate_name($data, $sport) {
        $sport->name = $data->{'name_' . $sport->id};
        if(!preg_match("/\D+/", $sport->name)) {
            $this->errors[$sport->id] = get_string('name_error', 
                                                   'block_student_gradeviewer', $sport);
            return false;
        }
        return true;
    }
}

?>
