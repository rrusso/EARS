<?php

require_once('lib.php');

// This page does all the academic based assignments
// users can be assigned based on students year, college, and majors
class admin_academics extends admin_page {
    function init() {
        global $CFG;

        // All the years to choose from
        // All the colleges to choose from
        // All the majors to choose from
        $fields = array('years' => 'year',
                        'colleges' => 'college',
                        'majors' => 'classification');
        $fields = array_map('special_sql', $fields);

        $this->fields = $fields;

        // All the current assignments in this category
        $sql = "SELECT id, path
                FROM {$CFG->prefix}block_student_academics
                WHERE path != 'NA'
                Group By path";

        $inter = get_records_sql_menu($sql);
        $assignments = array_values(array(0 => 'NA') + ((!$inter) ? array() : $inter));
        $assignments = array_combine($assignments, $assignments);
        $assignments['NA'] = 'NA/NA/NA';

        $this->current_assignments = $assignments;
    }

    function __construct($userid) {
        $this->name = get_string('admin_academic', 'block_student_gradeviewer');
        $this->userid = $userid;
        $this->type = 'academics';
        $this->debug = get_config('', 'block_student_gradeviewer_debug');

        $this->input_fields = array('year', 'college', 'major');

        // Assign the path here
        $values = array_map(array($this, 'na_or_empty'), $this->input_fields);
        
        //Pull this the categories from a form submission rather the url params
        if ($data = data_submitted()) {
            $p = (isset($data->path)) ? $data->path : 
                  $this->year . '/' . $this->college .'/' . $this->major;
        } else {
            $p = optional_param('path', 'NA');
        }

        if($p != 'NA') {
            $this->path = $p;
            $fields = explode('/', $p);
            
            foreach($this->input_fields as $index => $field) {
                $this->{$field} = $fields[$index];
            }
        } else if(array_reduce($values, 'empty_reduce_check', true)) {
            $this->path = 'NA';
        } else {
            $this->path = implode('/', $values);
        }

        $this->capabilities = array('block/student_gradeviewer:academicadmin');
    }

    function print_header() {
        global $CFG;

        popup_form('admin.php?type='.$this->type.'&amp;path=', 
                   $this->current_assignments,
                   'path_selector', $this->path, 'Year/College/Major', '', '', 
                   false, 'self', get_string('academic_current', 
                   'block_student_gradeviewer'));
        // Once we have everything we need, then we can load the javascript
        // for the auto complete
        echo '<script type="text/javascript">';
        foreach($this->fields as $field => $values) {
            echo data_reduce($field, $values);
        }      
        echo '</script>';

        // Get all the libraries required for the yui stuff
        require_js(array(
                   $CFG->wwwroot . '/lib/yui/yahoo/yahoo-min.js',
                   $CFG->wwwroot . '/lib/yui/event/event-min.js',
                   $CFG->wwwroot . '/lib/yui/animation/animation-min.js',
                   $CFG->wwwroot . '/lib/yui/yahoo-dom-event/yahoo-dom-event.js',
                   $CFG->wwwroot . '/lib/yui/autocomplete/autocomplete-min.js',
                   $CFG->wwwroot . '/blocks/student_gradeviewer/admin/functions.js'));

        // The yui autocomplete has to have the most complicated html setup
        echo '<div class="admin_header">
                <div class="yui-skin-sam">
                  <form method="post">
                    <div class="admin_inputs">
                  '. array_reduce($this->input_fields, array($this, 'input_reduce'), ' ') .'
                    </div>
                    <div class="submit_button">
                    <input type="submit" value="'.get_string('submit').'">
                    </div>
                  </form>
                </div>
              </div>';

        $this->print_errors();
        
        $header_strings = array_reduce($this->input_fields, 
                          array($this, 'header_strings'), '');

        $assigning = get_string('admin_assigning', 'block_student_gradeviewer');
        $this->header_string($assigning . $header_strings);
    }
    
    function header_strings($in, $field) {
        $c = (empty($in)) ? '' : $in;

        return $c . (($this->{$field} != 'NA') ? 
                  get_string($field . '_desc', 'block_student_gradeviewer', $this) :
                       '');
    }

    function print_form() {
        parent::print_form();
        // Adding this for the YUI stuff
        echo '<script type="text/javascript">tagInputs();</script>';
    }

    function input_reduce($in, $field) {
        $value = $this->{$field};

        $add = (empty($in)) ? '' : $in;
        $label = get_string($field, 'block_student_gradeviewer');

        $html = $add . '<div class="collective">
                       <span>'.$label.'</span>
                        <div id="admin_field_'.$field.'">
                           <input type="text" id="'.$field.'_AC" name="'.
                           $field.'" class="admin_field" value="'.$value.'">
                           <div id="'.$field.'_container"></div>
                        </div></div>';
        return $html;
    }

    function na_or_empty($field) {
        $value = optional_param($field, 'NA');
        
        $check = trim($value);
        $this->{$field} = ($check === '') ? 'NA' : $check;
        return $this->{$field};
    }

    function validate_data($data) {
        // Make sure that the inputs are valid things
        return array_reduce($this->input_fields, array($this, 'validate_reduce'), true);
    }

    function validate_reduce($in, $field) {
        if(!in_array($this->{$field}, $this->fields[$field . 's'])) {
            $a->field = $this->{$field};
            $this->errors[$field] = get_string('academic_error', 'block_student_gradeviewer', $a) . 
                                    get_string($field, 'block_student_gradeviewer');
            return $in && false;
        }
        return $in && true;
    }
}

function special_sql($field) {
    global $CFG;

    // Where only matters for year
    $where = ($field == 'year') ? '' : " WHERE {$field} != '' ";

    $sql = "SELECT id, {$field} FROM {$CFG->prefix}block_courseprefs_users 
               {$where} GROUP BY {$field} ORDER BY {$field}";
   
    $tmp = get_records_sql_menu($sql);
    if(!$tmp) {
        $tmp = array();
    }
 
    return array(0 => 'NA') + $tmp;
}


function empty_reduce_check($in, $value) {
    return $in && strtolower($value) == 'na';
}

function data_reduce($field, $fields) {
    return "var {$field} = ['" . implode("','", $fields) . "'];";
}

?>
