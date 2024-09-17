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

export const init = async() => {
    if (typeof window.player !== 'undefined') {
        await window.player.destroy(); // We destroy the previous player before creating a new one.
        delete window.player;
    }

    const iframe = document.querySelector('.video-container > iframe.videoframe');
    window.player = new window.VimeoPlayerConstructor(iframe);

    window.extref = await window.player.getVideoTitle().then(function(title) {
        return title;
    });

    // When progress is updated, if the video has a higher status already, we don't update it.
    window.viewedStatus = 'unwatched';
    window.player.on('play', function() {
        if (window.viewedStatus === 'unwatched') {
            window.viewedStatus = 'inprogress';
        }
    });

    window.player.on('ended', async function() {
        if (!window.watched) {
            videoWatched();
            await updateProgressAndActivity();
        }
    });

    window.currentTime = 0;
    window.player.on('timeupdate', function(data) {
        window.player.getDuration().then(async function(duration) {
            // If 90% of the video has been watched, we update the status to 'watched'.
            if (data.seconds / duration >= 0.9) {
                if (!window.watched) {
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
        if (!window.watched) {
            await updateProgressAndActivity();
        }
    }, true);

    // Every 30 minutes we update the progress.
    setInterval(async function() {
        if (!window.watched) {
            await updateProgressAndActivity();
        }
    }, 1800000);
};

/**
 * Update the progress and status of the video.
 *
 * @return {Promise} The promise with the response of the AJAX call.
 */
const updateProgress = async() => {
    if (typeof window.extref === 'undefined'
        || typeof window.viewedStatus === 'undefined' || typeof window.currentTime === 'undefined'
        || typeof window.courseid === 'undefined' || typeof window.cmid === 'undefined'
        || typeof window.resourceRef === 'undefined' || typeof window.type === 'undefined') {
        return false;
    }

    const request = {
                        methodname: 'mod_clearlesson_update_progress',
                        args: {
                            externalref: window.extref,
                            status: window.viewedStatus,
                            duration: window.currentTime,
                            courseid: window.courseid,
                            cmid: window.cmid,
                            resourceref: window.resourceRef,
                            type: window.type
                        }
                    };

    return Ajax.call([request])[0];
};

/**
 * The video has been watched, update the status and reveal the watched check.
 */
function videoWatched() {
    window.viewedStatus = 'watched';
    window.watched = true;
    document.querySelector('.video-title-wrapper .watched-check').classList.remove('notwatched');
    const playlistVideoLink = document.querySelector('.video-card-side a[data-externalref="' + window.extref + '"]');
    if (playlistVideoLink) {
       playlistVideoLink.closest('.video-card-side').querySelector('.watched-check').classList.remove('notwatched');
    }
}

/**
 * Update the progress of the video and then the activity module in the course page if required.
 */
export async function updateProgressAndActivity() {
    const response = await updateProgress();
    if (response?.activitymodulehtml) {
        const activity = document.querySelector('.activity[data-id="' + window.cmid + '"]');
        if (activity) {
            activity.outerHTML = response.activitymodulehtml;
        }
    }
}