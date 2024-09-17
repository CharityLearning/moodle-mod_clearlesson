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
use core_courseformat\output\local\content\cm\completion;

defined('MOODLE_INTERNAL') || die;
require_once($CFG->dirroot . '/mod/clearlesson/lib.php');
require_once("{$CFG->libdir}/completionlib.php");
require_once($CFG->dirroot . '/course/format/lib.php');

/**
 * Even if the play button has not been pressed the course module is considered viewed.
 * Marking a course module as viewed is the only purpose of this file.
 *
 * @package    mod_clearlesson
 * @category   external
 * @copyright  2024 Dan Watkins
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @since      Moodle 3.1
 */
class course_module_viewed extends \core_external\external_api {
    
        /**
        * Returns description of method parameters
        *
        * @return external_function_parameters
        * @since Moodle 3.0
        */
        public static function execute_parameters(): external_function_parameters {
            return new external_function_parameters(
                array(
                    'courseid' => new external_value(PARAM_INT, 'Course id'),
                    'cmid' => new external_value(PARAM_INT, 'Course module id')
                )
            );
        }
    
        /**
        * Mark the course module as viewed.
        *
        * @param int $courseid the course id
        * @param int $cmid the course module id
        * @return array of warnings and status result
        * @since Moodle 3.0
        * @throws moodle_exception
        */
        public static function execute(int $courseid,
                                        int $cmid): array {

            global $PAGE, $USER, $DB;
            require_login();
            $params = self::validate_parameters(
                        self::execute_parameters(),
                            ['courseid' => $courseid,
                            'cmid' => $cmid]);

            $PAGE->set_context(\context_course::instance($courseid)); 
            $course = $DB->get_record('course', array('id' => $courseid));
            $completion = new \completion_info($course);
            $format = \course_get_format($course);
            $modinfo = $format->get_modinfo();
            $cm = $modinfo->get_cm($cmid);
            $completion->set_module_viewed($cm);
            $section = $modinfo->get_section_info($cm->sectionnum);
            $renderer = $format->get_renderer($PAGE);
            $updatedhtml = $renderer->course_section_updated_cm_item($format, $section, $cm);
            return ['activitymodulehtml' => $updatedhtml];
        }

        /**
         * Returns description of method result value
         *
         * @return external_multiple_structure
         * @since Moodle 3.0
         */
        public static function execute_returns(): external_single_structure {
            return new external_single_structure(
                ['activitymodulehtml' => new external_value(PARAM_RAW, 'HTML to update the course page Todo button with')]
            );
        }
}