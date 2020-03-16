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
 * Remote User Sync.
 *
 * @package    mod_clearlesson
 * @copyright  2017 Josh Willcock  http://josh.cloud
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace mod_clearlesson\task;
defined('MOODLE_INTERNAL') || die();
GLOBAL $CFG;
require_once($CFG->dirroot.'/mod/clearlesson/lib.php');

class clearlesson_task extends \core\task\scheduled_task {
    public function get_name() {
        return 'Clear Lesson';
    }
    public function execute() {
        $this->sync();
    }
    public function sync() {
        require_once(dirname(__FILE__) . '../../../../../config.php');
        require_once(dirname(__FILE__) . '../../../lib/php-jws/Exception/JWSException.php');
        require_once(dirname(__FILE__) . '../../../lib/php-jws/Util/Base64Url.php');
        require_once(dirname(__FILE__) . '../../../lib/php-jws/Util/Json.php');
        require_once(dirname(__FILE__) . '../../../lib/php-jws/Exception/UnspecifiedAlgorithmException.php');
        require_once(dirname(__FILE__) . '../../../lib/php-jws/Algorithm/AlgorithmInterface.php');
        require_once(dirname(__FILE__) . '../../../lib/php-jws/Algorithm/RSA_SSA_PKCSv15.php');
        require_once(dirname(__FILE__) . '../../../lib.php');
        require_once(dirname(__FILE__) . '../../../lib/php-jws/JWS.php');
        require_once(dirname(__FILE__) . '../../../lib/php-jws/Algorithm/HMACAlgorithm.php');
        GLOBAL $DB, $CFG;
        require_once($CFG->libdir . '/filelib.php');
        $cltask = $DB->get_record('task_scheduled', array('classname' => '\mod_clearlesson\task\clearlesson_task'));
        $weekint = $cltask->lastruntime;
        $rawusersinfo = $DB->get_records_sql("SELECT * FROM {user} WHERE (timemodified  >= $weekint)");
        $users = array();
        foreach ($rawusersinfo as $rawuserinfo) {
            $processeduser = new \stdClass();
            $processeduser->email = $rawuserinfo->email;
            $processeduser->firstName = $rawuserinfo->firstname;
            $processeduser->lastName = $rawuserinfo->lastname;
            if ($rawuserinfo->deleted) {
                $processeduser->email = substr($rawuserinfo->username, 0, -11);
                $processeduser->deleted = true;
            } else {
                $processeduser->deleted = false;
                $processeduser->user_info_fields = profile_user_record($rawuserinfo->id);
                $processeduser->user_info_fields->referrer = str_replace('https://', '', $CFG->wwwroot);
            }
            $users[] = $processeduser;
        }
        $pluginconfig = get_config('clearlesson');
        $headers = clearlesson_set_header($pluginconfig);
        $body = array('origin' => $CFG->wwwroot,
        'users' => $users,
        'date' => gmdate("Y-m-d\TH:i:s\Z")
        );
    
        $jws = new \Gamegos\JWS\JWS();
        $body = $jws->encode($headers, $body, $pluginconfig->secretkey);
        $curl = new \curl;
        if (!empty($headers)) {
            foreach ($headers as $key => $header) {
                $curl->setHeader("$key:$header");
            }
        }
        $endpoint = new \moodle_url($pluginconfig->clearlessonurl.'/api/v1/usersync/');
        var_dump($curl->post($endpoint, $body));
        $response = json_decode($curl->post($endpoint, $body));
        var_dump($response);
    }
}