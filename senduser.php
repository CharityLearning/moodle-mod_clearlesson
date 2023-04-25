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
 * .
 *
 * @package    mod_clearlesson
 * @copyright  2017 Josh Willcock  {@link http://josh.cloud}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
require_once(dirname(__FILE__) . '/../../config.php');
require_once(dirname(__FILE__) . '/lib/php-jws/Exception/JWSException.php');
require_once(dirname(__FILE__) . '/lib/php-jws/Util/Base64Url.php');
require_once(dirname(__FILE__) . '/lib/php-jws/Util/Json.php');
require_once(dirname(__FILE__) . '/lib/php-jws/Exception/UnspecifiedAlgorithmException.php');
require_once(dirname(__FILE__) . '/lib/php-jws/Algorithm/AlgorithmInterface.php');
require_once(dirname(__FILE__) . '/lib/php-jws/Algorithm/RSA_SSA_PKCSv15.php');
require_once(dirname(__FILE__) . '/lib.php');
require_once(dirname(__FILE__) . '/lib/php-jws/JWS.php');
require_once(dirname(__FILE__) . '/lib/php-jws/Algorithm/HMACAlgorithm.php');
require_once($CFG->libdir . '/filelib.php');
require_login();
$id = optional_param('id', null, PARAM_INT);
$redirect = optional_param('redirect', '', PARAM_URL);
$emailtoken = optional_param('email_token', '', PARAM_TEXT);
global $DB, $USER;
$pluginconfig = get_config('clearlesson');
if (is_null($id)) {
    if (!is_null($redirect)) {
        if (substr($redirect, 0, 1 ) !== "/") {
            $redirect = '/'.$redirect;
        }
        $url = new \moodle_url($pluginconfig->clearlessonurl.$redirect, array('email_token' => $emailtoken));
        // TODO: Set Anchor on for legacy
        // Temporary - Remove set anchor when HomepageMedia sites are gone.
        $url = $url->__toString();
    } else {
        $url = $pluginconfig->clearlessonurl;
    }
} else {
    $record = $DB->get_record('clearlesson', array('id' => $id));
    if ($record) {
        $url = clearlesson_build_url($record, $pluginconfig);
    } else {
        notice(get_string('invalidstoredurl', 'url'), new moodle_url('/course/view.php', array('id' => $cm->course)));
        die;
    }
}
$headers = clearlesson_set_header($pluginconfig);
$body = clearlesson_set_body($pluginconfig, $url);
$jws = new Gamegos\JWS\JWS();
$data = $jws->encode($headers, $body, $pluginconfig->secretkey);
clearlesson_redirect_post($data, $headers);
