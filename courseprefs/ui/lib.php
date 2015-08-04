<?php

require_once($CFG->dirroot . '/blocks/courseprefs/lib.php');

/**
 * The name filter is a filter that displays a string of alphabetical
 * letters that filter cps names. 
 */
class name_filter extends cps_user_component {
    var $value;
    var $key;
    var $base;
    var $params;

    function __construct($base, $key, $value = null, $params=null, $extra=null) {
        $this->value = $this->get_value_or_default($value);
        $this->key = $key;
        $this->params = $params;
        $this->base = $base;        
    }

    public function default_value() {
        return get_string('all');
    }

    public function print_component() {
        $alpha = array(-1 => $this->default_value()) + explode(',', get_string('alphabet'));

        echo '<div class="initialbar '.$this->key.'initial">'.get_string($this->key). ' : ' .
            array_reduce($alpha, array($this, 'reduce_letters')) .
        '     </div>'; 
    }

    function reduce_letters($in, $letter) {
        if($letter == $this->value) {
            $html = '<strong>'.$letter.'</strong> ';
        } else {
            $this->params[$this->key] = $letter;
            $params = implode("&amp;", 
                        array_map(array($this, 'transform_param'), 
                            array_keys($this->params), array_values($this->params)));
            $html = '<a href="'.$this->base.'?'.$params.'">'.$letter.'</a> ';
        }

        $inter = (empty($in)) ? '' : $in;
        return $inter . $html;
    }

    public function where() {
        return "{$this->key} LIKE '".addslashes($this->value)."%'";
    }
}

/**
 * The cps_user_component base is everything needed for ui code to
 * to filter results from CPS data. There are two types of components:
 * Filters and web ui component:
 * Both require 
 * $key - the optional param in the url
 * $value - what was POSTed or GET in the header
 * Filters require:
 * $base - the script to direct he user to
 * $params - the url parameters to load upon a selection
 */
class cps_user_component {
    static public function inputs() {
        return array('username', 'idnumber', 'firstname', 'lastname', 'keypadid',
                        'classification', 'college', 'year');
    }

    static public function drop_downs() {
        return array('reg_status', 'degree_candidacy');
    }

    static public function filters() {
        return array('section', 'firstname', 'lastname');
    }

    static public function build_filters_from_array($script, $filters, $params=null, $filter_data= null) {
        return cps_user_component::build_filters($script, $params, $filter_data, null, $filters);
    }
    
    static public function build_filters($script, $params = null, $filter_data=null, $filter = null, $filters = null) {
        $filters = (!is_null($filters)) ? $filters : cps_user_component::filters(); 

        $filters = ($filter) ? array_filter($filters, $filter) : $filters;

        $param_dict = ($filters) ? array_combine($filters, array_map('cps_transform_filter', $filters)) : array();

        $param_dict = (($param_dict) ? $param_dict: array())
                   + (($params && is_array($params)) ? $params : array());

        $components = array();
        foreach($param_dict as $key => $value) {
            if(!in_array($key, $filters)) {
                continue;
            }

            if(isset($filter_data[$key])) {
                $extra = $filter_data[$key];
            } else {
                $extra = null;
            }

            $rest = $param_dict;
            unset($rest[$key]);
            $class = $key . "_filter";
            $components[] = new $class($script, cps_transform_filter($key), $rest, $extra);
        }

        return new cps_user_component_collection($components, $param_dict);
    }

    static public function build_components($filter = null) {
        $inputs = ($filter) ? array_filter(cps_user_component::inputs(), 
                              $filter) : cps_user_component::inputs();
        $dropdowns = ($filter) ? array_filter(cps_user_component::drop_downs(),
                              $filter) : cps_user_component::drop_downs();

        $components = array_merge(
                      array_map(create_function('$input', '
            return new input_box($input, cps_transform_filter($input));
        '), $inputs),
                      array_map(create_function('$drop_down', '
            $class = $drop_down . "_drop_down";
            return new $class(cps_transform_filter($drop_down));
        '), $dropdowns)
         );
       
        return new cps_user_component_collection($components);
    }

    public function get_value_or_default($value) {
        return ($value) ? $value : $this->default_value();
    }

    public function default_value() {
        return get_string($this->key, 'block_courseprefs');
    }

    public function where_eligible() {
        return ($this->default_value() != $this->value && !empty($this->value));
    }
    
    public function add_quotes($value) {
        return "'" . addslashes(trim($value)) . "'";
    }

    function transform_param($key, $value) {
        return "$key=$value";
    }

    function flatten_params() {
        return implode("&amp;", 
                        array_map(array($this, 'transform_param'), 
                            array_keys($this->params), array_values($this->params)));
    }

    function base_url() {
        unset($this->params[$this->key]);
        return $this->base . '?' . $this->flatten_params();
    }

    function where() {
        return "{$this->key} = {$this->value}";
    }
}

/**
 * A cps_user_component_collection is a special collection class
 * catered to cps_user_component. 
 */
class cps_user_component_collection extends cps_user_component {
    var $components;

    function __construct($components, $params=null) {
        $this->components = $components;
        $this->params = $params;
    }

    public function with_components($fun) {
        foreach($this->components as $component) {
            $fun($component);
        }
    }

    public function find($key) {
        foreach($this->components as $component) {
            if($key == $component->key) return $component;
        }
    }

    public function as_dict() {
        return array_combine(
               array_map(create_function('$c', 'return $c->key;'), $this->components),
               array_map(create_function('$c', 'return $c->default_value();'), 
                $this->components)
               );
    }

    public function display() {
        $this->with_components(create_function('$c', '
            $c->print_component();
        '));
    }

    public function display_as_table($heading = null) {
        $heading = (empty($heading)) ? get_string('content_viewer', 'block_courseprefs') : 
                          $heading;

        $help_button = helpbutton('content_viewer', 'User Data Viewer', 
                       'block_courseprefs', true, false, '', true);
        echo '<form method="POST" class="userDataViewer">';
        echo '  <fieldset class="aligncenter">';
        echo '      <legend><strong>' .$heading. '</strong>'.$help_button.'</legend>';
        echo '          <table class = "generaltable" id = "tabular">';
        echo '              <thead>';
        echo '                  <tr>';
        $this->with_components(create_function('$c', '
            echo "<th class=\"header\">".$c->default_value()."</th>";
        '));
        
        echo '                  </tr>';
        echo '              </thead>';
        echo '              <tbody>';
        echo '                  <tr>';
        $this->with_components(create_function('$c', '
            echo "<td>";
            $c->print_component();
            echo "</td>";
        '));
        echo '  </tr></tbody></table>';
        echo '  </fieldset>';
        echo '  <input class = "middlebutton" type="submit" />';
        echo '</form>';
    }

    public function where_clause($prefix = null) {
        $where_eligible_components = array_filter($this->components,
            create_function('$c', '
                return $c->where_eligible();
            ')
        );

        $keys = array_map(create_function('$c', '
                return $c->key;
               '), $where_eligible_components);

        $clause = array_map(create_function('$c', '
                return $c->where();
                '), $where_eligible_components);


        if(empty($keys) and empty($clause)) return array();

        return ($prefix) ? array_combine($keys, array_map($prefix, $keys, $clause)) : 
                           array_combine($keys, $clause);
    }
}

/**
 * The input box is a web ui component that displays a input box
 * useful for the content viewer and mentee pages.
 */
class input_box extends cps_user_component {
    var $key;
    var $value;
    
    function __construct($key, $value=null) {
        $this->key = $key;
        $this->value = $this->get_value_or_default($value);
    }

    public function print_component() {
        $class = ($this->value != $this->default_value()) ? "other" : "field_input";
        echo '<input class="'.$class.'" type="text" name="'.
             $this->key.'" value="'.$this->value.'">';
        echo '<input type="hidden" id="'.$this->key.'" value="'.$this->default_value().'">';
    }

    public function where() {
        if(strtolower($this->value) == 'null') {
            return "({$this->key} IS NULL OR {$this->key} = '')";
        } else if (strtolower($this->value) == 'not null') {
            return "{$this->key} != ''";
        } else if (preg_match('/%/', $this->value)) {
            return "{$this->key} LIKE '".addslashes($this->value)."'";
        } else if (preg_match('/,/', $this->value)) {
            return "{$this->key} IN (".implode(',', 
                array_map(array($this, 'add_quotes'), 
                        explode(',', $this->value))
            ).")";
        } else {
            return "{$this->key} = '".addslashes($this->value)."'";
        }
    }
}

/**
 * A specialized drop down specifically for reg_status and degree_candidacy
 */
class boolean_drop_down extends drop_down {
    public function get_options() {
        $that = get_string($this->key . 'ing', 'block_courseprefs');
        $not_that = get_string('not', 'block_courseprefs') . $that;
        return array($this->default_value() => $this->default_value() .
                     get_string('nr', 'block_courseprefs'),
                     $this->key . 'that' => $that,
                     $this->key . 'not_that' => $not_that
                    );
    }
}

/**
 * The drop_down class displays elements in a moodle drop down
 */
class drop_down extends cps_user_component {
    function __construct($key, $value=null) {
        $this->key = $key;
        $this->value = $this->get_value_or_default($value);
        $this->options = $this->get_options();
    }

    public function print_component() {
        choose_from_menu($this->options, $this->key, $this->value, 
                         '', '', $this->default_value());
    }
}

class reg_status_drop_down extends boolean_drop_down {
    function __construct($value) {
        parent::__construct('reg_status', $value);
    }

    function where() {
        if($this->key . 'that' == $this->value) {
            return "({$this->key} IS NOT NULL AND {$this->key} != '')";
        } else {
            return "({$this->key} IS NULL OR {$this->key} = '')";
        }
    }
}


class degree_candidacy_drop_down extends boolean_drop_down {
    function __construct($value) {
        parent::__construct('degree_candidacy', $value);
    }

    function where() {
        if($this->key . 'that' == $this->value) {
            return "{$this->key}='Y'";
        } else {
            return "{$this->key}='N'";
        }
    }
}

class firstname_filter extends name_filter {
    function __construct($base, $value = null, $params = null, $extra = null) {
        parent::__construct($base, 'firstname', $value, $params);
    }
}

class lastname_filter extends name_filter {
    function __construct($base, $value = null, $params = null, $extra = null) {
        parent::__construct($base, 'lastname', $value, $params);
    }
}

class section_filter extends cps_user_component {
    function __construct($base, $value=null, $params=null, $extra = null) {
        $this->base = $base;
        $this->key = 'section';
        $this->value = $this->get_value_or_default($value);
        $this->params = $params;
        if($extra) {
            foreach(array('course', 'user', 'callback') as $field) {
                if(isset($extra[$field])) {
                    $this->{$field} = $extra[$field];
                }
            }
        }
    }

    public function default_value() {
        return 0;
    }    

    public function get_options() {
        global $CFG, $USER, $COURSE;

        if(!isset($this->user)) {
            require_once($CFG->dirroot . '/blocks/courseprefs/classes/CoursePrefsUser.php');
            $this->user = CoursePrefsUser::findByUnique($USER->username);
        }

        if(!isset($this->course)) {
            $this->course = ($COURSE->id == 1) ? null : $COURSE;
        }

        if(isset($this->callback)) {
            $sections = call_user_func($this->callback, $this);
        }

        if(empty($sections) and is_siteadmin($USER->id) and $this->course) {
            $sections = cps_sections($this->course);
        }

        if(empty($sections) and $this->user) {
            $sections = $this->user->getSectionsForMoodleCourse($this->course, false);
        }

        $this->sections = $sections;

        return ($sections) ? array_map(array($this, 'transform_section'), 
                $sections) : array();
    }

    function transform_section($section) {
        return get_string('format_section', 'block_courseprefs', $section);
    }

    public function print_component() {
        $options = $this->get_options();
        if(empty($options)) return;

        unset($this->params[$this->key]);
        $html_params = '?' . $this->flatten_params();
        popup_form($this->base . $html_params . '&amp;'.$this->key.'=', 
        array($this->default_value() => get_string('allparticipants')) + $options,
        'section_filter', $this->value, '', '', '', false, 'self', 
        get_string('sections', 'block_courseprefs'));
    }

    public function where_eligible() {
        return is_numeric($this->value) and $this->sections;
    }

    public function where() {
        if($this->value === 0 and $this->sections) {
            $secs = implode(',', array_keys($this->sections));
            return "id IN ($secs)";
        }
        return "id='" .addslashes($this->value) . "'";
    }
}

function cps_transform_filter($filter) {
    return optional_param($filter);
}
?>
