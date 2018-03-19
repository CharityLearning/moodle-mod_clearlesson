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

        // The Clear Lesson module stores no user info.

        // Define each element separated.
        $clearlesson = new backup_nested_element('clearlesson', array('id'), array(
            'name', 'intro', 'introformat', 'externalref',
            'display', 'displayoptions', 'parameters', 'timemodified', 'type'));

        // Build the tree.
        // Nothing here for Clear Lessons.

        // Define sources.
        $clearlesson->set_source_table('clearlesson', array('id' => backup::VAR_ACTIVITYID));

        // Define id annotations.
        // Module has no id annotations.

        // Define file annotations
        $clearlesson->annotate_files('mod_clearlesson', 'intro', null); // This file area hasn't itemid.

        // Return the root element (ClearLesson), wrapped into standard activity structure.
        return $this->prepare_activity_structure($clearlesson);

    }
}
