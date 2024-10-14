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
import * as progressTracker from './progress-tracker';
import Ajax from 'core/ajax';
import {get_string as getString} from 'core/str';
import * as pageFunctions from './page-functions';

var url, playerModal, formClass, backString, modalType, playerModalFromMenu, completionInfoElement;
var firstLoad = 1;

export const init = () => {
    var position = 1;
    window.buttonClicktimeOut = false;
    window.pageType = 'course';
    window.openPlayer = async(e, type) => {
        // Window.buttonClicktimeOut is set to true and checked in the course page link onclick.
        modalDoubleClickTimeout();
        if (e.target.href.includes('clearlesson')) {
            e.preventDefault();
            backString = await getString('back');
            const elementAncestor = e.target.closest('.activity');
            completionInfoElement = elementAncestor.querySelector('.completion-dropdown button');
            // Used in the progress tracker as well as on this page.
            window.cmid = elementAncestor.querySelector('.activityname a')
                                                .getAttribute('href').split('id=')[1];

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

            if (window.updateProgress) {
                await progressTracker.updateProgressAndActivity(); // Record any progress from the last player.
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
                // Store the resource reference for the progress tracker.
                pageFunctions.setModalButtons(modalRootInner, backString);
                pageFunctions.setModalBodyGrey(modalRootInner);

                // Fullscreen modal for series, topics and playlists.
                if (type !== 'play' && modalType === 'player') {
                    pageFunctions.setModalFullscreen(modalRootInner);
                }

                if (modalType === 'player') {
                    Utils.waitForElement('.incourse-player', modalRootInner, async function() {
                        // Store the resource reference and resource type for the progress tracker.
                        // These are only set from the base layer.
                        window.resourceRef = modalRootInner.querySelector('.incourse-player').getAttribute('data-resourceref');
                        pageFunctions.updateCompletionStatusIfIncorrect(completionInfoElement, modalRootInner);
                        window.type = document.querySelector('.incourse-player').getAttribute('data-type');
                        pageFunctions.setWindowWatched();
                        firstLoad = 0;
                    });
                }

                if (modalType === 'menu') {
                    Utils.waitForElement('.incourse-menu', modalRootInner, async function() {
                        // Store the resource reference and resource type for the progress tracker.
                        // These are only set from the base layer.
                        window.resourceRef = modalRootInner.querySelector('.incourse-menu').getAttribute('data-resourceref');
                        window.type = document.querySelector('.incourse-menu').getAttribute('data-type');
                        pageFunctions.updateCompletionStatusIfIncorrect(completionInfoElement, modalRootInner);
                        window.updateProgress = false;
                    });
                }

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
                    playerModalFromMenu = await pageFunctions.openPlayerFromMenu(e,
                                                                                url,
                                                                                firstLoad,
                                                                                completionInfoElement,
                                                                                backString);
                } else {
                    // For collections we'll open the series menu.
                    await pageFunctions.openNewMenuModal(e, url, firstLoad, completionInfoElement);
                }
            } else {
                position = parseInt(element.closest('.has-position').getAttribute('data-position'));
                reRenderCoursePlayerModal(position, url);
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
async function reRenderCoursePlayerModal(position, url) {
    var theModal;
    const formParams = {cmid: window.cmid, course: window.courseid, url: url, position: position, firstload: 0};
    if (modalType === 'player') {
        theModal = playerModal;
    }
    if (modalType === 'menu') {
        theModal = playerModalFromMenu;
        formParams.externalref = document.querySelector('.incourse-player').getAttribute('data-resourceref');
    }
    const serialFormParams = Utils.serialize(formParams);
    if (window.updateProgress) {
        await progressTracker.updateProgressAndActivity(); // Record any progress from the last player.
    }
    const bodyContent = theModal.getBody(serialFormParams);
    await theModal.modal.setBodyContent(bodyContent);
    pageFunctions.setWindowWatched();
}

/**
 * Prevent double clicks opening duplicate modals.
 */
function modalDoubleClickTimeout() {
    setTimeout(function() {
        window.buttonClicktimeOut = false;
    }, 600);
}