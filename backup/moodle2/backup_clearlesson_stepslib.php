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
 * Define all the backup steps that will be used by the backup_clearlesson_activity_task
 *
 * @package    mod_clearlesson
 * @copyright  2017 Josh Willcock
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

 /**
  * Define the complete Clear Lesson structure for backup, with file and id annotations.
  */
class backup_clearlesson_activity_structure_step extends backup_activity_structure_step {

    protected function define_structure() {

        // Check if user records are required.
        $userinfo = $this->get_setting_value('userinfo');

        // Define each element separated.
        $clearlesson = new backup_nested_element('clearlesson', array('id'), array(
            'course', 'name', 'intro', 'introformat', 'externalref',
            'display', 'displayoptions', 'parameters', 'timemodified', 'type'));

        // Build the tree.
        if ($userinfo) {
            $clearlessontrack = new backup_nested_element('clearlesson_track', array('id'), array(
            'userid', 'clearlessonid', 'timemodified'));
            $clearlesson->add_child($clearlessontrack);
        }
        // Define sources.
        $clearlesson->set_source_table('clearlesson', array('id' => backup::VAR_ACTIVITYID));
        if ($userinfo) {
            $clearlessontrack->set_source_table('clearlesson_track', array('id' => backup::VAR_PARENTID));
        }
        // Define id annotations.
        // Module has no id annotations.

        // Define file annotations
        $clearlesson->annotate_files('mod_clearlesson', 'intro', null); // This file area hasn't itemid.
        if ($userinfo) {
            $clearlessontrack->annotate_ids('user', 'userid');
        }
        // Return the root element (ClearLesson), wrapped into standard activity structure.
        return $this->prepare_activity_structure($clearlesson);

    }
}
