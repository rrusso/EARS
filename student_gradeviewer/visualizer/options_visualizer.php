<?php

class referral_options_visualizer extends referral_visualizer {
    var $primary_sections;
    var $sections;

    function exportable() {
        return false;
    }

    function create_obj($id) {
        $user = CoursePrefsUser::findByUnique($id);
        $this->primary_sections = $user->getSectionsInfoAsPrimaryTeacher(true);
        $this->sections = $user->getSectionsInfoAsTeacher(false, null, true);
        return $user;
    }

    function section_params() {
        return array('callback' => create_function('$self', '
            return $self->user->getSectionsInfoAsTeacher();
        '));
    }

    function define_cps_user() {
        $this->cps_user = $this->obj;
    }

    function is_capable() {
        $sections = $this->obj->getSectionsInfoAsTeacher();
        return !empty($sections);
    }

    function heading() {
        return $this->obj->getFirstname() . ' ' . $this->obj->getLastname();
    }

    function referral_where() {
        global $CFG;

        $sections = $this->obj->getSectionsInfoAsTeacher();
        return "FROM {$CFG->prefix}block_courseprefs_sections ref WHERE ref.id IN (".
                implode(',', array_keys($sections)).")";
    }

    function referral_process() {
        $sections = $this->obj->getSectionsInfoAsTeacher();
        $id = $this->filters->find('section')->value;
        if(!empty($id)) {
            return array($id => $sections[$id]);
        }
        return $sections;
    }
    
    function prune_filters($filter) {
        return $filter == 'section';
    }

    function perform_prune() {
        $this->prune_keys(array(0, 2, 3, 4, 6));
        unset($this->fields['section'], $this->fields['semester']);
        unset($this->protected['semester']);
        $this->keys['primary_instructor'] = 'primary';
        $this->keys['non_primary_instructor'] = 'non_primary';
        $this->keys['student'] = 'student';
        $this->keys['non_primary_control'] = 'non_primary_control';
    }

    function map_referral($section) {
        $this->current_ref = find_option($this->obj, $section);
        return array_map(array($this, 'transform_referral'), $this->keys);
    }

    function print_named_checkbox($permission, $key, $option) {
        return (!$permission) ? '-' : '<input type="hidden" name="opt|'.$key.'|'.$option->sectionsid.'" value="0"/>
                <input type="checkbox" name="opt|'.$key.'|'.$option->sectionsid.'" value="1"'.
               (!empty($option->{$key}) ? 'checked="checked"' : '') . '/>';
    }

    function is_non_primary($option) {
        return !isset($this->primary_sections[$option->sectionsid]);
    }

    function has_control($option) {
        return !empty($option->non_primary_control);
    }

    function determine_control($option) {
        $non_primary = $this->is_non_primary($option);
        if(!$non_primary) {
            return true;
        }

        return $non_primary && $this->has_control($option);
    }

    function handle_primary($option) {
        return $this->print_named_checkbox($this->determine_control($option), 'primary_instructor', $option);
    }

    function handle_student($option) {
        return $this->print_named_checkbox($this->determine_control($option), 'student', $option);
    }

    function handle_non_primary($option) {
        return $this->print_named_checkbox($this->determine_control($option),'non_primary_instructor', $option);
    }

    function handle_non_primary_control($option) {
        return $this->print_named_checkbox(!$this->is_non_primary($option), 'non_primary_control', $option);
    }

    function process($data) {
        global $CFG;

        $sections = $this->obj->getSectionsInfoAsTeacher();
        $options = get_options($sections);
       
        $this->notices = array();

        foreach($sections as $id => $section) {

            $opts = array('non_primary_control' => $this->get_value_or_default($data->{'opt|non_primary_control|'.$id}, 
                                                   'block_student_gradeviewer_non_primary_control'),
                          'primary' => $this->get_value_or_default($data->{'opt|primary_instructor|'.$id}, 
                                                   'cas_reporting_primary'), 
                          'non_primary' => $this->get_value_or_default($data->{'opt|non_primary_instructor|'.$id}, 
                                                   'cas_reporting_non_primary'), 
                          'student' => $this->get_value_or_default($data->{'opt|student|'.$id}, 'cas_reporting_student'));

            $option = $options[$id];
            $saved_option = null;
            if(!$option) {
                $option = new stdClass;
                $option->sectionsid = $id;
            } else {
                $saved_option = clone $option; 
            }

            $option->primary_instructor = $opts['primary'];
            $option->non_primary_instructor = $opts['non_primary'];
            $option->student = $opts['student'];
            $option->non_primary_control = $opts['non_primary_control'];
          
            $no_control = $this->is_non_primary($option) && !$this->has_control($option);
            $same = $this->compare($saved_option, $option);

            if($no_control or $same) {
                continue;
            }
 
            $a->section = $this->handle_section($section);
            $saved_options = array_map(array($this, 'map_options'), 
                          array_keys(array_filter($opts, array($this,'filter_options'))));
            $a->options = (empty($saved_options)) ? "No reporting options" : 
                          implode(' and ', $saved_options);
            $code = 'saved';
            if(!$this->save_option($option)) $code = 'error';
            $this->notices[] = get_string('option_'.$code, 'block_student_gradeviewer', $a);
        }

        /*
        foreach($options as $option) {
            $a->section = $this->handle_section($sections[$option->sectionsid]);
            $this->notices[] = get_string('option_removed', 'block_student_gradeviewer', $a);
        }

        $delete = implode(',', array_map(array($this, 'transform_option'), $options));
        delete_records_select('block_teacher_referral_opt', 'id IN ('.$delete .')');
        */
    }

    function get_value_or_default($value, $key) {
        global $CFG;

        return (isset($value)) ? $value : $CFG->{$key};
    }
  
    function compare($saved_option, $option) {
        global $CFG;

        if(!$saved_option) {
            $saved_option = new stdclass;
            $saved_option->primary_instructor = $CFG->cas_reporting_primary;
            $saved_option->non_primary_instructor = $CFG->cas_reporting_non_primary;
            $saved_option->non_primary_control = $CFG->block_student_gradeviewer_non_primary_control;
            $saved_option->student = $CFG->cas_reporting_student;
        }

        return $saved_option &&
               $saved_option->primary_instructor == $option->primary_instructor &&
               $saved_option->non_primary_instructor == $option->non_primary_instructor &&
               $saved_option->non_primary_control == $option->non_primary_control &&
               $saved_option->student == $option->student;
    }
 
    function transform_option($option) {
        return $option->id;
    }

    function filter_options($value) {
        return $value > 0;
    }

    function map_options($option) {
        return get_string($option, 'block_student_gradeviewer');
    }

    function print_notices() {
        echo '<ul class="student_list">' .
                array_reduce($this->notices, array($this, 'reduce_notices')).
             '</ul>';
    }

    function reduce_notices($in, $notice) {
        $inter = (empty($in)) ? '' : $in;
        return $inter . '<li>'.$notice.'</li>';
    }

    function save_option($option) {
        if($option->id) return update_record('block_teacher_referral_opt', $option);
        return insert_record('block_teacher_referral_opt', $option);
    }
}

// Gets all the referrals for a specific course
function find_option($user, $section) {
    global $CFG;
    
    $sql = "SELECT opt.*, sem.year, sem.name, 
                   cou.department, cou.course_number, sec.section_number
                FROM {$CFG->prefix}block_courseprefs_sections sec,
                     {$CFG->prefix}block_courseprefs_courses cou,
                     {$CFG->prefix}block_courseprefs_semesters sem,
                     {$CFG->prefix}block_teacher_referral_opt opt
                WHERE opt.sectionsid = {$section->id}
                  AND sec.id = opt.sectionsid
                  AND sec.coursesid = cou.id
                  AND sem.id = sec.semestersid";

    $option = get_record_sql($sql);
    
    if(!$option) {
        $option = $section;
        $option->sectionsid = $section->id;

        $option->opt = $CFG->cas_reporting_default;
        $option->primary_instructor = $CFG->cas_reporting_primary;
        $option->non_primary_instructor = $CFG->cas_reporting_non_primary;
        $option->student = $CFG->cas_reporting_student;
        $option->non_primary_control = $CFG->block_student_gradeviewer_non_primary_control;
    }

    return $option;
}

function get_options($sections) {
    global $CFG;
    
    $sectionsid = implode(',', array_keys($sections));

    $sql = "SELECT opt.sectionsid, opt.id, opt.primary_instructor, 
                   opt.non_primary_instructor, opt.non_primary_control, opt.student
                FROM {$CFG->prefix}block_teacher_referral_opt opt
                WHERE opt.sectionsid IN ($sectionsid)";

    return get_records_sql($sql);
}
?>
