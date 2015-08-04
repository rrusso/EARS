<?php

function initial_content($params) {
    list($crosslisted, $crosslistable) = $params;

    $html = '<div id="error_message" class="no_splits"></div>';
    $html .= '<legend class="please_select">'.get_string('crosslist_select', 'block_courseprefs').'</legend>';
    foreach($crosslistable as $cr) {
        $name = "{$cr->year} {$cr->name} {$cr->department} {$cr->course_number}";
        $exist = '';
        if (array_key_exists($cr->semestersid, $crosslisted) &&
            array_key_exists($cr->coursesid, $crosslisted[$cr->semestersid])){
            $exist = get_string('crosslist_option_taken', 'block_courseprefs');
        }
        $html .= '<input type="checkbox" name="'.$cr->semestersid.'_'.$cr->coursesid.'">'.
                 $name. $exist .'</input><br/>';
    }
    
    return $html;
}

function selected_content($params) {
    global $SESSION;
    list($courses, $courseids, $semestersid) = $params;

    // Some arbitrarily large number at first
    $last = 0;
    $total = 0;

    $html = '<legend class="explanation">'.get_string('crosslist_selected', 
             'block_courseprefs').'</legend>';
    $html .= '<div class="select_crosslists">';
    foreach ($courseids as $coursesid) {
        $course = $courses[$semestersid][$coursesid];
        $total += $course->count;
        $last = $course->count;
        $name = "{$course->year} {$course->name} {$course->department} {$course->course_number}";
        $html .= '<strong>'.$name.'</strong><br/>';
    }
    $html .= '<span class="please_select">'.get_string('split_how_many', 
             'block_courseprefs').'</span>';
    $html .= '<select name="number">';

    // The formula for determining the correct number of shells to appropriate
    $count = min($total / 2, $total - $last);
    for ($i = 1; $i <= $count; $i++) {
        $html .= '<option value="'.$i.'">'.$i.'</option>';
    }
    $html .= '</select>';
    $html .= '</div>';
    return $html;
}

function option_content($params) {
    global $SESSION;

    list($accepted_cr, $courses, $semestersid, $user) = $params;

    $html = '<legend class="please_select">'.get_string('crosslist_current', 
             'block_courseprefs').'</legend>';
    $html .= '<div class="previous_crosslist">';
    $html .= '<ul>';

    foreach ($accepted_cr as $cr) {
        $status = $cr->status;
        if ($status == 'resolved') {
            $status = get_string('crosslist', 'block_courseprefs');
        } else if ($status == 'todo') {
            $status = get_string('form_marked', 'block_courseprefs') . 
                      get_string('crosslist', 'block_courseprefs');
        } else {
            continue;
        }
        $html .= '<li>'.$cr->department.' '.$cr->course_number.
                       ' Section '.$cr->section_number.' is <strong>'.$status.'</strong> into '.
                       'course '.$cr->shell_name.'</li>';
    }
    $html .= '</ul>';
    $html .= '</div>';

    $groups = $user->findCrosslistNumber($semestersid, $courses);
    $a->groups = $groups;
    $total = $user->findTotalGroupCount($semestersid, $courses);
    $diff = $total - $groups;

    $html .= '<div class"option_crosslists">';
    $html .= '<input type="radio" name="mode" value="reset"/>'. 
              get_string('form_reset', 'block_courseprefs') .
             '('.get_string('form_current_num', 'block_courseprefs', $a).')<br/>';
    if ($diff > 0) {
        $a->html = '<select name="extra">';
            $options = ($diff <= 0) ? array() : range(1, $diff);
            foreach ($options as $option) {
                $a->html .= '<option value="'.$option.'">'.$option.'</option>';
            }
        $a->html .= '</select>';
        $html .= '<input type="radio" name="mode" value="regroup_add"/>'.
                 get_string('form_regroup_add', 'block_courseprefs', $a).'<br/>';
    }
    $html .= '<input type="radio" name="mode" value="regroup" checked="checked"/>'.
             get_string('form_regroup', 'block_courseprefs').'<br/>';
    $html .= '<input type="hidden" name="number" value="'.$groups.'"/>';
    $html .= '</div>';
    return $html;
}

function move_sections($params) {
    list($semestersid, $numbers, $courses, $user) = $params;

    $semester = get_record('block_courseprefs_semesters', 'id', $semestersid);
    $semester_name = "{$semester->year} {$semester->name} ";

    $label = array();
    $crosslists = array();
    $new_label = "";
    $avail_sections = array();
    foreach ($numbers as $number) {
        $cr = $user->getCrosslistsByClNumber($semester, $number);
        if (empty($cr)) {
            if (empty($new_label)) {
                foreach ($courses as $sections) {
                    $course = current($sections);
                    $new_label .= "{$course->department} {$course->course_number} / ";
                }
                $new_label = substr($new_label, 0, strlen($new_label) - 3);
            }
            $label[$number] = $new_label;   
        } else {
            $crosslists += $cr;
            $label[$number] = current(current($cr))->shell_name;
        }
    }

    foreach ($courses as $sections) {
        // array_diff breaks here (string) === will break these obj's
        foreach($crosslists as $crs) {
            foreach($crs as $r) {
                unset($sections[$r->id]);
            }
        }
        $avail_sections += $sections;
    }

    unset($courses);

    $html = "<legend>{$semester->year} {$semester->name} {$new_label}</legend>"; 
    $html .= '<div class="select_crosslists">';
    $html .= '<div id="error_message" class="no_splits"></div>';
    $html .= '<div class="available_sections">';
    $html .= '<span class="course_bank">'.get_string('crosslist_course_bank', 
             'block_courseprefs').'</span><br/>';
    $html .= '<select name="available_sections[]" id="available_sections" multiple size="30">';
    foreach ($avail_sections as $section) {
        // If the section has been crosslisted, then don't
        // add to the bank
        $name = "{$semester_name}{$section->department} {$section->course_number} {$section->section_number}";
        $html .= '<option value="'.$section->id.'">'.$name.'</option>';
    }
    $html .= '</select>';
    $html .= '</div>';
    $html .= '<div class="select_move">';
    $html .= '<input type="button" onclick="moveSectionTo()" value="&gt;"/><br/>';
    $html .= '<input type="button" onclick="moveSectionFrom()" value="&lt;"/><br/>';
    $html .= '</div>';
    $html .= '<div class="bucket_sections">';

    $first_bucket = true;
    foreach ($numbers as $i) {
        $idnumber = "{$semester->year}{$semester->name}{$user->getUsername()}cl{$i}";
        $html .= '<span class="bucket_label">' . $semester_name;
        $html .= '<strong id="bucket_'.$i.'_label">'.$label[$i].'</strong>';
        $html .= ' for '.$user->getFirstname().' '.$user->getLastname().' ';
        $html .= ' (<a onclick="toggleBucketName(\''.$i.'\')" href="javascript:void(0)">'.
                 get_string('form_customize', 'block_courseprefs').'</a>)</span>';
        $html .= '<input class="label_input" type="text" onkeyup="nameChanger(\''.$i.'\')" style="display:none" value="'.$label[$i].'" id="bucket_'.$i.'_name" name="bucket_'.$i.'_name"/>';
        $html .= '<input class="cps_bucket_radio" type="radio" name="selected_bucket" '.
                  ($first_bucket ? 'checked="checked"' : '') . ' value="bucket_'.$i.'"/>';
        $html .= '<select class="cps_bucket" name="bucket_'.$i.'[]" id="bucket_'.$i.'" multiple>';
        if (array_key_exists($idnumber, $crosslists) && !empty($crosslists[$idnumber])) {
            foreach ($crosslists[$idnumber] as $cr) {
                $name = "{$semester_name}{$cr->department} {$cr->course_number} {$cr->section_number}";
                $html .= '<option value="'.$cr->id.'">'.$name.'</option>';
            }
        }
        $html .= '</select>';
        if ($first_bucket) {
            $first_bucket = false;
        }
    }
    $html .= '</div>';
    $html .= '</div>';
    $html .= '<script type="text/javascript">registerPreSubmit(\'available_sections\');</script>';
    
    return $html;
}

function reset_content($courses) {
    $name = '<ul>';
    foreach ($courses as $course) {
        $name .= '<li>' . $course->department.' '.$course->course_number.'</li>';
    }
    $a->name = $name . '</ul>';

    $html = '<div class="warning_crosslists">';
    $html .= '<span class="warning_label">' . get_string('form_reset_warning', 'block_courseprefs', $a);
    $html .= '</span>';
    $html .= '<input type="hidden" name="mode" value="perform_reset"/>';
    $html .= '</div>';
    return $html;
}

?>
