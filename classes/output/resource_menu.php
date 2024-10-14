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
 * clearlesson resource menu renderable.
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
 * Resource Browser Renderable.
 * @package    mod_clearlesson
 */
class resource_menu implements \renderable, \templatable {

    /**
     * The type of resource to display.
     * @var string
     */
    public $type;

    /**
     * The external reference for the resource.
     * @var string
     */
    public $externalref;

    /**
     * The response from the API.
     * @var array
     */
    public $response;

    /**
     * The type of item to display.
     * @var string
     */
    public $itemtype;

    /**
     * Construct this renderable.
     *
     * @param string $type
     *
     * @return void
     */
    public function __construct(string $type, string $externalref, array $response = []) {
        $this->type = $type;
        switch ($this->type) {
            case 'collections':
                $this->itemtype = 'series';
                break;
            case 'series':
                $this->itemtype = 'playlists';
                break;
            case 'speakers':
            case 'topics':
            case 'playlists':
                $this->itemtype = 'play';
                break;
        }
        $this->externalref = $externalref;
        if (!empty($response)) {
            $this->response = $response;
        } else {
            $this->response = \mod_clearlesson\call::get_menuform_data($this->type, $this->externalref);
        }
    }

    /**
     * Export this data so it can be used as the context for a mustache template.
     *
     * @param renderer_base $output
     * @return stdClass
     */
    public function export_array_for_template(renderer_base $output): array {
        switch ($this->type) {
            case 'collections':
                $this->response['countstring'] = get_string('playlists', 'mod_clearlesson');
                $this->response['selectstring'] = ucfirst(get_string('select')) . ' ' . get_string('series', 'mod_clearlesson');
                break;
            case 'series':
                $this->response['countstring'] = get_string('videos', 'mod_clearlesson');
                $this->response['selectstring'] = ucfirst(get_string('select')) . ' ' . get_string('playlist', 'mod_clearlesson');
                break;
            case 'speakers':
            case 'topics':
            case 'playlists':
                $this->response['selectstring'] = ucfirst(get_string('select')) . ' ' . get_string('video', 'mod_clearlesson');
                break;
        }
        $this->response['type'] = $this->type;
        $this->response['externalref'] = $this->externalref;
        $this->response['itemtype'] = $this->itemtype;
        if ($this->modal) {
            $this->response['modal'] = true;
        }
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