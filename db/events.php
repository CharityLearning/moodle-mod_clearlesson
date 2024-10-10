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
 * This file defines observers needed by the plugin.
 *
 * @author     Dan Watkins
 * @copyright  2024 Dan Watkins
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$observers = array(
    [
        'eventname' => '\local_recompletion\event\completion_reset',
        'callback' => 'mod_clearlesson_observer::completion_reset',
    ],
    // [
    //     'eventname' => '\core\event\user_updated',
    //     'callback' => 'mod_clearlesson_observer::user_updated',
    // ],
    // [
    //     'eventname'   => '\core\event\course_deleted',
    //     'callback'    => 'mod_clearlesson_observer::remove_deleted_group_item',
    //     'includefile' => '/admin/tool/pathways/lib.php',
    //     'priority'    => 200,
    //     'internal'    => false,
    // ],
    // [
    //     'eventname'   => '\core\event\course_module_deleted',
    //     'callback'    => 'mod_clearlesson_observer::remove_deleted_group_item',
    //     'includefile' => '/admin/tool/pathways/lib.php',
    //     'priority'    => 200,
    //     'internal'    => false,
    // ],
    // [
    //     'eventname'   => '\core\event\course_section_deleted',
    //     'callback'    => 'mod_clearlesson_observer::remove_deleted_group_item',
    //     'includefile' => '/admin/tool/pathways/lib.php',
    //     'priority'    => 200,
    //     'internal'    => false,
    // ],
    // [
    //     'eventname' => '\core\event\course_completed',
    //     'callback' => 'mod_clearlesson_observer::user_based_pathway_completion_changes',
    //     'includefile' => '/admin/tool/pathways/lib.php',
    //     'priority' => 200,
    //     'internal' => false,
    // ],
    // [
    //     'eventname' => '\core\event\course_module_completion_updated',
    //     'callback' => 'mod_clearlesson_observer::user_based_pathway_completion_changes',
    //     'includefile' => '/admin/tool/pathways/lib.php',
    //     'priority' => 200,
    //     'internal' => false,
    // ],
    // [
    //     'eventname' => '\core\event\course_module_updated',
    //     'callback' => 'mod_clearlesson_observer::pathway_based_pathway_completion_changes',
    //     'includefile' => '/admin/tool/pathways/lib.php',
    //     'priority' => 200,
    //     'internal' => false,
    // ],
    // [
    //     'eventname' => '\core\event\course_module_deleted',
    //     'callback' => 'mod_clearlesson_observer::pathway_based_pathway_completion_changes',
    //     'includefile' => '/admin/tool/pathways/lib.php',
    //     'priority' => 200,
    //     'internal' => false,
    // ],
    // [
    //     'eventname' => '\core\event\course_module_completion_updated',
    //     'callback' => 'mod_clearlesson_observer::pathway_based_pathway_completion_changes',
    //     'includefile' => '/admin/tool/pathways/lib.php',
    //     'priority' => 200,
    //     'internal' => false,
    // ],
    // [
    //     'eventname' => '\core\event\course_section_updated',
    //     'callback' => 'mod_clearlesson_observer::pathway_based_pathway_completion_changes',
    //     'includefile' => '/admin/tool/pathways/lib.php',
    //     'priority' => 200,
    //     'internal' => false,
    // ],
    // [
    //     'eventname' => '\core\event\course_deleted',
    //     'callback' => 'mod_clearlesson_observer::pathway_based_pathway_completion_changes',
    //     'includefile' => '/admin/tool/pathways/lib.php',
    //     'priority' => 200,
    //     'internal' => false,
    // ],
    // [
    //     'eventname' => '\core\event\course_completion_updated',
    //     'callback' => 'mod_clearlesson_observer::pathway_based_pathway_completion_changes',
    //     'includefile' => '/admin/tool/pathways/lib.php',
    //     'priority' => 200,
    //     'internal' => false,
    // ],
    // [
    //     'eventname' => '\mod_clearlesson\event\pathway_updated',
    //     'callback' => 'mod_clearlesson_observer::pathway_based_pathway_completion_changes',
    //     'includefile' => '/admin/tool/pathways/lib.php',
    //     'priority' => 200,
    //     'internal' => false,
    // ],
    // [
    //     'eventname' => '\mod_clearlesson\event\pathway_allocation_created',
    //     'callback' => 'mod_clearlesson_observer::user_based_pathway_completion_changes',
    //     'includefile' => '/admin/tool/pathways/lib.php',
    //     'priority' => 200,
    //     'internal' => false,
    // ],
    // [
    //     'eventname' => '\mod_clearlesson\event\pathway_allocation_updated',
    //     'callback' => 'mod_clearlesson_observer::user_based_pathway_completion_changes',
    //     'includefile' => '/admin/tool/pathways/lib.php',
    //     'priority' => 200,
    //     'internal' => false,
    // ]
);