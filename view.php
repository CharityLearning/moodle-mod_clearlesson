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
 * Clear Lesson module main user interface
 *
 * @package    mod_clearlesson
 * @copyright  2017 Josh Willcock  {@link http://josh.cloud}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require('../../config.php');
require_once("$CFG->dirroot/mod/clearlesson/lib.php");
require_once("$CFG->dirroot/mod/clearlesson/locallib.php");
require_once($CFG->libdir . '/completionlib.php');
$pluginconfig = get_config("clearlesson");
$id = optional_param('id', 0, PARAM_INT);        // Course module ID.
$u = optional_param('u', 0, PARAM_INT);         // URL instance id.
$popup = optional_param('popup', 0, PARAM_INT);
$redirect = optional_param('redirect', 0, PARAM_BOOL);
if ($u) {  // Two ways to specify the module.
    $clearlesson = $DB->get_record('clearlesson', array('id' => $u), '*', MUST_EXIST);
    $cm = get_coursemodule_from_instance('clearlesson', $clearlesson->id, $clearlesson->course, false, MUST_EXIST);
} else {
    $cm = get_coursemodule_from_id('clearlesson', $id, 0, false, MUST_EXIST);
    $clearlesson = $DB->get_record('clearlesson', array('id' => $cm->instance), '*', MUST_EXIST);
}
$course = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);
require_course_login($course, true, $cm);
$context = context_module::instance($cm->id);
require_capability('mod/clearlesson:view', $context);
// Completion and trigger events.
clearlesson_view($clearlesson, $course, $cm, $context);
$PAGE->set_url('/mod/clearlesson/view.php', array('id' => $cm->id));
$PAGE->add_body_class('mod-clearlesson-bodyrestricted');
$PAGE->add_body_class('mod-clearlesson-' . $clearlesson->type);
if ($popup) {
    $PAGE->requires->css('/mod/clearlesson/styles/popup.css');
    $PAGE->add_body_class('mod-clearlesson-popup');
}
if ($clearlesson->type == 'play') {
    $PAGE->add_body_class('is-video');
}
clearlesson_print_header($clearlesson, $cm, $course);
clearlesson_print_intro($clearlesson, $cm, $course);

// Output starts here.
// First determine what type of display we are going to use, menu or player.
switch ($clearlesson->type) {
    case 'play':
    case 'speakers':
    case 'topics':
    case 'playlists':
        $displaytype = 'player';
        break;
    case 'series':
    case 'collections':
        $displaytype = 'menu';
        break;
}

// Open a div for the player or menu.
echo html_writer::start_tag('div', array('id' => 'clearlesson-page-container', 'class' => 'main-inner'));
if ($popup) {
    echo html_writer::start_tag('div', array('id' => 'popupdiv_' . $cm->id, 'class' => 'popupdiv'));
}

if ($displaytype == 'player') {
    $renderable = new \mod_clearlesson\output\incourse_player(type: $clearlesson->type,
                                                            externalref: $clearlesson->externalref,
                                                            position: 1,
                                                            response: [],
                                                            firstload: 1,
                                                            instance: $clearlesson->id);
    $renderable->response['inpage'] = 1;
    $output = $PAGE->get_renderer('mod_clearlesson');
    echo $output->render_incourse_player($renderable);
}

if ($displaytype == 'menu') {
    $renderable = new \mod_clearlesson\output\incourse_menu(type: $clearlesson->type,
                                                            externalref: $clearlesson->externalref,
                                                            response: [],
                                                            instance: $clearlesson->id);
    $renderable->inpage = 1;
    $output = $PAGE->get_renderer('mod_clearlesson');
    echo $output->render_incourse_menu($renderable);
}

$PAGE->requires->js_call_amd('mod_clearlesson/module-page', 'init', [$clearlesson->type]);
if ($displaytype == 'player') {
    $PAGE->requires->js_call_amd('mod_clearlesson/progress-tracker', 'init');
}
echo html_writer::end_tag('div');

echo $OUTPUT->footer();
