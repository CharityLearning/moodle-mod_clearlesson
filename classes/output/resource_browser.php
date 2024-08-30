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
 * Resource Browser Renderable.
 * @package    mod_clearlesson
 */
class resource_browser implements \renderable, \templatable {
    /**
     * The original resource type.
     * @var string
     */
    public $originaltype;

    /**
     * The resource type to load.
     * @var string
     */
    public $loadtype;

    /**
     * The filter name
     * @var string
     */
    public $destinationtype;

    /**
     * The filter value
     * @var string
     */
    public $filtervalue;

    /**
     * The browserdata response, if provided.
     * @var array
     */
    public $response;

    /**
     * Is the browser being used in Safari?
     * @var bool
     */
    public $lazyload;

    /**
     * Construct this renderable.
     *
     * @param string $type
     *
     * @return void
     */
    public function __construct(string $type, string $destinationtype = '', string $filtervalue = '', bool $lazyload = false, array $response = []) {
        $this->originaltype = $type;
        $this->loadtype = $destinationtype ? $destinationtype : $type;
        if (!empty($response)) {
            $this->response = $response;
        } else {
            $this->response = \mod_clearlesson\call::get_browserform_data($this->loadtype);
        }
        $this->destinationtype = $destinationtype;
        $this->filtervalue = $filtervalue;
        $this->lazyload = $lazyload;
    }

    /**
     * Export this data so it can be used as the context for a mustache template.
     *
     * @param renderer_base $output
     * @return stdClass
     */
    public function export_array_for_template(renderer_base $output): array {
        $resourcetypes = clearlessons_get_resource_type_options();
        $this->response['types'] = [];
        foreach ($resourcetypes as $key => $value) {
            $type = ['key' => $key,
                    'type' => ucfirst(get_string($value, 'mod_clearlesson'))];
            if ($this->loadtype === $key) {
                $type['disabled'] = true;
            }
            $this->response['types'][] = $type;
        }
        $responsefilters = [];
        foreach ($this->response['filters'] as $filterkey => $options) {
            $filter = ['title' => ucfirst(get_string($filterkey, 'mod_clearlesson'))];
            $filter['key'] = $filterkey;
            foreach ($options as $key => $value) {
                $option = ['key' => $key, 'value' => $value];
                if ($this->originaltype === $filterkey) {
                    if ($this->filtervalue === (string) $key) {
                        $option['selected'] = true;
                        $filter['initial'] = $value;
                    }
                } else if ($key === '') {
                    $option['selected'] = true;
                    $filter['initial'] = $value;

                }
                $filter['options'][] = $option;
            }
            $responsefilters[] = $filter;
        }
        $this->response['filters'] = $responsefilters;

        $type = ($this->loadtype === 'play') ? 'video' : rtrim($this->loadtype, 's');
        $selectstring = get_string('select'). ' ' . get_string($type, 'mod_clearlesson');

        switch($this->loadtype) {
            case 'topics':
                $whatisbeingcounted = get_string('videos', 'mod_clearlesson');
                $this->response['topiccard'] = true;
                break;
            case 'playlists':
            case 'speakers':
                $whatisbeingcounted = get_string('videos', 'mod_clearlesson');
                $this->response['resourcecard'] = true;
                break;
            case 'collections':
                $whatisbeingcounted = get_string('series', 'mod_clearlesson');
                $this->response['collectioncard'] = true;
                break;
            case 'series':
                $whatisbeingcounted = get_string('playlists', 'mod_clearlesson');
                $this->response['resourcecard'] = true;
                break;
            case 'play':
                $whatisbeingcounted = '';
                $this->response['videocard'] = true;
                break;
        }
        $this->response['viewstring'] = get_string('view', 'mod_clearlesson');
        $this->response['showselectbutton'] = true;
        $this->response['selectstring'] = $selectstring;
        $this->response['type'] = $this->loadtype;
        $resources = [];
        $x = 0;
        foreach ($this->response['resources'] as $resource) {
            if ($this->destinationtype) {
                $resource['hidden'] = true;
            }
            $resource['countstring'] = '' . $resource['count'] . ' ' . $whatisbeingcounted;
            $searchstring = ($this->destinationtype) ? $resource[$this->originaltype] : '';
            if ($searchstring) {
                $fragments = explode(' ', $searchstring);
                foreach ($fragments as $fragment) {
                    if (str_contains($fragment, '___')) {
                        $subfrags = explode('___', $fragment);
                        $fragment = $subfrags[0];
                    }
                    if ($fragment === $this->filtervalue) {
                        $resource['hidden'] = false;
                        break;
                    }
                }
                if ($resource['hidden'] === true) {
                    $resource['hiddenby'] = $this->originaltype;
                }
            }
            // Safari struggles with large amounts of resources.
            // Unless lazyload is enabled;
            if ($this->lazyload && !$resource['hidden']) {
                if (++$x > CLEARLESSON_LAZYLOAD_LIMIT) {
                    $resource['lazyload'] = true;
                }
            }
            $resources[] = $resource;
        }
        $this->response['resources'] = $resources;
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
