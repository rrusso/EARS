<?php

require_once('../../config.php');
require_once('lib.php');
require_once('classes/CoursePrefsSection.php');
require_once('classes/CoursePrefsCrosslist.php');
require_once('classes/CoursePrefsUser.php');
require_once('classes/CoursePrefsLog.php');

require_login();

$submit = required_param('submit');

if ($submit == "Back") {
    redirect('crosslist.php');
}

// require the $numbers from the user SESSION
$numbers = $SESSION->crosslist_preferences['numbers'];

if (!$numbers) {
    redirect_error(ERROR_USER_MISSING);
}

$user= CoursePrefsUser::findByUnique($USER->username);

if (!$user) {
    redirect_error(ERROR_USER_MISSING);
}

$crosslistable = $user->getCrosslistableSections();

if (!$crosslistable) {
    redirect_error(ERROR_COURSE_NONE);
}

$sections = $user->getSectionsInfoAsPrimaryTeacher();

$data = data_submitted();

$heading_main = get_string('crosslist_heading', 'block_courseprefs');
$navigation = array(
    array('name' => get_string('blockname', 'block_courseprefs'), 'link' => '', 'type' => 'title'),
    array('name' => $heading_main, 'link' => '', 'type' => 'title',)
    );

print_header_simple($heading_main, '', build_navigation($navigation));
print_heading_with_help($heading_main, 'crosslist', 'block_courseprefs');

$changed_cr = array();
$new_cr = array();
$errors = array();

$crosslisted = $user->getCrosslists();

// First let's remove the old ones
if (isset($data->{'available_sections'}) && !empty($data->{'available_sections'})) {
    foreach ($data->{'available_sections'} as $sectionsid) {
        $section = $sections[$sectionsid];
        if (array_key_exists($sectionsid, $crosslisted)) {
            $cr = $crosslisted[$sectionsid];
            reset_prefs(array($cr));
            insert_into_array($section, $changed_cr, $cr->getShellName());
        }
    }
}

// Add/update existing ones
foreach($numbers as $i) {
    if (!isset($data->{'bucket_'.$i}) || count($data->{'bucket_'. $i}) < 2) {
        $a->name = $data->{'bucket_'.$i.'_name'};
        $a->number = 2;
        $errors[] = get_string('err_invalid_bucket', 'block_courseprefs', $a);
        continue;
    }

    foreach ($data->{'bucket_'.$i} as $sectionsid) {
        // They tried to hack section's id
        if (!array_key_exists($sectionsid, $sections)) {
            $a->sectionsid = $sectionsid;
            $errors[] = get_string('err_invalid_section', 'block_courseprefs', $a);
            continue;
        }
        $section = $sections[$sectionsid];
        $suggested_idnumber = "{$section->year}{$section->name}{$user->getUsername()}cl{$i}";

        if (array_key_exists($sectionsid, $crosslisted)) {
            $cr = $crosslisted[$sectionsid];
            if ($suggested_idnumber == $cr->getIdnumber()) {
                $cr->setShellName($data->{'bucket_'.$i.'_name'});
                $cr->setStatus('todo');
            } else {
                reset_prefs(array($cr));
            }
            insert_into_array($section, $changed_cr, $cr->getShellName());
        } else {
            $cr = new CoursePrefsCrosslist($user->getId(), $sectionsid, 'todo', 
                $data->{'bucket_'.$i.'_name'}, $suggested_idnumber);
            insert_into_array($section, $new_cr, $cr->getShellName());
        }

        try {
            $cr->save();
        } catch (Exception $e){
            $errors[] = 'Could not save a cross list entry!';
        }
    }
}

$params = array($changed_cr, $new_cr, $errors);
build_form('finished_content', $params, 'Next', '', 'GET', 'crosslist.php');
print_footer();

function insert_into_array($object, &$array, $key = null) {
    if ($key) {
        if (!array_key_exists($key, $array)) {
            $array[$key] = array();
        }
        $array[$key][] = $object;
    } else {
        $array[] = $object;
    }
}
?>
