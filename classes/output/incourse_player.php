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
 * ftfclassroom renderable.
 *
 * @package    mod_clearlesson
 * @subpackage clearlesson
 * @copyright  2024 onwards CharityLearningConsortium
 * @author     Josh Willcock
 * @author     Dan Watkins
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace mod_clearlesson\output;
defined('MOODLE_INTERNAL') || die();
require_once($CFG->dirroot . '/mod/clearlesson/lib.php');

use renderable;
use renderer_base;
use stdClass;
use templatable;

/**
 * Resource player Renderable.
 * @package    mod_clearlesson
 */
class incourse_player implements \renderable, \templatable {
    /**
     * The original resource type.
     * 'play' or 'playlists'.
     * @var string
     */
    public $type;

    /**
     * The externalref of the first or primary video.
     * @var string
     */
    public $externalref;

    /**
     * The playerdata response, if provided.
     * @var array
     */
    public $response;

    /**
     * The position of the video in the playlist.
     * @var int
     */
    public $position;

    /**
     * Construct this renderable.
     *
     * @param string $type
     * @param string $externalref
     * @param int $position
     * @param array $response
     * @param int $watchedall
     *
     * @return void
     */
    public function __construct(string $type, string $externalref, int $position = 1, array $response = [], int $watchedall = 0) {
        $this->type = $type;
        $this->externalref = $externalref;
        $this->position = $position;
        if (!empty($response)) {
            $this->response = $response;
        } else {
            $this->response = \mod_clearlesson\call::get_playerform_data($this->type, $this->externalref, $this->position, $watchedall);
        }
        // Add the resourceref to the response.
        // The response externalref is the externalref of the first video in the resource.
        $this->response['resourceref'] = $this->externalref;
        $this->response['type'] = $this->type;
    }

    /**
     * Export this data so it can be used as the context for a mustache template.
     *
     * @param renderer_base $output
     * @return stdClass
     */
    public function export_array_for_template(renderer_base $output): array {
        return $this->response;
    }

    /**
    * Export this data so it can be used as the context for a mustache template.
    *
    * @param renderer_base $output
    * @return stdClass
    */
   public function export_for_template(renderer_base $output): stdClass {
       return (object) $this->export_array_for_template($output);
   } 
}
