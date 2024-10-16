<?php
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
 * Strings for component 'clearlesson', language 'en', branch 'MOODLE_30_STABLE'
 *
 * @package    mod_clearlesson
 * @copyright  1999 onwards Martin Dougiamas  {@link http://moodle.com}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
$string['clearlessonurl'] = 'Clear Lesson Platform URL';
$string['clearlessonurldesc'] = 'Url for your Clear Lesson Platform with no trailing slash.';
$string['apikey'] = 'API Key';
$string['apikeydesc'] = 'Your API Key from the Clear Lesson Platform.';
$string['secretkey'] = 'Secret Key';
$string['secretkeydesc'] = 'Your Secret Key from the Clear Lesson Platform';
$string['externalref'] = 'External Reference eg. ABC123';
$string['type'] = 'Type';
$string['invalidresponse'] = 'Invalid response from Clear Lesson Platform.';
$string['clicktoopen'] = 'Click {$a} link to open video.';
$string['configdisplayoptions'] = 'Select all options that should be available, existing settings are not modified. Hold CTRL key to select multiple fields.';
$string['configframesize'] = 'When a web page or an uploaded file is displayed within a frame, this value is the height (in pixels) of the top frame (which contains the navigation).';
$string['configrolesinparams'] = 'Enable if you want to include localized role names in list of available parameter variables.';
$string['configsecretphrase'] = 'This secret phrase is used to produce encrypted code value that can be sent to some servers as a parameter.  The encrypted code is produced by an md5 value of the current user IP address concatenated with your secret phrase. ie code = md5(IP.secretphrase). Please note that this is not reliable because IP address may change and is often shared by different computers.';
$string['contentheader'] = 'Content';
$string['createurl'] = 'Create a URL';
$string['displayoptions'] = 'Available display options';
$string['displayselect'] = 'Display';
$string['displayselect_help'] = 'This setting, together with the URL file type and whether the browser allows embedding, determines how the URL is displayed. Options may include:

* Automatic - The best display option for the URL is selected automatically
* Embed - The URL is displayed within the page below the navigation bar together with the URL description and any blocks
* Open - Only the URL is displayed in the browser window
* In pop-up - The URL is displayed in a new browser window without menus or an address bar
* In frame - The URL is displayed within a frame below the navigation bar and URL description
* New window - The URL is displayed in a new browser window with menus and an address bar';
$string['displayselectexplain'] = 'Choose display type, unfortunately not all types are suitable for all URLs.';
$string['externalurl'] = 'External URL';
$string['framesize'] = 'Frame height';
$string['invalidstoredurl'] = 'Cannot display this resource, URL is invalid.';
$string['chooseavariable'] = 'Choose a variable...';
$string['invalidurl'] = 'Entered URL is invalid';
$string['modulename'] = 'Clear Lesson Video';
$string['modulename_help'] = 'The Clear Lesson module provides a Clear Lesson Video as a course resource.

There are a number of display options for the video, such as embedded or opening in a new window and advanced options for passing information, such as a student\'s name, to the URL if required.

Note that URLs can also be added to any other resource or activity type through the text editor.';
$string['modulename_link'] = 'mod/clearlesson/view';
$string['modulenameplural'] = 'Clear Lesson Videos';
$string['page-mod-url-x'] = 'Any URL module page';
$string['parameterinfo'] = '&amp;parameter=variable';
$string['parametersheader'] = 'Clear Lesson variables';
$string['parametersheader_help'] = 'Some internal Moodle variables may be automatically appended to the URL. Type your name for the parameter into each text box(es) and then select the required matching variable.';
$string['pluginadministration'] = 'Clear Lesson Video module administration';
$string['pluginname'] = 'Clear Lesson Video';
$string['popupheight'] = 'Pop-up height (in pixels)';
$string['popupheightexplain'] = 'Specifies default height of popup windows.';
$string['popupwidth'] = 'Pop-up width (in pixels)';
$string['popupwidthexplain'] = 'Specifies default width of popup windows.';
$string['printintro'] = 'Display URL description';
$string['printintroexplain'] = 'Display URL description below content? Some display types may not display description even if enabled.';
$string['rolesinparams'] = 'Include role names in parameters';
$string['search:activity'] = 'URL';
$string['serverurl'] = 'Server URL';
$string['addinstance'] = 'Add a new Clear Lesson resource';
$string['clearlesson:addinstance'] = 'Add a new Clear Lesson resource';
$string['view'] = 'View Clear Lesson Video';
$string['clearlesson:view'] = 'View Clear Lesson Video';

/** */
$string['aboutcap'] = 'About';
$string['animation'] = 'Animation';
$string['browseresources'] = 'Browse Resources';
$string['resetclearwatched'] = 'Recompletion reset and watched status';
$string['resetclearwatchedinfo'] = 'Should the video watched status be cleared when the activity is reset?';
$string['displaytypemodal'] = 'Modal';
$string['filters'] = 'Filters';
$string['noresourceselected'] = 'No resource selected';
$string['resourcebrowser'] = 'Resource Browser';
$string['selectaresource'] = 'Select a resource';
$string['video'] = 'video';
$string['videos'] = 'videos';
$string['playlist'] = 'playlist';
$string['playlists'] = 'playlists';
$string['topic'] = 'topic';
$string['topics'] = 'topics';
$string['topicscap'] = 'Topics';
$string['topicsmenu'] = 'Topic videos';
$string['topicvideos'] = 'Topic videos';
$string['seriesmenu'] = 'series playlists';
$string['speaker'] = 'speaker';
$string['speakercap'] = 'Speaker';
$string['speakers'] = 'speakers';
$string['speakermenu'] = 'Speaker videos';
$string['speakervideos'] = 'Speaker videos';
$string['relatedvideoscap'] = 'Related videos';
$string['collection'] = 'collection';
$string['collections'] = 'collections';
$string['collectionmenu'] = 'collection series';
$string['transcriptcap'] = 'Transcript';
$string['serie'] = 'series';
$string['series'] = 'series';
$string['preview'] = 'Preview';
$string['pleaseselectaresource'] = 'Please select a resource';
$string['selectspeaker'] = 'Select speaker';
$string['selectplaylist'] = 'Select playlist';
$string['selectcollection'] = 'Select collection';
$string['selecttopic'] = 'Select topic';
$string['selectseries'] = 'Select series';
$string['selectvideo'] = 'Select video';
$string['speakerviewer'] = 'Speaker Viewer';
$string['playlistviewer'] = 'Playlist Viewer';
$string['playlistvideos'] = 'Playlist videos';
$string['playlistmenu'] = 'playlist videos';
$string['videoplayer'] = 'Video Player';
$string['videowatched'] = 'Video watched';
$string['view'] = 'View';
$string['watchedallrule'] = 'All videos in the resource must be watched.';