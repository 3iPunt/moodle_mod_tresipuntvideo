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
 * Defines backup_tresipuntvideo_activity_task class
 *
 * @package     mod_tresipuntvideo
 * @copyright   2021 Tresipunt
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

global $CFG;

require_once($CFG->dirroot . '/mod/tresipuntvideo/backup/moodle2/backup_tresipuntvideo_stepslib.php');

/**
 * Provides the steps to perform one complete backup of the tresipuntvideo instance
 */
class backup_tresipuntvideo_activity_task extends backup_activity_task {

    /**
     * @param bool $tresipuntvideooldexists True if there are records in the tresipuntvideo_old table.
     */
    protected static $tresipuntvideooldexists = null;

    /**
     * No specific settings for this activity
     */
    protected function define_my_settings() {
    }

    /**
     * Defines a backup step to store the instance data in the tresipuntvideo.xml file
     */
    protected function define_my_steps() {
        $this->add_step(new backup_tresipuntvideo_activity_structure_step(
            'tresipuntvideo_structure', 'tresipuntvideo.xml'));
    }

    /**
     * Encodes URLs to the index.php and view.php scripts
     *
     * @param string $content some HTML text that eventually contains URLs to the activity instance scripts
     * @return string the content with the URLs encoded
     */
    static public function encode_content_links($content) {
        global $CFG, $DB;

        $base = preg_quote($CFG->wwwroot,"/");

        // Link to the list of tresipuntvideos.
        $search="/(".$base."\/mod\/tresipuntvideo\/index.php\?id\=)([0-9]+)/";
        $content= preg_replace($search, '$@TRESIPUNTVIDEOINDEX*$2@$', $content);

        // Link to tresipuntvideo view by moduleid.
        $search = "/(".$base."\/mod\/tresipuntvideo\/view.php\?id\=)([0-9]+)/";
        // Link to tresipuntvideo view by recordid
        $search2 = "/(".$base."\/mod\/tresipuntvideo\/view.php\?r\=)([0-9]+)/";

        // Check whether there are contents in the tresipuntvideo old table.
        if (static::$tresipuntvideooldexists === null) {
            static::$tresipuntvideooldexists = $DB->record_exists('tresipuntvideo_old', array());
        }

        // If there are links to items in the tresipuntvideo_old table, rewrite them to be links to the correct URL
        // for their new module.
        if (static::$tresipuntvideooldexists) {
            // Match all of the tresipuntvideos.
            $result = preg_match_all($search, $content, $matches, PREG_PATTERN_ORDER);

            // Course module ID tresipuntvideo links.
            if ($result) {
                list($insql, $params) = $DB->get_in_or_equal($matches[2]);
                $oldrecs = $DB->get_records_select(
                    'tresipuntvideo_old', "cmid $insql", $params, '', 'cmid, newmodule');

                for ($i = 0; $i < count($matches[0]); $i++) {
                    $cmid = $matches[2][$i];
                    if (isset($oldrecs[$cmid])) {
                        // tresipuntvideo_old item, rewrite it
                        $replace = '$@' . strtoupper($oldrecs[$cmid]->newmodule) . 'VIEWBYID*' . $cmid . '@$';
                    } else {
                        // Not in the tresipuntvideo old table, don't rewrite
                        $replace = '$@TRESIPUNTVIDEOVIEWBYID*'.$cmid.'@$';
                    }
                    $content = str_replace($matches[0][$i], $replace, $content);
                }
            }

            $matches = null;
            $result = preg_match_all($search2, $content, $matches, PREG_PATTERN_ORDER);

            // No tresipuntvideo links.
            if (!$result) {
                return $content;
            }
            // tresipuntvideo ID links.
            list($insql, $params) = $DB->get_in_or_equal($matches[2]);
            $oldrecs = $DB->get_records_select(
                'tresipuntvideo_old', "oldid $insql", $params, '', 'oldid, cmid, newmodule');

            for ($i = 0; $i < count($matches[0]); $i++) {
                $recordid = $matches[2][$i];
                if (isset($oldrecs[$recordid])) {
                    // tresipuntvideo_old item, rewrite it
                    $replace = '$@' .
                        strtoupper($oldrecs[$recordid]->newmodule) . 'VIEWBYID*' . $oldrecs[$recordid]->cmid . '@$';
                    $content = str_replace($matches[0][$i], $replace, $content);
                }
            }
        } else {
            $content = preg_replace($search, '$@TRESIPUNTVIDEOVIEWBYID*$2@$', $content);
        }
        return $content;
    }
}
