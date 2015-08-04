<?php

require_once('../../config.php');
require_once('classes/CoursePrefsUser.php');
require_once('classes/CoursePrefsSplit.php');
require_once('classes/CoursePrefsSection.php');
require_once('classes/CoursePrefsLog.php');
require_once('lib.php');
require_once('split_form.php');

$selected = required_param('selected');
$number = required_param('number', PARAM_INT);
$submit = required_param('submit');

// They want to go back, so take them back
if ($submit == "Back") {
    redirect('split.php');
}

list($semestersid, $courseid) = split_selected($selected);

require_login();

$user = CoursePrefsUser::findByUnique($USER->username);

// Oops, we don't know this fella
if (!$user) {
    redirect_error(ERROR_USER_MISSING);
}

// Validation comes first
$splittable = $user->getSplittableCourses();

// Hmm, selected entry is not splittable...? Hacker?
if (!$splittable || !array_key_exists($courseid, $splittable[$semestersid])) {
    redirect_error(ERROR_COURSE_NONE);
}

$sections = $user->getSectionsInfoAsPrimaryTeacher();

if ($number < 2 || $number > count($sections)) {
    // error: number does not match the split citerion
    // user tried to hack post data
    redirect('split.php?selected='.$selected);
}

$current_splits = $user->getSplits();
if (!empty($current_splits)) {
    $splits = array_get_or_else($courseid, $current_splits[$semestersid], array());
} else {
    $splits = array();
}

// Render the page headers and forms
$heading_main = get_string('split_heading', 'block_courseprefs');
$navigation = array(
        array('name' => get_string('blockname', 'block_courseprefs'), 'link' => '', 'type' => 'title'),
        array('name' => $heading_main, 'link' => '', 'type' => 'title',)
        );

print_header_simple($heading_main, '', build_navigation($navigation));
print_heading_with_help($heading_main, 'split', 'block_courseprefs');

$data = data_submitted();

$changed_splits = array();
$new_splits = array();
$errors = array();

// We're either editing existing data, or creating the splits
for($i = 1; $i <= $number; $i++) {
    // Tell them if something happened
    if (!isset($data->{'bucket_'.$i}) || count($data->{'bucket_'.$i}) < 1) {
        $a->name = $data->{'bucket_'.$i.'_name'};
        $a->number = 1;
        $errors[] = get_string('err_invalid_bucket', 'block_courseprefs', $a);
        continue;
    }

    foreach ($data->{'bucket_'.$i} as $sectionsid) {
        // Tried to hack a sectionised that's not theirs
        if (!array_key_exists($sectionsid, $sections)) {
            $a->sectionsid = $sectionsid;
            $errors[] = get_string('err_invalid_section', 'block_courseprefs', $a);
            continue;
        }

        if (array_key_exists($sectionsid, $splits)) {
            $split = $splits[$sectionsid];
            if($i != $split->getGroupingsId()){
                reset_prefs(array($split));
                $split->setId(null);
                $split->setGroupingsId($i);
            }
            $split->setShellName($data->{'bucket_'.$i.'_name'});
            $split->setStatus('todo');

            insert_into_array($sections[$sectionsid], $changed_splits, $split->getShellName());
        }else {
            $split = new CoursePrefsSplit($user->getId(),
                $sectionsid, $i, $data->{'bucket_'.$i.'_name'}, 'todo');
            insert_into_array($sections[$sectionsid], $new_splits, $split->getShellName());
        }

        // Try to save
        try {
            $split->save();
        } catch (Exception $e) {
            $errors[] = 'Error trying to insert a split record.';
        }
    }
}

$params = array($changed_splits, $new_splits, $errors);
build_form('finished_content', $params, 'Next', '');

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

function array_get_or_else($key, $array, $value) {
    if (array_key_exists($key, $array)) {
        return $array[$key];
    }
    return $value;
}

?>
