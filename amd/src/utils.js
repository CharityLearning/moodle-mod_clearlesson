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
 * Utils for mod_clearlesson.
 *
 * @module     mod_clearlesson/utils
 * @copyright  2024 Dan Watkins <dwatkins@charitylearning.org>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @since      2.9
 */

/**
 * Observe one element for another then call a callback.
 * @param {string} checkElementSelector
 * @param {HTMLelement} watchElement
 * @param {function} callBack
 */
export const waitForElement = (checkElementSelector, watchElement, callBack) => {
    if (watchElement) {
        waitForElementInner(checkElementSelector, watchElement, callBack);
    } else {
        waitForElementInner(watchElement, document.body, function() {
            waitForElementInner(checkElementSelector, watchElement, callBack);
        });
    }
};

/**
 * @param {string} checkElementSelector
 * @param {HTMLelement} watchElement
 * @param {function} callBack
 */
function waitForElementInner(checkElementSelector, watchElement, callBack) {
    let checkElement = watchElement.querySelector(checkElementSelector);
    if (checkElement) {
        callBack();
    } else {
        let observer = new MutationObserver((mutationList, observer) => {
            let checkElement = watchElement.querySelector(checkElementSelector);
            if (checkElement) {
                observer.disconnect();
                callBack();
            }
        });
        observer.observe(watchElement, {
            childList: true,
            subtree: true
        });
    }
}

/**
 * Stolen from core_form/util.js
 * Unfortunately, we cannot import this function from core_form/util.js at present
 *
 * @param {Object} data
 * @param {String} prefix
 */
export const serialize = (data, prefix = '') => [
    ...Object.entries(data).map(([index, value]) => {
        const key = prefix ? `${prefix}[${index}]` : index;
        return (value !== null && typeof value === "object") ? serialize(value, key) : `${key}=${encodeURIComponent(value)}`;
    })
].join("&");