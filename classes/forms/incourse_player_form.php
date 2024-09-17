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
class incourse_player_form extends \core_form\dynamic_form {

    /**
     * The course module id.
     * @var int
     */
    protected $cmid;
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

        $sql = "SELECT cl.id, cl.externalref, cl.type
                    FROM {course_modules} cm
                    JOIN {clearlesson} cl ON cl.id = cm.instance
                    WHERE cm.id = {$this->cmid}";
        if ($resource = $DB->get_record_sql($sql)) {
            $type = $resource->type;
            $externalref = $resource->externalref;
        } else {
            throw new \moodle_exception('Resource not found');
        }

        $sql = "SELECT clt.clearlessonid,
                    MAX(clt.watchedall) as watchedall
                FROM {course_modules} cm
                JOIN {clearlesson} cl ON cl.id = cm.instance
                JOIN {clearlesson_track} clt ON clt.clearlessonid = cl.id
                WHERE cm.id = {$this->cmid}
                    AND clt.userid = {$USER->id}
                    AND clt.watchedall = 1
                GROUP BY clt.clearlessonid";
        if ($clearlesson = $DB->get_record_sql($sql)) {
            $watchedall = 1;
        } else {
            // Double check the clearlesson track is up to date and update if necessary.
            // A user may have watched some videos on the clearlessons platform already.
            $counts = \mod_clearlesson\call::get_video_count($externalref, $type);
            if ($counts['watchedcount'] === $counts['videocount']) {
                $watchedall = 1;
                // Get the latest track record
                $sql = "SELECT clt.*
                        FROM {course_modules} cm
                        JOIN {clearlesson} cl ON cl.id = cm.instance
                        JOIN {clearlesson_track} clt ON clt.clearlessonid = cl.id
                        WHERE cm.id = {$this->cmid}
                            AND clt.userid = {$USER->id}
                        ORDER BY clt.id DESC
                        LIMIT 1";
                
                if ($clearlesson = $DB->get_record_sql($sql)) {
                    $clearlesson->watchedall = 1;
                    $DB->update_record('clearlesson_track', $clearlesson);
                } else {
                    $newrecord = new \stdClass();
                    $newrecord->clearlessonid = $resource->id;
                    $newrecord->userid = $USER->id;
                    $newrecord->watchedall = 1;
                    $newrecord->timemodified = time();
                    $DB->insert_record('clearlesson_track', $newrecord);
                }
            } else {
                $watchedall = 0;
            }
        }
        $renderable = new \mod_clearlesson\output\incourse_player($type,
                                                                $externalref,
                                                                $position,
                                                                [],
                                                                $watchedall);
        $renderable->response['firstload'] = $firstload;
        $output = $PAGE->get_renderer('mod_clearlesson');
        $dform->addElement('html', $output->render_incourse_player($renderable));
        $dform->addElement('hidden', 'type', $type);
        $dform->setType('type', PARAM_TEXT);
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