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

use core_external\external_function_parameters;
use core_external\external_multiple_structure;
use core_external\external_single_structure;
use core_external\external_value;
use core_external\external_warnings;

defined('MOODLE_INTERNAL') || die;

require_once($CFG->dirroot . '/mod/clearlesson/lib.php');

/**
* Trigger the course module viewed event and update the module completion status.
 *
 * @package    mod_clearlesson
 * @category   external
 * @copyright  2017 Josh Willcock
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @since      Moodle 3.1
 */
class view_clearlesson extends \core_external\external_api {

    /**
     * Returns description of method parameters
     *
     * @return external_function_parameters
     * @since Moodle 3.0
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters(
            array(
                'refid' => new external_value(PARAM_INT, 'Clear Lesson Video instance id')
            )
        );
    }

    /**
     * Trigger the course module viewed event and update the module completion status.
     *
     * @param int $urlid the url instance id
     * @return array of warnings and status result
     * @since Moodle 3.0
     * @throws moodle_exception
     */
    public static function execute($refid): array {
        global $DB, $CFG;
        require_once($CFG->dirroot . "/mod/clearlesson/lib.php");
        $params = self::validate_parameters(self::view_url_parameters(), array('refid' => $refid));
        $warnings = array();

        // Request and permission validation.
        $clearlesson = $DB->get_record('clearlesson', array('id' => $params['refid']), '*', MUST_EXIST);
        list($course, $cm) = \get_course_and_cm_from_instance($clearlesson, 'clearlesson');

        $context = \context_module::instance($cm->id);
        self::validate_context($context);

        \require_capability('mod/clearlesson:view', $context);

        // Call the clearlesson/lib API.
        clearlesson_view($clearlesson, $course, $cm, $context);

        $result = array();
        $result['status'] = true;
        $result['warnings'] = $warnings;
        return $result;
    }

    /**
     * Returns description of method result value
     *
     * @return external_description
     * @since Moodle 3.0
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure(
            array(
                'status' => new external_value(PARAM_BOOL, 'status: true if success'),
                'warnings' => new external_warnings()
            )
        );
    }
}