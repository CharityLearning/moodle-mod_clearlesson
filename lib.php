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
 * Clear Lesson module API.
 *
 * @package    clearlesson
 * @copyright  2017 Josh Willcock  {@link http://josh.cloud}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

define('CLEARLESSON_LAZYLOAD_LIMIT', 15);

// Define privacy constants.
define('CLEARLESSON_PRIVACY_NONE', 0);
define('CLEARLESSON_PRIVACY_MYAUDIENCE', 1);
define('CLEARLESSON_PRIVACY_PUBLIC', 2);

/** Clearlesson modal display type 
 * To be used along side the resourcelib display types.
 * eg RESOURCELIB_DISPLAY_OPEN
 */
define('CLEARLESSON_DISPLAY_MODAL', 1982);

/**
 * List of features supported in Clear Lesson module.
 * @param string $feature FEATURE_xx constant for requested feature
 * @return mixed True if module supports feature, false if not, null if doesn't know
 */
function clearlesson_supports($feature) {
    switch ($feature) {
        case FEATURE_MOD_ARCHETYPE:
            return MOD_ARCHETYPE_RESOURCE;
        case FEATURE_GROUPS:
            return false;
        case FEATURE_GROUPINGS:
            return false;
        case FEATURE_MOD_INTRO:
            return true;
        case FEATURE_COMPLETION_TRACKS_VIEWS:
            return true;
        case FEATURE_GRADE_HAS_GRADE:
            return false;
        case FEATURE_GRADE_OUTCOMES:
            return false;
        case FEATURE_BACKUP_MOODLE2:
            return true;
        case FEATURE_SHOW_DESCRIPTION:
            return true;
        case FEATURE_COMPLETION_HAS_RULES:
            return true;
        default:
            return null;
    }
}

/**
 * Returns all other caps used in module.
 * @return array
 */
function clearlesson_get_extra_capabilities() {
    return array('moodle/site:accessallgroups');
}

/**
 * This function is used by the reset_course_userdata function in moodlelib.
 * @param $data the data submitted from the reset course.
 * @return array status array
 */
function clearlesson_reset_userdata($data) {
    return array();
}
/**
 * List the actions that correspond to a view of this module.
 * This is used by the participation report.
 *
 * Note: This is not used by new logging system. Event with
 *       crud = 'r' and edulevel = LEVEL_PARTICIPATING will
 *       be considered as view action.
 *
 * @return array
 */
function clearlesson_get_view_actions() {
    return array('view', 'view all');
}

/**
 * List the actions that correspond to a post of this module.
 * This is used by the participation report.
 *
 * Note: This is not used by new logging system. Event with
 *       crud = ('c' || 'u' || 'd') and edulevel = LEVEL_PARTICIPATING
 *       will be considered as post action.
 *
 * @return array
 */
function clearlesson_get_post_actions() {
    return array('update', 'add');
}

/**
 * Add clearlesson instance.
 * @param object $data
 * @param object $mform
 * @return int new url instance id
 */
function clearlesson_add_instance($data, $mform) {
    global $CFG, $DB;
    require_once($CFG->dirroot.'/mod/clearlesson/locallib.php');
    $parameters = array();
    for ($i = 0; $i < 100; $i++) {
        $parameter = "parameter_$i";
        $variable  = "variable_$i";
        if (empty($data->$parameter) or empty($data->$variable)) {
            continue;
        }
        $parameters[$data->$parameter] = $data->$variable;
    }
    $data->parameters = serialize($parameters);

    // No longer used. TODO check for other unsused fields.
    $displayoptions = array();
    if ($data->display == RESOURCELIB_DISPLAY_POPUP) {
        $displayoptions['popupwidth']  = $data->popupwidth;
        $displayoptions['popupheight'] = $data->popupheight;
    }
    $data->displayoptions = serialize($displayoptions);
    $data->timemodified = time();
    $data->id = $DB->insert_record('clearlesson', $data);

    return $data->id;
}

/**
 * Update url instance.
 * @param object $data
 * @param object $mform
 * @return bool true
 */
function clearlesson_update_instance($data, $mform) {
    global $CFG, $DB;
    require_once($CFG->dirroot.'/mod/clearlesson/locallib.php');
    $parameters = array();
    for ($i = 0; $i < 100; $i++) {
        $parameter = "parameter_$i";
        $variable  = "variable_$i";
        if (empty($data->$parameter) or empty($data->$variable)) {
            continue;
        }
        $parameters[$data->$parameter] = $data->$variable;
    }
    $data->parameters = serialize($parameters);
    $displayoptions = array();
    if ($data->display == RESOURCELIB_DISPLAY_POPUP) {
        $displayoptions['popupwidth']  = $data->popupwidth;
        $displayoptions['popupheight'] = $data->popupheight;
    }
    $data->displayoptions = serialize($displayoptions);
    $data->externalref = clearlesson_fix_submitted_ref($data->externalref);
    $data->timemodified = time();
    $data->id           = $data->instance;
    $DB->update_record('clearlesson', $data);
    return true;
}

/**
 * Delete clearlesson instance.
 * @param int $id
 * @return bool true
 */
function clearlesson_delete_instance($id) {
    global $DB;
    if (!$url = $DB->get_record('clearlesson', array('id' => $id))) {
        return false;
    }
    // Note: all context files are deleted automatically.
    $DB->delete_records('clearlesson', array('id' => $url->id));
    return true;
}

/**
 * Given a course_module object, this function returns any
 * "extra" information that may be needed when printing
 * this activity in a course listing.
 *
 * See {@link get_array_of_activities()} in course/lib.php
 *
 * @param object $coursemodule
 * @return cached_cm_info info
 */
function clearlesson_get_coursemodule_info($coursemodule) {
    global $CFG, $DB;
    require_once("$CFG->dirroot/mod/clearlesson/locallib.php");
    if (!$clearlessonref = $DB->get_record('clearlesson', array('id' => $coursemodule->instance),
    'id, name, display, displayoptions, externalref, parameters, intro, introformat ,type, completionwatchedall')) {
        return null;
    }
    $info = new cached_cm_info();
    $info->name = $clearlessonref->name;
    // Note: there should be a way to differentiate links from normal resources.
    $info->icon = clearlesson_guess_icon($clearlessonref->externalref, 24);

    switch ($clearlessonref->display) {
        case RESOURCELIB_DISPLAY_NEW:
            $fullurl = "$CFG->wwwroot/mod/clearlesson/view.php?id=$coursemodule->id&amp;redirect=1";
            $info->onclick = "window.open('$fullurl'); return false;";
            break;
        case RESOURCELIB_DISPLAY_OPEN:
            $fullurl = "$CFG->wwwroot/mod/clearlesson/view.php?id=$coursemodule->id";
            $info->onclick = "";
            break;
        case RESOURCELIB_DISPLAY_POPUP:
            $fullurl = "$CFG->wwwroot/mod/clearlesson/view.php?id=$coursemodule->id&popup=1#topofscroll";
            $options = empty($clearlessonref->displayoptions) ? array() : unserialize($clearlessonref->displayoptions);
            $width  = empty($options['popupwidth']) ? 620 : $options['popupwidth'];
            $height = empty($options['popupheight']) ? 450 : $options['popupheight'];
            $wh = "width=$width,height=$height,toolbar=no,location=no,menubar=no,copyhistory=no,status=no,directories=no,scrollbars=yes,resizable=yes";
            $info->onclick = "window.open('$fullurl', '', '$wh'); return false;";
            break;
        case CLEARLESSON_DISPLAY_MODAL:
            $onclickjs = "event.preventDefault(); 
            if (typeof window.buttonClicktimeOut === 'undefined' || window.buttonClicktimeOut === false) {
                window.buttonClicktimeOut = true;
                if (typeof window.openPlayer === 'undefined') {
                    require(['mod_clearlesson/course-page'], function (coursePage) {
                        coursePage.init();
                        window.openPlayer(event, '$clearlessonref->type');
                    });
                } else {
                    window.openPlayer(event, '$clearlessonref->type');
                }
            }";
            // Strip any new lines from the js.
            $info->onclick = trim(preg_replace('/\s\s+/', ' ', $onclickjs));
            break;
        default:
            $info->onclick = '';
            break;
    }

    // Populate the custom completion rules as key => value pairs, but only if the completion mode is 'automatic'.
    if ($coursemodule->completion == COMPLETION_TRACKING_AUTOMATIC) {
        // This rules asks that all videos in the resource be watched until the end.
        $info->customdata['customcompletionrules']['completionwatchedall'] = $clearlessonref->completionwatchedall;
    }

    $info->content = '';
    if ($coursemodule->showdescription) {
        // Convert intro to html. Do not filter cached version, filters run at display time.
        $info->content = format_module_intro('url', $clearlessonref, $coursemodule->id, false);
    }

    return $info;
}

/**
 * Return a list of page types
 * @param string $pagetype current page type
 * @param stdClass $parentcontext Block's parent context
 * @param stdClass $currentcontext Current context of block
 */
function clearlesson_page_type_list($pagetype, $parentcontext, $currentcontext) {
    $modulepagetype = array('mod-clearlesson-*' => get_string('page-mod-url-x', 'clearlesson'));
    return $modulepagetype;
}

/**
 * Export Clear Lesson resource contents.
 *
 * @return array of file content
 */
function clearlesson_export_contents($cm, $baseurl) {
    global $CFG, $DB;
    require_once("$CFG->dirroot/mod/clearlesson/locallib.php");
    $contents = array();
    $context = context_module::instance($cm->id);
    $course = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);
    $urlrecord = $DB->get_record('clearlesson', array('id' => $cm->instance), '*', MUST_EXIST);
    $fullurl = str_replace('&amp;', '&', clearlesson_get_full_url($urlrecord, $cm, $course));
    $isurl = clean_param($fullurl, PARAM_URL);
    if (empty($isurl)) {
        return null;
    }

    $url = array();
    $url['type'] = 'clearlesson';
    $url['filename']     = clean_param(format_string($urlrecord->name), PARAM_FILE);
    $url['filepath']     = null;
    $url['filesize']     = 0;
    $url['fileurl']      = $fullurl;
    $url['timecreated']  = null;
    $url['timemodified'] = $urlrecord->timemodified;
    $url['sortorder']    = null;
    $url['userid']       = null;
    $url['author']       = null;
    $url['license']      = null;
    $contents[] = $url;

    return $contents;
}

/**
 * Register the ability to handle drag and drop file uploads
 * @return array containing details of the files / types the mod can handle
 */
function clearlesson_dndupload_register() {
    return array('types' => array(
        array('identifier' => 'url', 'message' => get_string('createurl', 'url'))
    ));
}
/**
 * Handle a file that has been uploaded
 * @param object $uploadinfo details of the file / content that has been uploaded
 * @return int instance id of the newly created mod
 */
function clearlesson_dndupload_handle($uploadinfo) {
    // Gather all the required data.
    $data = new stdClass();
    $data->course = $uploadinfo->course->id;
    $data->name = $uploadinfo->displayname;
    $data->intro = '<p>'.$uploadinfo->displayname.'</p>';
    $data->introformat = FORMAT_HTML;
    $data->externalurl = clean_param($uploadinfo->content, PARAM_URL);
    $data->timemodified = time();
    // Set the display options to the site defaults.
    $config = get_config('clearlesson');
    $data->display = $config->display;
    $data->popupwidth = $config->popupwidth;
    $data->popupheight = $config->popupheight;
    $data->printintro = $config->printintro;
    return clearlesson_add_instance($data, null);
}

/**
 * Mark the activity completed (if required) and trigger the course_module_viewed event.
 *
 * @param  stdClass $clearlessonref   clearlesson record
 * @param  stdClass $course     course object
 * @param  stdClass $cm         course module object
 * @param  stdClass $context    context object
 * @since Moodle 3.0
 */
function clearlesson_view($clearlessonref, $course, $cm, $context) {
    global $DB, $USER;
    // Trigger course_module_viewed event.
    $params = array(
        'context' => $context,
        'objectid' => $clearlessonref->id
    );

    $event = \mod_clearlesson\event\course_module_viewed::create($params);
    $event->add_record_snapshot('course_modules', $cm);
    $event->add_record_snapshot('course', $course);
    $event->add_record_snapshot('clearlesson', $clearlessonref);
    $event->trigger();

    $newview = new \stdClass();
    $newview->userid = $USER->id;
    $newview->clearlessonid = $clearlessonref->id;
    $newview->timemodified = time();
    $DB->insert_record('clearlesson_track', $newview);

    // Completion.
    $completion = new completion_info($course);
    $completion->set_module_viewed($cm);
}

/**
 * Check if the module has any update that affects the current user since a given time.
 *
 * @param  cm_info $cm course module data
 * @param  int $from the time to check updates from
 * @param  array $filter  if we need to check only specific updates
 * @return stdClass an object with the different type of areas indicating if they were updated or not
 * @since Moodle 3.2
 */
function clearlesson_check_updates_since(cm_info $cm, $from, $filter = array()) {
    $updates = course_check_module_updates_since($cm, $from, array('content'), $filter);
    return $updates;
}
function clearlesson_redirect_post($data, array $headers = null) {
    global $CFG;
    $pluginconfig = get_config('clearlesson');
    $curl = new \curl;
    if (!empty($headers)) {
        foreach ($headers as $key => $header) {
            $curl->setHeader("$key:$header");
        }
    }
    $endpoint = new \moodle_url($pluginconfig->clearlessonurl.'/api/v1/userlogin.php');
    $response = json_decode($curl->post($endpoint, $data));
    if (isset($response->success)) {
        $url = new \moodle_url($response->authUrl);
        redirect($url);
    } else {
        if (debugging()) {
            var_dump($response);
        }
        throw new \moodle_exception(get_string('invalidresponse', 'clearlesson'));
    }
}
function clearlesson_build_url($url, $pluginconfig) {
    if (substr($pluginconfig->clearlessonurl, -1) != '/') {
        $pluginconfig->clearlessonurl .= '/';
    }
    if ($url->display == 1 AND $url->type == 'play') {
        $url->type = 'soloplay';
    }
    if ($url->type == 'series') {
        $url->type = 'collections/series';
    }
    $url = $pluginconfig->clearlessonurl.$url->type.'/'.$url->externalref;
    return $url;
}

function clearlesson_set_header($pluginconfig) {
    return array('Content-Type' => 'application/jose',
    'Authorization' => 'APIKEY '.$pluginconfig->apikey,
    'alg' => 'HS256');
}

function clearlesson_set_body($pluginconfig, $url) {
    GLOBAL $CFG, $USER;
    $userinfofields = array();
    $userinfofields['referrer'] = str_replace('https://', '', $CFG->wwwroot);
    foreach ($USER as $key => $value) {
        if (!empty($value)) {
            if (substr($key, 0, 14) == 'profile_field_') {
                $userinfofields[$key] = $value;
            }
        }
    }
    return array('APIKEY' => $pluginconfig->apikey,
    'origin' => $CFG->wwwroot,
    'firstName' => $USER->firstname,
    'email' => $USER->email,
    'lastName' => $USER->lastname,
    'date' => gmdate("Y-m-d\TH:i:s\Z"),
    'redirectUrl' => $url,
    'userInfoFields' => $userinfofields);
}

function clearlesson_get_resource_type_options() {
    return ['play' => 'video',
            'speakers' => 'speakers',
            'topics' => 'topics',
            'playlists' => 'playlists',
            'series' => 'series',
            'collections' => 'collections'];
}

function clearlesson_get_display_options($displayoptionsstring, $default = '') {
    $displayoptionsarray = explode(',', $displayoptionsstring);
    if ($default) {
        $options = resourcelib_get_displayoptions($displayoptionsarray, $default);
    } else {
        $options = resourcelib_get_displayoptions($displayoptionsarray);
    }
    if (in_array(CLEARLESSON_DISPLAY_MODAL, $displayoptionsarray)) {
        $options[CLEARLESSON_DISPLAY_MODAL] = get_string('displaytypemodal', 'clearlesson');
    }
    return $options;
}