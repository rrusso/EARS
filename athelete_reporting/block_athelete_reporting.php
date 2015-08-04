<?php

require_once($CFG->dirroot . '/blocks/moodleblock.class.php');
require_once($CFG->dirroot . '/blocks/student_gradeviewer/lib.php');
require_once($CFG->dirroot . '/blocks/courseprefs/classes/CoursePrefsUser.php');
require_once($CFG->dirroot . '/blocks/courseprefs/lib.php');
require_once($CFG->libdir . '/grade/grade_item.php');
require_once($CFG->libdir . '/grade/grade_grade.php');
require_once($CFG->libdir . '/gradelib.php');
require_once($CFG->dirroot . '/grade/report/simple_grader/lib/simple_gradelib.php');

class block_athelete_reporting extends block_base {

    function init() {
        $this->title = get_string('blockname', 'block_athelete_reporting');
        $this->version = 2010042012;
    }

    function applicable_formats() {
        return array('course' => true, 'site' => false, 'my' => false);
    }

    function get_content() {
        if($this->content !== NULL) {
            return $this->content;
        }

        global $CFG, $COURSE, $USER;

        $this->content = new stdClass;
       
        $this->content->text = ''; 
        $this->content->footer = '';

        // Does the user have the right capability?
        // Line 4354 of lib/weblib.php suggests that a teacher 
        // is defined by one being able to view details of other users
        $context = get_context_instance(CONTEXT_COURSE, $COURSE->id);
        if(!has_capability('moodle/user:viewdetails', $context)) {
            return $this->content;
        }

        $user = CoursePrefsUser::findByUnique($USER->username);
        // No Atheletes in the course means this block is hidden
        if(get_athelete_count($COURSE) == 0) {
            // If in Edit mode: display the no athletes text
            $edit = optional_param('edit', 0, PARAM_INT);
            if($edit) $this->content->text = '<span>'.get_string('no_atheletes',
                                             'block_athelete_reporting').'</span>';
            return $this->content;
        }


        // Check course's grade to pass
        $this->courseitem = grade_item::fetch_course_item($COURSE->id);

        if(is_siteadmin($USER->id)) {
            $sections = cps_sections($COURSE);
        } else if($user) {
            $sections = $user->getSectionsForMoodleCourse($COURSE);
        } else {
            return $this->content;
        }


        $out = '<div class="block_athelete_reporting_body">
                 <form method="POST" action="'.$CFG->wwwroot.
                                             '/blocks/athelete_reporting/report.php">';

        foreach($sections as $section) {
            $atheletes = get_atheletes($section);
            if(!$atheletes) {
                continue;
            }
            $id = 'block_athelete_reporting_section_' . $section->id;
            $out .= '<div class="block_athelete_reporting_header">
                        <input type="image" src="'.$CFG->pixpath.'/t/switch_plus.gif"
                         onclick="elementToggleHide(this, true, function(el) { 
              return document.getElementById(\''.$id.'\');}, \'Show Section\', \'Hide Section\'); return false;">
                    '.get_string('format_section', 'block_courseprefs', $section).'
                     </div>
                     <div id="'.$id.'"
                          class="block_athelete_reporting_section_body hidden">
                        '.array_reduce($atheletes, array($this, 'reduce_athelete')).'
                     </div>';
        }
       
        if(!empty($sections)) {
            $out .= '       <input type="hidden" name="id" value="'.$COURSE->id.'">
              <input type="submit" value="'.get_string('report', 'block_athelete_reporting').'">
                        </form>';
        }

        $out .= '<a href="'.$CFG->wwwroot.'/blocks/student_gradeviewer/analysis.php?id='.
          $COURSE->id.'">'.get_string('analysis', 'block_student_gradeviewer').'</a>'; 
        
        $user = CoursePrefsUser::findByUnique($USER->username);
        if(!empty($CFG->cas_email) 
           and $user 
           and $user->getSectionsInfoAsTeacher(false, null, true)) {
            $out .= '<br/><span class="athlete_small_text">
                        <a href="'.$CFG->wwwroot.
                            '/blocks/student_gradeviewer/options.php?id='.$COURSE->id.'">'.
                                     get_string('options', 'block_student_gradeviewer').
                        '</a>
                     </span>';
        }        
        $out .= '</div>';
        $this->content->text = $out;

        return $this->content;
    }

    function reduce_athelete($in, $athelete) {
        global $COURSE, $CFG;

        // If the teacher has set grade to pass, we need to test
        // this user's final grade with it
        $user_grade = get_record('grade_grades', 'itemid', $this->courseitem->id, 
                                 'userid', $athelete->moodleid);
        $class = passing_grade($this->courseitem, $user_grade);

        $label = '<a class="'.$class.'" href="'.$CFG->wwwroot.'/grade/report/user/index.php?id='.
                            $COURSE->id.'&amp;userid='. $athelete->moodleid.'">'.
                        fullname($athelete). ' <span class="athlete_grade">' .
                        simple_grade_format_gradevalue($user_grade->finalgrade, $this->courseitem, true).
                            '</span></a>';

        $inter = (empty($in)) ? '' : $in;
        return $inter . '<div class="block_athelete_reporting_row">'.
                        print_checkbox('user_'.$athelete->id, 1, false, $label, '', '', true).
                        '</div>';
    }
}

?>
