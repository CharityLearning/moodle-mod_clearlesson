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
 * clearlesson module admin settings and defaults
 *
 * @package    mod_clearlesson
 * @copyright  2009 Petr Skoda  {@link http://skodak.org}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

if ($ADMIN->fulltree) {
    require_once("$CFG->libdir/resourcelib.php");

    $displayoptions = resourcelib_get_displayoptions(array(RESOURCELIB_DISPLAY_AUTO,
                                                           RESOURCELIB_DISPLAY_EMBED,
                                                           RESOURCELIB_DISPLAY_FRAME,
                                                           RESOURCELIB_DISPLAY_OPEN,
                                                           RESOURCELIB_DISPLAY_NEW,
                                                           RESOURCELIB_DISPLAY_POPUP,
                                                          ));
    $defaultdisplayoptions = array(RESOURCELIB_DISPLAY_AUTO,
                                   RESOURCELIB_DISPLAY_EMBED,
                                   RESOURCELIB_DISPLAY_OPEN,
                                   RESOURCELIB_DISPLAY_POPUP,
                                  );

    // General settings.
    $settings->add(new admin_setting_configtext('clearlesson/clearlessonurl',
        get_string('clearlessonurl', 'clearlesson'),
        get_string('clearlessonurldesc', 'clearlesson'),
        '', PARAM_URL));
    $settings->add(new admin_setting_configtext('clearlesson/framesize',
        get_string('framesize', 'clearlesson'),
        get_string('configframesize', 'clearlesson'),
        130,
        PARAM_INT));
    $settings->add(new admin_setting_configpasswordunmask('clearlesson/apikey', get_string('apikey', 'clearlesson'),
        get_string('apikeydesc', 'clearlesson'), ''));
    $settings->add(new admin_setting_configpasswordunmask('clearlesson/secretkey', get_string('secretkey', 'clearlesson'),
        get_string('secretkeydesc', 'clearlesson'), ''));
    $settings->add(new admin_setting_configcheckbox('clearlesson/rolesinparams',
        get_string('rolesinparams', 'clearlesson'), get_string('configrolesinparams', 'clearlesson'), false));
    $settings->add(new admin_setting_configmultiselect('clearlesson/displayoptions',
        get_string('displayoptions', 'clearlesson'), get_string('configdisplayoptions', 'clearlesson'),
        $defaultdisplayoptions, $displayoptions));

    // Modedit defaults.
    $settings->add(new admin_setting_heading('clearlessonmodeditdefaults',
    get_string('modeditdefaults', 'admin'),
    get_string('condifmodeditdefaults', 'admin')));
    $settings->add(new admin_setting_configcheckbox('clearlesson/printintro',
        get_string('printintro', 'clearlesson'), get_string('printintroexplain', 'clearlesson'), 1));
    $settings->add(new admin_setting_configselect('clearlesson/display',
        get_string('displayselect', 'clearlesson'),
        get_string('displayselectexplain', 'clearlesson'),
        RESOURCELIB_DISPLAY_AUTO,
        $displayoptions));
    $settings->add(new admin_setting_configtext('clearlesson/popupwidth',
        get_string('popupwidth', 'clearlesson'), get_string('popupwidthexplain', 'clearlesson'), 620, PARAM_INT, 7));
    $settings->add(new admin_setting_configtext('clearlesson/popupheight',
        get_string('popupheight', 'clearlesson'), get_string('popupheightexplain', 'clearlesson'), 450, PARAM_INT, 7));
}
