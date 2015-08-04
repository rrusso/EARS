<?php

require_once('../../config.php');
require_once($CFG->dirroot . '/blocks/courseprefs/lib.php');

require_login();

$semester = optional_param('semester', 0, PARAM_INT);
$action = optional_param('action');

// only admins allowed here
if (!is_siteadmin($USER->id)) {
    redirect($CFG->wwwroot);
}

// In this case, there was a problem, just redirect them
// back to this screen
if ($semester != 0 && !$action) {
    redirect($CFG->wwwroot . '/blocks/courseprefs/cleanup.php'); 
}

$heading = get_string('cleanup_header', 'block_courseprefs');
$navigation = array(
            array('name' => get_string('blockname', 'block_courseprefs'), 'link' => '', 'type' => 'title'),
        array('name' => $heading, 'link' => '', 'type' => 'title',)
            );

print_header_simple($heading, '', build_navigation($navigation));
print_heading($heading);

echo '<div class="output">';
if ($action == 'cleanup') {
    $logger = new Logger(true);
    courseprefs_cleanup(__FILE__, $semester, $logger, true);
}

$semesters = get_records_sql("SELECT sem.*, COUNT(sec.id) as count
            FROM {$CFG->prefix}block_courseprefs_semesters sem,
                 {$CFG->prefix}block_courseprefs_sections sec
            WHERE sec.semestersid = sem.id
            GROUP BY sec.semestersid");

echo '<div class="cleanup_header">
       <span >
        <a href="cleanup.php?action=cleanup">'.get_string('perform_old', 'block_courseprefs').'</a>
      </span>
      </div>';

echo '<table class="flexible generaltable generalbox">
        <tr>
            <th class="header">Name</th>
            <th class="header">Section count</th>
            <th class="header">Perform</th>
        </tr>';

$row = 1;
foreach ($semesters as $id => $sem) {
    $row = ($row == 1) ? 0 : 1;
    echo '<tr class="r'.$row.'">
            <td class="cell">'.$sem->year.' '.$sem->name.' '.$sem->campus.'</td>
            <td class="cell">'.$sem->count.'</td>
            <td class="cell"><a href="cleanup.php?action=cleanup&amp;semester='.$sem->id.'">'.
                get_string('cleanup', 'block_courseprefs').'</a></td>
          </tr>';
}

echo   '</table>';

echo '</div>';

print_footer();
?>
