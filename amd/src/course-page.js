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

var player;

export const init = () => {
    var position = 1;
    var coursModuleId, courseid, url;
    window.openPlayer = (e) => {
        if (e.target.href.includes('clearlesson')) {
            e.preventDefault();
            const elementAncestor = e.target.closest('.activity');
            coursModuleId = elementAncestor.querySelector('.activityname a')
                                                .getAttribute('href').split('id=')[1];

            const searchParams = new URLSearchParams(window.location.search);
            url = e.target.href;
            courseid = searchParams.get('id');

            var instanceNameElement = elementAncestor.querySelector('.activityname .instancename');
            const span = instanceNameElement.querySelector('span');
            if (span) {
                instanceNameElement.removeChild(span);
            }
            const instanceName = instanceNameElement.innerText;

            const playerModal = new ModalForm({
                formClass: 'mod_clearlesson\\forms\\incourse_player_form',
                args: {cmid: coursModuleId, course: courseid, url: url},
                modalConfig: {title: instanceName},
            });

            player = playerModal;

            playerModal.addEventListener(playerModal.events.LOADED, function() {
                // Set the modal to nearly fullscreen size.
                let modalHeight = Math.ceil(window.innerHeight * 0.94);
                let modalWidth = Math.ceil(window.innerWidth * 0.94);
                modalWidth = modalWidth > 1800 ? 1800 : modalWidth;
                const modalRootInner = playerModal.modal.getRoot()[0].children[0];
                modalRootInner.setAttribute('style',
                    'width: ' + modalWidth +
                    'px;max-width: ' + modalWidth +
                    'px;height: ' + modalHeight +
                    'px;max-height: ' + modalHeight + 'px;'
                );
            });

            playerModal.show();
        }
    };

    document.addEventListener('click', function(e) {
        const element = e.target;
        if (element.classList?.contains('play-icon')
        || element.parentElement.classList?.contains('play-icon')
        || element.classList?.contains('video-player-link')
        || element.parentElement.classList?.contains('video-player-link')) {
            e.preventDefault();
            position = parseInt(element.closest('.has-position').getAttribute('data-position'));
            reRenderPlayerModal(position, coursModuleId, courseid, url);
        }
    });
};

/**
 * Open the player modal.
 *
 * @param {Number} position The position of the resource in the list.
 * @param {Number} coursModuleId The course module ID.
 * @param {Number} courseid The course ID.
 * @param {String} url The URL of the resource.
 */
async function reRenderPlayerModal(position, coursModuleId, courseid, url) {
    const formParams = {cmid: coursModuleId, course: courseid, url: url, position: position};
    const serialFormParams = Utils.serialize(formParams);
    const bodyContent = player.getBody(serialFormParams);
    await player.modal.setBodyContent(bodyContent);
}