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
 * @package    mod_clearlesson
 * @subpackage backup-moodle2
 * @copyright 2017 Josh WIllcock {@link http://josh.cloud}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();
/**
 * Define all the restore steps that will be used by the restore_clearlesson_activity_task
 */

/**
 * Structure step to restore one clearlesson activity
 */
class restore_clearlesson_activity_structure_step extends restore_activity_structure_step {

    protected function define_structure() {
        $userinfo = $this->get_setting_value('userinfo');
        $paths = array();
        $paths[] = new restore_path_element('clearlesson', '/activity/clearlesson');
        if ($userinfo) {
            $paths[] = new restore_path_element('clearlesson_track', '/activity/clearlesson_track');
        }

        // Return the paths wrapped into standard activity structure.
        return $this->prepare_activity_structure($paths);
    }

    protected function process_clearlesson($data) {
        global $DB;

        $data = (object)$data;
        $data->course = $this->get_courseid();

        // Insert the clearlesson record.
        $newitemid = $DB->insert_record('clearlesson', $data);
        // Immediately after inserting "activity" record, call this.
        $this->apply_activity_instance($newitemid);
    }
    protected function process_clearlesson_track($data) {
        global $DB;

        $data = (object)$data;
        $oldid = $data->id;

        $data->clearlessonid = $this->get_new_parentid('clearlesson');
        $data->userid = $this->get_mappingid('user', $data->userid);

        // Insert the entry record.
        $newitemid = $DB->insert_record('clearlesson_track', $data);
        $this->set_mapping('clearlesson_track', $oldid, $newitemid, true); // Childs and files by itemname.
    }
    protected function after_execute() {
        // Add clearlesson related files, no need to match by itemname (just internally handled context).
        $this->add_related_files('mod_clearlesson', 'intro', null);
    }
}
