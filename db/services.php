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
 * URL external functions and service definitions.
 *
 * @package    mod_clearlesson
 * @category   external
 * @copyright  2017 Josh Willcock
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

$functions = array(
    'mod_clearlesson_view_clearlesson' => [
        'classname' => 'mod_clearlesson\external\view_clearlesson',
        'description'   => 'Trigger the course module viewed event and update the module completion status.',
        'type' => 'read',
        'capabilities'  => 'mod/clearlesson:view',
        'ajax' => true,
        'services'      => array(MOODLE_OFFICIAL_MOBILE_SERVICE)
    ],
    'mod_clearlesson_get_potential_resources' => array(
        'classname' => 'mod_clearlesson\external\get_potential_resources',
        'description'   => 'Get potential resources for a given type.',
        'type'          => 'read',
        'capabilities'  => 'mod/clearlesson:view',
        'ajax' => true,
        'services'      => array(MOODLE_OFFICIAL_MOBILE_SERVICE)
    ),
    'mod_clearlesson_update_progress' => array(
        'classname' => 'mod_clearlesson\external\update_progress',
        'description'   => 'Update the viewed progress of a video in the Clear Lesson Platform.',
        'type'          => 'write',
        'capabilities'  => 'mod/clearlesson:view',
        'ajax' => true,
        'services'      => array(MOODLE_OFFICIAL_MOBILE_SERVICE)
    ),
    'mod_clearlesson_course_module_viewed' => array(
        'classname' => 'mod_clearlesson\external\course_module_viewed',
        'description'   => 'Mark the course module as viewed.',
        'type'          => 'write',
        'capabilities'  => 'mod/clearlesson:view',
        'ajax' => true,
        'services'      => array(MOODLE_OFFICIAL_MOBILE_SERVICE)
    )

);
