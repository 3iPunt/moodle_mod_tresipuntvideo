<?php

// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Tresipunt Video module version information
 *
 * @package    mod_tresipuntvideo
 * @copyright  2021 Tresipunt
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

global $CFG, $DB, $PAGE;

require('../../config.php');
require_once($CFG->dirroot.'/mod/tresipuntvideo/lib.php');
require_once($CFG->dirroot.'/mod/tresipuntvideo/locallib.php');
require_once($CFG->libdir.'/completionlib.php');

$id        = optional_param('id', 0, PARAM_INT); // Course Module ID
$r         = optional_param('r', 0, PARAM_INT);  // Tresipuntvideo instance ID
$redirect  = optional_param('redirect', 0, PARAM_BOOL);
$forceview = optional_param('forceview', 0, PARAM_BOOL);

if ($r) {
    if (!$tresipuntvideo = $DB->get_record('tresipuntvideo', array('id'=>$r))) {
        tresipuntvideo_redirect_if_migrated($r, 0);
        print_error('invalidaccessparameter');
    }
    $cm = get_coursemodule_from_instance(
        'tresipuntvideo',
        $tresipuntvideo->id,
        $tresipuntvideo->course,
        false,
        MUST_EXIST
    );

} else {
    if (!$cm = get_coursemodule_from_id('tresipuntvideo', $id)) {
        tresipuntvideo_redirect_if_migrated(0, $id);
        print_error('invalidcoursemodule');
    }
    $tresipuntvideo = $DB->get_record('tresipuntvideo', array('id'=>$cm->instance), '*', MUST_EXIST);
}

$course = $DB->get_record('course', array('id'=>$cm->course), '*', MUST_EXIST);

require_course_login($course, true, $cm);
$context = context_module::instance($cm->id);
require_capability('mod/tresipuntvideo:view', $context);

// Completion and trigger events.
tresipuntvideo_view($tresipuntvideo, $course, $cm, $context);

$PAGE->set_url('/mod/tresipuntvideo/view.php', array('id' => $cm->id));

if ($tresipuntvideo->tobemigrated) {
    tresipuntvideo_print_tobemigrated($tresipuntvideo, $cm, $course);
    die;
}

$fs = get_file_storage();
$files = $fs->get_area_files(
    $context->id, 'mod_tresipuntvideo',
    'content', 0,
    'sortorder DESC, id ASC', false);
if (count($files) < 1) {
    tresipuntvideo_print_filenotfound($tresipuntvideo, $cm, $course);
    die;
} else {
    $file = reset($files);
    unset($files);
}

$tresipuntvideo->mainfile = $file->get_filename();
$displaytype = tresipuntvideo_get_final_display_type($tresipuntvideo);
if ($displaytype == RESOURCELIB_DISPLAY_OPEN || $displaytype == RESOURCELIB_DISPLAY_DOWNLOAD) {
    if (strpos(get_local_referer(false), 'modedit.php') === false) {
        $redirect = true;
    }
}

// Don't redirect teachers, otherwise they can not access course or module settings.
if ($redirect && !course_get_format($course)->has_view_page() &&
        (has_capability('moodle/course:manageactivities', $context) ||
        has_capability('moodle/course:update', context_course::instance($course->id)))) {
    $redirect = false;
}

if ($redirect && !$forceview) {
    // coming from course page or url index page
    // this redirect trick solves caching problems when tracking views ;-)
    $path = '/'.$context->id.'/mod_tresipuntvideo/content/'.$tresipuntvideo->revision.$file->get_filepath().$file->get_filename();
    $fullurl = moodle_url::make_file_url('/pluginfile.php', $path, $displaytype == RESOURCELIB_DISPLAY_DOWNLOAD);
    redirect($fullurl);
}

switch ($displaytype) {
    case RESOURCELIB_DISPLAY_EMBED:
        tresipuntvideo_display_embed($tresipuntvideo, $cm, $course, $file);
        break;
    case RESOURCELIB_DISPLAY_FRAME:
        tresipuntvideo_display_frame($tresipuntvideo, $cm, $course, $file);
        break;
    default:
        tresipuntvideo_print_workaround($tresipuntvideo, $cm, $course, $file);
        break;
}

