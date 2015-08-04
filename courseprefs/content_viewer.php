<?php

require_once('../../config.php');
require_once($CFG->dirroot . '/blocks/courseprefs/ui/lib.php');

// Are you a valid user?
$context = get_context_instance(CONTEXT_SYSTEM);
if (!has_capability('block/courseprefs:viewdata', $context)) {
    // Nope, redirecting to main
    redirect($CFG->wwwroot);
}

require_login();

$heading_main = get_string('content_viewer', 'block_courseprefs');
$navigation = array(
              array('name' => get_string('blockname', 'block_courseprefs'), 'link' => '', 'type' => 'title'),
              array('name' => $heading_main, 'link' => '', 'type' => 'title')
              );

print_header_simple($heading_main, '', build_navigation($navigation));

$components = cps_user_component::build_components();
$components->display_as_table();

$where = $components->where_clause();

// Draw table
if ($data = data_submitted()) {
    if (!empty($where)) {
        build_table($components, $where);
    }
}

print_footer();

function build_table($components, $where) {
    global $CFG;

    $count_sql = "SELECT COUNT(id) FROM {$CFG->prefix}block_courseprefs_users ";
    $sql = "SELECT * FROM {$CFG->prefix}block_courseprefs_users ";
    $where_sql = "WHERE " . implode (' AND ', $where);
    $users = get_records_sql($sql . $where_sql);

    $defaults = $components->as_dict();
    $keys = array_keys($defaults);

    $count = count_records_sql($count_sql . $where_sql);

    // No results gets a special message
    if (empty($users)) {
        echo '<div class = "results">'.get_string('content_no_results', 'block_courseprefs').'</div>';
    } else {
        echo '<div class = "results">'.$count. get_string('content_results', 'block_courseprefs'). '</div>';

        echo '<table class = "generaltable" id = "tabular">';
        echo '<thead>';
        echo '<tr>';
        foreach ($defaults as $key => $value) {
            echo '<th class="header">'.$value.'</th>';
        }
        echo '</tr>';
        echo '</thead>';
        echo '<tbody>';
        $row = 1;
        foreach ($users as $user) {
            $row = ($row == 1) ? 0 : 1;
            echo '  <tr class="r' . $row . '">';
            foreach ($keys as $i=>$key) {
                $value = $user->{$key};
                // Trac ticket 1
                if ($key == 'username') {
                    $value = '<a href="'.$CFG->wwwroot.'/user/view.php?id='.$user->moodleid.'">'.$user->username.'</a>';
                }
                echo '      <td class="cell ' . $i . '">'.$value.'</td>';
            }
            echo '  </tr>';
        }
        echo '</tbody>';
        echo '</table>';
    }
}

?>
