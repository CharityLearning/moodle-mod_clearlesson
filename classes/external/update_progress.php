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

namespace mod_clearlesson\external;
use mod_clearlesson;

use core_external\external_function_parameters;
use core_external\external_multiple_structure;
use core_external\external_single_structure;
use core_external\external_value;
use core_external\external_warnings;
use completion_info;
use cm_info;

defined('MOODLE_INTERNAL') || die;
require_once($CFG->dirroot . '/mod/clearlesson/lib.php');
require_once("{$CFG->libdir}/completionlib.php");

/**
* Trigger the course module viewed event and update the module completion status.
 *
 * @package    mod_clearlesson
 * @category   external
 * @copyright  2024 Dan Watkins
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @since      Moodle 3.1
 */
class update_progress extends \core_external\external_api {
    
        /**
        * Returns description of method parameters
        *
        * @return external_function_parameters
        * @since Moodle 3.0
        */
        public static function execute_parameters(): external_function_parameters {
            return new external_function_parameters(
                array(
                    'externalref' => new external_value(PARAM_TEXT, 'Externalref of video'),
                    'duration' => new external_value(PARAM_INT, 'Current video progress in seconds'),
                    'status' => new external_value(PARAM_TEXT, 'View status of video for this session,
                                                                "unwatched", "inprogress", "watched"'),
                    'courseid' => new external_value(PARAM_INT, 'Course id'),
                    'cmid' => new external_value(PARAM_INT, 'Course module id'),
                    'resourceref' => new external_value(PARAM_TEXT, 'Externalref of resource'),
                    'type' => new external_value(PARAM_TEXT, 'Type of resource'),
                    'pagetype' => new external_value(PARAM_TEXT, 'Type of page, "course" or "activity"'),
                    'watchedall' => new external_value(PARAM_BOOL, 'Whether all videos in the resource have been watched')
                )
            );
        }
    
        /**
        * Update the viewed progress of a video in the Clear Lesson Platform.
        *
        * @param string $externalref the external reference of the video.
        * It's presence indicates progress should be updated in the Clear Lesson Platform.
        * @param int $duration the time watched in seconds
        * @param string $status the view status of the video for this session
        * @param int $courseid the course id
        * @param int $cmid the course module id
        * @param string $resourceref the external reference of the parent resource
        * @param string $type the type of resource
        * @param string $pagetype the type of page 'course' or 'activity'
        * @param bool $watchedall whether all videos in the resource have been watched
        *
        * @return array of warnings and status result
        * @since Moodle 3.0
        * @throws moodle_exception
        */
        public static function execute(string $externalref,
                                        int $duration,
                                        string $status,
                                        int $courseid,
                                        int $cmid,
                                        string $resourceref,
                                        string $type,
                                        string $pagetype,
                                        bool $watchedall): array {

            global $DB, $USER, $OUTPUT, $PAGE, $CFG;
            require_login();
            // Set module context.
            $PAGE->set_context(\context_module::instance($cmid));

            $params = self::validate_parameters(
                        self::execute_parameters(),
                            ['externalref' => $externalref,
                            'duration' => $duration,
                            'status' => $status,
                            'courseid' => $courseid,
                            'cmid' => $cmid,
                            'resourceref' => $resourceref,
                            'type' => $type,
                            'pagetype' => $pagetype,
                            'watchedall' => $watchedall]);
            
            if ($comprec = $DB->get_record_sql('SELECT * FROM {course_modules_completion} cmc
                                            WHERE cmc.coursemoduleid = ? AND cmc.userid = ?
                                                ORDER BY cmc.id DESC LIMIT 1', [$cmid, $USER->id])) {

                if ($comprec->completionstate > 0) {
                    return ['success' => true,
                            'activitymodulehtml' => '']; // Module already completed, do nothing.
                }
            }

            $activitysql = "SELECT cl.id,
                                    MAX(cm.completion) as completion,
                                    MAX(cl.completionwatchedall) as completionwatchedall,
                                    MAX(ct.resetdate) as resetdate
                                FROM {course_modules} cm
                                JOIN {clearlesson} cl ON cm.instance = cl.id
                                LEFT JOIN {clearlesson_track} ct ON cl.id = ct.clearlessonid
                                WHERE cm.id = ?
                                GROUP BY cl.id";

            if ($activity = $DB->get_record_sql($activitysql, [$cmid])) {
                $resetdate = $activity->resetdate;
                if (!$activity->completion // If completion is not enabled,
                    || !$activity->completionwatchedall // or if the completionwatchedall rule is not enabled,
                    || $watchedall) { // or if we already know all the videos in the resource have been watched,
                    // then we don't need to check if all videos in the resource have been watched.
                        $resourceref = '';
                }
            }

            // If a '$resourceref' is set, then we need to check if all videos in the resource have been watched.
            // If they have, then we can update the completion status of the module.
            if (!$watchedall && $externalref && $response = \mod_clearlesson\call::update_progress($externalref,
                                                                                    $duration,
                                                                                    $status,
                                                                                    $resourceref,
                                                                                    $type,
                                                                                    $resetdate)) {
                // Update completion of activity module.
                if ($response['result']['completionstatus']) {
                    // All videos in the resource have now been watched, check & mark the module as complete if so.
                    $updatedhtml = self::mark_module_complete_and_get_updatedhtml($activity, $course, $cm, $pagetype);
                } else if ($trackrecord = $DB->get_record('clearlesson_track',
                                                ['clearlessonid' => $activity->id,
                                                'userid' => $USER->id,
                                                'resetdate' => 0])) {
                    $trackrecord->timemodified = time();
                    $trackrecord->watchedall = 0;
                    $DB->update_record('clearlesson_track', $trackrecord);
                    $updatedhtml = '';
                } else {
                    $newtrackrecord = new \stdClass();
                    $newtrackrecord->clearlessonid = $activity->id;
                    $newtrackrecord->userid = $USER->id;
                    $newtrackrecord->timemodified = time();
                    $newtrackrecord->watchedall = 0;
                    $DB->insert_record('clearlesson_track', $newtrackrecord);
                    $updatedhtml = '';
                }
                return ['success' => true,
                        'activitymodulehtml' => $updatedhtml];
            } else if ($watchedall && $status == 'watched') {
                // All the videos in the resource have already been watched, but the module has not been marked as complete.
                // The videos may have been watched in other resources or on the Clear Lessons platform.
                // Alternatively the completion conditions may have changed.
                // Mark completed in moodle.
                $updatedhtml = self::mark_module_complete_and_get_updatedhtml($activity, $course, $cm, $pagetype, $resetdate);
                return ['success' => true,
                        'activitymodulehtml' => $updatedhtml];
            } else {
                // No action taken.
                return ['success' => false,
                        'activitymodulehtml' => ''];
            }
        }

        /**
         * Returns description of method result value
         *
         * @return external_multiple_structure
         * @since Moodle 3.0
         */
        public static function execute_returns(): external_single_structure {
            return new external_single_structure(
                ['success' => new external_value(PARAM_BOOL, 'Success of the update'),
                'activitymodulehtml' => new external_value(PARAM_RAW, 'HTML to update the course page Todo button with')]
            );
        }

        public static function mark_module_complete_and_get_updatedhtml($activity, $course, $cm, $pagetype) {
            global $PAGE, $DB, $USER;
            // Check the clearlesson track and set watchedall to 1 if all videos have been watched.
            $sql = "SELECT ct.userid, MAX(ct.watchedall) AS watchedall, MAX(ct.id) AS id
            FROM {clearlesson_track} ct
            WHERE ct.clearlessonid = {$activity->id}
                AND ct.userid = {$USER->id}
                AND ct.resetdate = 0
                GROUP BY ct.userid
                LIMIT 1";
            if ($result = $DB->get_record_sql($sql)) {
                if (!$result->watchedall) {
                    $latestrecord = $DB->get_record('clearlesson_track', array('id' => $result->id), '*', MUST_EXIST);
                    $latestrecord->watchedall = 1;
                    $latestrecord->timemodified = time();
                    $DB->update_record('clearlesson_track', $latestrecord);
                }
            } else {
                $newrecord = new \stdClass();
                $newrecord->clearlessonid = $activity->id;
                $newrecord->userid = $USER->id;
                $newrecord->watchedall = 1;
                $newrecord->timemodified = time();
                $newrecord->resetdate = 0;
                $DB->insert_record('clearlesson_track', $newrecord);
            } 

            // Course module completion.
            $clearlesson = $DB->get_record('clearlesson', array('id' => $activity->id), '*', MUST_EXIST);
            list($course, $cm) = \get_course_and_cm_from_instance($clearlesson, 'clearlesson');
            $completion = new completion_info($course);
            // Mark the module as complete, if it is not already.
            $completion->update_state($cm, COMPLETION_COMPLETE);

            // The format of the completion details output depends on the page type.
            if ($pagetype == 'course') {
                $format = \course_get_format($course);
                $modinfo = $format->get_modinfo();
                $section = $modinfo->get_section_info($cm->sectionnum);
                $renderer = $PAGE->get_renderer('format_' . $course->format);
                return $renderer->course_section_updated_cm_item($format, $section, $cm);
            } else if ($pagetype == 'activity') {
                $renderer = $PAGE->get_renderer('mod_clearlesson');
                $completiondetails = \core_completion\cm_completion_details::get_instance($cm, $USER->id);
                $activitydates = \core\activity_dates::get_dates_for_module($cm, $USER->id);
                $activitycompletion = new \core_course\output\activity_completion($cm, $completiondetails);
                $activitycompletiondata = (array) $activitycompletion->export_for_template($renderer);
                $activitydates = new \core_course\output\activity_dates($activitydates);
                $activitydatesdata = (array) $activitydates->export_for_template($renderer);
                $data = array_merge($activitycompletiondata, $activitydatesdata);
                return $renderer->render_from_template('core_course/activity_info', $data);
            }
        }
}