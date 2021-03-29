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
 * tresipuntvideo external API
 *
 * @package    mod_tresipuntvideo
 * @copyright  2021 Tresipunt
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

global $CFG;

require_once("$CFG->libdir/externallib.php");

/**
 * tresipuntvideo external functions
 *
 * @package    mod_tresipuntvideo
 * @copyright  2021 Tresipunt
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_tresipuntvideo_external extends external_api {

    /**
     * Returns description of method parameters
     *
     * @return external_function_parameters
     */
    public static function view_tresipuntvideo_parameters() {
        return new external_function_parameters(
            array(
                'tresipuntvideoid' => new external_value(PARAM_INT, 'tresipuntvideo instance id')
            )
        );
    }

    /**
     * Simulate the tresipuntvideo/view.php web interface page: trigger events, completion, etc...
     *
     * @param int $tresipuntvideoid the tresipuntvideo instance id
     * @return array of warnings and status result
     * @since Moodle 3.0
     * @throws moodle_exception
     */
    public static function view_tresipuntvideo($tresipuntvideoid) {
        global $DB, $CFG;
        require_once($CFG->dirroot . "/mod/tresipuntvideo/lib.php");

        $params = self::validate_parameters(self::view_tresipuntvideo_parameters(),
                                            array(
                                                'tresipuntvideoid' => $tresipuntvideoid
                                            ));
        $warnings = array();

        // Request and permission validation.
        $tresipuntvideo = $DB->get_record('tresipuntvideo',
            array('id' => $params['tresipuntvideoid']), '*', MUST_EXIST);
        list($course, $cm) = get_course_and_cm_from_instance($tresipuntvideo, 'tresipuntvideo');

        $context = context_module::instance($cm->id);
        self::validate_context($context);

        require_capability('mod/tresipuntvideo:view', $context);

        // Call the tresipuntvideo/lib API.
        tresipuntvideo_view($tresipuntvideo, $course, $cm, $context);

        $result = array();
        $result['status'] = true;
        $result['warnings'] = $warnings;
        return $result;
    }

    /**
     * Returns description of method result value
     *
     * @return external_description
     * @since Moodle 3.0
     */
    public static function view_tresipuntvideo_returns() {
        return new external_single_structure(
            array(
                'status' => new external_value(PARAM_BOOL, 'status: true if success'),
                'warnings' => new external_warnings()
            )
        );
    }

    /**
     * Describes the parameters for get_tresipuntvideos_by_courses.
     *
     * @return external_function_parameters
     * @since Moodle 3.3
     */
    public static function get_tresipuntvideos_by_courses_parameters() {
        return new external_function_parameters (
            array(
                'courseids' => new external_multiple_structure(
                    new external_value(
                        PARAM_INT, 'Course id'), 'Array of course ids', VALUE_DEFAULT, array()
                ),
            )
        );
    }

    /**
     * Returns a list of files in a provided list of courses.
     * If no list is provided all files that the user can view will be returned.
     *
     * @param array $courseids
     * @return array
     * @throws coding_exception
     * @throws invalid_parameter_exception
     * @throws coding_exception
     */
    public static function get_tresipuntvideos_by_courses($courseids = array()): array {

        $warnings = array();
        $returnedtresipuntvideos = array();

        $params = array(
            'courseids' => $courseids,
        );
        $params = self::validate_parameters(self::get_tresipuntvideos_by_courses_parameters(), $params);

        $mycourses = array();
        if (empty($params['courseids'])) {
            $mycourses = enrol_get_my_courses();
            $params['courseids'] = array_keys($mycourses);
        }

        // Ensure there are courseids to loop through.
        if (!empty($params['courseids'])) {

            list($courses, $warnings) = external_util::validate_courses($params['courseids'], $mycourses);

            // Get the tresipuntvideos in this course, this function checks users visibility permissions.
            // We can avoid then additional validate_context calls.
            $tresipuntvideos = get_all_instances_in_courses("tresipuntvideo", $courses);
            foreach ($tresipuntvideos as $tresipuntvideo) {
                $context = context_module::instance($tresipuntvideo->coursemodule);
                // Entry to return.
                $tresipuntvideo->name = external_format_string($tresipuntvideo->name, $context->id);
                $options = array('noclean' => true);
                list($tresipuntvideo->intro, $tresipuntvideo->introformat) =
                    external_format_text(
                        $tresipuntvideo->intro,
                        $tresipuntvideo->introformat,
                        $context->id,
                        'mod_tresipuntvideo',
                        'intro', null,
                        $options);
                $tresipuntvideo->introfiles = external_util::get_area_files(
                    $context->id, 'mod_tresipuntvideo', 'intro', false, false);
                $tresipuntvideo->contentfiles = external_util::get_area_files(
                    $context->id, 'mod_tresipuntvideo', 'content');

                $returnedtresipuntvideos[] = $tresipuntvideo;
            }
        }

        return array(
            'tresipuntvideos' => $returnedtresipuntvideos,
            'warnings' => $warnings
        );
    }

    /**
     * Describes the get_tresipuntvideos_by_courses return value.
     *
     * @return external_single_structure
     * @since Moodle 3.3
     */
    public static function get_tresipuntvideos_by_courses_returns() {
        return new external_single_structure(
            array(
                'tresipuntvideos' => new external_multiple_structure(
                    new external_single_structure(
                        array(
                            'id' => new external_value(PARAM_INT, 'Module id'),
                            'coursemodule' => new external_value(PARAM_INT, 'Course module id'),
                            'course' => new external_value(PARAM_INT, 'Course id'),
                            'name' => new external_value(PARAM_RAW, 'Page name'),
                            'intro' => new external_value(PARAM_RAW, 'Summary'),
                            'introformat' => new external_format_value('intro', 'Summary format'),
                            'introfiles' => new external_files('Files in the introduction text'),
                            'contentfiles' => new external_files('Files in the content'),
                            'tobemigrated' => new external_value(PARAM_INT,
                                'Whether this tresipuntvideo was migrated'),
                            'legacyfiles' => new external_value(PARAM_INT, 'Legacy files flag'),
                            'legacyfileslast' => new external_value(PARAM_INT,
                                'Legacy files last control flag'),
                            'display' => new external_value(PARAM_INT,
                                'How to display the tresipuntvideo'),
                            'displayoptions' => new external_value(PARAM_RAW,
                                'Display options (width, height)'),
                            'filterfiles' => new external_value(PARAM_INT,
                                'If filters should be applied to the tresipuntvideo content'),
                            'revision' => new external_value(PARAM_INT,
                                'Incremented when after each file changes, to avoid cache'),
                            'timemodified' => new external_value(PARAM_INT,
                                'Last time the tresipuntvideo was modified'),
                            'section' => new external_value(PARAM_INT, 'Course section id'),
                            'visible' => new external_value(PARAM_INT, 'Module visibility'),
                            'groupmode' => new external_value(PARAM_INT, 'Group mode'),
                            'groupingid' => new external_value(PARAM_INT, 'Grouping id'),
                        )
                    )
                ),
                'warnings' => new external_warnings(),
            )
        );
    }
}
