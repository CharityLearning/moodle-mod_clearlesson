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

var newResourceSelected = false;
var orderedResources = false;
var filterValues = {
    animation: '',
    speakers: '',
    topics: '',
    playlists: '',
    series: '',
    collections: '',
};
var modalForm;

export const init = () => {
    document.addEventListener('click', function(e) {
        const element = e.target;
        // const typeSelect = document.getElementById('id_type');
        if (element.getAttribute('id') === 'resource-select-button') { // In the page form.
            e.preventDefault();
            openResourceBrowser();
        }

        if (element.classList?.contains('select-resource-button')) { // In the modal browser & menu.
            e.preventDefault();
            const externalref = element.getAttribute('data-externalref');
            selectResource(externalref, e.target.getAttribute('data-type'));
        }
        if (element.classList?.contains('close')
            || element.getAttribute('data-action') === 'cancel'
            || element.getAttribute('data-action') === 'hide'
            || element.parentElement.getAttribute('data-action') === 'hide') {
                onModalClose();
        }
        searchIfSearchbutton(e);
        // if ((element.classList?.contains('video-details-view')
        // || element.parentelement.classList?.contains('video-details-view'))
        // && !element.classList?.contains('view-link')) {
        //     e.preventDefault();
        //     if (element.hasAttribute('data-view')) {
        //         externalref = element.getAttribute('data-view');
        //     } else {
        //         externalref = element.parentElement.getAttribute('data-view');
        //     }
        //     // This is the {{count}} videos or playlists etc link.
        //     const type = modalForm.modal.getRoot()[0]
        //                 .querySelector('button.type-button.disabled-looking').getAttribute('data-type');
        //     loadResourcePageWithFilter(externalref, type);
        // }
        // // This is the speaker link in the video card.
        // if (element.classList?.contains('vid-speaker-link')) {
        //     e.preventDefault();
        //     externalref = element.closest('.video-card-wrapper').getAttribute('data-speakers');
        //     loadResourcePageWithFilter(externalref, 'speakers');
        // }
    });

    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            onModalClose();
        }
    });

    document.addEventListener('change', async function(e) {
        if (e.target?.getAttribute('id') === 'id_type') {
            clearSelectedResource();
        }
        if (e.target?.classList.contains('browser-filter')) {
            const filterName = e.target.getAttribute('name');
            const seletedValue = e.target.value;
            if (orderedResources) {
                await clearOrdering();
            }
            await filterResources(filterName, seletedValue);
            if (filterName === 'playlists' || filterName === 'series') {
                orderFilteredResources(filterName, seletedValue);
            }
        }
    });

    document.addEventListener('keydown', function(e) {
        // For enter, return or space key press while in the search input.
        if (e.key === 'Enter' || e.key === 'Return') {
            if (e.target?.getAttribute('id') === 'search') {
                e.preventDefault();
                searchResources(e.target.value);
            }
            searchIfSearchbutton(e);
        }
    });
};

/**
 * Check if the search button was activated.
 * If so, prevent the default action and search the resources.
 * @param {Event} e
 */
function searchIfSearchbutton(e) {
    if (e.target.classList?.contains('search-button-parent')
        || e.target.classList?.contains('cl-search-append')
        || e.target.classList?.contains('fa-search')) {
        e.preventDefault();
        searchResources(document.getElementById('search').value);
    }
}

/**
 * Open the resoure browser modal form.
 */
function openResourceBrowser() {
    const resourceType = document.getElementById('id_type').value;
    const cmid = document.querySelector('input[name="coursemodule"]').value;
    const courseid = document.querySelector('input[name="course"]').value;
    const url = window.location.href;
    var lazyLoad = false;
    if (resourceType === 'video') {
        lazyLoad = true;
    }

    const browserForm = new ModalForm({
        formClass: 'mod_clearlesson\\forms\\resource_browser_form',
        args: {type: resourceType, cmid: cmid, course: courseid, url: url, lazyload: lazyLoad},
        modalConfig: {title: getString('resourcebrowser', 'mod_clearlesson')},
    });

    modalForm = browserForm;

    browserForm.addEventListener(browserForm.events.LOADED, function() {
        // Set the modal to nearly fullscreen size.
        let modalHeight = Math.ceil(window.innerHeight * 0.94);
        let modalWidth = Math.ceil(window.innerWidth * 0.94);
        modalWidth = modalWidth > 1800 ? 1800 : modalWidth;
        const modalRootInner = browserForm.modal.getRoot()[0].children[0];
        modalRootInner.setAttribute('style',
            'width: ' + modalWidth +
            'px;max-width: ' + modalWidth +
            'px;height: ' + modalHeight +
            'px;max-height: ' + modalHeight + 'px;'
        );
        newResourceSelected = false;
        setTimeout(function() {
            // Hide the autocomplete while we are manipulating it.
            document.querySelector('span[data-fieldtype="autocomplete"').classList.add('d-none');
        }, 100); // With a short delay to allow the modal to load first.

        modalRootInner.addEventListener('click', function(e) {
            if (e.target.classList.contains('type-button')) {
                e.preventDefault();
                const type = e.target.getAttribute('data-type');
                viewResourceType(type, cmid, courseid, url, browserForm);
            }
        });

        Utils.waitForElement('.modal-footer button.btn-primary', modalRootInner, function() {
            modalRootInner.querySelector('.modal-footer button.btn-primary').classList.add('d-none');
        });
    });

    browserForm.show();
}

/**
 * Select a resource from the browser modal.
 * When a user selects a resource from the modal, we capture the external ref and shut the modal.
 * We then create and add an autocomplete option for this resource to the list.
 * We then 'click' on this option, thus hijacking the autocomplete to select our resource.
 * Bur wont work unless the autocomplete is active.
 * This is done in the background when the browser modal is first opened.
 *
 * @param {String} externalref
 * @param {String} resourceType
 */
export function selectResource(externalref, resourceType) {
    // Set the type of the resource in the form to match what was selected.
    document.getElementById('id_type').value = resourceType;
    // Well use this var in another function.
    newResourceSelected = externalref;
    const parent = document.getElementById('id_externalref').parentElement;
    // Search for the selected resource in the autocomplete list.
    const input = document.querySelector('input[id^="form_autocomplete_input"]');
    input.value = externalref;
    input.dispatchEvent(
        new Event("input", {bubbles: true})
    );
    let suggestions = parent.parentElement.querySelector('ul.form-autocomplete-suggestions');
    if (externalref) {
        Utils.waitForElement('li[data-value="' + externalref + '"]', suggestions.parentElement, function() {
            // Once the option is loaded, click on it to select the resource.
            document.querySelector('li[data-value="' + externalref + '"]').click();
            // We can close the modals now.
            document.querySelectorAll('.modal-header button.close').forEach(function(button) {
                button.click();
            });
        });
    }
}

/**
 * Actions to take when the modal is closed.
 * If the autocomplete div is currently hidden because we are manipulating it,
 * we need to show it again when we are done with it.
 */
function onModalClose() {
    if (!newResourceSelected) {
        // If the modal is closed without selecting a resource, we need to show the autocomplete.
        document.querySelector('span[data-fieldtype="autocomplete"').classList.remove('d-none');
    } else {
        const selectedOptionSelector =
        '.form-autocomplete-selection span[role="option"][data-value="' + newResourceSelected + '"]';
        const watch = document.getElementById('id_externalref').parentElement.parentElement;
        Utils.waitForElement(selectedOptionSelector, watch, function() {
            // Once we have have searched for and added the selected resouce,
            // we can reveal the autocomplete, which is not going nuts anymore.
            document.querySelector('span[data-fieldtype="autocomplete"').classList.remove('d-none');
        });
    }
}

/**
 * Event listener for the resource type buttons.
 * This will essentially re-render the modal form with the new resource type.
 *
 * @param {String} type
 * @param {Number} cmid
 * @param {Number} courseid
 * @param {String} url
 * @param {ModalForm} browserForm
 * @param {String} externalref
 */
async function viewResourceType(type, cmid, courseid, url, browserForm, externalref = '') {
    var lazyLoad = false;
    if (type === 'play') {
        lazyLoad = true;
    }
    var formParams = {type: type,
                        cmid: cmid,
                        course: courseid,
                        url: url,
                        lazyload: lazyLoad};
    // If there is an externalref, the page we load needs to be filtered.
    if (externalref) {
        formParams.destinationtype = getDestinationTypeFromType(type);
        formParams.filtervalue = externalref;
    }
    setWaitingCursor(true, modalForm);
    const serialFormParams = Utils.serialize(formParams);
    const bodyContent = browserForm.getBody(serialFormParams);
    await browserForm.modal.setBodyContent(bodyContent);
    setWaitingCursor(false, modalForm);
    scrollToTop();
    // Reset the filter values.
    filterValues = {
        animation: '',
        speakers: '',
        topics: '',
        playlists: '',
        series: '',
        collections: '',
    };
}

/**
 * Get the filter name from the resource type.
 * @param {String} type
 */
function getDestinationTypeFromType(type) {
    switch (type) {
        case 'speakers':
        case 'topics':
        case 'playlists':
            return 'play';
        case 'series':
            return 'playlists';
        case 'collections':
            return 'series';
        default:
            return '';
    }
}

/**
 * When the resource type is changed, clear any selections.
 * We do this by clicking the on the selected option.
 */
function clearSelectedResource() {
    const selectedoption = document.getElementById('id_externalref')
                            .parentElement.querySelector('span[role="option"]');
    selectedoption?.firstElementChild.click();
}

/**
 * Set the cursor to waiting or default.
 * We avoid updating any newly rendered content.
 *
 * @param {Boolean} waiting
 * @param {ModalForm} modalObject
 * @param {Boolean} selective
 */
export function setWaitingCursor(waiting, modalObject, selective = true) {
    var selector;
    if (selective) {
        selector = '*:is(.browser-filters *, .modal-content > *, form, form > *, .modal-header *,';
        selector += ' .modal-footer *, .browser-sidebar, .browser-sidebar *, .resource-container)';
        selector += ', .video-card-wrapper, .video-card-wrapper *';
    } else {
        selector = '*';
    }
    const modalElement = modalObject.modal.getRoot()[0];
    const resources = [...modalElement.querySelectorAll(selector)];
    if (waiting) {
        for (const node of resources) {
            if (!node.classList.contains('d-none')) {
                node.style.cursor = 'wait';
            }
        }
    } else {
        modalElement.querySelector('.modal-header > button > span').style.cursor = 'pointer';
        for (const node of resources) {
            if (!node.classList.contains('d-none')) {
                let cursor = (node.tagName === 'BUTTON'
                                || node.tagName === 'A'
                                || node.tagName === 'I') ? 'pointer' : 'default';
                node.style.cursor = cursor;
            }
        }
    }
}

/**
 * Filter the resources in the browser by hiding them in the DOM.
 * @param {String} filterName
 * @param {String} selectedValue
 */
async function filterResources(filterName, selectedValue,) {
    return new Promise((resolve) => {
        if (filterValues[filterName] === selectedValue) {
            return;
        }
        setWaitingCursor(true, modalForm);
        // The timeout is required to allow the DOM to update before we start filtering.
        setTimeout(function() {
            const resources = modalForm.modal.getRoot()[0].getElementsByClassName('video-card-wrapper');
                for (const resource of resources) {
                    const showResource = checkDisplayResource(resource, filterName, selectedValue);
                    toggleElement(resource, showResource, filterName);
                }
                setWaitingCursor(false, modalForm);
                filterValues[filterName] = selectedValue;
                resolve();
        }, 100);
    });
}

/**
 * Check for see whether to display a resource or not.
 * @param {HTMLelement} resource
 * @param {String} filterName
 * @param {String} selectedValue
 *
 * @returns {bool}
 */
function checkDisplayResource(resource, filterName, selectedValue) {
    if (selectedValue === '') {
        return true;
    }
    const filterValues = resource.getAttribute('data-' + filterName)?.split(' ');
    for (let value of filterValues) {
        // Some values are stored with a suffix, indicating video or playlist order.
        value = value.split('___')[0];
        if (value === selectedValue) {
            return true;
        }
    }
    return false;
}

/**
 * Toggle the display of a element.
 * @param {HTMLelement} element
 * @param {bool} display
 * @param {string} filterName
 */
function toggleElement(element, display, filterName) {
    let hiddenByList = element.getAttribute('data-hiddenby');
    if (display) {
        if (hiddenByList.includes(filterName)) {
            hiddenByList = hiddenByList.replace(filterName, '');
            hiddenByList = hiddenByList.trim();
            element.setAttribute('data-hiddenby', hiddenByList);
            if (hiddenByList === '') {
                element.classList.remove('d-none');
                element.setAttribute('aria-hidden', 'false');
            }
        }
    }
    if (!display) {
        if (hiddenByList === '' || !hiddenByList.includes(filterName)) {
            hiddenByList += ' ' + filterName;
            element.setAttribute('data-hiddenby', hiddenByList.trim());
            element.classList.add('d-none');
            element.setAttribute('aria-hidden', 'true');
        }
    }
}

/**
 * Search the resources by query
 *
 * @param {String} query
 */
function searchResources(query) {
    const lowerQuery = query.trim().toLowerCase();
    const resources = modalForm.modal.getRoot()[0].getElementsByClassName('video-card-wrapper');
    for (const resource of resources) {
        const searchables = resource.getElementsByClassName('searchable');
        var display = false;
        if (lowerQuery === '') {
            display = true;
        } else {
            for (const searchItem of searchables) {
                display = searchItem.textContent.toLowerCase().includes(lowerQuery);
                if (display) {
                    break;
                }
            }
        }
        toggleElement(resource, display, 'search');
    }
    return true;
}

// Not currently used.
// /**
//  * Load the resource page with a filter.
//  * @param {String} externalref
//  * @param {String} type
//  */
// export async function loadResourcePageWithFilter(externalref, type) {
//     const cmid = document.querySelector('input[name="coursemodule"]').value;
//     const courseid = document.querySelector('input[name="course"]').value;
//     const url = window.location.href;
//     await viewResourceType(type, cmid, courseid, url, modalForm, externalref);
//     if (type === 'playlists' || type === 'series') {
//         orderFilteredResources(type, externalref);
//     }
//     focusFilterAnimation(type);
// }

/**
 * Order filtered resources by playlist or series order.
 *
 * @param {String} type
 * @param {String} selectedValue
 */
function orderFilteredResources(type, selectedValue) {
    let positionArray = [];
    modalForm.modal.getRoot()[0].querySelectorAll('.video-card-wrapper:not(.d-none)')
    .forEach(function(resource) {
        const list = resource.getAttribute('data-' + type);
        if (list) {
            for (let listItem of list.split(' ')) {
                if (!listItem.trim()) {
                    continue;
                }
                if (listItem.includes(selectedValue)) {
                    // The order is stored in the listItem.
                    const order = listItem.split('___')[1];
                    positionArray.push({order: order, resource: resource});
                }
            }
        }
    });
    // Now we can reposition the resources.
    for (let i = 0; i < positionArray.length; i++) {
        positionArray[i].resource.style.order = positionArray[i].order;
    }
    orderedResources = true;
}

/**
 * Clear the sort order of the visible resources.
 * This is done when the filter is changed.
 *
 * @returns {Promise}
 */
async function clearOrdering() {
    return new Promise((resolve) => {
        modalForm.modal.getRoot()[0].querySelectorAll('.video-card-wrapper:not(.d-none)')
        .forEach(function(resource) {
            resource.style.order = '';
        });
        orderedResources = false;
        resolve();
    });
}

// /**
//  * Focus the filter animation.
//  * @param {String} type
//  */
// function focusFilterAnimation(type) {
//     const filter = modalForm.modal.getRoot()[0].querySelector('#' + type + '-filter');
//     scrollToTop();
//     const filterContainer = filter.closest('.filter-parent');
//     setTimeout(function() {
//         filterContainer.classList.add('filter-animation');
//         setTimeout(function() {
//             filterContainer.classList.remove('filter-animation');
//         }, 600);
//     }, 200);
// }

/**
 * Scroll to the top of the browser filters.
 */
function scrollToTop() {
    document.getElementsByClassName('browser-filters')[0].scrollIntoView({behavior: 'smooth', block: 'end'});
}