/* eslint-disable promise/catch-or-return */
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
 * Progress tracker for mod_clearlesson.
 *
 * @module     mod_clearlesson/progress-tracker
 * @copyright  2024 Dan Watkins <dwatkins@charitylearning.org>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @since      2.9
 */

import Ajax from 'core/ajax';

var loopCount = 0;

export const init = async() => {
    if (typeof window.VimeoPlayerConstructor === 'undefined') {
        setTimeout(init, 100);
        return;
    }
    if (typeof window.player !== 'undefined') {
        await window.player.destroy(); // We destroy the previous player before creating a new one.
        delete window.player;
    }

    const iframe = document.querySelector('.video-container > iframe.videoframe');

    window.player = new window.VimeoPlayerConstructor(iframe);

    window.extref = await window.player.getVideoTitle().then(function(title) {
        return title;
    });

    const container = iframe.closest('.incourse');
    if (container?.getAttribute('data-watchedall') == '1') {
        // If all videos in the resource have already been watched, we don't update the progress.
        // If the resource has been watched but the completion status is incorrect,
        // we'll update it in moodle via the page-functions method updateCompletionStatusIfIncorrect.
        window.watchedAll = true;
        window.updateProgress = false;
    } else {
        window.watchedAll = false;
        window.updateProgress = true;
    }

    window.viewedStatus =
        container?.querySelector('.video-title-wrapper .watched-check')?.classList
            .contains('notwatched') ? 'unwatched' : 'watched';

    // When progress is updated, if the video has a higher status already, we don't update it.
    window.player.on('play', function() {
        if (window.viewedStatus === 'unwatched') {
            window.viewedStatus = 'inprogress';
        }
    });

    window.player.on('ended', async function() {
        if (window.updateProgress) {
            videoWatched();
            updateProgressAndActivity();
        }
    });

    window.currentTime = 0;
    window.player.on('timeupdate', function(data) {
        window.player.getDuration().then(async function(duration) {
            // If 90% of the video has been watched, we update the status to 'watched'.
            if (data.seconds / duration >= 0.9) {
                if (window.updateProgress) {
                    videoWatched();
                    updateProgressAndActivity();
                }
            }
            return true;
        });
        // Round off and provide as integer.
        window.currentTime = Math.round(data.seconds);
    });

    // Before the user leaves the page, we update the progress.
    window.addEventListener('beforeunload', async function() {
        if (window.updateProgress) {
            updateProgressAndActivity();
        }
    }, true);

    // Every 30 minutes we update the progress.
    setInterval(async function() {
        if (window.updateProgress) {
            updateProgressAndActivity();
        }
    }, 1800000);
};

/**
 * Update the progress and status of the video.
 *
 * @return {Promise} The promise with the response of the AJAX call.
 */
const updateProgress = async() => {
    if (typeof window.viewedStatus === 'undefined'
        && (window.type === 'series' || window.type === 'collections')) {
        window.viewedStatus = (window.watchedAll) ? 'watched' : 'unwatched';
    }

    if (typeof window.extref === 'undefined'
        || typeof window.currentTime === 'undefined' || typeof window.viewedStatus === 'undefined'
        || typeof window.courseid === 'undefined' || typeof window.cmid === 'undefined'
        || typeof window.resourceRef === 'undefined' || typeof window.type === 'undefined'
        || typeof window.pageType === 'undefined' || typeof window.watchedAll === 'undefined') {
        return false;
    }
    loopCount = 0;

    const args = {
        externalref: window.extref,
        status: window.viewedStatus,
        duration: window.currentTime,
        courseid: window.courseid,
        cmid: window.cmid,
        resourceref: window.resourceRef,
        type: window.type,
        pagetype: window.pageType,
        watchedall: window.watchedAll
    };

    const request = {
                        methodname: 'mod_clearlesson_update_progress',
                        args: args
                    };

    return Ajax.call([request])[0];
};

/**
 * The video has been watched, update the status and reveal any watched checks relevant in the DOM.
 */
function videoWatched() {
    window.viewedStatus = 'watched';
    window.updateProgress = false;
    document.querySelector('.video-title-wrapper .watched-check').classList.remove('notwatched');
    const playlistVideoLink = document.querySelector('.video-card-side a[data-externalref="' + window.extref + '"]');
    if (playlistVideoLink) {
        playlistVideoLink.closest('.video-card-side').querySelector('.watched-check').classList.remove('notwatched');
        const otherVideosColumn = playlistVideoLink.closest('.box');
        let watchedAll = true;
        otherVideosColumn.querySelectorAll('.watched-check').forEach(function(watchedCheck) {
            if (watchedCheck.classList.contains('notwatched')) {
                watchedAll = false;
            }
        });
        if (watchedAll) {
            const itemRef = playlistVideoLink.closest('.incourse-player').getAttribute('data-resourceref');
            menuItemWatched(itemRef);
        }
    }
}

/**
 * All videos in a menu item have been watched, update the menuitem by removing the notwatched class.
 * @param {String} itemRef The externalref of the menu item.
 * @param {Boolean} direct Is this the direct parent menu?
 * We update the parent menu items by resource ref but sometimes 2 parents
 * eg playlist & series will have the same resource ref. Direct determines which parent to update.
 */
export function menuItemWatched(itemRef, direct = true) {
    var domPosition;
    const menuItems = document.querySelectorAll('.menu-item[data-externalref="' + itemRef + '"]');
    if (!menuItems) {
        return false;
    }
    if (direct) {
        domPosition = menuItems.length - 1;
    } else {
        domPosition = 0;
    }
    menuItems.forEach(function(singleItem, index) {
        // The last menu item is the one we want to update as this will be the menuItemParent of the player.
        if (index === domPosition) {
            singleItem.querySelector('.watched-check').classList.remove('notwatched');
            // If all menu items in the parent have been watched, we update the parents parent if it is present.
            const parentMenu = singleItem.closest('.menu-container');
            if (parentMenu) {
                let watchedAll = true;
                parentMenu.querySelectorAll('.watched-check').forEach(function(watchedCheck) {
                    if (watchedCheck.classList.contains('notwatched')) {
                        watchedAll = false;
                    }
                });
                if (watchedAll) {
                    const parentResourceRef = parentMenu.getAttribute('data-resourceref');
                    if (direct) { // There will only be ever be 2 parents.
                        // No infinite loops please.
                        menuItemWatched(parentResourceRef, false);
                    }
                }
            }
        }
    });
    return true;
}

/**
 * Update the progress of the video and then the activity module in the course page if required.
 */
export async function updateProgressAndActivity() {
    var activityInfo;
    const response = await updateProgress();
    if (!response) {
        if (loopCount++ < 20) {
            setTimeout(updateProgressAndActivity, 100);
            return false;
        }
    }
    if (response?.activitymodulehtml) {
        if (window.pageType === 'course') {
            activityInfo = document.querySelector('.activity[data-id="' + window.cmid + '"]');
        }
        if (window.pageType === 'activity') {
            activityInfo = document.querySelector('.activity-information');
        }
        if (activityInfo) {
            activityInfo.outerHTML = response.activitymodulehtml;
        }
        window.watchedAll = true;
        document.querySelector('.incourse')?.setAttribute('data-watchedall', '1');
    }
    return true;
}
