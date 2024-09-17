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
 * @module     mod_clearlesson/modal-resource-browser
 * @copyright  2024 Dan Watkins <dwatkins@charitylearning.org>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @since      2.9
 */

import ModalForm from 'core_form/modalform';
import {get_string as getString} from 'core/str';
import * as Utils from './utils';
import * as ResourceBrowser from './modal-resource-browser';

var currentPlayerForm;

export const init = () => {
    document.addEventListener('click', function(e) {
        var externalref, type;
        var position = 1;
        let element = e.target;
        if (element.classList?.contains('play-icon')
        || element.parentElement.classList?.contains('play-icon')
        || element.classList?.contains('video-player-link')
        || element.parentElement.classList?.contains('video-player-link')) {
            if (element.classList.contains('mini-player')) {
                return true;
            }
            e.preventDefault();
            externalref = element.getAttribute('data-externalref') ?? element.parentElement.getAttribute('data-externalref');
            const modals = document.getElementsByClassName('modal');
            if (modals.length > 1) {
                const videoListColumn = element.closest('.video-list-column');
                if (!videoListColumn) {
                    return false;
                }
                if (videoListColumn.hasAttribute('data-playlist')) {
                    position = parseInt(element.closest('.video-card-side').firstElementChild.innerText);
                    externalref = videoListColumn.getAttribute('data-playlist');
                    type = 'playlists';
                } else if (videoListColumn.hasAttribute('data-speaker')) {
                    externalref = videoListColumn.getAttribute('data-speaker');
                    position = parseInt(element.closest('.has-position').getAttribute('data-position'));
                    type = 'speakers';
                } else {
                    type = 'play';
                }
                reRenderResourcePlayer(externalref, position, type);
            } else {
                openResourcePlayer(externalref);
            }
        }
        const isSpeakerLink = element.classList?.contains('speaker-link');
        const isTopicLink = element.classList.contains('topic-tag');
        if (isSpeakerLink || isTopicLink) {
            e.preventDefault();
            if (isSpeakerLink) {
                externalref = element.getAttribute('data-speakers');
                if (externalref === 'CLC-Animation') {
                    type = 'collections';
                    externalref = 'Charity-Focused';
                } else {
                    type = 'speakers';
                }
            }
            if (isTopicLink) {
                externalref = element.getAttribute('data-externalref');
                type = 'topics';
            }
            if (isTopicLink || isSpeakerLink) {
                ResourceBrowser.setWaitingCursor(true, currentPlayerForm, false);
                // The timeout is required to allow the DOM to update before we start filtering.
                setTimeout(async function() {
                    await ResourceBrowser.loadResourcePageWithFilter(externalref, type);
                    ResourceBrowser.setWaitingCursor(false, currentPlayerForm, false);
                    currentPlayerForm.modal.hide();
                });
            }
            // Speaker links jsut filter the browser window videos page atm.
            // if (isSpeakerLink) {
            //     externalref = element.getAttribute('data-speakers');
            //     reRenderResourcePlayer(externalref, 1, 'speakers');
            // }
        }

        // Select the resource and close both modals.
        if (element.closest('button[data-action="select"]')) {
            e.preventDefault();
            const button = element.closest('button[data-action="select"]');
            const type = button.getAttribute('data-type');
            const externalref = button.getAttribute('data-externalref');
            ResourceBrowser.selectResource(externalref, type);
        }
        return true;
    });
};

/**
 * Open the resource player modal.
 *
 * @param {string} externalref The external reference of the resource
 */
const openResourcePlayer = (externalref) => {
    const resourceType = document.getElementsByClassName('disabled-looking')[0].getAttribute('data-type');
    const cmid = document.querySelector('input[name="coursemodule"]').value;
    const courseid = document.querySelector('input[name="course"]').value;
    const url = window.location.href;
    var titleString, selectString;
    if (resourceType === 'playlists') {
        titleString = getString('playlistviewer', 'mod_clearlesson');
        selectString = getString('selectplaylist', 'mod_clearlesson');
    }
    if (resourceType === 'play') {
        titleString = getString('videoplayer', 'mod_clearlesson');
        selectString = getString('selectvideo', 'mod_clearlesson');
    }
    if (resourceType === 'speakers') {
        titleString = getString('speakerviewer', 'mod_clearlesson');
        selectString = getString('selectspeaker', 'mod_clearlesson');
    }
    const playerForm = new ModalForm({
        formClass: 'mod_clearlesson\\forms\\resource_player_form',
        args: {type: resourceType, cmid: cmid, course: courseid, url: url, externalref: externalref, position: 1},
        modalConfig: {title: titleString, saveButtonText: selectString},
    });

    currentPlayerForm = playerForm;

    playerForm.addEventListener(playerForm.events.LOADED, async function() {
        // Set the modal to nearly fullscreen size.
        let modalHeight = Math.ceil(window.innerHeight * 0.94);
        let modalWidth = Math.ceil(window.innerWidth * 0.94);
        modalWidth = modalWidth > 1800 ? 1800 : modalWidth;
        const modalRootInner = playerForm.modal.getRoot()[0].children[0];
        modalRootInner.setAttribute('style',
            'width: ' + modalWidth +
            'px;max-width: ' + modalWidth +
            'px;height: ' + modalHeight +
            'px;max-height: ' + modalHeight + 'px;'
        );
        let saveString = await selectString;
        let backString = await getString('back');
        Utils.waitForElement('.modal-footer button.btn-primary', modalRootInner, function() {
            const saveButton = modalRootInner.querySelector('.modal-footer button.btn-primary');
            saveButton.innerHTML = saveString;
            saveButton.setAttribute('data-action', 'select');
            saveButton.setAttribute('data-type', resourceType);
            saveButton.setAttribute('data-externalref', externalref);
            saveButton.classList.add('d-block');
            const cancelButton = modalRootInner.querySelector('.modal-footer button.btn-secondary');
            cancelButton.innerHTML = backString;
        });
    });

    playerForm.show();
};

/**
 * Re-render the resource player modal.
 * @param {String} externalref
 * @param {Number} position
 * @param {String} type
 */
const reRenderResourcePlayer = async(externalref, position, type) => {
    const cmid = document.querySelector('input[name="coursemodule"]').value;
    const courseid = document.querySelector('input[name="course"]').value;
    const url = window.location.href;
    const formParams = {type: type, cmid: cmid, course: courseid, url: url, externalref: externalref, position: position};
    const serialFormParams = Utils.serialize(formParams);
    const bodyContent = currentPlayerForm.getBody(serialFormParams);
    await currentPlayerForm.modal.setBodyContent(bodyContent);
};
