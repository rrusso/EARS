<?php

require_once('../../config.php');
require_once('lib.php');
require_once('admin/lib.php');

$admin_type = optional_param('type', 'person');

require_login();

$context = get_context_instance(CONTEXT_SYSTEM);

$sportsadmin = has_capability('block/student_gradeviewer:sportsadmin', $context);
$academicadmin = has_capability('block/student_gradeviewer:academicadmin', $context);

// If they are not an admin, then error out; they should not be here
if (!$sportsadmin && !$academicadmin) {
    error(get_string('no_permission', 'block_student_gradeviewer'));
}

// Add the appropritate autocomplete css
$CFG->stylesheets[] = $CFG->wwwroot . '/lib/yui/autocomplete/assets/skins/sam/autocomplete.css';

// Get the admin pages
// Gets the classes only the user has capabilities for
$classes = student_gradeviewer_get_classes();

// Get the current admin page, they are capable of seeing
$current = $classes[$admin_type];

// If in fact, they try something funny, force them on the right path
if(!$current) {
    $current = $classes['person'];
}

// Boilerplate header info
$blockname = get_string('blockname', 'block_student_gradeviewer');
$heading_main = get_string('admin', 'block_student_gradeviewer');
$navigation = array(
              array('name' => $blockname, 'type' => 'title', 'link' => ''),
              array('name' => $heading_main, 'type' => 'title', 'link' => ''),
              array('name' => $current->get_name(), 'type' => 'title', 'link' => '')
            );
print_header_simple($heading_main, '', build_navigation($navigation));

// print out the admin page selector
admin_page::type_selector($admin_type, $classes);

// Print out the name of the page
$current->print_heading();

// Initialize some internal data.
$current->init();

// Process the submitted data
if($data = data_submitted()) {
    $current->process_data($data);
}

// Every page has a header of some kind
// and a form of some kind
$current->print_header();
$current->print_form();

print_footer();


?>
