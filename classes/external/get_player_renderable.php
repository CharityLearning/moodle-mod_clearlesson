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
 * @copyright  2017 Josh Willcock
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @since      Moodle 3.1
 */
class get_player_renderable extends \core_external\external_api {
    
        /**
        * Returns description of method parameters
        *
        * @return external_function_parameters
        * @since Moodle 3.0
        */
        public static function execute_parameters(): external_function_parameters {
            return new external_function_parameters(
                array(
                    'cmid' => new external_value(PARAM_INT, 'Course module id'),
                    'course' => new external_value(PARAM_INT, 'Course id'),
                    'position' => new external_value(PARAM_INT, 'The position of the video in the resource')
                )
            );
        }
    
        /**
        * Use the clearlessons API to get a list of potential resources for an autocomplete search.
        *
        * @param int $cmid the course module id
        * @param int $course the course id
        * @param int $position the position of the video in the resource
        */
        public static function execute(int $cmid, int $course, int $position): \mod_clearlesson\output\incourse_player {
            global $DB, $USER, $OUTPUT, $PAGE;
            require_login();
            $PAGE->set_context(\context_system::instance()); // Use module context?

            $params = self::validate_parameters(
                        self::execute_parameters(),
                            ['cmid' => $cmid,
                            'course' => $course,
                            'position' => $position]);

            $sql = "SELECT cl.type, cl.externalref
                    FROM {clearlesson} cl
                    JOIN {course_modules} cm ON cl.id = cm.instance
                    WHERE cm.id = :cmid";
            $clearlesson = $DB->get_record_sql($sql, ['cmid' => $cmid]);
                    
            $renderable = new \mod_clearlesson\output\incourse_player($clearlesson->type,
                                                                        $clearlesson->externalref,
                                                                        $position,
                                                                        [], 0);
            return $renderable;
        }

        /**
         * Returns description of method result value
         *
         * @return external_multiple_structure
         * @since Moodle 3.0
         */
        public static function execute_returns(): external_single_structure {
            return new external_single_structure(
                array(
                    'type' => new external_value(PARAM_TEXT, 'Resource type'),
                    'externalref' => new external_value(PARAM_TEXT, 'External ref of the resource'),
                    'response' => new external_single_structure(
                        array(
                            'id' => new external_value(PARAM_INT, 'The id of the video', VALUE_OPTIONAL),
                            'name' => new external_value(PARAM_TEXT, 'The name of the video', VALUE_OPTIONAL),
                            'externalref' => new external_value(PARAM_TEXT, 'The external reference of the video', VALUE_OPTIONAL),
                            'src' => new external_value(PARAM_RAW, 'The source of the video'),
                            'thumbnail' => new external_value(PARAM_RAW, 'The thumbnail of the video', VALUE_OPTIONAL),
                            'panelshtml' => new external_value(PARAM_RAW, 'The panels html of the video', VALUE_OPTIONAL),
                            'description' => new external_value(PARAM_RAW, 'The description of the video', VALUE_OPTIONAL),
                            'topics' => new external_multiple_structure(
                                new external_single_structure(
                                    array(
                                        'externalref' => new external_value(PARAM_TEXT, 'The external reference of the topic', VALUE_OPTIONAL),
                                        'topic' => new external_value(PARAM_TEXT, 'The name of the topic', VALUE_OPTIONAL),
                                    )
                                    ), 'The topics of the video', VALUE_OPTIONAL
                            ),
                            'topicnames' => new external_value(PARAM_TEXT, 'The names of the topics', VALUE_OPTIONAL),
                            'speaker' => new external_value(PARAM_INT, 'The speakerid', VALUE_OPTIONAL),
                            'speakername' => new external_value(PARAM_TEXT, 'The name of the speaker', VALUE_OPTIONAL),
                            'speakers' => new external_value(PARAM_TEXT, 'The cleannames of the speakers', VALUE_OPTIONAL),
                            'duration' => new external_value(PARAM_TEXT, 'The duration of the video in MM:SS', VALUE_OPTIONAL),
                            'transcript' => new external_value(PARAM_RAW, 'The transcript of the video', VALUE_OPTIONAL),
                            'speakerimgsrc' => new external_value(PARAM_RAW, 'The speaker image source', VALUE_OPTIONAL),
                            'small' => new external_value(PARAM_BOOL, 'The small format?', VALUE_OPTIONAL),
                            'playlists' => new external_value(PARAM_TEXT, 'The cleannames of the playlists', VALUE_OPTIONAL),
                            'series' => new external_value(PARAM_TEXT, 'The cleannames of the series', VALUE_OPTIONAL),
                            'collections' => new external_value(PARAM_TEXT, 'The cleannames of the collections', VALUE_OPTIONAL),
                            'videoorder' => new external_value(PARAM_INT, 'The position of the video in the resource', VALUE_OPTIONAL),
                            'progress' => new external_value(PARAM_INT, 'The progress of the video in seconds', VALUE_OPTIONAL),
                            'viewedstatus' => new external_value(PARAM_TEXT, 'The viewed status of the video', VALUE_OPTIONAL),
                            'watched' => new external_value(PARAM_INT, 'Has the user watched this video', VALUE_OPTIONAL),
                            'playlistref' => new external_value(PARAM_TEXT, 'The external reference of the playlist', VALUE_OPTIONAL),
                            'othervideos' => new external_multiple_structure(
                                new external_single_structure(
                                    array('id' => new external_value(PARAM_INT, 'The id of the video', VALUE_OPTIONAL),
                                        'name' => new external_value(PARAM_TEXT, 'The name of the video', VALUE_OPTIONAL),
                                        'vimeoref' => new external_value(PARAM_TEXT, 'The vimeoref of the video', VALUE_OPTIONAL),
                                        'src' => new external_value(PARAM_TEXT, 'The source of the video', VALUE_OPTIONAL),
                                        'smallthumbnail' => new external_value(PARAM_RAW, 'The small thumbnail of the video', VALUE_OPTIONAL),
                                        'largethumbnail' => new external_value(PARAM_RAW, 'The large thumbnail of the video', VALUE_OPTIONAL),
                                        'description' => new external_value(PARAM_RAW, 'The description of the video', VALUE_OPTIONAL),
                                        'duration' => new external_value(PARAM_TEXT, 'The duration of the video in s', VALUE_OPTIONAL),
                                        'active' => new external_value(PARAM_BOOL, 'Is the video active?', VALUE_OPTIONAL),
                                        'position' => new external_value(PARAM_INT, 'The position of the video in the resource', VALUE_OPTIONAL),
                                        'convertedduration' => new external_value(PARAM_TEXT, 'The converted duration of the video', VALUE_OPTIONAL),
                                        'progress' => new external_value(PARAM_INT, 'The progress of the video in seconds', VALUE_OPTIONAL),
                                        'progresspercent' => new external_value(PARAM_INT, 'The progress of the video in percent', VALUE_OPTIONAL),
                                        'watched' => new external_value(PARAM_INT, 'Has the user watched this video', VALUE_OPTIONAL),
                                    )
                                )
                            ),
                            'watchedall' => new external_value(PARAM_INT, 'Has the user watched all videos in the resource', VALUE_OPTIONAL),
                            'videowatchedstring' => new external_value(PARAM_TEXT, 'The string of watched videos', VALUE_OPTIONAL),
                            'firstload' => new external_value(PARAM_INT, 'Is this the first load of the player', VALUE_OPTIONAL),
                            'recourceref' => new external_value(PARAM_TEXT, 'The external reference of the resource', VALUE_OPTIONAL),
                            'type' => new external_value(PARAM_TEXT, 'The type of resource', VALUE_OPTIONAL),
                            'speakeref' => new external_value(PARAM_TEXT, 'The external reference of the speaker', VALUE_OPTIONAL),
                            'topicref' => new external_value(PARAM_TEXT, 'The external reference of the topic', VALUE_OPTIONAL),
                            'playlistref' => new external_value(PARAM_TEXT, 'The external reference of the playlist', VALUE_OPTIONAL),
                            'position' => new external_value(PARAM_INT, 'The position of the video in the resource', VALUE_OPTIONAL)
                        )
                    ),
                )
            );
        }
}