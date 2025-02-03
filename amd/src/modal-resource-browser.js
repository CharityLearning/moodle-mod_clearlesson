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
        if (element.getAttribute('id') === 'resource-select-button') { // In the page form.
            e.preventDefault();
            openResourceBrowser();
        }

        if (element.classList?.contains('select-resource-button')) { // In the modal browser & menu.
            e.preventDefault();
            const externalref = element.getAttribute('data-externalref');
            selectResource(externalref, e.target.getAttribute('data-type'));
        }
        if (element.classList?.contains('btn-close')
            || element.parentElement.classList?.contains('btn-close')
            || element.getAttribute('data-action') === 'cancel'
            || element.getAttribute('data-action') === 'hide'
            || element.parentElement.getAttribute('data-action') === 'hide') {
                onModalClose();
        }
        searchIfSearchbutton(e);

        if (element.closest('.form-autocomplete-suggestions li[role="option"]')) {
            // An autocomplete option has been selected.
            // Hide any error messages on the resource selector group.
            document.getElementById('fgroup_id_error_ref_select_group').removeAttribute('style');
        }

        if (element.closest('#open-filters-parent')) {
            e.preventDefault();
            toggleFilters('open');
        }
        if (element.closest('#close-filters-parent')) {
            e.preventDefault();
            toggleFilters('close');
        }
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

    Utils.waitForElement('.form-autocomplete-selection', document, function() {
        // Unlock these two elements when the autocomplete has loaded.
        document.getElementById('id_type').removeAttribute('disabled');
        document.getElementById('resource-select-button').removeAttribute('disabled');
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
    if (e.target.classList?.contains('clear-search-button')
    || e.target.parentElement?.classList?.contains('clear-search-button')) {
        e.preventDefault();
        document.getElementById('search').value = '';
        searchResources();
    }
}

/**
 * Open the resoure browser modal form.
 */
async function openResourceBrowser() {
    const resourceType = document.getElementById('id_type').value;
    const cmid = document.querySelector('input[name="coursemodule"]').value;
    const courseid = document.querySelector('input[name="course"]').value;
    const url = window.location.href;
    var lazyLoad = false;
    if (resourceType === 'video') {
        lazyLoad = true;
    }

    const browserForm = await new ModalForm({
        formClass: 'mod_clearlesson\\forms\\resource_browser_form',
        args: {type: resourceType, cmid: cmid, course: courseid, url: url, lazyload: lazyLoad},
        modalConfig: {title: getString('resourcebrowser', 'mod_clearlesson')},
    });

    modalForm = browserForm;

    browserForm.addEventListener(browserForm.events.LOADED, async function() {
        // Set the modal to nearly fullscreen size.
        let modalHeight = Math.ceil(window.innerHeight * 0.94);
        let modalWidth = Math.ceil(window.innerWidth * 0.94);
        modalWidth = modalWidth > 1800 ? 1800 : modalWidth;
        const modalRootInner = await browserForm.modal.getRoot()[0].children[0];
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
                if (!e.target.classList.contains('disabled-looking')) {
                    const type = e.target.getAttribute('data-type');
                    viewResourceType(type, cmid, courseid, url, browserForm);
                }
            }
            if (e.target.id === 'clear-all') {
                e.preventDefault();
                clearAll(browserForm, true);
            }
            if (e.target.id === 'clear-filters') {
                e.preventDefault();
                clearSelect(browserForm);
            }
            if (e.target.id === 'clear-search') {
                e.preventDefault();
                document.querySelector('.clear-search-button').click();
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
export async function setWaitingCursor(waiting, modalObject, selective = true) {
    var selector;
    const modalElement = await modalObject.modal.getRoot()[0];
    if (selective) {
        selector = '.browser-sidebar, .browser-sidebar *';
        updateWaitingDivs(modalElement, waiting, selector); // Sidebar first
        selector = ' .modal-content > *, form, form > *, .modal-header *,';
        selector += ' .modal-footer *, .resource-container  ';
        selector += ', .video-card-wrapper, .video-card-wrapper *';
        updateWaitingDivs(modalElement, waiting, selector);
    } else {
        selector = '*';
        updateWaitingDivs(modalElement, waiting, selector);
    }
}

/**
 * Update the waiting cursor on a set of elements.
 * @param {HTMLElement} modalElement
 * @param {Boolean} waiting
 * @param {String} selector
 */
function updateWaitingDivs(modalElement, waiting, selector) {
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
async function filterResources(filterName, selectedValue) {
    return new Promise((resolve) => {
        if (filterValues[filterName] === selectedValue) {
            return;
        }
        setWaitingCursor(true, modalForm);
        // The timeout is required to allow the DOM to update before we start filtering.
        setTimeout(async function() {
            const resources = await modalForm.modal.getRoot()[0].getElementsByClassName('video-card-wrapper');
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
                if (element.classList.contains('collection-card')) {
                    element.classList.add('d-flex');
                }
                element.classList.remove('d-none');
                element.setAttribute('aria-hidden', 'false');
            }
        }
    }
    if (!display) {
        if (hiddenByList === '' || !hiddenByList.includes(filterName)) {
            hiddenByList += ' ' + filterName;
            element.setAttribute('data-hiddenby', hiddenByList.trim());
            if (element.classList.contains('collection-card')) {
                element.classList.remove('d-flex');
            }
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
async function searchResources(query = '') {
    const lowerQuery = query.trim().toLowerCase();
    const clearSearchButton = document.querySelector('.clear-search-button');
    if (lowerQuery === '') {
        clearSearchButton.classList.remove('move');
    } else {
        clearSearchButton.classList.add('move');
    }
    const resources = await modalForm.modal.getRoot()[0].getElementsByClassName('video-card-wrapper');
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

/**
 * Order filtered resources by playlist or series order.
 *
 * @param {String} type
 * @param {String} selectedValue
 */
async function orderFilteredResources(type, selectedValue) {
    let positionArray = [];
    await modalForm.modal.getRoot()[0].querySelectorAll('.video-card-wrapper:not(.d-none)')
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

/**
 * Scroll to the top of the browser filters.
 */
function scrollToTop() {
    document.getElementById('resource-container').scrollIntoView({behavior: 'smooth', block: 'start'});
}

/**
 * Clear all filters and the search bar.
 *
 * @param {ModalForm} browserForm
 */
function clearAll(browserForm) {
    clearSelect(browserForm, true);
}

/**
 * Clear all filters.
 * @param {ModalForm} browserForm
 * @param {Boolean} all
 */
async function clearSelect(browserForm, all = false) {
    let emptyCount = 0;
    for (var filterC in filterValues) {
        if (filterValues[filterC] == '') {
            emptyCount++;
        }
    }
    const doc = await browserForm.modal.getRoot()[0];
    const query = doc.querySelector('#search').value.trim().toLowerCase();
    for (var filter in filterValues) {
        let filterElement = doc.querySelector('#' + filter + '-filter');
        if (filterElement) {
            filterElement.value = '';
        }
    }
    if (emptyCount === filterValues.length
        && (query === '' || !all)) {
        return false;
    } else {
        setWaitingCursor(true, browserForm);
    }
    setTimeout(function() {
        const cardArray = [];
        doc.querySelectorAll('.video-card-wrapper').forEach(function(card) {
            if (query !== '' && all) {
                const searchables = card.getElementsByClassName('searchable');
                var display = false;
                if (query === '') {
                    display = true;
                } else {
                    for (const searchItem of searchables) {
                        display = searchItem.textContent.toLowerCase().includes(query);
                        if (display) {
                            break;
                        }
                    }
                }
                if (display) {
                    card.setAttribute('data-hiddenby', '');
                    card.classList.remove('d-none');
                    card.setAttribute('aria-hidden', 'false');
                    if (card.classList.contains('collection-card')) {
                        card.classList.add('d-flex');
                    }
                } else {
                    card.setAttribute('data-hiddenby', 'search');
                    card.classList.add('d-none');
                    card.setAttribute('aria-hidden', 'true');
                    if (card.classList.contains('collection-card')) {
                        card.classList.remove('d-flex');
                    }
                }
            } else {
                card.setAttribute('data-hiddenby', '');
                card.classList.remove('d-none');
                card.setAttribute('aria-hidden', 'false');
            }
            card.style.order = '';
            cardArray.push(card);
            card.remove();
        });

        if (all) {
            // Clear the search.
            doc.querySelector('#search').value = '';
            doc.querySelector('.clear-search-button').classList.remove('move');
        }
        // Sort by card title
        cardArray.sort(function(a, b) {
            let titleA = a.querySelector('.card-title').textContent;
            let titleB = b.querySelector('.card-title').textContent;
            titleA = updateTiteForSorting(titleA);
            titleB = updateTiteForSorting(titleB);
            return titleA.localeCompare(titleB);
        });
        orderedResources = false;
        // Now put the cards back in the right order.
        const container = doc.querySelector('#resource-container');
        cardArray.forEach(function(card) {
            container.appendChild(card);
        });
        setWaitingCursor(false, browserForm);
    }, 150);
    return true;
}

/**
 * Update the title for sorting.
 *
 * @param {String} title
 */
function updateTiteForSorting(title) {
    // If the title contains any solo digit numbers, we need to add a leading zero.
    title = title.replace(/\b\d\b/g, '0$&');
    return title;
}

/**
 * Toggle the filters open or closed.
 *
 * @param {String} action
 */
function toggleFilters(action) {
    const filters = document.querySelector('.browser-sidebar');
    const filtersInner = document.querySelector('.browser-filters');
    const openButton = document.getElementById('open-filters-parent');
    const closeButton = document.getElementById('close-filters-parent');
    const modalBody = document.querySelector('.modal-body .mod-clearlesson.browser');

    setTimeout(function() {
        if (action === 'open') {
            modalBody.classList.add('expanded');
            filters.setAttribute('aria-expanded', 'true');
            filtersInner.classList.remove('inwisible-small');
            openButton.setAttribute('aria-hidden', 'true');
            closeButton.setAttribute('aria-hidden', 'false');
            closeButton.classList.add('inwisible-small');
            setTimeout(function() {
                closeButton.classList.add('d-block');
                closeButton.classList.add('d-xl-none');
                closeButton.classList.remove('d-none');
                closeButton.classList.remove('inwisible-small');
                openButton.classList.add('d-none');
                openButton.classList.remove('d-block');
                openButton.classList.remove('d-xl-none');
                openButton.classList.add('inwisible-small');
            }, 500);
        } else {
            modalBody.classList.remove('expanded');
            closeButton.setAttribute('aria-hidden', 'true');
            openButton.setAttribute('aria-hidden', 'false');
            openButton.classList.add('inwisible-small');
            filters.setAttribute('aria-expanded', 'false');
            setTimeout(function() {
                openButton.classList.remove('d-none');
                openButton.classList.add('d-block');
                openButton.classList.add('d-xl-none');
                openButton.classList.remove('inwisible-small');
                closeButton.classList.remove('d-block');
                closeButton.classList.remove('d-xl-none');
                closeButton.classList.add('d-none');
                closeButton.classList.add('inwisible-small');
                filtersInner.classList.add('inwisible-small');
            }, 500);
        }
    }, 5);
}