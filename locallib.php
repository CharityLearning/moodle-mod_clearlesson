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
 * Private clearlesson module utility functions
 *
 * @package    mod_clearlesson
 * @copyright  2017 Josh Willcock  {@link http://josh.cloud}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;
require_once("$CFG->libdir/filelib.php");
require_once("$CFG->libdir/resourcelib.php");
require_once("$CFG->dirroot/mod/clearlesson/lib.php");

/**
 * This methods does weak clearlesson validation, we are looking for major problems only,
 * no strict RFE validation.
 *
 * @param $clearlesson
 * @return bool true is seems valid, false if definitely not valid clearlesson
 */
function clearlesson_appears_valid_clearlesson($clearlesson) {
    if (preg_match('/^(\/|https?:|ftp:)/i', $clearlesson)) {
        // Note: this is not exact validation, we look for severely malformed clearlessons only.
        return (bool)preg_match('/^[a-z]+:\/\/([^:@\s]+:[^@\s]+@)?[a-z0-9_\.\-]+(:[0-9]+)?(\/[^#]*)?(#.*)?$/i', $clearlesson);
    } else {
        return (bool)preg_match('/^[a-z]+:\/\/...*$/i', $clearlesson);
    }
}

/**
 * Fix common clearlesson problems that we want teachers to see fixed
 * the next time they edit the resource.
 *
 * This function does not include any XSS protection.
 *
 * @param string $clearlesson
 * @return string
 */
function clearlesson_fix_submitted_ref($clearlesson) {
    // Note: empty clearlessons are prevented in form validation.
    $clearlesson = trim($clearlesson);
    // Remove encoded entities - we want the raw URI here.
    $clearlesson = html_entity_decode($clearlesson, ENT_QUOTES, 'UTF-8');
    return $clearlesson;
}

// /**
//  * Return full clearlesson with all extra parameters
//  *
//  * This function does not include any XSS protection.
//  *
//  * @param string $clearlesson
//  * @param object $cm
//  * @param object $course
//  * @param object $config
//  * @return string clearlesson with & encoded as &amp;
//  */
// function clearlesson_get_full_clearlesson($clearlesson, $cm, $course, $config=null, $embed=null) {
//     $parameters = empty($clearlesson->parameters) ? array() : unserialize($clearlesson->parameters);

//     // Make sure there are no encoded entities, it is ok to do this twice.
//     if ($embed) {
//         $options = array('id' => $clearlesson->id, 'embed' => true);
//     } else {
//         $options = array('id' => $clearlesson->id);
//     }
//     $fullclearlesson = new moodle_url("/mod/clearlesson/senduser.php", $options);

//     if (preg_match('/^(\/|https?:|ftp:)/i', $fullclearlesson) or preg_match('|^/|', $fullclearlesson)) {
//         // Encode extra chars in clearlessons - this does not make it always valid, but it helps with some UTF-8 problems.
//         $allowed = "a-zA-Z0-9".preg_quote(';/?:@=&$_.+!*(),-#%', '/');
//         $fullclearlesson = preg_replace_callback("/[^$allowed]/", 'clearlesson_filter_callback', $fullclearlesson);
//     } else {
//         // Encode special chars only.
//         $fullclearlesson = str_replace('"', '%22', $fullclearlesson);
//         $fullclearlesson = str_replace('\'', '%27', $fullclearlesson);
//         $fullclearlesson = str_replace(' ', '%20', $fullclearlesson);
//         $fullclearlesson = str_replace('<', '%3C', $fullclearlesson);
//         $fullclearlesson = str_replace('>', '%3E', $fullclearlesson);
//     }

//     // Add variable clearlesson parameters.
//     if (!empty($parameters)) {
//         if (!$config) {
//             $config = get_config('clearlesson');
//         }
//         $paramvalues = clearlesson_get_variable_values($clearlesson, $cm, $course, $config);

//         foreach ($parameters as $parse => $parameter) {
//             if (isset($paramvalues[$parameter])) {
//                 $parameters[$parse] = rawclearlessonencode($parse).'='.rawclearlessonencode($paramvalues[$parameter]);
//             } else {
//                 unset($parameters[$parse]);
//             }
//         }
//     }

//     // Encode all & to &amp; entity.
//     $fullclearlesson = str_replace('&', '&amp;', $fullclearlesson);
//     $fullclearlesson = $fullclearlesson;
//     return $fullclearlesson;
// }

/**
 * Unicode encoding helper callback
 * @internal
 * @param array $matches
 * @return string
 */
function clearlesson_filter_callback($matches) {
    return rawclearlessonencode($matches[0]);
}

/**
 * Print clearlesson header.
 * @param object $clearlesson
 * @param object $cm
 * @param object $course
 * @return void
 */
function clearlesson_print_header($clearlesson, $cm, $course) {
    global $PAGE, $OUTPUT;

    $PAGE->set_title($course->shortname.': '.$clearlesson->name);
    $PAGE->set_heading($course->fullname);
    $PAGE->set_activity_record($clearlesson);
    echo $OUTPUT->header();
}

/**
 * Print clearlesson heading.
 * @param object $clearlesson
 * @param object $cm
 * @param object $course
 * @param bool $notused This variable is no longer used.
 * @return void
 */
function clearlesson_print_heading($clearlesson, $cm, $course, $notused = false) {
    global $OUTPUT;
    echo $OUTPUT->heading(format_string($clearlesson->name), 2);
}

/**
 * Print clearlesson introduction.
 * @param object $clearlesson
 * @param object $cm
 * @param object $course
 * @param bool $ignoresettings print even if not specified in modedit
 * @return void
 */
function clearlesson_print_intro($clearlesson, $cm, $course, $ignoresettings=false) {
    global $OUTPUT;

    $options = empty($clearlesson->displayoptions) ? array() : unserialize($clearlesson->displayoptions);
    if ($ignoresettings or !empty($options['printintro'])) {
        if (trim(strip_tags($clearlesson->intro))) {
            echo $OUTPUT->box_start('mod_introbox', 'clearlessonintro');
            echo format_module_intro('clearlesson', $clearlesson, $cm->id);
            echo $OUTPUT->box_end();
        }
    }
    // Echo the course id in a hidden data attribute.
    echo html_writer::empty_tag('div', array('id' => 'clearlesson-courseid', 'data-courseid' => $course->id));
    echo html_writer::end_tag('div');
}

// /**
//  * Display clearlesson frames.
//  * @param object $clearlesson
//  * @param object $cm
//  * @param object $course
//  * @return does not return
//  */
// function clearlesson_display_frame($clearlesson, $cm, $course) {
//     global $PAGE, $OUTPUT, $CFG;
//     $frame = optional_param('frameset', 'main', PARAM_ALPHA);

//     if ($frame === 'top') {
//         $PAGE->set_pagelayout('frametop');
//         clearlesson_print_header($clearlesson, $cm, $course);
//         clearlesson_print_heading($clearlesson, $cm, $course);
//         clearlesson_print_intro($clearlesson, $cm, $course);
//         echo $OUTPUT->footer();
//         die;

//     } else {
//         $config = get_config('clearlesson');
//         $context = context_module::instance($cm->id);
//         $exteclearlesson = clearlesson_get_full_clearlesson($clearlesson, $cm, $course, $config);
//         $navclearlesson = "$CFG->wwwroot/mod/clearlesson/view.php?id=$cm->id&frameset=top";
//         $coursecontext = context_course::instance($course->id);
//         $courseshortname = format_string($course->shortname, true, array('context' => $coursecontext));
//         $title = strip_tags($courseshortname.': '.format_string($clearlesson->name));
//         $framesize = $config->framesize;
//         $modulename = (get_string('modulename', 'clearlesson'));
//         $contentframetitle = s(format_string($clearlesson->name));
//         $dir = get_string('thisdirection', 'langconfig');

//         $extframe = <<<EOF
// <!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Frameset//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-frameset.dtd">
// <html dir="$dir">
//   <head>
//     <meta http-equiv="content-type" content="text/html; charset=utf-8" />
//     <title>$title</title>
//   </head>
//   <frameset rows="$framesize,*">
//     <frame src="$navclearlesson" title="$modulename"/>
//     <frame src="$exteclearlesson" title="$contentframetitle"/>
//   </frameset>
// </html>
// EOF;

//         @header('Content-Type: text/html; charset=utf-8');
//         echo $extframe;
//         die;
//     }
// }

// /**
//  * Print clearlesson info and link.
//  * @param object $clearlesson
//  * @param object $cm
//  * @param object $course
//  * @return does not return
//  */
// function clearlesson_print_workaround($clearlesson, $cm, $course) {
//     global $OUTPUT;

//     clearlesson_print_header($clearlesson, $cm, $course);
//     clearlesson_print_heading($clearlesson, $cm, $course, true);
//     clearlesson_print_intro($clearlesson, $cm, $course, true);

//     $fullclearlesson = clearlesson_get_full_clearlesson($clearlesson, $cm, $course);
//     // $display = clearlesson_get_final_display_type($clearlesson);
//     if ($clearlesson->display == RESOURCELIB_DISPLAY_POPUP) {
//         $jsfullclearlesson = addslashes_js($fullclearlesson);
//         $options = empty($clearlesson->displayoptions) ? array() : unserialize($clearlesson->displayoptions);
//         $width  = empty($options['popupwidth']) ? 620 : $options['popupwidth'];
//         $height = empty($options['popupheight']) ? 450 : $options['popupheight'];
//         $wh = "width=$width,height=$height,toolbar=no,location=no,menubar=no,copyhistory=no,
//         status=no,directories=no,scrollbars=yes,resizable=yes";
//         $extra = "onclick=\"window.open('$jsfullclearlesson', '', '$wh'); return false;\"";

//     } else if ($display == RESOURCELIB_DISPLAY_NEW) {
//         $extra = "onclick=\"this.target='_blank';\"";

//     } else {
//         $extra = '';
//     }

//     echo '<div class="clearlessonworkaround">';
//     print_string('clicktoopen', 'clearlesson', "<a href=\"$fullclearlesson\" $extra>$fullclearlesson</a>");
//     echo '</div>';

//     echo $OUTPUT->footer();
//     die;
// }

// /**
//  * Display embedded clearlesson file.
//  * @param object $clearlesson
//  * @param object $cm
//  * @param object $course
//  * @return does not return
//  */
// function clearlesson_display_embed($clearlesson, $cm, $course) {
//     global $CFG, $PAGE, $OUTPUT;

//     $fullclearlesson  = clearlesson_get_full_clearlesson($clearlesson, $cm, $course, null, true);
//     $title    = $clearlesson->name;

//     $link = html_writer::tag('a', $fullclearlesson, array('href' => str_replace('&amp;', '&', $fullclearlesson)));
//     $clicktoopen = get_string('clicktoopen', 'clearlesson', $link);
//     $moodleclearlesson = new moodle_url(str_replace('&amp;', '&', $fullclearlesson));
//     $extension = resourcelib_get_extension($moodleclearlesson);

//     $mediamanager = core_media_manager::instance($PAGE);
//     $embedoptions = array(
//         core_media_manager::OPTION_TRUSTED => true,
//         core_media_manager::OPTION_BLOCK => true
//     );
//     $code = resourcelib_embed_general($fullclearlesson, $title, $clicktoopen, 'text/html');
//     clearlesson_print_header($clearlesson, $cm, $course);
//     clearlesson_print_heading($clearlesson, $cm, $course);
//     echo $code;

//     clearlesson_print_intro($clearlesson, $cm, $course);

//     echo $OUTPUT->footer();
//     die;
// }

// /**
//  * Decide the best display format.
//  * @param object $clearlesson
//  * @return int display type constant
//  */
// function clearlesson_get_final_display_type($clearlesson) {
//     global $CFG;

//     if ($clearlesson->display != RESOURCELIB_DISPLAY_AUTO) {
//         return $clearlesson->display;
//     }
//     return RESOURCELIB_DISPLAY_OPEN;
// }

/**
 * Get the parameters that may be appended to clearlesson
 * @param object $config clearlesson module config options
 * @return array array describing opt groups
 */
function clearlesson_get_variable_options($config) {
    global $CFG;

    $options = array();
    $options[''] = array('' => get_string('chooseavariable', 'clearlesson'));

    $options[get_string('course')] = array(
        'courseid'        => 'id',
        'coursefullname'  => get_string('fullnamecourse'),
        'courseshortname' => get_string('shortnamecourse'),
        'courseidnumber'  => get_string('idnumbercourse'),
        'coursesummary'   => get_string('summary'),
        'courseformat'    => get_string('format'),
    );

    $options[get_string('modulename', 'clearlesson')] = array(
        'clearlessoninstance'     => 'id',
        'clearlessoncmid'         => 'cmid',
        'clearlessonname'         => get_string('name'),
        'clearlessonidnumber'     => get_string('idnumbermod'),
    );

    $options[get_string('miscellaneous')] = array(
        'sitename'        => get_string('fullsitename'),
        'serverclearlesson'       => get_string('serverclearlesson', 'clearlesson'),
        'currenttime'     => get_string('time'),
        'lang'            => get_string('language'),
    );
    if (!empty($config->secretphrase)) {
        $options[get_string('miscellaneous')]['encryptedcode'] = get_string('encryptedcode');
    }

    $options[get_string('user')] = array(
        'userid'          => 'id',
        'userusername'    => get_string('username'),
        'useridnumber'    => get_string('idnumber'),
        'userfirstname'   => get_string('firstname'),
        'userlastname'    => get_string('lastname'),
        'userfullname'    => get_string('fullnameuser'),
        'useremail'       => get_string('email'),
        'usericq'         => get_string('icqnumber'),
        'userphone1'      => get_string('phone1'),
        'userphone2'      => get_string('phone2'),
        'userinstitution' => get_string('institution'),
        'userdepartment'  => get_string('department'),
        'useraddress'     => get_string('address'),
        'usercity'        => get_string('city'),
        'usertimezone'    => get_string('timezone'),
        'userclearlesson'         => get_string('webpage'),
    );

    if ($config->rolesinparams) {
        $roles = role_fix_names(get_all_roles());
        $roleoptions = array();
        foreach ($roles as $role) {
            $roleoptions['course'.$role->shortname] = get_string('yourwordforx', '', $role->localname);
        }
        $options[get_string('roles')] = $roleoptions;
    }

    return $options;
}

/**
 * Get the parameter values that may be appended to clearlesson
 * @param object $clearlesson module instance
 * @param object $cm
 * @param object $course
 * @param object $config module config options
 * @return array of parameter values
 */
function clearlesson_get_variable_values($clearlesson, $cm, $course, $config) {
    global $USER, $CFG;

    $site = get_site();

    $coursecontext = context_course::instance($course->id);

    $values = array (
        'courseid'        => $course->id,
        'coursefullname'  => format_string($course->fullname),
        'courseshortname' => format_string($course->shortname, true, array('context' => $coursecontext)),
        'courseidnumber'  => $course->idnumber,
        'coursesummary'   => $course->summary,
        'courseformat'    => $course->format,
        'lang'            => current_language(),
        'sitename'        => format_string($site->fullname),
        'serverclearlesson'       => $CFG->wwwroot,
        'currenttime'     => time(),
        'clearlessoninstance'     => $clearlesson->id,
        'clearlessoncmid'         => $cm->id,
        'clearlessonname'         => format_string($clearlesson->name),
        'clearlessonidnumber'     => $cm->idnumber,
    );

    if (isloggedin()) {
        $values['userid']          = $USER->id;
        $values['userusername']    = $USER->username;
        $values['useridnumber']    = $USER->idnumber;
        $values['userfirstname']   = $USER->firstname;
        $values['userlastname']    = $USER->lastname;
        $values['userfullname']    = fullname($USER);
        $values['useremail']       = $USER->email;
        $values['usericq']         = $USER->icq;
        $values['userphone1']      = $USER->phone1;
        $values['userphone2']      = $USER->phone2;
        $values['userinstitution'] = $USER->institution;
        $values['userdepartment']  = $USER->department;
        $values['useraddress']     = $USER->address;
        $values['usercity']        = $USER->city;
        $now = new DateTime('now', core_date::get_user_timezone_object());
        $values['usertimezone']    = $now->getOffset() / 3600.0; // Value in hours for BC.
        $values['userclearlesson']         = $USER->clearlesson;
    }
    if (!empty($config->secretphrase)) {
        $values['encryptedcode'] = clearlesson_get_encrypted_parameter($clearlesson, $config);
    }
    if ($config->rolesinparams) {
        $coursecontext = context_course::instance($course->id);
        $roles = role_fix_names(get_all_roles($coursecontext), $coursecontext, ROLENAME_ALIAS);
        foreach ($roles as $role) {
            $values['course'.$role->shortname] = $role->localname;
        }
    }

    return $values;
}

/**
 * BC internal function
 * @param object $clearlesson
 * @param object $config
 * @return string
 */
function clearlesson_get_encrypted_parameter($clearlesson, $config) {
    global $CFG;

    if (file_exists("$CFG->dirroot/local/externserverfile.php")) {
        require_once("$CFG->dirroot/local/externserverfile.php");
        if (function_exists('extern_server_file')) {
            return extern_server_file($clearlesson, $config);
        }
    }
    return md5(getremoteaddr().$config->secretphrase);
}

/**
 * Optimised mimetype detection from general clearlesson
 * @param $fullclearlesson
 * @param int $size of the icon.
 * @return string|null mimetype or null when the filetype is not relevant.
 */
function clearlesson_guess_icon($fullclearlesson, $size = null) {
    global $CFG;
    require_once("$CFG->libdir/filelib.php");

    if (substr_count($fullclearlesson, '/') < 3 or substr($fullclearlesson, -1) === '/') {
        // Most probably default directory - index.php, index.html, etc. Return null because
        // we want to use the default module icon instead of the HTML file icon.
        return null;
    }

    $icon = file_extension_icon($fullclearlesson, $size);
    $htmlicon = file_extension_icon('.htm', $size);
    $unknownicon = file_extension_icon('', $size);

    // We do not want to return those icon types, the module icon is more appropriate.
    if ($icon === $unknownicon || $icon === $htmlicon) {
        return null;
    }

    return $icon;
}
