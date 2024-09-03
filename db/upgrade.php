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
    global $CFG;

    if ($oldversion < 2017091202) {
        echo("Pre-release version detected. The plugin should be completely removed first.\n");
        exit(1);
    }

    // TODO remove this block
    // if ($oldversion < 2023091207) {
    //     // Add the tables required for saving custom playlists.
    //     global $DB;
    //     $dbman = $DB->get_manager();

    //     $table = new xmldb_table('clearlesson_playlist');

    //     $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
    //     $table->add_field('name', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null);
    //     $table->add_field('visibility', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
    //     $table->add_field('createdby', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');

    //     $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));
        
    //     if (!$dbman->table_exists($table)) {
    //         $dbman->create_table($table);
    //     }

    //     $table = new xmldb_table('clearlesson_playlist_link');

    //     $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
    //     $table->add_field('playlistid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
    //     $table->add_field('externalref', XMLDB_TYPE_TEXT, '255', null, XMLDB_NOTNULL, null, null);
    //     $table->add_field('videoorder', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        
    //     $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));

    //     if (!$dbman->table_exists($table)) {
    //         $dbman->create_table($table);
    //     }
    // }

    return true;
}
