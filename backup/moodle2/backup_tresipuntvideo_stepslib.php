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
 * Define all the backup steps that will be used by the backup_tresipuntvideo_activity_task
 *
 * @package     mod_tresipuntvideo
 * @copyright   2021 Tresipunt
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

/**
 * Define the complete tresipuntvideo structure for backup, with file and id annotations
 */
class backup_tresipuntvideo_activity_structure_step extends backup_activity_structure_step {

    protected function define_structure() {

        // To know if we are including userinfo
        $userinfo = $this->get_setting_value('userinfo');

        // Define each element separated
        $tresipuntvideo = new backup_nested_element('tresipuntvideo', array('id'), array(
            'name', 'intro', 'introformat', 'tobemigrated',
            'legacyfiles', 'legacyfileslast', 'display',
            'displayoptions', 'filterfiles', 'revision', 'timemodified'));

        // Build the tree
        // (love this)

        // Define sources
        $tresipuntvideo->set_source_table('tresipuntvideo', array('id' => backup::VAR_ACTIVITYID));

        // Define id annotations
        // (none)

        // Define file annotations
        $tresipuntvideo->annotate_files('mod_tresipuntvideo', 'intro', null);
        $tresipuntvideo->annotate_files('mod_tresipuntvideo', 'content', null);

        // Return the root element (tresipuntvideo), wrapped into standard activity structure
        return $this->prepare_activity_structure($tresipuntvideo);
    }
}
