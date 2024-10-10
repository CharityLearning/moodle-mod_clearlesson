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
 * Email signup notification event observers.
 *
 * @package    mod_clearlesson
 * @author     Dan Watkins
 * @copyright  2024 Dan Watkins
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * mod_clearlesson event observers.
 *
 * @package    mod_clearlesson
 * @author     Dan Watkins
 * @copyright  2024 Dan Watkins
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_clearlesson_observer {
    /**
     * Event processor - completion reset
     *
     * @param \local_recompletion\event\completion_reset $event
     * @return bool
     */
    public static function completion_reset(\local_recompletion\event\completion_reset $event) {
        global $USER, $DB;
        // $lastcron = get_config('tool_task', 'lastcronstart');
        // $cronoverdue = ($lastcron < time() - 3600);
        // // Check if this is the first time the user has logged in or if the scheduled task has not ran in 60 minutes.
        // if ($USER->lastaccess == 0 OR $cronoverdue) {
        //     $singleuser = new \mod_clearlesson\user_action($USER);
        //     $singleuser->allocate();
        // }
        $event = json_encode($event);
        $testrecord = new stdClass();
        $testrecord->data = $event;
        $DB->insert_record('dan_test', $testrecord);
        return true;
    }

//     /**
//      * Event processor - user_updated
//      * This user's profile has been updated. 
//      * Check allocation rules and allocate to any relevant pathways
//      *
//      * @param \core\event\user_updated $event
//      * @return bool
//      */
//     public static function user_updated(\core\event\user_updated $event) {
//         $user = (object) ['id' => $event->objectid];
//         $singleuser = new \mod_clearlesson\user_action($user);
//         $singleuser->allocate();
//         return true;
//     }

//     /**
//      * Event processor - remove deleted group item
//      *
//      * @param \core\event\remove_deleted_group_item $event
//      * @return bool
//      */
//     public static function remove_deleted_group_item($event) {
//         global $USER, $DB;
//         switch($event->objecttable) {
//             case 'course':
//                 $type = 'course';
//                 break;
//             case 'course_sections':
//                 $type = 'section';
//                 break;
//             case 'course_modules':
//                 $type = 'module';
//                 break;
//             }
//             if (isset($type)) {
//                 $DB->delete_records('mod_clearlesson_group_items', ['itemid' => $event->objectid, 'itemtype' => $type]);
//             }

//         return true;
//     }

//     /**
//      * Event processor - Check Pathway Completion Progress.
//      *
//      * @param Multiple events $event
//      * @return bool
//      */
//     public static function user_based_pathway_completion_changes($event) {
//         global $USER, $DB, $CFG;
//         require_once($CFG->dirroot . '/admin/tool/pathways/lib.php');

//         $completionhandler = new \mod_clearlesson\completion_handler();
//         $rs = $completionhandler->fetch_records(" AND tpc.userid = {$event->relateduserid} ");
//         // Check if recordset is empty. Uses Recordset incase results are large.
//         if (!$rs->valid()) {
//             $rs->close();
//             return;
//         }
//         foreach ($rs as $record) {
//             $actions = $completionhandler->check_for_action($record);
//             if (!empty($actions)) {
//                 $completionhandler->execute_actions($record, $actions);
//             }
//         }
//         $rs->close();
//         return true;
//     }   
//       /**
//      * Event processor - Check Pathway Completion Progress.
//      *
//      * @param Multiple events $event
//      * @return bool
//      */
//     public static function pathway_based_pathway_completion_changes($event) {
//         global $USER, $DB, $CFG;
//         require_once($CFG->dirroot . '/admin/tool/pathways/lib.php');             
//         if ($event->objecttable == 'mod_clearlesson') {
//             $pathways = $event->objectid;
//         }else {
//             // Check if course is in a pathway 
//             $sql = "SELECT tpgi.pathwayid FROM mdl_mod_clearlesson_group_items tpgi 
//                         LEFT JOIN mdl_course_sections cs ON (tpgi.itemtype='section' AND tpgi.itemid = cs.id)
//                         LEFT JOIN mdl_course_modules cm ON (tpgi.itemtype='module' AND tpgi.itemid = cm.id)
//                         WHERE (itemtype = 'section' AND cs.course = ?)
//                             OR (itemtype = 'module' AND cm.course = ?)
//                             OR (itemtype = 'course' AND itemid = ?)
//                         GROUP BY tpgi.pathwayid";
//             $params = [$event->courseid, $event->courseid, $event->courseid];
//             $pathways = $DB->get_records_sql($sql, $params);
//             if ($pathways == false) {
//                 return;
//             }
//             $pathways = implode(', ',array_keys($pathways));
//         }
//         $task = new \mod_clearlesson\task\pathway_based_completion_task();
//         $task->set_custom_data(['pathways' => $pathways]);
//         \core\task\manager::reschedule_or_queue_adhoc_task($task);
//     }   
}