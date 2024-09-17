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

import ModalSaveCancel from 'core/modal_save_cancel';
import Templates from 'core/templates';
import {get_string as getString} from 'core/str';
import * as Utils from './utils';

var modalVideoPlayer;

export const init = () => {
    document.addEventListener('click', function(e) {
        var element;
        if (e.target?.classList?.contains('play-icon')) {
            element = e.target;
        } else if (e.target?.parentElement.classList?.contains('play-icon')) {
            element = e.target.parentElement;
        }

        if (element) {
            e.preventDefault();
            var name;
            if (element.closest('.menu-item-card')) {
                name = element.closest('.menu-item-card').querySelector('.card-title').innerText;
            } else {
                name = element.closest('.video-card-wrapper').querySelector('.card-title').innerText;
            }
            const src = element.getAttribute('data-src');
            const externalref = element.getAttribute('data-externalref');
            openVideoPlayer(externalref, name, src);
        }
    });
};

/**
 * Open the video player modal.
 *
 * @param {string} externalref
 * @param {string} name
 * @param {string} src
 */
async function openVideoPlayer(externalref, name, src) {
    const {html, js} = await Templates.renderForPromise('mod_clearlesson/elements/video_iframe', {
        externalref: externalref,
        src: src,
        name: name,
        editform: true
    });

    const videoPlayer = await ModalSaveCancel.create({
        title: getString('videoplayer', 'mod_clearlesson'),
        body: html,
        large: true,
        show: true,
    });

    Templates.runTemplateJS(js);

    modalVideoPlayer = videoPlayer;
    const backString = await getString('back');
    const selectVideoString = await getString('selectvideo', 'mod_clearlesson');
    const modalRootInner = modalVideoPlayer.getRoot()[0].children[0];
    Utils.waitForElement('.modal-footer button.btn-secondary', modalRootInner, function() {
        const cancelButton = modalRootInner.querySelector('.modal-footer button.btn-secondary');
        cancelButton.innerHTML = backString;
    });
    Utils.waitForElement('.modal-footer button.btn-primary', modalRootInner, function() {
        const saveButton = modalRootInner.querySelector('.modal-footer button.btn-primary');
        saveButton.innerHTML = selectVideoString;
        saveButton.setAttribute('data-action', 'select');
        saveButton.setAttribute('data-type', 'play');
        saveButton.setAttribute('data-externalref', externalref);
    });

    videoPlayer.show();
}