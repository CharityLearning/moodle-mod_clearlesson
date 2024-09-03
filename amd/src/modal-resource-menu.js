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
 * @module     mod_clearlesson/modal-resource-menu
 * @copyright  2024 Dan Watkins <dwatkins@charitylearning.org>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @since      2.9
 */

import ModalForm from 'core_form/modalform';
import {get_string as getString} from 'core/str';
import * as Utils from './utils';

// eslint-disable-next-line no-unused-vars
var currentMenuForm;
export const init = () => {
    document.addEventListener('click', function(e) {
        var element = e.target;
        var speakerLink = false;
        var externalref;
        // This is the eye icon on non-video resources in the resource menu.
        if (element.classList?.contains('view-icon')
        || element.classList?.contains('view-link')) {
            externalref = element.getAttribute('data-externalref');
        }
        // This is also the eye icon on non-video resources in the resource menu.
        if (element.parentElement.classList?.contains('view-icon')
        || element.parentElement.classList?.contains('view-link')) {
            externalref = element.parentElement.getAttribute('data-externalref');
        }

        // This is the {{count}} videos or playlists etc link in the resource browser.
        if ((element.classList?.contains('video-details-view'))
        && !element.classList?.contains('view-link')) {
            if (element.hasAttribute('data-view')) {
                externalref = element.getAttribute('data-view');
            } else {
                externalref = element.parentElement.getAttribute('data-view');
            }
        }

        // This is the speaker link in the video card.
        if (element.classList?.contains('vid-speaker-link')) {
            externalref = element.closest('.video-card-wrapper').getAttribute('data-speakers');
            speakerLink = true;
        }

        if (externalref) {
            e.preventDefault();
            if (element.classList.contains('view-link')) {
                const type = document.getElementById('menu-container').getAttribute('data-itemtype');
                const card = element.closest('.menu-item-title');
                const name = card.querySelector('.card-title').innerText.trim();
                reRenderResourceMenu(externalref, name, type);
            } else {
                const card = element.closest('.video-card-wrapper');
                var name;
                if (speakerLink) {
                    name = element.innerText;
                } else {
                    name = card.querySelector('.card-title').innerText;
                }
                openResourceMenu(externalref, name, speakerLink);
            }
        }
    });
};

/**
 * Open the resource menu modal.
 * @param {String} externalref
 * @param {String} name
 * @param {Boolean} speaker
 */
async function openResourceMenu(externalref, name, speaker = false) {
    var resourceType;
    if (speaker) {
        resourceType = 'speakers';
    } else {
        resourceType = document.getElementsByClassName('disabled-looking')[0].getAttribute('data-type');
    }
    const cmid = document.querySelector('input[name="coursemodule"]').value;
    const courseid = document.querySelector('input[name="course"]').value;
    const url = window.location.href;
    const {titleString, selectString} = await getStringsFromResourceType(resourceType, name);

    const menuForm = new ModalForm({
        formClass: 'mod_clearlesson\\forms\\resource_menu_form',
        args: {type: resourceType, cmid: cmid, course: courseid, url: url, externalref: externalref},
        modalConfig: {title: titleString, saveButtonText: selectString},
    });

    currentMenuForm = menuForm;

    menuForm.addEventListener(menuForm.events.LOADED, async function() {
        // Set the modal to nearly fullscreen size.
        let modalHeight = Math.ceil(window.innerHeight * 0.94);
        let modalWidth = Math.ceil(window.innerWidth * 0.94);
        modalWidth = modalWidth > 1800 ? 1800 : modalWidth;
        const modalRootInner = menuForm.modal.getRoot()[0].children[0];
        modalRootInner.setAttribute('style',
            'width: ' + modalWidth +
            'px;max-width: ' + modalWidth +
            'px;height: ' + modalHeight +
            'px;max-height: ' + modalHeight + 'px;'
        );

        setSaveButton(await selectString, resourceType, externalref);
    });

    menuForm.show();
}

/**
 * Re-render the resource menu modal.
 *
 * @param {String} externalref
 * @param {String} name
 * @param {String} type
 */
async function reRenderResourceMenu(externalref, name, type) {
    const {titleString, selectString} = await getStringsFromResourceType(type, name);
    const cmid = document.querySelector('input[name="coursemodule"]').value;
    const courseid = document.querySelector('input[name="course"]').value;
    const url = window.location.href;
    const formParams = {type: type, cmid: cmid, course: courseid, url: url, externalref: externalref};
    const serialFormParams = Utils.serialize(formParams);
    const bodyContent = currentMenuForm.getBody(serialFormParams);
    // Set the title
    await currentMenuForm.modal.setTitle(titleString);
    await currentMenuForm.modal.setBodyContent(bodyContent);
    setSaveButton(await selectString, type, externalref);
}

/**
 * Get the title and select strings for the resource menu modal.
 *
 * @param {String} resourceType
 * @param {String} name
 */
async function getStringsFromResourceType(resourceType, name) {
    var titleString, selectString;
    switch (resourceType) {
        case 'playlists':
            titleString = await getString('playlistmenu', 'mod_clearlesson');
            titleString = "'" + name + "' " + titleString;
            selectString = getString('selectplaylist', 'mod_clearlesson');
            break;
        case 'speakers':
            titleString = await getString('videos', 'mod_clearlesson');
            titleString = name + "'s " + titleString;
            selectString = getString('selectspeaker', 'mod_clearlesson');
            break;
        case 'topics':
            titleString = await getString('videos', 'mod_clearlesson');
            titleString = "'" + name + "' " + titleString;
            selectString = getString('selecttopic', 'mod_clearlesson');
            break;
        case 'series':
            titleString = await getString('seriesmenu', 'mod_clearlesson');
            titleString = "'" + name + "' " + titleString;
            selectString = getString('selectseries', 'mod_clearlesson');
            break;
        case 'collections':
            titleString = await getString('collectionmenu', 'mod_clearlesson');
            titleString = "'" + name + "' " + titleString;
            selectString = getString('selectcollection', 'mod_clearlesson');
            break;
    }
    return {titleString, selectString};
}

/**
 * Set the save button for the resource menu modal.
 * @param {String} saveString
 * @param {String} type
 * @param {String} externalref
 */
async function setSaveButton(saveString, type, externalref) {
    const modalRootInner = currentMenuForm.modal.getRoot()[0].children[0];
    Utils.waitForElement('.modal-footer button.btn-primary', modalRootInner, function() {
        const saveButton = modalRootInner.querySelector('.modal-footer button.btn-primary');
        saveButton.innerHTML = saveString;
        saveButton.setAttribute('data-action', 'select');
        saveButton.setAttribute('data-type', type);
        saveButton.setAttribute('data-externalref', externalref);
        saveButton.classList.add('d-block');
    });
}
