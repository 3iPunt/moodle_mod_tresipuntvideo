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
 * @package    mod_tresipuntvideo
 * @copyright  2021 Tresipunt
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

require_once("$CFG->dirroot/mod/tresipuntvideo/locallib.php");

/**
 * List of features supported in Tresipunt Video module
 * @param string $feature FEATURE_xx constant for requested feature
 * @return mixed True if module supports feature, false if not, null if doesn't know
 */
function tresipuntvideo_supports(string $feature) {
    switch($feature) {
        case FEATURE_MOD_ARCHETYPE:           return MOD_ARCHETYPE_RESOURCE;
        case FEATURE_GROUPS:                  return false;
        case FEATURE_GROUPINGS:               return false;
        case FEATURE_MOD_INTRO:               return true;
        case FEATURE_COMPLETION_TRACKS_VIEWS: return true;
        case FEATURE_GRADE_HAS_GRADE:         return false;
        case FEATURE_GRADE_OUTCOMES:          return false;
        case FEATURE_BACKUP_MOODLE2:          return true;
        case FEATURE_SHOW_DESCRIPTION:        return true;

        default: return null;
    }
}

/**
 * This function is used by the reset_course_userdata function in moodlelib.
 * @param $data the data submitted from the reset course.
 * @return array status array
 */
function tresipuntvideo_reset_userdata($data): array {

    // Any changes to the list of dates that needs to be rolled should be same during course restore and course reset.
    // See MDL-9367.

    return array();
}

/**
 * List the actions that correspond to a view of this module.
 * This is used by the participation report.
 *
 * Note: This is not used by new logging system. Event with
 *       crud = 'r' and edulevel = LEVEL_PARTICIPATING will
 *       be considered as view action.
 *
 * @return array
 */
function tresipuntvideo_get_view_actions(): array {
    return array('view','view all');
}

/**
 * List the actions that correspond to a post of this module.
 * This is used by the participation report.
 *
 * Note: This is not used by new logging system. Event with
 *       crud = ('c' || 'u' || 'd') and edulevel = LEVEL_PARTICIPATING
 *       will be considered as post action.
 *
 * @return array
 */
function tresipuntvideo_get_post_actions() {
    return array('update', 'add');
}

/**
 * Add tresipuntvideo instance.
 * @param object $data
 * @param object $mform
 * @return int new tresipuntvideo instance id
 */
function tresipuntvideo_add_instance($data, $mform) {
    global $CFG, $DB;
    require_once("$CFG->libdir/resourcelib.php");
    require_once("$CFG->dirroot/mod/tresipuntvideo/locallib.php");
    $cmid = $data->coursemodule;
    $data->timemodified = time();

    tresipuntvideo_set_display_options($data);

    $data->id = $DB->insert_record('tresipuntvideo', $data);

    // we need to use context now, so we need to make sure all needed info is already in db
    $DB->set_field('course_modules', 'instance', $data->id, array('id'=>$cmid));
    tresipuntvideo_set_mainfile($data);

    $completiontimeexpected = !empty($data->completionexpected) ? $data->completionexpected : null;
    \core_completion\api::update_completion_date_event(
        $cmid, 'tresipuntvideo', $data->id, $completiontimeexpected);

    return $data->id;
}

/**
 * Update tresipuntvideo instance.
 * @param object $data
 * @param object $mform
 * @return bool true
 * @throws dml_exception
 */
function tresipuntvideo_update_instance($data, $mform) {
    global $CFG, $DB;
    require_once("$CFG->libdir/resourcelib.php");
    $data->timemodified = time();
    $data->id           = $data->instance;
    $data->revision++;

    tresipuntvideo_set_display_options($data);

    $DB->update_record('tresipuntvideo', $data);
    tresipuntvideo_set_mainfile($data);

    $completiontimeexpected = !empty($data->completionexpected) ? $data->completionexpected : null;
    \core_completion\api::update_completion_date_event(
        $data->coursemodule, 'tresipuntvideo', $data->id, $completiontimeexpected);

    return true;
}

/**
 * Updates display options based on form input.
 *
 * Shared code used by tresipuntvideo_add_instance and tresipuntvideo_update_instance.
 *
 * @param object $data Data object
 */
function tresipuntvideo_set_display_options($data) {
    $displayoptions = array();
    if ($data->display == RESOURCELIB_DISPLAY_POPUP) {
        $displayoptions['popupwidth']  = $data->popupwidth;
        $displayoptions['popupheight'] = $data->popupheight;
    }
    if (in_array($data->display, array(
        RESOURCELIB_DISPLAY_AUTO, RESOURCELIB_DISPLAY_EMBED, RESOURCELIB_DISPLAY_FRAME))) {
        $displayoptions['printintro']   = (int)!empty($data->printintro);
    }
    if (!empty($data->showsize)) {
        $displayoptions['showsize'] = 1;
    }
    if (!empty($data->showtype)) {
        $displayoptions['showtype'] = 1;
    }
    if (!empty($data->showdate)) {
        $displayoptions['showdate'] = 1;
    }
    $data->displayoptions = serialize($displayoptions);
}

/**
 * Delete tresipuntvideo instance.
 *
 * @param $id
 * @return bool
 * @throws coding_exception
 * @throws dml_exception
 */
function tresipuntvideo_delete_instance($id) {
    global $DB;

    if (!$tresipuntvideo = $DB->get_record('tresipuntvideo', array('id'=>$id))) {
        return false;
    }

    $cm = get_coursemodule_from_instance('tresipuntvideo', $id);
    \core_completion\api::update_completion_date_event(
        $cm->id, 'tresipuntvideo', $id, null);

    // note: all context files are deleted automatically

    $DB->delete_records('tresipuntvideo', array('id'=>$tresipuntvideo->id));

    return true;
}

/**
 * Given a course_module object, this function returns any
 * "extra" information that may be needed when printing
 * this activity in a course listing.
 *
 * See {@link get_array_of_activities()} in course/lib.php
 *
 * @param $coursemodule
 * @return cached_cm_info|null
 * @throws coding_exception
 * @throws dml_exception
 */
function tresipuntvideo_get_coursemodule_info($coursemodule) {
    global $CFG, $DB;
    require_once("$CFG->libdir/filelib.php");
    require_once("$CFG->dirroot/mod/resource/locallib.php");
    require_once($CFG->libdir.'/completionlib.php');

    $context = context_module::instance($coursemodule->id);

    if (!$tresipuntvideo = $DB->get_record('tresipuntvideo', array('id'=>$coursemodule->instance),
            'id, name, display, displayoptions, tobemigrated, revision, intro, introformat')) {
        return NULL;
    }

    $info = new cached_cm_info();
    $info->name = $tresipuntvideo->name;
    if ($coursemodule->showdescription) {
        global $PAGE;
        $context = context_module::instance( $coursemodule->id );

        $fs = get_file_storage();
        $files = $fs->get_area_files(
            $context->id, 'mod_' . $coursemodule->modname,
            'content', 0,
            'sortorder DESC, id ASC', false);

        $file = reset($files);

        $moodleurl = moodle_url::make_pluginfile_url(
            $context->id, 'mod_' . $coursemodule->modname, 'content', $tresipuntvideo->revision,
            $file->get_filepath(), $file->get_filename());

        $embedoptions = array(
            core_media_manager::OPTION_TRUSTED => true,
            core_media_manager::OPTION_BLOCK => true,
        );

        $mediamanager = core_media_manager::instance($PAGE);

        $content = $mediamanager->embed_url(
            $moodleurl, $coursemodule->name, 0, 0, $embedoptions
        );
        $content .= format_module_intro('tresipuntvideo', $tresipuntvideo, $coursemodule->id, false);

        $info->content = $content;
    }

    if ($tresipuntvideo->tobemigrated) {
        $info->icon ='i/invalid';
        return $info;
    }

    // See if there is at least one file.
    $fs = get_file_storage();
    $files = $fs->get_area_files(
        $context->id, 'mod_tresipuntvideo', 'content', 0,
        'sortorder DESC, id ASC', false, 0, 0, 1);
    if (count($files) >= 1) {
        $mainfile = reset($files);
        $info->icon = file_file_icon($mainfile, 24);
        $tresipuntvideo->mainfile = $mainfile->get_filename();
    }

    $display = tresipuntvideo_get_final_display_type($tresipuntvideo);

    if ($display == RESOURCELIB_DISPLAY_POPUP) {
        $fullurl = "$CFG->wwwroot/mod/tresipuntvideo/view.php?id=$coursemodule->id&amp;redirect=1";
        $options = empty($tresipuntvideo->displayoptions) ? array() : unserialize($tresipuntvideo->displayoptions);
        $width  = empty($options['popupwidth'])  ? 620 : $options['popupwidth'];
        $height = empty($options['popupheight']) ? 450 : $options['popupheight'];
        $wh = "width=$width,height=$height,toolbar=no,location=no,menubar=no,copyhistory=no,status=no,directories=no,scrollbars=yes,resizable=yes";
        $info->onclick = "window.open('$fullurl', '', '$wh'); return false;";

    } else if ($display == RESOURCELIB_DISPLAY_NEW) {
        $fullurl = "$CFG->wwwroot/mod/tresipuntvideo/view.php?id=$coursemodule->id&amp;redirect=1";
        $info->onclick = "window.open('$fullurl'); return false;";

    }

    // If any optional extra details are turned on, store in custom data,
    // add some file details as well to be used later by tresipuntvideo_get_optional_details() without retriving.
    // Do not store filedetails if this is a reference - they will still need to be retrieved every time.
    if (($filedetails = tresipuntvideo_get_file_details($tresipuntvideo, $coursemodule)) && empty($filedetails['isref'])) {
        $displayoptions = @unserialize($tresipuntvideo->displayoptions);
        $displayoptions['filedetails'] = $filedetails;
        $info->customdata = serialize($displayoptions);
    } else {
        $info->customdata = $tresipuntvideo->displayoptions;
    }

    return $info;
}

/**
 * Called when viewing course page. Shows extra details after the link if
 * enabled.
 *
 * @param cm_info $cm Course module information
 */
function tresipuntvideo_cm_info_view(cm_info $cm) {
    global $CFG;
    require_once($CFG->dirroot . '/mod/tresipuntvideo/locallib.php');

    $tresipuntvideo = (object)array('displayoptions' => $cm->customdata);
    $details = tresipuntvideo_get_optional_details($tresipuntvideo, $cm);
    if ($details) {
        $cm->set_after_link(' ' . html_writer::tag('span', $details,
                array('class' => 'tresipuntvideolinkdetails')));
    }
}

/**
 * Lists all browsable file areas
 *
 * @param $course
 * @param $cm
 * @param $context
 * @return array
 * @throws coding_exception
 */
function tresipuntvideo_get_file_areas($course, $cm, $context) {
    $areas = array();
    $areas['content'] = get_string('tresipuntvideocontent', 'tresipuntvideo');
    return $areas;
}

/**
 * File browsing support for tresipuntvideo module content area.
 *
 * @param stdClass $browser file browser instance
 * @param stdClass $areas file areas
 * @param stdClass $course course object
 * @param stdClass $cm course module object
 * @param stdClass $context context object
 * @param string $filearea file area
 * @param int $itemid item ID
 * @param string $filepath file path
 * @param string $filename file name
 * @return file_info instance or null if not found
 * @throws coding_exception
 * @package  mod_tresipuntvideo
 * @category files
 */
function tresipuntvideo_get_file_info($browser, $areas, $course, $cm, $context, $filearea, $itemid, $filepath, $filename) {
    global $CFG;

    if (!has_capability('moodle/course:managefiles', $context)) {
        // students can not peak here!
        return null;
    }

    $fs = get_file_storage();

    if ($filearea === 'content') {
        $filepath = is_null($filepath) ? '/' : $filepath;
        $filename = is_null($filename) ? '.' : $filename;

        $urlbase = $CFG->wwwroot.'/pluginfile.php';
        if (!$storedfile = $fs->get_file(
            $context->id, 'mod_tresipuntvideo', 'content', 0, $filepath, $filename)) {
            if ($filepath === '/' and $filename === '.') {
                $storedfile = new virtual_root_file($context->id, 'mod_tresipuntvideo', 'content', 0);
            } else {
                // not found
                return null;
            }
        }
        require_once("$CFG->dirroot/mod/tresipuntvideo/locallib.php");
        return new tresipuntvideo_content_file_info(
            $browser, $context, $storedfile, $urlbase, $areas[$filearea], true, true, true, false);
    }

    // note: tresipuntvideo_intro handled in file_browser automatically

    return null;
}

/**
 * Serves the tresipuntvideo files.
 *
 * @param stdClass $course course object
 * @param stdClass $cm course module object
 * @param stdClass $context context object
 * @param string $filearea file area
 * @param array $args extra arguments
 * @param bool $forcedownload whether or not force download
 * @param array $options additional options affecting the file serving
 * @return bool false if file not found, does not return if found - just send the file
 * @throws coding_exception
 */
function tresipuntvideo_pluginfile($course, $cm, $context, $filearea, $args, $forcedownload, array $options=array()) {
    global $CFG, $DB;
    require_once("$CFG->libdir/resourcelib.php");

    if ($context->contextlevel != CONTEXT_MODULE) {
        return false;
    }

    require_course_login($course, true, $cm);
    if (!has_capability('mod/tresipuntvideo:view', $context)) {
        return false;
    }

    if ($filearea !== 'content') {
        // intro is handled automatically in pluginfile.php
        return false;
    }

    array_shift($args); // ignore revision - designed to prevent caching problems only

    $fs = get_file_storage();
    $relativepath = implode('/', $args);
    $fullpath = rtrim("/$context->id/mod_tresipuntvideo/$filearea/0/$relativepath", '/');
    do {
        if (!$file = $fs->get_file_by_hash(sha1($fullpath))) {
            if ($fs->get_file_by_hash(sha1("$fullpath/."))) {
                if ($file = $fs->get_file_by_hash(sha1("$fullpath/index.htm"))) {
                    break;
                }
                if ($file = $fs->get_file_by_hash(sha1("$fullpath/index.html"))) {
                    break;
                }
                if ($file = $fs->get_file_by_hash(sha1("$fullpath/Default.htm"))) {
                    break;
                }
            }
            $tresipuntvideo = $DB->get_record(
                'tresipuntvideo', array('id'=>$cm->instance), 'id, legacyfiles', MUST_EXIST);
            if ($tresipuntvideo->legacyfiles != RESOURCELIB_LEGACYFILES_ACTIVE) {
                return false;
            }
            if (!$file = resourcelib_try_file_migration(
                '/'.$relativepath, $cm->id, $cm->course, 'mod_tresipuntvideo', 'content', 0)) {
                return false;
            }
            // file migrate - update flag
            $tresipuntvideo->legacyfileslast = time();
            $DB->update_record('tresipuntvideo', $tresipuntvideo);
        }
    } while (false);

    // should we apply filters?
    $mimetype = $file->get_mimetype();
    if ($mimetype === 'text/html' or $mimetype === 'text/plain' or $mimetype === 'application/xhtml+xml') {
        $filter = $DB->get_field('tresipuntvideo', 'filterfiles', array('id'=>$cm->instance));
        $CFG->embeddedsoforcelinktarget = true;
    } else {
        $filter = 0;
    }

    // finally send the file
    send_stored_file($file, null, $filter, $forcedownload, $options);
}

/**
 * Return a list of page types
 *
 * @param string $pagetype current page type
 * @param stdClass $parentcontext Block's parent context
 * @param stdClass $currentcontext Current context of block
 * @return array
 * @throws coding_exception
 */
function tresipuntvideo_page_type_list($pagetype, $parentcontext, $currentcontext) {
    $module_pagetype = array(
        'mod-tresipuntvideo-*'=>get_string('page-mod-tresipuntvideo-x', 'tresipuntvideo'));
    return $module_pagetype;
}

/**
 * Export file tresipuntvideo contents
 *
 * @param $cm
 * @param $baseurl
 * @return array of file content
 * @throws coding_exception
 * @throws dml_exception
 */
function tresipuntvideo_export_contents($cm, $baseurl) {
    global $CFG, $DB;
    $contents = array();
    $context = context_module::instance($cm->id);
    $tresipuntvideo = $DB->get_record('tresipuntvideo', array('id'=>$cm->instance), '*', MUST_EXIST);

    $fs = get_file_storage();
    $files = $fs->get_area_files(
        $context->id, 'mod_tresipuntvideo',
        'content', 0, 'sortorder DESC, id ASC', false);

    foreach ($files as $fileinfo) {
        $file = array();
        $file['type'] = 'file';
        $file['filename']     = $fileinfo->get_filename();
        $file['filepath']     = $fileinfo->get_filepath();
        $file['filesize']     = $fileinfo->get_filesize();
        $file['fileurl']      = file_encode_url(
            "$CFG->wwwroot/" . $baseurl,
            '/'.$context->id.'/mod_tresipuntvideo/content/'.
            $tresipuntvideo->revision.$fileinfo->get_filepath().$fileinfo->get_filename(), true);
        $file['timecreated']  = $fileinfo->get_timecreated();
        $file['timemodified'] = $fileinfo->get_timemodified();
        $file['sortorder']    = $fileinfo->get_sortorder();
        $file['userid']       = $fileinfo->get_userid();
        $file['author']       = $fileinfo->get_author();
        $file['license']      = $fileinfo->get_license();
        $file['mimetype']     = $fileinfo->get_mimetype();
        $file['isexternalfile'] = $fileinfo->is_external_file();
        if ($file['isexternalfile']) {
            $file['repositorytype'] = $fileinfo->get_repository_type();
        }
        $contents[] = $file;
    }

    return $contents;
}

/**
 * Register the ability to handle drag and drop file uploads
 *
 * @return array containing details of the files / types the mod can handle
 * @throws coding_exception
 */
function tresipuntvideo_dndupload_register() {
    return array('files' => array(
                     array(
                         'extension' => '*',
                         'message' => get_string('dnduploadtresipuntvideo', 'mod_tresipuntvideo'))
                 ));
}

/**
 * Handle a file that has been uploaded
 *
 * @param object $uploadinfo details of the file / content that has been uploaded
 * @return int instance id of the newly created mod
 * @throws dml_exception
 */
function tresipuntvideo_dndupload_handle($uploadinfo) {
    // Gather the required info.
    $data = new stdClass();
    $data->course = $uploadinfo->course->id;
    $data->name = $uploadinfo->displayname;
    $data->intro = '';
    $data->introformat = FORMAT_HTML;
    $data->coursemodule = $uploadinfo->coursemodule;
    $data->files = $uploadinfo->draftitemid;

    // Set the display options to the site defaults.
    $config = get_config('tresipuntvideo');
    $data->display = $config->display;
    $data->popupheight = $config->popupheight;
    $data->popupwidth = $config->popupwidth;
    $data->printintro = $config->printintro;
    $data->showsize = (isset($config->showsize)) ? $config->showsize : 0;
    $data->showtype = (isset($config->showtype)) ? $config->showtype : 0;
    $data->showdate = (isset($config->showdate)) ? $config->showdate : 0;
    $data->filterfiles = $config->filterfiles;

    return tresipuntvideo_add_instance($data, null);
}

/**
 * Mark the activity completed (if required) and trigger the course_module_viewed event.
 *
 * @param  stdClass $tresipuntvideo   tresipuntvideo object
 * @param  stdClass $course     course object
 * @param  stdClass $cm         course module object
 * @param  stdClass $context    context object
 * @since Moodle 3.0
 */
function tresipuntvideo_view($tresipuntvideo, $course, $cm, $context) {

    // Trigger course_module_viewed event.
    $params = array(
        'context' => $context,
        'objectid' => $tresipuntvideo->id
    );

    $event = \mod_tresipuntvideo\event\course_module_viewed::create($params);
    $event->add_record_snapshot('course_modules', $cm);
    $event->add_record_snapshot('course', $course);
    $event->add_record_snapshot('tresipuntvideo', $tresipuntvideo);
    $event->trigger();

    // Completion.
    $completion = new completion_info($course);
    $completion->set_module_viewed($cm);
}

/**
 * Check if the module has any update that affects the current user since a given time.
 *
 * @param  cm_info $cm course module data
 * @param  int $from the time to check updates from
 * @param  array $filter  if we need to check only specific updates
 * @return stdClass an object with the different type of areas indicating if they were updated or not
 * @since Moodle 3.2
 */
function tresipuntvideo_check_updates_since(cm_info $cm, $from, $filter = array()) {
    $updates = course_check_module_updates_since($cm, $from, array('content'), $filter);
    return $updates;
}

/**
 * This function receives a calendar event and returns the action associated with it, or null if there is none.
 *
 * This is used by block_myoverview in order to display the event appropriately. If null is returned then the event
 * is not displayed on the block.
 *
 * @param calendar_event $event
 * @param \core_calendar\action_factory $factory
 * @param int $userid
 * @return \core_calendar\local\event\entities\action_interface|null
 * @throws coding_exception
 * @throws moodle_exception
 */
function mod_tresipuntvideo_core_calendar_provide_event_action(calendar_event $event,
                                                      \core_calendar\action_factory $factory, $userid = 0) {

    global $USER;

    if (empty($userid)) {
        $userid = $USER->id;
    }

    $cm = get_fast_modinfo($event->courseid, $userid)->instances['tresipuntvideo'][$event->instance];

    $completion = new \completion_info($cm->get_course());

    $completiondata = $completion->get_data($cm, false, $userid);

    if ($completiondata->completionstate != COMPLETION_INCOMPLETE) {
        return null;
    }

    return $factory->create_instance(
        get_string('view'),
        new \moodle_url('/mod/tresipuntvideo/view.php', ['id' => $cm->id]),
        1,
        true
    );
}


/**
 * Given an array with a file path, it returns the itemid and the filepath for the defined filearea.
 *
 * @param  string $filearea The filearea.
 * @param  array  $args The path (the part after the filearea and before the filename).
 * @return array The itemid and the filepath inside the $args path, for the defined filearea.
 */
function mod_tresipuntvideo_get_path_from_pluginfile(string $filearea, array $args) : array {
    // tresipuntvideo never has an itemid (the number represents the revision but it's not stored in database).
    array_shift($args);

    // Get the filepath.
    if (empty($args)) {
        $filepath = '/';
    } else {
        $filepath = '/' . implode('/', $args) . '/';
    }

    return [
        'itemid' => 0,
        'filepath' => $filepath,
    ];
}
