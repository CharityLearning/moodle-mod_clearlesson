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

defined('MOODLE_INTERNAL') || die;
require_once($CFG->dirroot . '/mod/clearlesson/lib.php');

/**
* Trigger the course module viewed event and update the module completion status.
 *
 * @package    mod_clearlesson
 * @category   external
 * @copyright  2024 Dan Watkins
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @since      Moodle 3.1
 */
class get_potential_resources extends \core_external\external_api {
    
        /**
        * Returns description of method parameters
        *
        * @return external_function_parameters
        * @since Moodle 3.0
        */
        public static function execute_parameters(): external_function_parameters {
            return new external_function_parameters(
                array(
                    'type' => new external_value(PARAM_TEXT, 'play, playlist, topics, speakers, collections, series'),
                    'query' => new external_value(PARAM_TEXT, 'Search query')
                )
            );
        }
    
        /**
        * Use the clearlessons API to get a list of potential resources for an autocomplete search.
        *
        * @param string $type the type of resource to search for
        * @param string $query the search query
        * @return array of warnings and status result
        * @since Moodle 3.0
        * @throws moodle_exception
        */
        public static function execute(string $type, string $query): array {
            global $DB, $USER, $OUTPUT, $PAGE;
            require_login();
            $PAGE->set_context(\context_system::instance()); // Use module context?

            $params = self::validate_parameters(
                        self::execute_parameters(),
                            ['type' => $type,
                            'query' => $query]);

            $results = \mod_clearlesson\call::get_potential_resources($type, $query);
            foreach ($results as $key => $result) {
                $results[$key]['label'] = $OUTPUT->render_from_template('mod_clearlesson/autocomplete_results/form_resource_selector_suggestion', $result);
            }

            return $results;
        }

        /**
         * Returns description of method result value
         *
         * @return external_multiple_structure
         * @since Moodle 3.0
         */
        public static function execute_returns(): external_multiple_structure {
            return new external_multiple_structure(
                new external_single_structure(
                    array(
                        'id' => new external_value(PARAM_TEXT, 'Clear Lesson External Reference'),
                        'externalref' => new external_value(PARAM_TEXT, 'External ref'),
                        'label' => new external_value(PARAM_RAW, 'HTML label')
                    )
                )
            );
        }
}