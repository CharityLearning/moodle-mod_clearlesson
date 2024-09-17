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

var player;
var firstLoad = 1;

export const init = () => {
    var position = 1;
    var url;

    window.openPlayer = (e, type) => {
        if (e.target.href.includes('clearlesson')) {
            e.preventDefault();
            const elementAncestor = e.target.closest('.activity');
            // Used in the progress tracker as well as on this page.
            window.cmid = elementAncestor.querySelector('.activityname a')
                                                .getAttribute('href').split('id=')[1];

            const searchParams = new URLSearchParams(window.location.search);
            url = e.target.href;
            window.courseid = searchParams.get('id'); // Used in the progress tracker as well as on this page.

            var instanceNameElement = elementAncestor.querySelector('.activityname .instancename');
            const span = instanceNameElement.querySelector('span');
            if (span) {
                instanceNameElement.removeChild(span);
            }
            const instanceName = instanceNameElement.innerText;

            const playerModal = new ModalForm({
                formClass: 'mod_clearlesson\\forms\\incourse_player_form',
                args: {cmid: window.cmid,
                        course: window.courseid,
                        url: url,
                        firstload: firstLoad},
                modalConfig: {title: instanceName},
            });

            player = playerModal;

            playerModal.addEventListener(playerModal.events.LOADED, function() {
                const modalRootInner = playerModal.modal.getRoot()[0].children[0];
                if (type !== 'play') {
                    // Set the modal to nearly fullscreen size.
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

                Utils.waitForElement('.incourse-player', modalRootInner, async function() {
                    // Store the resource reference for the progress tracker.
                    await updateProgressAndActivity();
                    window.resourceRef = document.querySelector('.incourse-player').getAttribute('data-resourceref');
                    window.type = document.querySelector('.incourse-player').getAttribute('data-type');
                    setWindowWatched();
                });

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
            await updateProgressAndActivity();
            position = parseInt(element.closest('.has-position').getAttribute('data-position'));
            reRenderPlayerModal(position, url);
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
    const bodyContent = player.getBody(serialFormParams);
    await player.modal.setBodyContent(bodyContent);
    setWindowWatched();
}

/**
 * Set the watched status of the window.
 */
function setWindowWatched() {
    const watchedCheck = document.querySelector('.incourse-player .player-column .watched-check');
    window.watched = !watchedCheck?.classList.contains('notwatched');
}