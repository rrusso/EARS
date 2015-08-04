<?php

/*
 * Form allowing teachers to set preferences related to splitting courses.
 *
 * @author Adam Zapletal
 */

require_once('classes/CoursePrefsCourse.php');
require_once('classes/CoursePrefsUser.php');


function initial_content($params) {
    list($splits, $current_splits) = $params;

    if (empty($splits)) {
        // This case should never happen
        $html = '<span class="no_splits">'.get_string('no_string', 'block_courseprefs').'</span>';
    } else {
        $html = '<legend class="please_select">'.get_string('please_select', 
                 'block_courseprefs').'</legend>';
        foreach ($splits as $split_course) {
            foreach($split_course as $split) {
                $extra = '';
                if (!empty($current_splits) and array_key_exists($split->coursesid, $current_splits[$split->semestersid])) {
                    $extra = '<span class="previous_split">'.get_string('split_option_taken',
                    'block_courseprefs').'</span>';
                }
                $html .= '<input type="radio" name="selected" value="'. $split->semestersid .
                        '_'.$split->coursesid .'">'. $split->year . ' '. $split->name. ' '. 
                        $split->department . ' ' . $split->course_number . ' '. $extra.'<br/>';
            }
        }
    }
    return $html; 
}

function option_content($params) {
    list($current_splits, $course, $user, $semestersid) = $params;

    $sections = $user->getSectionsForCourse($semestersid, $course->coursesid);

    $html = '<legend class="please_select">'.get_string('split_current', 
             'block_courseprefs').'</legend>';
    $html .= '<div class="previous_splits">';
    $html .= "<ul>";
    foreach ($current_splits as $split) {
        $status = $split->getStatus();
        if ($status == 'resolved') {
            $status = "split";
        } else if ($status == 'todo') {
            $status = "marked to be split";
        } else {
            continue;
        }
        $section = $sections[$split->getSectionsId()];
        $html .= '<li>'.$course->department.' '.$course->course_number.
                 ' Section '.$section->getSectionNumber().' is <strong>'.$status.'</strong> into '.
                 'course '.$split->getShellName().'.</li>';
    }
    $html .= "</ul>";
    $html .= '</div>';
    
    $groups = $user->getSplitGroupnumber($course->coursesid);
    $a->groups = $groups;

    $html .= '<div class="option_splits">';
    $html .= '<input type="radio" name="mode" checked="checked" value="reset" />'.
             get_string('form_reset', 'block_courseprefs') . '('.
             get_string('form_current_num', 'block_courseprefs', $a).')<br/>';
    if ($groups != $course->count) {
        $html .= '<input type="radio" name="mode" value="regroup" checked="checked"/>'.
                 get_string('form_regroup', 'block_courseprefs').'<br/>';
    }
    $html .= '<input type="hidden" name="selected" value="'.$semestersid . '_'.
                $course->coursesid.'"/>';
    $html .= '<input type="hidden" name="number" value="'.$groups.'"/>';
    $html .= '</div>';
    return $html; 
}

function selected_content($params) {
    list($selected, $split) = $params;

    $html = '<legend class="explanation"><strong> '.
             $split->year. ' ' .$split->name.' '.$split->department.' '.
             $split->course_number.'</strong></legend>';
    $html .= '<span class="please_select">'.get_string('split_how_many', 'block_courseprefs').'</span>';
    $html .= '<select name="number">';
    for ($i = $split->count; $i > 1; $i--) {
        $html .= '<option value="'.$i.'">'.$i.'</option>';
    }
    $html .= '</select>';
    $html .= '<input type="hidden" name="selected" value="'.$selected.'"/>';
    return $html;
}

function reset_content($course) {
    $a->name = $course->department.' '.$course->course_number;
    $html = '<div class="warning_splits">';
    $html .= '<span class="warning_label">'.get_string('form_reset_warning', 
             'block_courseprefs', $a).'</span>';
    $html .= '<input type="hidden" name="selected" value="'.$course->semestersid.'_'.
                $course->coursesid.'"/>';
    $html .= '<input type="hidden" name="mode" value="perform_reset"/>';
    $html .= '</div>';
    return $html;
}
    
function numbered_content($params) {
    list($semestersid, $coursesid, $number, $user, $current_splits) = $params;

    $semester = get_record('block_courseprefs_semesters', 'id', $semestersid);
    $course = get_record('block_courseprefs_courses', 'id', $coursesid);
    $name = "{$semester->year} {$semester->name} {$course->department} {$course->course_number} ";
    $sections = array();
    $label = array();
    if (!array_key_exists($semestersid, $current_splits) ||
        !array_key_exists($coursesid, $current_splits[$semestersid])) {
        $sections = $user->getSectionsForCourse($semestersid, $coursesid, false);
        for ($i = 1; $i<=$number;$i++){
            $label[$i] = "Course {$i}";
        }
    } else {
        foreach ($current_splits[$semestersid][$coursesid] as $split) {
            $label[$split->getGroupingsId()] = $split->getShellName();
        }
    }


    $html = '<legend>'.$name.'</legend>';
    //$html .= '<div class="select_splits">';
    $html .= '<div id="error_message" class="no_splits"></div>';
    $html .= '<div class="available_sections">';
    $html .= '<span class="course_bank">'.get_string('crosslist_course_bank', 
             'block_courseprefs').'</span><br/>';
    $html .= '<select class="cps_your_sections" id="available_sections" multiple>';
    $html .= print_sections($sections);
    $html .= '</select>';
    $html .= '</div>';
    $html .= '<div class="select_move">';
    $html .= '<input type="button" onclick="moveSectionTo()" value="&gt;"/><br/>';
    $html .= '<input type="button" onclick="moveSectionFrom()" value="&lt;"/>';
    $html .= '</div>';
    $html .= '<div class="bucket_sections">'; 
    $first_bucket = true;
    for ($i = 1; $i <= $number; $i++) {
        /*$html .= '<input type="hidden" id="submit_bucket_'.
                   $i.'" name="bucket_'.$i.'"/>';*/
        $sections = $user->getSectionsByGroupingsId($semestersid, $coursesid, $i);
        $html .= '<span class="bucket_label">' . $name;
        $html .= '<strong id="bucket_'.$i.'_label">'.$label[$i].'</strong>';
        $html .= ' for '.$user->getFirstname().' '.$user->getLastname().' ';
        $html .= ' (<a onclick="toggleBucketName(\''.$i.'\')" href="javascript:void(0)">'.
                 get_string('form_customize', 'block_courseprefs').'</a>)</span>';
        $html .= '<input class="label_input" type="text" onkeyup="nameChanger(\''.$i.'\')" style="display:none" value="'.$label[$i].'" id="bucket_'.$i.'_name" name="bucket_'.$i.'_name"/>';
        $html .= '<input class="cps_bucket_radio" type="radio" name="selected_bucket" '.
                 ($first_bucket ? 'checked="checked"' : '').' value="bucket_'.$i.'"/>';
        $html .= '<select class="cps_bucket" name="bucket_'.$i.'[]" id="bucket_'.$i.'" multiple>';
        $html .= print_sections($sections);
        $html .= '</select>';
        if ($first_bucket) {
            $first_bucket = false;
        }
    }
    $html .= '</div>';
    //$html .= '</div>';
    $html .= '<input type="hidden" name="number" value="'.$number.'"/>';
    $html .= '<input type="hidden" name="selected" value="'.$semestersid.'_'.$coursesid.'"/>';
    $html .= '<script type="text/javascript">registerPreSubmit(\'available_sections\');</script>';
    return $html;
}


function print_sections($sections, $return=true) {
    $html = '';
    if (!empty($sections)) {
        foreach ($sections as $section) {
            $html .= '<option value="'.$section->getId().'">Section '. 
                    $section->getSectionNumber() . '</option>';
        }
    }

    if ($return) {
        return $html;
    } else {
        echo $html;
    }
    
}

function split_selected($selected) {
    if ($selected) {
        return explode('_', $selected);
    }
    return array('', '');
}

?>
