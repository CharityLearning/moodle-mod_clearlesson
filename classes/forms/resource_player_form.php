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
 * Edit form for a teaminsights chart.
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
class resource_player_form extends \core_form\dynamic_form {
    /**
     * Define the form.
     */
    public function definition() {
        global $CFG, $DB, $PAGE, $OUTPUT;
        $dform = $this->_form;
        if (!isset($this->_ajaxformdata['type']) || !isset($this->_ajaxformdata['externalref'])) {
            throw new \moodle_exception('Missing form param/s');
        }
        $renderable = new \mod_clearlesson\output\resource_player($this->_ajaxformdata['type'],
                                                                $this->_ajaxformdata['externalref'],
                                                                $this->_ajaxformdata['position']);

        // switch ($this->_ajaxformdata['type']) {
        //     case 'play':
        //         $typestring = get_string('video', 'mod_clearlesson');
        //         break;
        //     case 'playlists':
        //         $typestring = get_string('playlist', 'mod_clearlesson');
        //         break;
        // }
        $output = $PAGE->get_renderer('mod_clearlesson');
        $dform->addElement('html', $output->render_resource_player($renderable));
    }

    /**
     * Get the context for the form submission
     */
    protected function get_context_for_dynamic_submission(): \context {
        $this->cmid = ($this->_ajaxformdata['cmid']) ?? 0;
        if ($this->cmid) {
            return \context_module::instance($this->cmid);
        } else {
            return \context_course::instance($this->_ajaxformdata['course']);
        }
    }

    /**
     * Check access for the form submission.
     */
    protected function check_access_for_dynamic_submission(): void {
        // reu
    }

    /**
     * Process the form submission.
     */
    public function process_dynamic_submission() {
        // global $DB;
    }

    /**
     * Set the form data
     */
    public function set_data_for_dynamic_submission(): void {
    }

    /**
     * Get the page url for the form submission.
     */
    protected function get_page_url_for_dynamic_submission(): \moodle_url {
        $url = parse_url($this->_ajaxformdata['url']);
        parse_str($url['query'], $queryarray);
        return new \moodle_url($url['path'], $queryarray);
    }

    /**
     * Validate the form submission.
     *
     * @param array $data The form data.
     * @param array $files The form files.
     */
    public function validation($data, $files) {
        global $DB;
        $errors = parent::validation($data, $files);
        return $errors;
    }
}