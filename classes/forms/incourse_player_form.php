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
 * The in-course player form. Used for topic, speaker, playlist and video resources.
 *
 * @package mod_clearlesson
 * @copyright Dan Watkins <dwatkins@charitylearning.org>
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace mod_clearlesson\forms;

defined('MOODLE_INTERNAL') || die;

require_once("$CFG->libdir/formslib.php");

/**
 * The resource player form.
 */
class incourse_player_form extends \mod_clearlesson\forms\base_dynamic_form {
    /**
     * Define the form.
     */
    public function definition() {
        global $DB, $PAGE, $USER;
        $dform = $this->_form;
        if (!isset($this->_ajaxformdata['cmid'])) {
            throw new \moodle_exception('Missing form param/s');
        }
        if (isset($this->_ajaxformdata['position'])) {
            $position = $this->_ajaxformdata['position'];
        } else {
            $position = 1;
        }
        if (isset($this->_ajaxformdata['firstload'])) {
            $firstload = $this->_ajaxformdata['firstload'];
        } else {
            throw new \moodle_exception('Missing form param/s');
        }

        $this->get_resource_properties();
        $renderable = new \mod_clearlesson\output\incourse_player($this->type,
                                                                $this->externalref,
                                                                $position,
                                                                []);
        $renderable->response['firstload'] = $firstload;
        $output = $PAGE->get_renderer('mod_clearlesson');
        $dform->addElement('html', $output->render_incourse_player($renderable));
        $dform->addElement('hidden', 'type', $this->type);
        $dform->setType('type', PARAM_TEXT);
    }
}