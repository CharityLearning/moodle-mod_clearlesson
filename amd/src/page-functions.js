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
                window.watchedAll = true;
                window.updateProgress = false;
                if (!moodleCompleted) {
                    // If the completionwatchedall rule is enabled, we can mark the activity as complete.
                    const watchdAllRuleString = await getString('watchedallrule', 'mod_clearlesson');
                    if (ruleCheckElement.outerHTML.includes(watchdAllRuleString)) {
                        window.currentTime = 0;
                        window.extref = '';
                        progressTracker.updateProgressAndActivity(); // We ignore window.updateProgress here.
                    }
                }
            } else {
                window.watchedAll = false;
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
 * @param {Boolean} popup The popup flag.
 * @param {Boolean} player Is the modal for a player?
 */
export function setModalFullscreen(modalRootInner, popup = false, player = false) {
    var idealModalHeight, modalWidth, style, smallHeight;
    if (popup) {
        let oneRem = parseFloat(getComputedStyle(document.documentElement).fontSize);
        idealModalHeight = Math.ceil(window.innerHeight - oneRem);
        modalWidth = Math.ceil(window.innerWidth - oneRem);
    } else {
        idealModalHeight = Math.ceil(window.innerHeight * 0.94);
        modalWidth = Math.ceil(window.innerWidth * 0.94);
    }

    const playerRatio = 1481 / 833;
    const extraHeight = 278;
    modalWidth = modalWidth > 1800 ? 1800 : modalWidth;
    // We adjust the modal width for small height windows.
    if (player && window.innerHeight < 915) {
        const playerWidth = Math.floor(((modalWidth - 96) / 7) * 6);
        const playerHeight = playerWidth / playerRatio;
        let actualModalHeight = playerHeight + extraHeight;
        let titleBarHeight = 100;
        if (actualModalHeight > idealModalHeight) {
            smallHeight = true;
            // Adust the modalWidth to fit the player.
            let actualPlayerWidth = (idealModalHeight - extraHeight) * playerRatio;
            let actualPlayerHeight = (idealModalHeight + titleBarHeight) - extraHeight;
            modalWidth = Math.floor(((actualPlayerWidth / 6) * 7));
            Utils.waitForElement('.player-row', modalRootInner, function() {
                modalRootInner.querySelector('.modal-body')
                .setAttribute('style', 'padding-top:0rem!important;padding-bottom:0!important;display:flex;align-items:center;');
                const playerRow = modalRootInner.querySelector('.player-row');
                playerRow.classList.add('reduced-width');
                playerRow.setAttribute('style', 'height: ' + actualPlayerHeight + 'px!important;');
                playerRow.children.forEach(function(element) {
                    element.setAttribute('style', 'height: ' + (actualPlayerHeight - 2) + 'px!important;');
                });
            });
        }
    }

    style = '';
    if (window.innerWidth > 576) {
        style += 'width: ' + modalWidth +
                'px;max-width: ' + modalWidth + 'px;';
    }
    if (!player) {
        style += 'height: ' + idealModalHeight +
                'px;max-height: ' + idealModalHeight + 'px;';
    }
    if (player && !smallHeight) {
        style += 'height: auto';
    }
    if (player && smallHeight) {
        style += 'height: ' + idealModalHeight + 'px!important;';
    }
    modalRootInner.setAttribute('style', style);
}

/**
 * Adjust the modal width for low height windows. (When compared to the width).
 * @param {HTMLElement} modalRootInner
 */
export function adjustModalWidthForLowHeights(modalRootInner) {
    var style, adjusteModaldWidth;
    if (window.innerWidth < 992) {
        return;
    }
    const modalHeight = Math.floor(window.innerHeight - 56);

    const playerRatio = 1481 / 833;
    const extraHeight = 250;
    if (window.innerHeight < 700) {
        // Adust the modalWidth.
        let playerHeight = modalHeight - extraHeight;
        adjusteModaldWidth = playerHeight * playerRatio;
        Utils.waitForElement('.player-row', modalRootInner, function() {
            let styleInner = '';
            styleInner += 'padding-top:0.5rem!important;padding-bottom:0.5rem!important;display:flex;align-items:center;';
            styleInner += 'justify-content: center;';
            modalRootInner.querySelector('.modal-body')
            .setAttribute('style', styleInner);
        });
    }

    style = 'width: ' + adjusteModaldWidth + 'px;max-width: ' + adjusteModaldWidth + 'px;';
    modalRootInner.setAttribute('style', style);

}

/**
 * Open the player modal from the menu item.
 * @param {Event} e The event object.
 * @param {string} url The URL to use for the AJAX call.
 * @param {number} firstLoad The first load flag.
 * @param {string} backString The string to use for the back button.
 */
export async function openPlayerFromMenu(e, url, firstLoad, backString) {
    var playerModalFromMenu;
    const elementAncestor = e.target.closest('.menu-item');
    const instanceName = elementAncestor.querySelector('.menu-item-title > .searchable').innerHTML;
    const externalRef = elementAncestor.getAttribute('data-externalref');
    if (window.updateProgress) {
        await progressTracker.updateProgressAndActivityPromise(); // Record any progress from the last player.
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
        if (window.innerWidth < 577) { // Video modals should fit the video player.
            modalRootInner.setAttribute('style', 'height: unset!important;');
        }
        const isPopup = document.querySelector('body.mod-clearlesson-popup') ? true : false;
        setModalFullscreen(modalRootInner, isPopup, true);
        setModalBodyGrey(modalRootInner);

        Utils.waitForElement('.incourse-player', modalRootInner, async function() {
            await setWindowWatched();
            firstLoad = 0;
        });
        setModalButtons(modalRootInner, backString);
        removeLoadingClasses(modalRootInner);
        modalRootInner.closest('.modal').classList.add('clearlesson-player');
    });

    playerModalFromMenu.show();
    return playerModalFromMenu;
}

/**
 * Open a new menu modal. This will be a menu of series for a collection.
 * @param {Event} e The event object.
 * @param {string} url The URL to use for the AJAX call.
 * @param {number} firstLoad The first load flag.
 * @param {string} backString The string to use for the back button.
 */
export async function openNewMenuModal(e, url, firstLoad, backString) {
    var newMenuModal;
    const menuItem = e.target.closest('.menu-item');
    const externalRef = menuItem.getAttribute('data-externalref');
    const instanceName = menuItem.querySelector('.menu-item-title > .searchable').innerHTML;
    const isPopup = document.querySelector('body.mod-clearlesson-popup') ? true : false;
    if (window.updateProgress) {
        await progressTracker.updateProgressAndActivityPromise(); // Record any progress from the last player.
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
        if (isPopup) {
            setModalFullscreen(modalRootInner, isPopup);
        }
        setModalBodyGrey(modalRootInner);
        setModalButtons(modalRootInner, backString);
        window.updateProgress = false;
        removeLoadingClasses(modalRootInner);
    });

    newMenuModal.show();
    return newMenuModal;
}

/**
 * Set the watched status of the window.
 */
export async function setWindowWatched() {
    return new Promise((resolve) => {
        Utils.waitForElement('.incourse-player .player-column .video-title-wrapper', document, function() {
            const watchedCheck = document.querySelector('.incourse-player .player-column .speakerinfo .watched-check');
            // If the video has been watched already dont update the progress.
            window.updateProgress = watchedCheck?.classList.contains('notwatched');
            resolve();
        });
    });
}

/**
 * Set the modal body to grey background.
 * @param {HTMLelement} modalRootInner The root of the modal.
 */
export function setModalBodyGrey(modalRootInner) {
    modalRootInner.querySelector('.modal-body').classList.add('mod-clearlesson-backgrounddgrey');
}

/**
 * This function is called inline by the play next button.
 */
window.playNextItem = function() {
    var nextVideo;
    const currentVideoSpan = document.querySelector('.video-card-side span[data-externalref="' + window.extref + '"]');
    if (currentVideoSpan) {
        nextVideo = currentVideoSpan.closest('.video-card-side').nextElementSibling;
    } else {
        // This is a speaker or a topic list. The current video is not in the list.
        let nextVideoPosition = progressTracker.getNextVideoPosition();
        nextVideo = document.querySelector('.video-card-side[data-position="' + nextVideoPosition + '"]');
    }
    if (nextVideo) {
        window.playNext = true;
        window.dontPauseNext = true;
        nextVideo.querySelector('span').click();
    }
};

/**
 * Remove loading classes.
 *
 * @param {HTMLelement} watchElement The element to watch for.
 */
export function removeLoadingClasses(watchElement) {
    Utils.waitForElement('.loading', watchElement, function() {
        setTimeout(function() {
            watchElement.querySelectorAll('.loading').forEach(function(element) {
                element.classList.remove('loading');
            });
        }, 100);
    });
}
