<?php // $Id: quickmail_config.php,v 2.5 2008/02/15 09:53:43 pcali1 Exp $

/**
 *   Author: Philip Cali
 *   Date: 2/15/2008
 *   Louisiana State University
 *
 *   Moodle form for the Courseprefs config
 */

require_once('../../config.php');
require_once("$CFG->dirroot/blocks/moodleblock.class.php");
require_once('config_form.php');
require_once($CFG->libdir . '/accesslib.php');
require_once('classes/CoursePrefsConfig.php');

require_login();

//can user alter courseprefs config?
if (!is_siteadmin($USER->id)) {
    error(get_string('no_permission', 'block_courseprefs'));
}

$form = new config_form();

if ($form->is_cancelled()) {
    redirect("$CFG->wwwroot");
}

if ($data = $form->get_data()) {
    $configs = CoursePrefsConfig::findAll();

    foreach ($form->form_values as $key => $value) {
        if (!$configs[$key]) {
            $config = new CoursePrefsConfig($key, $value);
        } else if ($configs[$key]) {
            $config = $configs[$key];
            $config->setValue($value);
        } else {
            continue;
        }
        $config->save();
    }

    //Redirect back
    redirect("$CFG->wwwroot");

} else if (!$form->is_submitted()) {
    //Load the form with the correct values
    $form_data = array();

    $configs = CoursePrefsConfig::findAll();

    foreach ($configs as $config) {
        $form_data[$config->getName()] = $config->getValue();
    }

    $form->set_data($form_data);
}

$strcourseprefsconf = get_string('blockname', 'block_courseprefs');

$navigation = array(
    array('name' => $strcourseprefsconf, 'link' => '', 'type'=>'title'),
    array('name' => $strcourseprefsconf. ' Settings', 'link'=>'', 'type'=>'title')
    );

print_header_simple($strcourseprefsconf, '', build_navigation($navigation));

print_heading('Configuring a '. $strcourseprefsconf .' block');
$form->display();
print_footer();

?>
