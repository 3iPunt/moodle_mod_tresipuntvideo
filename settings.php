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
 * Tresipuntvideo module admin settings and defaults
 *
 * @package    mod_tresipuntvideo
 * @copyright  2021 Tresipunt
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

global $ADMIN, $CFG;

if ($ADMIN->fulltree) {
    require_once("$CFG->libdir/resourcelib.php");

    $displayoptions = resourcelib_get_displayoptions(array(RESOURCELIB_DISPLAY_AUTO,
                                                           RESOURCELIB_DISPLAY_EMBED,
                                                           RESOURCELIB_DISPLAY_FRAME,
                                                           RESOURCELIB_DISPLAY_DOWNLOAD,
                                                           RESOURCELIB_DISPLAY_OPEN,
                                                           RESOURCELIB_DISPLAY_NEW,
                                                           RESOURCELIB_DISPLAY_POPUP,
                                                          ));
    $defaultdisplayoptions = array(RESOURCELIB_DISPLAY_AUTO,
                                   RESOURCELIB_DISPLAY_EMBED,
                                   RESOURCELIB_DISPLAY_DOWNLOAD,
                                   RESOURCELIB_DISPLAY_OPEN,
                                   RESOURCELIB_DISPLAY_POPUP,
                                  );

    //--- general settings -----------------------------------------------------------------------------------
    $settings->add(new admin_setting_configtext('tresipuntvideo/framesize',
        get_string('framesize', 'tresipuntvideo'),
        get_string('configframesize', 'tresipuntvideo'), 130, PARAM_INT));
    $settings->add(new admin_setting_configmultiselect('tresipuntvideo/displayoptions',
        get_string('displayoptions', 'tresipuntvideo'),
        get_string('configdisplayoptions', 'tresipuntvideo'),
        $defaultdisplayoptions, $displayoptions));

    //--- modedit defaults -----------------------------------------------------------------------------------
    $settings->add(new admin_setting_heading('tresipuntvideomodeditdefaults',
        get_string('modeditdefaults', 'admin'),
        get_string('condifmodeditdefaults', 'admin')));

    $settings->add(new admin_setting_configcheckbox('tresipuntvideo/printintro',
        get_string('printintro', 'tresipuntvideo'),
        get_string('printintroexplain', 'tresipuntvideo'), 1));
    $settings->add(new admin_setting_configselect('tresipuntvideo/display',
        get_string('displayselect', 'tresipuntvideo'),
        get_string('displayselectexplain', 'tresipuntvideo'), RESOURCELIB_DISPLAY_AUTO,
        $displayoptions));
    $settings->add(new admin_setting_configcheckbox('tresipuntvideo/showsize',
        get_string('showsize', 'tresipuntvideo'),
        get_string('showsize_desc', 'tresipuntvideo'), 0));
    $settings->add(new admin_setting_configcheckbox('tresipuntvideo/showtype',
        get_string('showtype', 'tresipuntvideo'),
        get_string('showtype_desc', 'tresipuntvideo'), 0));
    $settings->add(new admin_setting_configcheckbox('tresipuntvideo/showdate',
        get_string('showdate', 'tresipuntvideo'),
        get_string('showdate_desc', 'tresipuntvideo'), 0));
    $settings->add(new admin_setting_configtext('tresipuntvideo/popupwidth',
        get_string('popupwidth', 'tresipuntvideo'),
        get_string('popupwidthexplain', 'tresipuntvideo'), 620, PARAM_INT, 7));
    $settings->add(new admin_setting_configtext('tresipuntvideo/popupheight',
        get_string('popupheight', 'tresipuntvideo'),
        get_string('popupheightexplain', 'tresipuntvideo'), 450, PARAM_INT, 7));
    $options = array('0' => get_string('none'), '1' =>
        get_string('allfiles'), '2' => get_string('htmlfilesonly'));
    $settings->add(new admin_setting_configselect('tresipuntvideo/filterfiles',
        get_string('filterfiles', 'tresipuntvideo'),
        get_string('filterfilesexplain', 'tresipuntvideo'), 0, $options));
}
