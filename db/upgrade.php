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

    if ($oldverion < 2023091225) {
        require_once("$CFG->libdir/resourcelib.php");
        $dbman = $DB->get_manager();
        $table = new xmldb_table('clearlesson');
        $field = new xmldb_field('completionwatchedall', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL , null, '0');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        $table = new xmldb_table('clearlesson_track');
        // Define field watchedall to be added to clearlesson_track.
        $field = new xmldb_field('watchedall', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL , null, '0');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Update the plugin config displayoptions & display (defaultoption) value.
        // The only display options available are RESOURCELIB_DISPLAY_OPEN and RESOURCELIB_DISPLAY_POPUP
        $displayoptionsstring = RESOURCELIB_DISPLAY_OPEN . ',' . RESOURCELIB_DISPLAY_POPUP;
        if ($record = $DB->get_record('config_plugins', array('plugin' => 'clearlesson', 'name' => 'displayoptions'))) {
            $record->value = $displayoptionsstring;
            $DB->update_record('config_plugins', $record);
        } else {
            $record = new stdClass();
            $record->plugin = 'clearlesson';
            $record->name = 'displayoptions';
            $record->value = $displayoptionsstring;
            $DB->insert_record('config_plugins', $record);
        }

        $defaultdisplayoption = RESOURCELIB_DISPLAY_OPEN;
        if ($record = $DB->get_record('config_plugins', array('plugin' => 'clearlesson', 'name' => 'display'))) {
            $record->value = $defaultdisplayoption;
            $DB->update_record('config_plugins', $record);
        } else {
            $record = new stdClass();
            $record->plugin = 'clearlesson';
            $record->name = 'display';
            $record->value = $defaultdisplayoption;
            $DB->insert_record('config_plugins', $record);
        }
        // Also, for each clearlesson instance, set the display option to
        // RESOURCELIB_DISPLAY_OPEN unless it is set to RESOURCELIB_DISPLAY_POPUP already
        if ($instances = $DB->get_records('clearlesson')) {
            foreach ($instances as $instance) {
                if ($instance->display == RESOURCELIB_DISPLAY_POPUP) {
                    continue;
                }
                $instance->display = RESOURCELIB_DISPLAY_OPEN;
                $DB->update_record('clearlesson', $instance);
            }
        }

        upgrade_plugin_savepoint(true, 2023091225, 'mod', 'clearlesson');
    }

    return true;
}
