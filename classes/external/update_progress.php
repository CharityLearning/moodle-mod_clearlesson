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
                    'type' => new external_value(PARAM_TEXT, 'Type of resource')
                )
            );
        }
    
        /**
        * Update the viewed progress of a video in the Clear Lesson Platform.
        *
        * @param string $externalref the external reference of the video
        * @param int $duration the time watched in seconds
        * @param string $status the view status of the video for this session
        * @param int $courseid the course id
        * @param int $cmid the course module id
        * @param string $resourceref the external reference of the resource
        * @param string $type the type of resource
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
                                        string $type): array {

            global $DB, $USER, $OUTPUT, $PAGE, $CFG;
            require_login();
            $PAGE->set_context(\context_system::instance()); // Use module context?

            $params = self::validate_parameters(
                        self::execute_parameters(),
                            ['externalref' => $externalref,
                            'duration' => $duration,
                            'status' => $status,
                            'courseid' => $courseid,
                            'cmid' => $cmid,
                            'resourceref' => $resourceref,
                            'type' => $type]);
            
            if ($comprec = $DB->get_record_sql('SELECT * FROM {course_modules_completion} cmc
                                            WHERE cmc.coursemoduleid = ? AND cmc.userid = ?
                                                ORDER BY cmc.id DESC LIMIT 1', [$cmid, $USER->id])) {
                if ($comprec->completionstate > 0) {
                    return ['success' => true,
                            'activitymodulehtml' => '']; // Module already completed, do nothing.
                }
            }

            $activitysql = "SELECT cl.*, cm.completion FROM {course_modules} cm
                                JOIN {clearlesson} cl ON cm.instance = cl.id
                                WHERE cm.id = ?";
            if ($activity = $DB->get_record_sql($activitysql, [$cmid])) {
                if (!$activity->completion > 0
                || !$activity->completionwatchedall
                || $status !== 'watched') {
                    $resourceref = '';
                }
            }

            // If a '$resourceref' is set, then we need to check if all videos in the resource have been watched.
            // If they have, then we can update the completion status of the module.
            if ($response = \mod_clearlesson\call::update_progress($externalref, $duration, $status, $resourceref, $type)) {
                // Update completion of activity module.
                $newtrackrecord = new \stdClass();
                $newtrackrecord->clearlessonid = $activity->id;
                $newtrackrecord->userid = $USER->id;
                $newtrackrecord->timemodified = time();
                if ($response['result']['completionstatus']) {
                    $newtrackrecord->watchedall = 1;
                    // Update completion of course module.
                    $clearlesson = $DB->get_record('clearlesson', array('id' => $activity->id), '*', MUST_EXIST);
                    list($course, $cm) = \get_course_and_cm_from_instance($clearlesson, 'clearlesson');
                    $completion = new completion_info($course);
                    $completion->update_state($cm, COMPLETION_COMPLETE);
                    $format = \course_get_format($course);
                    $modinfo = $format->get_modinfo();
                    $section = $modinfo->get_section_info($cm->sectionnum);
                    $renderer = $format->get_renderer($PAGE);
                    $updatedhtml = $renderer->course_section_updated_cm_item($format, $section, $cm);
                } else {
                    $newtrackrecord->watchedall = 0;
                    $updatedhtml = '';
                }
                $DB->insert_record('clearlesson_track', $newtrackrecord);
                return ['success' => true,
                        'activitymodulehtml' => $updatedhtml];
            } else {
                // This only happens when the user does not have a clearlessons account with a matching email.
                // Which should neveer happen.
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
}