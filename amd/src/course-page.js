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
 * A set of functions to be added to charts tab load-tabs.js.
 * Most of these functions enable modal forms for the adding/deleting of charts or editing existing charts.
 *
 * @module     mod_clearlesson/course-page
 * @copyright  2024 Dan Watkins <dwatkins@charitylearning.org>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @since      2.9
 */

import ModalForm from 'core_form/modalform';
import * as Utils from './utils';
import {updateProgressAndActivity} from './progress-tracker';
import Ajax from 'core/ajax';
import {get_string as getString} from 'core/str';

var url, playerModal, formClass, backString, modalType, moodleCompleted, playerModalFromMenu, newMenuModal, completionDropdown;
var firstLoad = 1;


export const init = () => {
    var position = 1;
    window.openPlayer = async(e, type) => {
        if (e.target.href.includes('clearlesson')) {
            e.preventDefault();
            backString = await getString('back');
            const elementAncestor = e.target.closest('.activity');
            // Used in the progress tracker as well as on this page.
            window.cmid = elementAncestor.querySelector('.activityname a')
                                                .getAttribute('href').split('id=')[1];

            window.type = type;
            const searchParams = new URLSearchParams(window.location.search);
            url = e.target.href;
            window.courseid = searchParams.get('id'); // Used in the progress tracker as well as on this page.
            const instanceNameElement = elementAncestor.querySelector('.activityname .instancename');
            const span = instanceNameElement.querySelector('span');
            if (span) {
                instanceNameElement.removeChild(span);
            }
            const instanceName = instanceNameElement.innerText;

            if (type === 'series' || type === 'collections') {
                formClass = 'mod_clearlesson\\forms\\incourse_menu_form';
                modalType = 'menu';
            } else {
                formClass = 'mod_clearlesson\\forms\\incourse_player_form';
                modalType = 'player';
            }

            playerModal = new ModalForm({
                formClass: formClass,
                args: {cmid: window.cmid,
                        course: window.courseid,
                        url: url,
                        firstload: firstLoad},
                modalConfig: {title: instanceName},
            });

            playerModal.addEventListener(playerModal.events.LOADED, function() {
                const modalRootInner = playerModal.modal.getRoot()[0].children[0];
                if (type !== 'play') {
                    setModalFullscreen(modalRootInner);
                }

                if (modalType === 'player') {
                    Utils.waitForElement('.incourse-player', modalRootInner, async function() {
                        if (window.updateProgress) {
                            await updateProgressAndActivity(); // Record any progress from the last player.
                        }
                        // Store the resource reference for the progress tracker.
                        window.resourceRef = document.querySelector('.incourse-player').getAttribute('data-resourceref');
                        window.type = document.querySelector('.incourse-player').getAttribute('data-type');
                        setWindowWatched();
                    });
                }

                if (modalType === 'menu') {
                    Utils.waitForElement('.incourse-player', modalRootInner, async function() {
                        if (window.updateProgress) {
                            await updateProgressAndActivity(); // Record any progress from the last player.
                        }
                        window.updateProgress = false;
                    });
                    // TODO Check if the completion is enabled. and if the activity is marked as complete.
                    // If the completion is enabled, and all videos are watched and the activity is not marked as complete,
                    // mark the activity as complete.
                }

                completionDropdown = elementAncestor.querySelector('.completion-dropdown');
                updateCompletionStatusIfIncorrect(completionDropdown, modalRootInner);

                setModalButtons(modalRootInner);

                Ajax.call([{
                    methodname: 'mod_clearlesson_course_module_viewed',
                    args: {courseid: window.courseid, cmid: window.cmid},
                    done: function(response) {
                        // Update the entire activity module html, this update the completion status if present.
                        e.target.closest('.activity').outerHTML = response.activitymodulehtml;
                    }
                }]);
            });

            playerModal.show();
        }
    };

    document.addEventListener('click', async function(e) {
        const element = e.target;
        if (element.classList?.contains('play-icon')
        || element.parentElement.classList?.contains('play-icon')
        || element.classList?.contains('video-player-link')
        || element.parentElement.classList?.contains('video-player-link')) {
            e.preventDefault();
            if (element.classList.contains('incourse-player-link')
            || element.parentElement.classList.contains('incourse-player-link')) {
                if (element.getAttribute('data-type') === 'playlists'
                || element.parentElement.getAttribute('data-type') === 'playlists') {
                    // 2nd level modals.
                    openPlayerFromMenu(e);
                } else {
                    // For collections we'll open the series menu.
                    openNewMenuModal(e);
                }
            } else {
                position = parseInt(element.closest('.has-position').getAttribute('data-position'));
                reRenderPlayerModal(position, url);
            }
        }
    });
};

/**
 * Open the player modal.
 *
 * @param {Number} position The position of the resource in the list.
 * @param {String} url The URL of the resource.
 */
async function reRenderPlayerModal(position, url) {
    const formParams = {cmid: window.cmid, course: window.courseid, url: url, position: position, firstload: false};
    const serialFormParams = Utils.serialize(formParams);
    const bodyContent = playerModal.getBody(serialFormParams);
    await playerModal.modal.setBodyContent(bodyContent);
    setWindowWatched();
}

/**
 * Set the watched status of the window.
 */
function setWindowWatched() {
    const watchedCheck = document.querySelector('.incourse-player .player-column .watched-check');
    // If the video has been watched already dont update the progress.
    window.updateProgress = watchedCheck?.classList.contains('notwatched');
}

/**
 * Open the player modal from the menu item.
 * @param {Event} e The event object.
 */
function openPlayerFromMenu(e) {
    const elementAncestor = e.target.closest('.menu-item');
    const instanceName = elementAncestor.querySelector('.menu-item-title > .searchable').innerHTML;
    const externalRef = elementAncestor.getAttribute('data-externalref');
    window.type = 'playlists';
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

        Utils.waitForElement('.incourse-player', modalRootInner, async function() {
            if (window.updateProgress) {
                await updateProgressAndActivity(); // Record any progress from the last player.
            }
            // Store the resource reference for the progress tracker.
            window.resourceRef = document.querySelector('.incourse-player').getAttribute('data-resourceref');
            window.type = document.querySelector('.incourse-player').getAttribute('data-type');
            setWindowWatched();
        });

        updateCompletionStatusIfIncorrect(completionDropdown, modalRootInner);

        setModalButtons(modalRootInner);
    });

    playerModalFromMenu.show();
}

/**
 * Open a new menu modal. This will be a menu of series for a collection.
 * @param {Event} e The event object.
 */
function openNewMenuModal(e) {
    const menuItem = e.target.closest('.menu-item');
    const externalRef = menuItem.getAttribute('data-externalref');
    const instanceName = menuItem.querySelector('.menu-item-title > .searchable').innerHTML;

    window.type = 'series';
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
        setModalFullscreen(modalRootInner);
        Utils.waitForElement('.incourse-menu', modalRootInner, async function() {
            if (window.updateProgress) {
                await updateProgressAndActivity(); // Record any progress from the last player.
            }
            window.updateProgress = false;
        });
        updateCompletionStatusIfIncorrect(completionDropdown, modalRootInner);
    });

    newMenuModal.show();
}

/**
 * Set the modal to nearly fullscreen size.
 * @param {HTMLelement} modalRootInner The root of the modal.
 */
function setModalFullscreen(modalRootInner) {
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
 * Set the modal buttons.
 * @param {HMTLelement} modalRootInner The root of the modal.
 */
function setModalButtons(modalRootInner) {
    Utils.waitForElement('.modal-footer button.btn-primary', modalRootInner, async function() {
        const saveButton = modalRootInner.querySelector('.modal-footer button.btn-primary');
        saveButton.classList.add('d-none');
        const cancelButton = modalRootInner.querySelector('.modal-footer button.btn-secondary');
        cancelButton.innerHTML = backString;
    });
}

/**
 * Update the completion status if it is incorrect.
 * @param {HTMLelement} completionDropdown The completion dropdown/button (may not be present).
 * @param {HTMLelement} modalRootInner The root of the modal.
 */
function updateCompletionStatusIfIncorrect(completionDropdown, modalRootInner) {
    if (completionDropdown) {
        if (completionDropdown.classList.contains('btn-success')) {
            moodleCompleted = true;
        } else {
            moodleCompleted = false;
        }
        Utils.waitForElement('.incourse', modalRootInner, async function() {
            if (modalRootInner.querySelector('.incourse').getAttribute('data-watchedall') == '1') {
                window.viewedStatus = 'watched';
                window.updateProgress = false;
                if (!moodleCompleted) {
                    // All the videos have been watched but the activity has not been marked as complete.
                    // If the completionwatchedall rule is enabled, we can mark the activity as complete.
                    const watchdAllRuleString = await getString('watchedallrule', 'mod_clearlesson');
                    if (completionDropdown.outerHTML.includes(watchdAllRuleString)) {
                        window.currentTime = 0;
                        window.resourceRef = modalRootInner.querySelector('.incourse').getAttribute('data-resourceref');
                        window.extref = '';
                        await updateProgressAndActivity(); // We ignore window.updateProgress here.
                    }
                }
            }
        });
    }
}