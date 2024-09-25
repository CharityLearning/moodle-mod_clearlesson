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
 * @module     mod_clearlesson/module-page
 * @copyright  2024 Dan Watkins <dwatkins@charitylearning.org>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @since      2.9
 */

import * as Utils from './utils';
import {get_string as getString} from 'core/str';
import Ajax from 'core/ajax';
import Templates from 'core/templates';
import * as progressTracker from './progress-tracker';
import * as pageFunctions from './page-functions';

// eslint-disable-next-line no-unused-vars
var url, backString, outputType, playerModalFromMenu, newMenuModal, completionDropdown;
var firstLoad = 1;

require(['../../../mod/clearlesson/vimeo/vimeo-sdk'], function(VimeoPlayerConstructor) {
    window.VimeoPlayerConstructor = VimeoPlayerConstructor;
});

export const init = async(type) => {
    window.updateProgress = true;
    window.pageType = 'activity';
    var position = 1;
    backString = await getString('back');
    url = window.location.href;

    const searchParams = new URLSearchParams(window.location.search);
    window.cmid = searchParams.get('id');
    window.courseid = document.getElementById('clearlesson-courseid').getAttribute('data-courseid');

    if (type === 'series' || type === 'collections') {
        outputType = 'menu';
    } else {
        outputType = 'player';
    }

    const completionInfoElement = document.querySelector('.automatic-completion-conditions');
    const clearlessonElement = document.querySelector('.mod-clearlesson');
    if (outputType === 'player') {
        Utils.waitForElement('.incourse-player', clearlessonElement, async function() {
            // Store the resource reference and resource type for the progress tracker.
            // This is only set from the base layer.
            window.resourceRef = clearlessonElement.querySelector('.incourse-player').getAttribute('data-resourceref');
            window.type = clearlessonElement.querySelector('.incourse-player').getAttribute('data-type');
            pageFunctions.updateCompletionStatusIfIncorrect(completionInfoElement, clearlessonElement);
            pageFunctions.setWindowWatched();
        });
    }

    if (outputType === 'menu') {
        Utils.waitForElement('.incourse-menu', clearlessonElement, async function() {
            // Store the resource reference for the progress tracker.
            // This is only set from the base layer.
            window.resourceRef = clearlessonElement.querySelector('.incourse-menu').getAttribute('data-resourceref');
            window.type = clearlessonElement.querySelector('.incourse-menu').getAttribute('data-type');
            pageFunctions.updateCompletionStatusIfIncorrect(completionInfoElement, clearlessonElement);
            window.updateProgress = false;
        });
    }

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
                    playerModalFromMenu = await pageFunctions.openPlayerFromMenu(e, url, firstLoad, completionDropdown, backString);
                } else {
                    // For collections we'll open the series menu.
                    newMenuModal = await pageFunctions.openNewMenuModal(e, url, firstLoad, completionDropdown);
                }
            } else {
                position = parseInt(element.closest('.has-position').getAttribute('data-position'));
                if (element.closest('.modal-body')) {
                    reRenderModulePlayerModal(position, url);
                } else {
                    reRenderPlayer(position);
                }
            }
        }
    });
};

/**
 * Rerender the in-page player.
 *
 * @param {Number} position The position of the resource in the list.
 * @param {String} url The URL of the resource.
 */
async function reRenderPlayer(position, url) { // TODO rewrite this for page output.
    if (window.updateProgress) {
        await progressTracker.updateProgressAndActivity(); // Record any progress from the last player.
    }
    const data = await getPlayerRenderable(position, url);
    const {html, js} = await Templates.renderForPromise('mod_clearlesson/incourse_player', data.response);

    const pageContainer = document.getElementById('clearlesson-page-container');
    pageContainer.innerHTML = html;

    await Templates.runTemplateJS(js);
    progressTracker.init();
    pageFunctions.setWindowWatched();
}

/**
 * Get player renderable.
 * @param {Number} position The position of the resource in the list.
 */
function getPlayerRenderable(position) {
    const request = {
        methodname: 'mod_clearlesson_get_player_renderable',
        args: {cmid: window.cmid,
                course: window.courseid,
                position: position
            }
    };

    return Ajax.call([request])[0];
}

/**
 * Open the player modal.
 *
 * @param {Number} position The position of the resource in the list.
 * @param {String} url The URL of the resource.
 */
async function reRenderModulePlayerModal(position, url) {
    if (window.updateProgress) {
        await progressTracker.updateProgressAndActivity(); // Record any progress from the last player.
    }
    const externalref = document.querySelector('.incourse-player').getAttribute('data-resourceref');
    const formParams = {cmid: window.cmid,
                        course: window.courseid,
                        url: url,
                        position: position,
                        firstload: 0,
                        externalref: externalref};
    const serialFormParams = Utils.serialize(formParams);
    const bodyContent = playerModalFromMenu.getBody(serialFormParams);
    await playerModalFromMenu.modal.setBodyContent(bodyContent);
    pageFunctions.setWindowWatched();
}

