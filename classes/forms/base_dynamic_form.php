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
abstract class base_dynamic_form extends \core_form\dynamic_form  {

    /**
     * The course module id.
     * @var int
     */
    protected $cmid;
    /**
     * The resource type.
     * @var string
     */
    protected $type;
    /**
     * The resource external reference.
     * @var string
     */
    protected $externalref;
    /**
     * The watched all status.
     * @var int
     */
    protected $watchedall;
    /**
     * The resource instance id.
     * @var int
     */
    protected $instance;

    /**
     * Define the form.
     */
    public function definition() {
        $dform = $this->_form;
        $dform->addElement('html', 'This is a dynamic form');
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
    }

    /**
     * Process the form submission.
     */
    public function process_dynamic_submission() {
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

    /**
     * Get the externalref, instance and type from the database using the cmid.
     */
    protected function get_resource_properties() {
        global $DB;
        $sql = "SELECT cl.id, cl.externalref, cl.type
            FROM {course_modules} cm
            JOIN {clearlesson} cl ON cl.id = cm.instance
            WHERE cm.id = {$this->cmid}";
        if ($resource = $DB->get_record_sql($sql)) {
            $this->type = $resource->type;
            $this->externalref = $resource->externalref;
            $this->instance = $resource->id;
        } else {
            throw new \moodle_exception('Resource not found');
        }
    }
}