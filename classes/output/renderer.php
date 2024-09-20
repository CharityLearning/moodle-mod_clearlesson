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

namespace mod_clearlesson\output;
defined('MOODLE_INTERNAL') || die();
/**
 * Extend the mod_facetoface renderer cals for use in the local_ftfclassroom plugin.
 *
 * @package    local_ftfclassroom
 * @author     Josh Willcock <me@joshwillcock.co.uk>
 * @copyright  2023 Josh Willcock
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class renderer extends \plugin_renderer_base {
    /**
     * Render the resource browser.
     *
     * @param object $output 
     * @return string
     */
    public function render_resource_browser($output): string {
        $data = $output->export_array_for_template($this);
        return parent::render_from_template('mod_clearlesson/resource_browser', $data);
    }

    /**
     * Render the resource player.
     * 
     * @param object $output
     * @return string
     */
    public function render_resource_player($output): string {
        $data = $output->export_array_for_template($this);
        return parent::render_from_template('mod_clearlesson/resource_player', $data);
    }

    /**
     * Render the resource menu.
     * 
     * @param object $output
     * @return string
     */
    public function render_resource_menu($output): string {
        $data = $output->export_array_for_template($this);
        return parent::render_from_template('mod_clearlesson/resource_menu', $data);
    }

    /**
     * Render the in-course player.
     * 
     * @param object $output
     * @return string
     */
    public function render_incourse_player($output): string {
        $data = $output->export_array_for_template($this);
        return parent::render_from_template('mod_clearlesson/incourse_player', $data);
    }

    /**
     * Render the in-course menu.
     * 
     * @param object $output
     */
    public function render_incourse_menu($output): string {
        $data = $output->export_array_for_template($this);
        return parent::render_from_template('mod_clearlesson/incourse_menu', $data);
    }
}
