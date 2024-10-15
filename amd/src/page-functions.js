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
 * A set of functions to be used in bot course-page.js and module-page.js
 *
 * @module     mod_clearlesson/page-functions
 * @copyright  2024 Dan Watkins <dwatkins@charitylearning.org>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @since      2.9
 */

import * as Utils from './utils';
import * as progressTracker from './progress-tracker';
import {get_string as getString} from 'core/str';
import ModalForm from 'core_form/modalform';

/**
 * Update the completion status if it is incorrect.
 * @param {HTMLelement} completionInfoElement The completion info element. (May not be present).
 * @param {HTMLelement} pageClearlessonElement The root of the modal.
 */
export function updateCompletionStatusIfIncorrect(completionInfoElement, pageClearlessonElement) {
    var moodleCompleted, ruleCheckElement;
    if (completionInfoElement) {
        if (window.pageType === 'course') {
            ruleCheckElement = completionInfoElement.nextElementSibling;
            if (completionInfoElement.classList.contains('btn-success')) {
                moodleCompleted = true;
            } else {
                moodleCompleted = false;
            }
        }
        if (window.pageType === 'activity') {
            ruleCheckElement = completionInfoElement;
            moodleCompleted = true;
            for (const condition of completionInfoElement.children) {
                if (!condition.classList.contains('alert-success')) {
                    moodleCompleted = false;
                    break;
                }
            }
        }

        Utils.waitForElement('.incourse', pageClearlessonElement, async function() {
            if (pageClearlessonElement.querySelector('.incourse').getAttribute('data-watchedall') == '1') {
                window.viewedStatus = 'watched';
                window.updateProgress = false;
                if (!moodleCompleted) {
                    // If the completionwatchedall rule is enabled, we can mark the activity as complete.
                    const watchdAllRuleString = await getString('watchedallrule', 'mod_clearlesson');
                    if (ruleCheckElement.outerHTML.includes(watchdAllRuleString)) {
                        window.currentTime = 0;
                        window.extref = '';
                        await progressTracker.updateProgressAndActivity(); // We ignore window.updateProgress here.
                    }
                }
            }
        });
    }
}

/**
 * Set the modal buttons.
 * @param {HMTLelement} modalRootInner The root of the modal.
 * @param {string} backString The string to use for the back button.
 */
export function setModalButtons(modalRootInner, backString) {
    Utils.waitForElement('.modal-footer button.btn-primary', modalRootInner, async function() {
        const saveButton = modalRootInner.querySelector('.modal-footer button.btn-primary');
        saveButton.classList.add('d-none');
        const cancelButton = modalRootInner.querySelector('.modal-footer button.btn-secondary');
        cancelButton.innerHTML = backString;
    });
}

/**
 * Set the modal to nearly fullscreen size.
 * @param {HTMLelement} modalRootInner The root of the modal.
 */
export function setModalFullscreen(modalRootInner) {
    let modalHeight = Math.ceil(window.innerHeight * 0.94);
    let modalWidth = Math.ceil(window.innerWidth * 0.94);
    modalWidth = modalWidth > 1800 ? 1800 : modalWidth;
    modalRootInner.setAttribute('style',
        'width: ' + modalWidth +
        'px;max-width: ' + modalWidth +
        'px;height: ' + modalHeight +
        'px;max-height: ' + modalHeight + 'px;'
    );
}

/**
 * Open the player modal from the menu item.
 * @param {Event} e The event object.
 * @param {string} url The URL to use for the AJAX call.
 * @param {number} firstLoad The first load flag.
 * @param {HTMLelement} completionDropdown The completion dropdown/button (may not be present).
 * @param {string} backString The string to use for the back button.
 */
export async function openPlayerFromMenu(e, url, firstLoad, completionDropdown, backString) {
    var playerModalFromMenu;
    const elementAncestor = e.target.closest('.menu-item');
    const instanceName = elementAncestor.querySelector('.menu-item-title > .searchable').innerHTML;
    const externalRef = elementAncestor.getAttribute('data-externalref');

    if (window.updateProgress) {
        await progressTracker.updateProgressAndActivity(); // Record any progress from the last player.
    }

    playerModalFromMenu = new ModalForm({
        formClass: 'mod_clearlesson\\forms\\incourse_player_form',
        args: {cmid: window.cmid,
                course: window.courseid,
                url: url,
                firstload: firstLoad,
                externalref: externalRef},
        modalConfig: {title: instanceName},
    });

    playerModalFromMenu.addEventListener(playerModalFromMenu.events.LOADED, function() {
        const modalRootInner = playerModalFromMenu.modal.getRoot()[0].children[0];
        setModalFullscreen(modalRootInner);
        setModalBodyGrey(modalRootInner);

        Utils.waitForElement('.incourse-player', modalRootInner, async function() {
            setWindowWatched();
            firstLoad = 0;
        });

        updateCompletionStatusIfIncorrect(completionDropdown, modalRootInner);
        setModalButtons(modalRootInner, backString);
    });

    playerModalFromMenu.show();
    return playerModalFromMenu;
}

/**
 * Open a new menu modal. This will be a menu of series for a collection.
 * @param {Event} e The event object.
 * @param {string} url The URL to use for the AJAX call.
 * @param {number} firstLoad The first load flag.
 * @param {HTMLelement} completionDropdown The completion dropdown/button (may not be present).
 * @param {string} backString The string to use for the back button.
 */
export async function openNewMenuModal(e, url, firstLoad, completionDropdown, backString) {
    var newMenuModal;
    const menuItem = e.target.closest('.menu-item');
    const externalRef = menuItem.getAttribute('data-externalref');
    const instanceName = menuItem.querySelector('.menu-item-title > .searchable').innerHTML;

    if (window.updateProgress) {
        await progressTracker.updateProgressAndActivity(); // Record any progress from the last player.
    }

    newMenuModal = new ModalForm({
        formClass: 'mod_clearlesson\\forms\\incourse_menu_form',
        args: {cmid: window.cmid,
                course: window.courseid,
                url: url,
                firstload: firstLoad,
                externalref: externalRef},
        modalConfig: {title: instanceName},
    });

    newMenuModal.addEventListener(newMenuModal.events.LOADED, function() {
        const modalRootInner = newMenuModal.modal.getRoot()[0].children[0];
        setModalBodyGrey(modalRootInner);
        setModalButtons(modalRootInner, backString);
        updateCompletionStatusIfIncorrect(completionDropdown, modalRootInner);
        window.updateProgress = false;
    });

    newMenuModal.show();
    return newMenuModal;
}

/**
 * Set the watched status of the window.
 */
export function setWindowWatched() {
    const watchedCheck = document.querySelector('.incourse-player .player-column .watched-check');
    // If the video has been watched already dont update the progress.
    window.updateProgress = watchedCheck?.classList.contains('notwatched');
}

/**
 * Set the modal body to grey background.
 * @param {HTMLelement} modalRootInner The root of the modal.
 */
export function setModalBodyGrey(modalRootInner) {
    modalRootInner.querySelector('.modal-body').classList.add('mod-clearlesson-backgrounddgrey');
}