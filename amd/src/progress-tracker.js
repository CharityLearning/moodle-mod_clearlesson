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
import * as Utils from './utils';

var beforeUnloadEventSet = false;
var iframeHidden = false;
var loopCount = 0;
var dontPause = false;
var reset = false;

export const init = async() => {
    if (typeof window.players === 'undefined' || !window.playNext) {
        window.players = [];
        window.iFrames = [];
    }

    var iframe;
    window.currentTime = 0;

    // Convert return key press to a click events.
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Enter' && document.activeElement.id.substring(0, 15) !== 'id_inplacevalue') {
            e.preventDefault();
            document.activeElement.click();
        }
    });

    // Record progress on modal close if required
    if (typeof window.closeModalEventset === 'undefined') {
        window.closeModalEventset = true;
        document.addEventListener('click', function(e) {
            if (e.target.innerHTML.trim() === '×'
                || e.target.getAttribute('data-action') === 'cancel') {
                const playerModalOpen = document.querySelector('.modal.clearlesson-player.show');
                if (window.updateProgress && playerModalOpen) {
                    updateProgressAndActivity();
                }
            }
        }, true);

        document.addEventListener('keydown', function(e) {
            const playerModalOpen = document.querySelector('.modal.clearlesson-player.show');
            if (e.key === 'Escape' && window.updateProgress && playerModalOpen) {
                updateProgressAndActivity();
            }
        }, true);
    }

    const videoContainer = document.querySelector('.video-container');
    window.extref = videoContainer.getAttribute('data-externalref');

    if (typeof window.VimeoPlayerConstructor === 'undefined') {
        setTimeout(init, 50);
        return;
    }

    // Set up our iframe and player.
    iframe = document.getElementsByClassName('videoframe')[0];
    if (!window.playNext) {
        let player = await new window.VimeoPlayerConstructor(iframe);
        window.players.push(player);
        window.iFrames.push(iframe);
    } else {
        // Remove unused iframs and players.
        if (window.players.length > 0) {
            for (let i = 0; i < (window.players.length - 1); i++) {
                window.iFrames.shift();
                let player = window.players.shift();
                player.destroy();
            }
        }
    }

    if (!window.playNext) {
        setTimeout(async function() {
            await window.players[0].play();
            if (!dontPause) {
                await window.players[0].pause();
            }
        });
    }

    var longIdString = Math.random().toString(36).substring(2, 15) + Math.random().toString(36).substring(2, 15);

    // Wait for the player to render before we continue.
    Utils.waitForElement('.speakerinfo .watched-check', document, async function() {
        if (window.playNext) {
            let currentiFrame = document.getElementsByClassName('videoframe')[0];
            hideIframe(currentiFrame);
            currentiFrame.replaceWith(window.iFrames[0]);
        }

        // Lets take the thumbnail and put it over the video container.
        // This will hide the loading screen.
        const videoContainerHeight = videoContainer.offsetHeight;
        const thumbImage = document.getElementById('loading-thumb');
        thumbImage.style.height = videoContainerHeight + 'px';
        // Now lets add a play icon on top of the thumbnail.
        const playIconDiv = document.getElementById('play-icon-div');
        playIconDiv.style.height = videoContainerHeight + 'px';
        const playIcon = document.getElementById('play-video-button');

        /* Preload the 'nextvideo'  player */
        let nextIframe = document.getElementsByClassName('videoframe')[1];
        if (nextIframe) {
            nextIframe.parentElement.removeChild(nextIframe);
            if (!nextIframe.hasAttribute('id')) {
                nextIframe.setAttribute('id', 'videoframe_' + longIdString);
            }
            const nextPlayer = new window.VimeoPlayerConstructor(nextIframe);

            let nextVideoProgressBar;
            const currentVideoSpan = document.querySelector('.video-card-side span[data-externalref="' + window.extref + '"]');
            if (currentVideoSpan) {
                nextVideoProgressBar = currentVideoSpan.closest('.video-card-side')
                                        .nextElementSibling.querySelector('.progress-bar');
            } else {
                // This is a speaker or a topic list. The current video is not in the list.
                let nextVideoPosition = getNextVideoPosition();
                nextVideoProgressBar = document.querySelector('.video-card-side[data-position="' + nextVideoPosition + '"]')
                                        .querySelector('.progress-bar');
            }

            // Get the progress for the next player
            let progressInt = parseInt(nextVideoProgressBar.getAttribute('aria-valuenow'));
            const duration = parseInt(nextVideoProgressBar.getAttribute('aria-valuemax'));

            if (progressInt !== 0) {
                if (progressInt === duration) {
                    progressInt = 0;
                }
            }

            setTimeout(async function() {
                if (progressInt !== 0) {
                    await nextPlayer.setCurrentTime(progressInt);
                }
                await nextPlayer.play();
                if ((typeof window.dontPlayNext === 'undefined' || !window.dontPlayNext)
                && nextPlayer.id === 'videoframe_' + longIdString) {
                    await nextPlayer.pause();
                }
            });

            hideIframe(nextIframe);

            // We should have 2 player instances now.
            window.players.push(nextPlayer);
            window.iFrames.push(nextIframe);
        }

        // If the there is a next video, we should now have 2 players and 2 iframes.
        const container = document.querySelector('.incourse-player');
        if (container.getAttribute('data-watchedall') == '1') {
            // If all videos in the resource have already been watched, we don't update the progress.
            // If the resource has been watched but the completion status is incorrect,
            // we'll update it in moodle via the page-functions method updateCompletionStatusIfIncorrect.
            window.watchedAll = true;
            window.updateProgress = false;
        } else {
            window.watchedAll = false;
        }

        window.viewedStatus =
        container.querySelector('.speakerinfo .watched-check').classList
            .contains('notwatched') ? 'unwatched' : 'watched';

        // Set the timeWatched var. This is the time the user has watched up to.
        // If a user skipps back this will not change.
        // If disable forward seek is set, we will not allow the user to skip forward past this point.
        const playerElement = document.querySelector('.incourse-player');
        const lastProgress = playerElement.getAttribute('data-progress');
        const disableForwardSeek = (playerElement.getAttribute('data-noseek') === '0') ? false : true;
        const videoDuration = window.iFrames[0].getAttribute('data-duration');
        var timeWatched = 0;
        if (window.viewedStatus === 'watched') {
            timeWatched = parseInt(videoDuration);
        } else if (lastProgress && lastProgress !== '0') {
            window.currentTime = parseInt(lastProgress);
            timeWatched = window.currentTime;
            if (!window.playNext) {
                await window.players[0].setCurrentTime(window.currentTime);
            }
        } else {
            window.currentTime = 0;
            timeWatched = 0;
        }
        // Setup the play icon div.
        playIcon.classList.remove('not-opaque');
        playIconDiv.classList.add('clickable');

        playIconDiv.addEventListener('click', async function() {
            window.iFrames[0].style.visibility = 'visible';
            dontPause = true;
            thumbImage.remove();
            playIconDiv.remove();
            setTimeout(async function() {
                await window.players[0].play();
                dontPause = false;
            });
        });

        if (window.playNext) {
            window.playNext = false;
            playIconDiv.remove();
            setTimeout(async function() {
                showIframe(window.iFrames[0]);
                await window.players[0].play();
                thumbImage.remove();
            }, 50);
        }

        // When progress is updated, if the video has a higher status already, we don't update it.
        window.players[0].on('play', function() {
            if (window.viewedStatus === 'unwatched') {
                window.viewedStatus = 'inprogress';
            }
            if (iframeHidden) {
                thumbImage.remove();
                iframeHidden = false;
            }
        });

        window.players[0].on('ended', function() {
            if (window.updateProgress) {
                videoWatched();
                updateProgressAndActivity();
            }
            window.players[0].pause();
        });

        window.players[0].on('timeupdate', function(data) {
            // Disable seeking on the video player.
            if (disableForwardSeek) {
                if (timeWatched === 0
                || (data.seconds - 1 < timeWatched && data.seconds + 1 > timeWatched)) {
                    timeWatched = data.seconds;
                }
            } else if (data.seconds > timeWatched) {
                timeWatched = data.seconds;
            }
            window.players[0].getDuration().then(function(duration) {
                let currentProgress = (timeWatched / duration);
                const videoLink =
                document.querySelector('.video-card-side span[data-externalref="' + window.extref + '"]');
                if (videoLink) {
                    const progressBar = videoLink.closest('.video-card-side')?.querySelector('.progress-bar');
                    if (progressBar) {
                        const existingProgress = parseFloat(progressBar.style.width.slice(0, -1));
                        if ((currentProgress * 100) > existingProgress) {
                            progressBar.setAttribute('style', 'width: ' + currentProgress * 100 + '%!important');
                            progressBar.setAttribute('aria-valuenow', data.seconds);
                        }
                    }
                }
                // If 90% of the video has been watched, we update the status to 'watched'.
                if (currentProgress >= 0.9) {
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

        if (disableForwardSeek &&
        window.window.viewedStatus !== 'watched' &&
        !window.watchedAll && !reset) {
            window.players[0].on('seeking', async function(data) {
                dontPause = true;
                if (timeWatched < data.seconds) {
                    await window.players[0].setCurrentTime(timeWatched);
                }
                await window.players[0].play();
                dontPause = false;
            });
        }
    });
    // Save progress if required when the user leaves the page.
    if (!beforeUnloadEventSet) {
        window.addEventListener('beforeunload', async function() {
            if (window.updateProgress) {
                // If the user leaves the page, we update the progress before they go.
                await updateProgressAndActivityPromise();
                return undefined;
            } else {
                return undefined;
            }
        }, true);
        beforeUnloadEventSet = true;
    }

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
    document.querySelector('.speakerinfo .watched-check').classList.remove('notwatched');
    document.querySelector('.speakerinfo .watched-check').setAttribute('aria-hidden', 'false');

    const videoLinks = document.getElementsByClassName('video-player-link');
    if (videoLinks.length > 0) {
        const lastLinkRef = videoLinks[videoLinks.length - 1].getAttribute('data-externalref');
        if (lastLinkRef !== window.extref) {
            // Only reveal the play next button if this is not the last video link in the list.
            Utils.waitForElement('.speakerinfo .watched-check:not(.notwatched)', document, function() {
                setTimeout(function() {
                    document.querySelector('#play-next-button')?.classList.remove('notwatched');
                    document.querySelector('#play-next-button')?.setAttribute('aria-hidden', 'false');
                }, 500);
            });
        }
    }

    const playlistVideoLink = document.querySelector('.video-card-side span[data-externalref="' + window.extref + '"]');
    if (playlistVideoLink) {
        const videoCardSide = playlistVideoLink.closest('.video-card-side');
        videoCardSide.querySelector('.progress-bar').classList.add('width100');
        videoCardSide.querySelector('.watched-check').classList.remove('notwatched');
        videoCardSide.querySelector('.watched-check').setAttribute('aria-hidden', 'false');
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
            singleItem.querySelector('.watched-check').setAttribute('aria-hidden', 'false');
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
    return new Promise(function(resolve) {
        if (!response) {
            if (loopCount++ < 20) {
                return new Promise(function(resolve) {
                    setTimeout(function() {
                        resolve();
                    }, 50);
                }).then(updateProgressAndActivity);
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
        resolve(true);
        return true;
    });
}

/**
 * Update the progress and activity module in the course page if required.
 * This is designed to be used with await which will pause the script until
 * progress has been updated.  * Great for when a playlist item is clicked.
 * Or to update the progress before the user leaves the page.
 */
export async function updateProgressAndActivityPromise() {
    const result = await updateProgressAndActivity();
    return new Promise((resolve) => {
        if (result) {
            resolve(result);
        } else {
            setTimeout(() => {
                updateProgressAndActivity().then((result) => resolve(result));
            }, 50);
        }
    });
}

/**
 * Show the iframe.
 * @param {HTMLelement} iframe
 */
function showIframe(iframe) {
    iframe.classList.remove('d-none');
    iframe.style.visibility = 'visible';
    iframe.setAttribute('aria-hidden', 'false');
}

/**
 * Hide the iframe
 * @param {HTMLelement} iframe
 */
function hideIframe(iframe) {
    iframe.classList.add('d-none');
    iframe.style.visibility = 'hidden';
    iframe.setAttribute('aria-hidden', 'true');
}

/**
 * Get the current video position.
 * @returns {number} The current video position.
 */
export function getNextVideoPosition() {
    const videoCardSides = document.querySelectorAll('.video-card-side');
    let positionArray = [];
    videoCardSides.forEach(function(videoCardSide) {
        positionArray.push(parseInt(videoCardSide.getAttribute('data-position')));
    });
    let currentVideoPosition = 1;
    // In speakerlists and topic lists the current video is not in the list, hence the +1;
    const videoListLength = videoCardSides.length + 1;
    for (let i = 1; i < videoListLength; i++) {
        if (!positionArray.includes(i)) {
            currentVideoPosition = i;
            break;
        }
    }
    return ++currentVideoPosition;
}