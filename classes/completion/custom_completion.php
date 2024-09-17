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

declare(strict_types=1);

namespace mod_clearlesson\completion;

use core_completion\activity_custom_completion;

/**
 * Activity custom completion subclass for the clearlesson activity.
 *
 * Class for defining mod_clearlesson's custom completion rules and fetching the completion statuses
 * of the custom completion rules for a given forum instance and a user.
 *
 * @package mod_clearlesson
 * @copyright Dan Watkins <dwatkins@charitylearning.org>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class custom_completion extends activity_custom_completion {

    /**
     * Fetches the completion state for a given completion rule.
     *
     * @param string $rule The completion rule.
     * @return int The completion state.
     */
    public function get_state(string $rule): int {
        global $DB;

        $this->validate_rule($rule);

        $userid = $this->userid;
        $clearlessonid = $this->cm->instance;

        if (!$clearlesson = $DB->get_record('clearlesson', ['id' => $clearlessonid])) {
            throw new \moodle_exception('Unable to find clearlesson with id ' . $clearlessonid);
        }

        // If completion option is enabled, evaluate it and return true/false
        if ($clearlesson->completionwatchedall) {
            $sql = "SELECT ct.userid, MAX(ct.watchedall) AS watchedall
                    FROM mdl_clearlesson_track ct
                    WHERE ct.clearlessonid = {$clearlessonid}
                        AND ct.userid = {$userid}
                        GROUP BY ct.userid";
            
            if ($result = $DB->get_record_sql($sql)) {
                if ($result->watchedall == 1) {
                    return COMPLETION_COMPLETE;
                }
            }
            return COMPLETION_INCOMPLETE;
        } else {
            // Completion option is not enabled so just return $type
            return $type;
        }
    }

    /**
     * Fetch the list of custom completion rules that this module defines.
     *
     * @return array
     */
    public static function get_defined_custom_rules(): array {
        return [
            'completionwatchedall'
        ];
    }

    /**
     * Returns an associative array of the descriptions of custom completion rules.
     *
     * @return array
     */
    public function get_custom_rule_descriptions(): array {
        return [
            'completionwatchedall' => get_string('watchedallrule', 'clearlesson')
        ];
    }

    /**
     * Returns an array of all completion rules, in the order they should be displayed to users.
     *
     * @return array
     */
    public function get_sort_order(): array {
        return [
            'completionview',
            'completionwatchedall',
            'completionusegrade',
            'completionpassgrade',
        ];
    }
}
