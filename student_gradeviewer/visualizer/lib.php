<?php

require_once($CFG->dirroot . '/blocks/student_gradeviewer/report/lib.php');
require_once($CFG->dirroot . '/blocks/courseprefs/classes/CoursePrefsUser.php');
require_once($CFG->dirroot . '/blocks/courseprefs/ui/lib.php');

class source_filter extends cps_user_component {
    function __construct($script, $value, $params, $extra) {
        $this->key = 'source';
        $this->value = $this->get_value_or_default($value);
        $this->params = $params;
        $this->base = $script;
    }

    function default_value() {
        return 4;
    }

    function get_value_or_default($value) {
        return ($value != $this->default_value() and $value !== NULL) ? 
                $value : $this->default_value();
    }
    
    function print_component() {
        popup_form($this->base_url() . "&amp;{$this->key}=", 
                   array(0=> get_string('all'),
                         4 => get_string('neg', 'block_student_gradeviewer'),
                         3 => get_string('pos', 'block_student_gradeviewer'), 
                         1 => get_string('man', 'block_student_gradeviewer'), 
                         2 => get_string('auto', 'block_student_gradeviewer')), 
                   'source_filter', $this->value, '', '', '', false, 'self', 
                   get_string('source', 'block_student_gradeviewer'));
    }

    function where_eligible() {
        return (!empty($this->value));
    }

    function where() {
        if($this->value <= 3) {
            return "source = {$this->value}";
        }
        return "source != 3";
    }
}

class date_referred_filter extends cps_user_component {
    var $now;

    function __construct($script, $value, $params, $extra) {
        $this->key = 'date_referred';
        $this->value = $this->get_value_or_default($value);
        $this->params = $params;
        $this->script = $script;
        $this->now = usergetmidnight(time()) + (60 * 60 * 23) + 3599;
    }

    function default_value() {
        return 0;
    }

    function get_options() {
        $days = range(0, 365);
        $time_values = array_map(array($this, 'map_time'), $days);
        $options = array_combine($time_values, array_map(create_function('$time','
                    return date("m/d/Y", $time);
               '), $time_values));
        $options = array(0 => 'All days') + $options;
        return $options;
    }

    function map_time($day) {
        $sub = (60 * 60 * 24) * $day;
        return $this->now - $sub;
    }

    function print_component() {
        popup_form($this->base_url() . "&amp;{$this->key}=", $this->get_options(),
                    'date_referred_filter', $this->value, '', '', '', false, 'self',
                    get_string('date_referred', 'block_student_gradeviewer'));
                
    }

    function where() {
        $day_before = $this->value - (60 * 60 * 23);
        return "(ref.date_referred <= {$this->value} 
             AND ref.date_referred >= {$day_before})";
    }
}

class referral_visualizer {
    var $id;
    var $courseid;
    var $obj;
    var $keys = array('student', 'section', 'referrer', 
                      'reason', 'date_referred', 'semester', 'source');

    var $fields = array('student' => 'usersid', 
                        'section' => 'sectionsid',
                        'referrer' => 'referrerid',
                        'date_referred' => 'date_referred',
                        'semester' => 'semestersid');

    var $protected = array('student' => 'usersid'
                          ,'referrer' => 'referrerid'
                          //,'semester' => 'semestersid'
                          );

    var $capable;

    static function types() {
        return array('course', 'student', 'section' ,'referrer', 'date_referred', 'semester', 'options');
    }

    function define_cps_user() {
        global $USER;
        
        $this->cps_user = CoursePrefsUser::findByUnique($USER->username);
    }

    function exportable() {
        return true;
    }    

    function print_filters($script) {
        $this->baseurl = $script . '?' . $this->baseurl . '&amp;'; 

        $this->filters->with_components(create_function('$f', '
            $f->base = "'.$script.'";
        '));
        $this->filters->display();
    }

    function __construct($course, $key, $id = null, $page=0, $per_page=50, $export = false) {
        if(!$id and $course) $id = $course->id;

        if($course) {
            $this->courseid = $course->id;
        }
        $this->course = $course;
        $this->id = $id;
        $this->obj = $this->create_obj($id);
        $this->key = $key;
        $this->define_cps_user();

        $this->page = $page;
        $this->per_page = $per_page;

        $params = array('key' => $this->key, 
                        'keyid' => $this->id, 
                        'per_page' => $this->per_page);
        if($this->courseid) $params['id'] = $this->courseid;        
        $this->filters = cps_user_component::build_filters_from_array('', 
                            array_filter(
                                array('section', 'source', 'date_referred', 'firstname', 'lastname'), 
                                array($this, 'prune_filters')
                            ),
                            $params,
                            array('section' => 
                                array('user' => $this->cps_user,
                                      'course' => $course) + 
                                $this->section_params()
                                 ));

        $this->baseurl = $this->filters->flatten_params();

        $this->perform_prune();
        $this->refresh_function_map();

        $this->mentor = $this->master_caps();

        $this->exporting = $export;

        if(!$this->is_capable()) error(get_string('no_permission', 
                                                  'block_student_gradeviewer'));

    }

    function refresh_function_map() {
        $this->func_map = array_combine($this->keys,
                          array_map(array($this, 'map_function'), $this->keys));
    }

    function section_params() {
        return array();
    }

    static function build_visualizer($course, $key=null, $keyid=null, 
                                     $page=0, $per_page=50, $export=false) {
        global $CFG;

        $base = $CFG->dirroot . '/blocks/student_gradeviewer/visualizer/';

        if(in_array($key, referral_visualizer::types())) {
            require_once($base . $key .'_visualizer.php');
            $class = 'referral_' . $key . '_visualizer';
            return new $class($course, $key, $keyid, $page, $per_page, $export);
        }

        require_once($base . 'blank_visualizer.php');
        return new referral_blank_visualizer($course, 'blank');
    }

    function master_caps() {
        $context = get_context_instance(CONTEXT_SYSTEM);
        return (has_capability('block/student_gradeviewer:sportsviewgrades', $context) || 
               has_capability('block/student_gradeviewer:viewgrades', $context));
        
    }

    function export() {
        header("Content-type: text/plain");
        header("Content-Disposition: attachment; filename=".
                implode('_', explode(' ',$this->heading()))."_export.csv");

        // Source doesn't matter
        $this->prune_keys(array(6));

        $referrals = $this->referral_process();

        // Add the export fields:
        $this->prune_keys(array(0));
        array_unshift($this->keys, 'firstname', 'lastname', 'idnumber', 'username');
        $this->refresh_function_map();

        $output = array($this->setup_table()) +
                  array_map(array($this, 'map_referral'), $referrals);
        echo implode("\n", array_map(array($this, 'map_export'), $output));
    }
    
    function map_export($line) {
        return implode(',', array_map(array($this, 'wrap_quotes'), $line));
    }

    function wrap_quotes($elem) {
        return '"' . $elem . '"';
    }

    function is_capable() {
        return $this->master_caps();
    }

    function extra_navigation() {
        return array();
    }

    function perform_prune() {}

    function prune_filters($filter) {
        return true;
    }

    function prune_keys($indexes) {
        if(!$indexes) return;
        foreach($indexes as $i) {
            unset($this->keys[$i]);
        }
    }

    function setup_table() {
        return array_map(array($this, 'map_keys'), array_keys($this->keys), $this->keys);
    }

    function build_where() {
        if(!isset($this->where)) {
            $where = $this->referral_where($this->filters->where_clause(create_function(
                '$k,$w', '
                switch($k) {
                    case "source" : return "ref." . $w;
                    case "date_referred" : return $w;
                    case "section": return "sec." . $w;
                    default: return "u." . $w;
                }
            ')));
            $this->where = $where;
        }
        return $this->where;
    }

    function referral_process() {
        $sql = $this->referral_select() . $this->build_where() . $this->referral_limit();
        return get_records_sql($sql);
    }

    function print_table() {
        $table = new stdClass;
        $table->head = $this->setup_table();

        // Make sure that student is in the sql
       // $this->keys[0] = 'student';
        $where = $this->build_where();
        $referral_total = count_records_sql("SELECT COUNT(ref.id) $where");
        
        if(empty($referral_total)) {
            echo '<span class="error">'.get_string('no_referrals', 
                                                   'block_student_gradeviewer').'<span>';
            return false;
        }

        echo '<div style="width: 100%; text-align:center;">
               Found: '.$referral_total.'
              </div>';

        $bar = print_paging_bar($referral_total, $this->page, $this->per_page, $this->baseurl, 
                         'page', false, true);
        $show_all = '';
        if(!empty($bar)) {
            $show_all = '<div class="show_all_referrals">
                            <a href="'.$this->baseurl.'per_page=10000">'.get_string('showall').'('.$referral_total.')</a>
                         </div>';
        }
    
        $referrals = $this->referral_process();
        //$this->perform_prune();

        $table->data = array_map(array($this, 'map_referral'), $referrals);

        echo $bar;
        print_table($table);
        echo $bar . $show_all;
        return true;
    }

    function map_keys($special, $key) {
        $selectors = (!is_numeric($special)) ? 
                    '<a href="#" class="all|'.$special.'">' . get_string('all') . '</a> / '. 
                    '<a href="#" class="none|'.$special.'">' . get_string('none') . '</a>': "";
        return get_string($key, 'block_student_gradeviewer') . '<br/>' .$selectors;
    }
    
    function print_heading($heading = null) {
        if(!$heading) $heading = get_string('analysis', 'block_student_gradeviewer');
        print_heading($heading . ' for ' . $this->heading());
    }

    function link_wrap($title, $key, $location) {
        if($this->exporting) return $title;

        $loc = array('key='.$key, 'keyid='. $location);
        if($this->courseid and $key != 'semester') $loc[] = 'id='.$this->courseid;
        return '<a href="analysis.php?'.implode('&amp;', $loc).'">'.$title.'</a>';
    }

    function map_function($key) {
        return array($this, 'handle_'.$key);
    }

    function map_referral($referral) {
        $this->current_ref = $referral;
        return array_map(array($this, 'transform_referral'), $this->keys);
    }

    function transform_referral($key) {
        global $USER;

        $rtn = call_user_func($this->func_map[$key], $this->current_ref);

        //$field = $this->fields[$key];
        $protected_link = isset($this->protected[$key]);

        return ((isset($this->fields[$key]) && !$protected_link) ||
               ($key == 'referrer' && $USER->id == $this->current_ref->referrerid) ||
               ($protected_link && $this->mentor)) ? 
                      $this->link_wrap($rtn, $key,  
                      $this->current_ref->{$this->fields[$key]}) : $rtn;
    }

    function handle_student($referral) {
        return $referral->firstname. ' '. $referral->lastname;
    }

    function handle_section($referral) {
        return get_string('format_section', 'block_courseprefs', $referral);
    }

    function handle_referrer($referral) {
        return ($referral->referrerid == 1) ? 
               get_string('anon_report' ,'block_student_gradeviewer') : 
               $referral->moodlefirst . ' ' . $referral->moodlelast;
    }

    function handle_reason($referral) {
        return $referral->reason;
    }

    function handle_date_referred($referral) {
        return date(get_string('all_date_format', 'block_student_gradeviewer'),
                    $referral->date_referred);
    }

    function handle_semester($referral) {
        return get_string('format_semester', 'block_student_gradeviewer', $referral);
    }

    function handle_source($referral) {
        return print_source($referral);
    }

    function handle_idnumber($referral) {
        return $referral->idnumber;
    }

    function handle_firstname($referral) {
        return $referral->firstname;
    }

    function handle_lastname($referral) {
        return $referral->lastname;
    }

    function handle_username($referral) {
        return $referral->username;
    }

    function referral_select() {
        return "SELECT ref.* " . array_reduce($this->keys, array($this, 'reduce_values'));
    }

    function referral_where($where) {
        global $CFG, $USER;
        
        list($table, $prequery) = get_pre_query($USER->id);

        $order = (in_array('student', $this->keys)) ? ", u.lastname, u.firstname ASC" : '';

        $sql =    " FROM {$CFG->prefix}block_student_referrals ref " .
                    array_reduce($this->keys, array($this, 'reduce_tables')) . " {$table}
                    WHERE ".$this->{$this->key . '_where'}($this->id)." AND
                    " . implode(" AND " ,array_filter(
                                         array_map(array($this, 'transform_where'), 
                        $this->keys))) . "
                    ".(($where) ? " AND " . implode(" AND ", $where) : '')."
                    ".(($prequery) ? " AND {$prequery}" : '')."
                    ORDER BY ref.date_referred DESC {$order}";

        return $sql;
    }

    function referral_limit() {
        $offset = $this->per_page * $this->page;
        return " LIMIT {$this->per_page} OFFSET {$offset}";
    }
 
    function student_values() {
        return ", u.firstname, u.lastname, u.username, u.idnumber";
    }

    function referrer_values() {
        return ",mu.firstname AS moodlefirst, mu.lastname AS moodlelast"; 
    }
 
    function section_values() {
        return ", sec.section_number, cou.department, cou.course_number"; 
    }

    function semester_values() {
        return ", sem.year, sem.name, sem.campus";
    }

    function reduce_values($in, $value) {
        $inter = (empty($in)) ? '' : $in;
        $rtn = (method_exists($this, $value. '_values')) ? $this->{$value . '_values'}() : '';
        return $inter . $rtn;
    }

    function student_tables() {
        global $CFG;
        return ", {$CFG->prefix}block_courseprefs_users u,
                  {$CFG->prefix}block_courseprefs_students stu";
    }

    function section_tables() {
        global $CFG;
        return", {$CFG->prefix}block_courseprefs_sections sec,
                 {$CFG->prefix}block_courseprefs_courses cou";
    }

    function referrer_tables() {
        global $CFG;
        return ", {$CFG->prefix}user mu";
    }

    function semester_tables() {
        global $CFG;
        return ", {$CFG->prefix}block_courseprefs_semesters sem";
    }

    function reduce_tables($in, $table) {
        $inter = (empty($in)) ? '' : $in;
        $rtn = (method_exists($this, $table. '_tables')) ? $this->{$table . '_tables'}() : '';
        return $inter . $rtn;
    }
  
    function student_where($id = null) {
        $filler = ($id) ? $id : "u.id
                 AND u.id = stu.usersid
                 AND stu.status = 'enrolled'
                 AND ref.sectionsid = stu.sectionsid";

        return " ref.usersid = {$filler}";
    }

    function section_where($id = null) {
        $filler = ($id) ? $id : "sec.id
                 AND sec.coursesid = cou.id
                 AND ref.semestersid = sec.semestersid";
        return " ref.sectionsid = $filler";
    }
 
    function referrer_where($id = null) {
        $filler = ($id) ? $id : "mu.id";
        return " ref.referrerid = $filler";
    }

    function date_referred_where($id = null) {
        $filler = ($id) ? " ref.date_referred = $id" : "";
        return $filler;
    }

    function semester_where($id = null) {
        $filler = ($id) ? $id : "sem.id";
        return " ref.semestersid = {$filler}";
    }

    function course_where($id) {
        return " sec.idnumber = '{$this->obj->idnumber}'";
    }

    function transform_where($key) {
        return (method_exists($this, $key. '_where')) ? $this->{$key . '_where'}() : '';
    }
}

?>
