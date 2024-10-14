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
 * Clear Lesson module upgrade code
 *
 * This file keeps track of upgrades to
 * the resource module
 *
 * Sometimes, changes between versions involve
 * alterations to database structures and other
 * major things that may break installations.
 *
 * The upgrade function in this file will attempt
 * to perform all the necessary actions to upgrade
 * your older installation to the current version.
 *
 * If there's something it cannot do itself, it
 * will tell you what you need to do.
 *
 * The commands in here will all be database-neutral,
 * using the methods of database_manager class
 *
 * Please do not forget to use upgrade_set_timeout()
 * before any action that may take longer time to finish.
 *
 * @package    mod_clearlesson
 * @copyright  2017 Josh Willcock  {@link http://josh.cloud}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

function xmldb_clearlesson_upgrade($oldversion) {
    global $CFG, $DB;

    if ($oldversion < 2017091202) {
        echo("Pre-release version detected. The plugin should be completely removed first.\n");
        exit(1);
    }

    if ($oldverion < 2023091304) {
        require_once("$CFG->libdir/resourcelib.php");
        require_once("$CFG->dirroot/mod/clearlesson/lib.php");

        if ($olddisplayoptions = $DB->get_record('config_plugins', array('plugin' => 'clearlesson', 'name' => 'displayoptions'))) {
            $olddisplaysettings = explode(',', $olddisplayoptions->value);
            $olddisplayoptions = [RESOURCELIB_DISPLAY_AUTO,
                                    RESOURCELIB_DISPLAY_EMBED,
                                    RESOURCELIB_DISPLAY_FRAME,
                                    RESOURCELIB_DISPLAY_OPEN,
                                    RESOURCELIB_DISPLAY_NEW,
                                    RESOURCELIB_DISPLAY_POPUP];
            // Out of the above display options, which ones are not in the old display settings?
            $notused = [];
            foreach ($olddisplaysettings as $oldsetting) {
                if (!in_array($oldsetting, $olddisplayoptions)) {
                    $notused[] = $oldoption;
                }
            }
        } else {
            $notused = [];
        }
        $redundant = [RESOURCELIB_DISPLAY_AUTO, RESOURCELIB_DISPLAY_EMBED, RESOURCELIB_DISPLAY_FRAME];
        // Add the now redunant options to the notused options, if they are not already there.
        $unwantedoptions = array_merge($notused, $redundant);
        // Update the plugin config displayoptions & display (defaultoption) value.
        $newdisplayoptions = [RESOURCELIB_DISPLAY_OPEN,
                                RESOURCELIB_DISPLAY_NEW,
                                RESOURCELIB_DISPLAY_POPUP,
                                CLEARLESSON_DISPLAY_MODAL];
        // Remove any unwanted options from the new display options. What remains will be the new display settings for the plugin.
        $newdisplaysettings = [];
        foreach ($newdisplayoptions as $newoption) {
            if (!in_array($newoption, $unwantedoptions)) {
                $newdisplaysettings[] = $newoption;
            }
        }
        $displayoptionsstring = implode(',', $newdisplaysettings);

        // Insert or update the new display settings for the plugin.
        if ($record = $DB->get_record('config_plugins', array('plugin' => 'clearlesson', 'name' => 'displayoptions'))) {
            if ($record->value != $displayoptionsstring) {
                $record->value = $displayoptionsstring;
                $DB->update_record('config_plugins', $record);
            }
        } else {
            $record = new stdClass();
            $record->plugin = 'clearlesson';
            $record->name = 'displayoptions';
            $record->value = $displayoptionsstring;
            $DB->insert_record('config_plugins', $record);
        }

        // Determine the new default display config setting for the plugin.
        $defaultdisplay = RESOURCELIB_DISPLAY_OPEN;
        foreach ($newdisplaysettings as $newdisplaysetting) {
            if (!in_array($newdisplaysetting, $unwantedoptions)) {
                $defaultdisplay = $newdisplaysetting;
                break;
            }
        }

        // Insert or update the default display setting for the plugin if required.
        if ($result = $DB->get_record('config_plugins', array('plugin' => 'clearlesson', 'name' => 'display'))) {
            if ($result->value != $defaultdisplay) {
                $result->value = $defaultdisplay;
                $DB->update_record('config_plugins', $result);
            }
        } else {
            $result = new stdClass();
            $result->plugin = 'clearlesson';
            $result->name = 'display';
            $result->value = $defaultdisplay;
            $DB->insert_record('config_plugins', $result);
        }

        // By this stage the plugin config has already been updated but we still need to update the instances.
        if ($defaultdisplay == RESOURCELIB_DISPLAY_POPUP) {
            // Never update a clearlesson instance diplay setting to popup.
            // In doing this we greatly simplify the code required in this file because
            // no longer need to worry about the required displaysettings JSON column.
            // There is a low chance of this code getting used, but it is here just in case.
            $defaultdisplay = CLEARLESSON_DISPLAY_MODAL;
        }

        // For each clearlesson instance,
        // if the display option is in the unwanted options,
        // set it to the default display option.
        if ($instances = $DB->get_records('clearlesson')) {
            foreach ($instances as $instance) {
                // Update any disabled display types to the default display type.
                if ($instance->display != $defaultdisplay && in_array($instance->display, $unwantedoptions)) {
                    $instance->display = $defaultdisplay;
                    $DB->update_record('clearlesson', $instance);
                }
            }
        }

        // Add the new completionwatchedall field to the clearlesson table.
        // This field will be used to set if the 'watchedall' completion rule is enabled for that clearlesson.
        $dbman = $DB->get_manager();
        $table = new xmldb_table('clearlesson');
        $field = new xmldb_field('completionwatchedall', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL , null, '0');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        $table = new xmldb_table('clearlesson_track');
        // Define field watchedall to be added to clearlesson_track.
        // This field will be used to record if a user has watched all the videos in a clearlesson.
        $field = new xmldb_field('watchedall', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL , null, '0');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        // The reset date field will be used to mark when the clearlesson completion was reset for the user.
        // Track records with a reset date greater than 0 will be ignored 
        // when checking if a user has watched all the videos in a clearlesson.
        $field = new xmldb_field('resetdate', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL , null, '0');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        upgrade_plugin_savepoint(true, 2023091304, 'mod', 'clearlesson');
    }
    return true;
}
