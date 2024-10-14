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
        global $DB;
        $clearwatched = get_config('mod_clearlesson', 'clearwatched');
        // False is returned when the config is not set, so use the default setting of 'Yes';
        if ($clearwatched === false || $clearwatched) {
            // Set the reset date for the last track record.
            // Any viewed or watchedstatus data before that date will be ignored.
            $resetdate = time();
            $alltrackssql = "SELECT cl.* FROM {course_modules} cm
                        JOIN {modules} m ON m.id = cm.module AND m.name = 'clearlesson'
                        JOIN {clearlesson_track} cl ON cl.clearlessonid = cm.instance
                        WHERE cm.course = ?
                            AND cl.userid = ?
                            AND cl.resetdate = 0";   
            // Get all clearlesson tracks without a reset date for the user in the course.
            if ($cltracks = $DB->get_records_sql($alltrackssql, [$event->objectid, $event->userid])) {
                foreach ($cltracks as $cltrack) {
                    // Mark the track record as having been reset on this date.
                    $cltrack->resetdate = $resetdate;
                    $DB->update_record('clearlesson_track', $cltrack, true);
                }
            }
        }
        return true;
    } 
}