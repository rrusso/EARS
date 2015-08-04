<?php

/**
 * Page rendering block-specific errors to users before redirecting them to
 * another destination; either document root or back to the reporting page.
 * Checks and decodes GET parameters and looks up related error.
 */
require_once('../../config.php');
require_once('lib.php');

// Require users be logged in before accessing page
require_login();

// Determine if there was any data passed into the page
$error = urldecode(optional_param('error'));
//$url = urldecode(optional_param('url'));

// Set the redirect URL if it wasn't passed in as a parameter
/*if (!$url) {
    $url = $CFG->wwwroot;
}*/

// Display the error page and setup a second redirect
print_header_simple(get_string('cps_error', 'block_courseprefs'), '',
        build_navigation( array(
            array('name' => get_string('blockname', 'block_courseprefs'), 'link' => '', 'type'=>'title'),
            array('name' => get_string('cps_error', 'block_courseprefs'), 'link' => '', 'type'=>'title')
        )));
print_heading(get_string('blockname', 'block_courseprefs') . 
              get_string('cps_error', 'block_courseprefs'));
$rtn = lookup_errorcode($error);
echo '<div class="cps_error">' . $rtn->error. '</div>';
echo $rtn->reasons;
print_continue($CFG->wwwroot . '/my');
print_footer();

//redirect($url, lookup_errorcode($error), 10);

?>
