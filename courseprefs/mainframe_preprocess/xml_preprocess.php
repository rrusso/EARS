<?php

require_once('../../../config.php');
require_once('lib.php');
require_once($CFG->dirroot. '/blocks/courseprefs/classes/CoursePrefsUser.php');
require_once($CFG->dirroot. '/blocks/courseprefs/classes/CoursePrefsStudent.php');
require_once($CFG->dirroot. '/blocks/courseprefs/classes/CoursePrefsTeacher.php');
require_once($CFG->dirroot. '/blocks/courseprefs/classes/CoursePrefsSemester.php');
require_once($CFG->dirroot. '/blocks/courseprefs/classes/CoursePrefsLog.php');
require_once($CFG->dirroot. '/blocks/courseprefs/classes/CoursePrefsCourse.php');
require_once($CFG->dirroot. '/blocks/courseprefs/classes/CoursePrefsSection.php');

$base_url = 'http://130.39.194.228:8080/moodle';

$preprocess = new XmlPreprocess();
try {
    $preprocess->setURL($base_url . '/users/20092S/891466560', null, true);
    $preprocess->process();
} catch (Exception $e) {
    $preprocess->errorlog[] $e->getMessage();
}
report_errors($preprocess->errorlog, __FILE__, 'Courseprefs XML Preprocessor',
            'XML File preprocessor errors');

