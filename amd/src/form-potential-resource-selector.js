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
 * Frameworks datasource.
 *
 * This module is compatible with core/form-autocomplete.
 *
 * @module     mod_clearlesson/form-potential-resource-selector
 * @copyright  2016 Frédéric Massart - FMCorz.net
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
import Ajax from 'core/ajax';
import Notification from 'core/notification';

export default {/** @alias module:tool_lpmigrate/frameworks_datasource */
    /**
     * List frameworks.
     *
     * @param {String} type The type of resource.
     * @param {String} query The query.
     * @return {Promise}
     */
    list: function(type, query) {
        var args = {
                type: type,
                query: query
            };
        return Ajax.call([{
            methodname: 'mod_clearlesson_get_potential_resources',
            args: args
        }])[0];
    },

    /**
     * Process the results for auto complete elements.
     *
     * @param {String} selector The selector of the auto complete element.
     * @param {Array} results An array or results.
     * @return {Array} New array of results.
     */
    processResults: function(selector, results) {
        var options = [];
        for (const result of results) {
            result.value = result.externalref;
            options.push(result);
        }
        return options;
    },

    /**
     * Source of data for Ajax element.
     *
     * @param {String} selector The selector of the auto complete element.
     * @param {String} query The query string.
     * @param {Function} callback A callback function receiving an array of results.
     */
    /* eslint-disable promise/no-callback-in-promise */
    transport: function(selector, query, callback) {
        const selectedType = document.getElementById('id_type').value;
        this.list(selectedType, query).then((callback))
        .catch(Notification.exception);
    }
};