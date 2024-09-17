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
 * Search area for mod_clearlesson activities.
 *
 * @package    mod_clearlesson
 * @copyright  2024 Dan Watkins
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_clearlesson;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/mod/clearlesson/lib.php');
require_once(dirname(__FILE__) . '/../../../config.php');
require_once(dirname(__FILE__) . '/../lib/php-jws/Exception/JWSException.php');
require_once(dirname(__FILE__) . '/../lib/php-jws/Util/Base64Url.php');
require_once(dirname(__FILE__) . '/../lib/php-jws/Util/Json.php');
require_once(dirname(__FILE__) . '/../lib/php-jws/Exception/UnspecifiedAlgorithmException.php');
require_once(dirname(__FILE__) . '/../lib/php-jws/Algorithm/AlgorithmInterface.php');
require_once(dirname(__FILE__) . '/../lib/php-jws/Algorithm/RSA_SSA_PKCSv15.php');
require_once(dirname(__FILE__) . '/../lib.php');
require_once(dirname(__FILE__) . '/../lib/php-jws/JWS.php');
require_once(dirname(__FILE__) . '/../lib/php-jws/Algorithm/HMACAlgorithm.php');
require_once($CFG->libdir . '/filelib.php');

/**
 * Calls the clearlessons API.
 *
 * @package    mod_clearlesson
 * @copyright  2017 Josh Willcock {@link http://josh.cloud}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class call {

    /**
     * Initiates a call to the clearlessons API.
     * 
     * @param string $endpoint The endpoint to call.
     * @param array $params The parameters to pass to the endpoint.
     */
    public static function initate_call(string $endpoint, array $params = []): string {
        $pluginconfig = get_config('clearlesson');
        $endpoint = new \moodle_url($pluginconfig->clearlessonurl . $endpoint);
        $endpoint = $endpoint->__toString();

        $headers = clearlesson_set_header($pluginconfig);
        $body = clearlesson_set_body($pluginconfig, $endpoint);

        foreach ($params as $key => $param) {
            $body[$key] = $param;
        }

        $jws = new \Gamegos\JWS\JWS();
        $data = $jws->encode($headers, $body, $pluginconfig->secretkey);

        $curl = new \curl;
        if (!empty($headers)) {
            foreach ($headers as $key => $header) {
                $curl->setHeader("$key:$header");
            }
        }
        return $curl->post($endpoint, $data);
    }

    /**
     * Gets potential resources.
     *
     * @param string $type The type of resource.
     * @param string $query The search query.
     */
    public static function get_potential_resources(string $type, string $query = '', $exactref = false): array {
        $response = self::initate_call('/api/v1/get_potential_resources.php', ['type' => $type,
                                                                    'query' => $query,
                                                                    'exactref' => $exactref,
                                                                    'small' => true]);

        // debugging 
        // var_dump($response);
        $decodedresponse = json_decode($response, true);
        // var_dump($decodedresponse);
        return $decodedresponse['records'];
    }

    /**
     * Gets the browser form data.
     * 
     * @param string $type The type of resource.
     */
    public static function get_browserform_data(string $type): array {
        $response = self::initate_call('/api/v1/get_browserform_data.php', ['type' => $type]);
        // debugging 
        // var_dump($response);
        $decodedresponse = json_decode($response, true);
        $decodedresponse['records'][$type] = true;
        if ($type == 'series') {
            $decodedresponse['records']['isseries'] = true;
        }
        // var_dump($decodedresponse);
        return $decodedresponse['records'];
    }

    /**
     * Gets the player form data.
     * 
     * @param string $type The type of resource.
     * @param string $externalref The externalref of the resource.
     * @param int $position The position of the resource.
     */
    public static function get_playerform_data(string $type, string $externalref, int $position = 1): array {
        $response = self::initate_call('/api/v1/get_playerform_data.php', ['type' => $type,
                                                                    'externalref' => $externalref,
                                                                    'position' => $position]);
        // debugging 
        // var_dump($response);
        $decodedresponse = json_decode($response, true);
        return $decodedresponse['records'];
    }

    /**
     * Gets the menuform data.
     * 
     * @param string $type The type of resource.
     * @param string $externalref The externalref of the resource.
     */
    public static function get_menuform_data(string $type, string $externalref): array {
        $response = self::initate_call('/api/v1/get_menuform_data.php', ['type' => $type,
                                                                    'externalref' => $externalref]);
        // debugging 
        // var_dump($response);
        $decodedresponse = json_decode($response, true);
        // var_dump($decodedresponse);
        return $decodedresponse['records'];
    }
}