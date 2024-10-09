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
 * The resource browser form.
 */
class resource_browser_form extends \mod_clearlesson\forms\base_dynamic_form {
    /**
     * Define the form.
     */
    public function definition() {
        global $CFG, $DB, $PAGE, $OUTPUT;
        $destinationtype = $this->_ajaxformdata['destinationtype'] ?? '';
        $filtervalue = $this->_ajaxformdata['filtervalue'] ?? '';
        $lazyload = $this->_ajaxformdata['lazyload'] ?? false;
        $dform = $this->_form;
        $renderable = new \mod_clearlesson\output\resource_browser($this->_ajaxformdata['type'],
                                                                    $destinationtype,
                                                                    $filtervalue,
                                                                    $lazyload);
        $renderable->modal = true;
        $output = $PAGE->get_renderer('mod_clearlesson');
        $dform->addElement('html', $output->render_resource_browser($renderable));
    }
}