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
 * This file contains the implementation of Noto API function calls
 *
 * @package assignsubmission_noto
 * @copyright 2020 Enovation {@link https://enovation.ie}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace assignsubmission_noto;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/filelib.php');

class notoapi {

    const STARTPOINT = './';
    const MAXDEPTH = 10;

    private $modconfig;
    private $curloptions = [];
    /**
     * Constructor - reads the global module settings
     */
    public function __construct ($courseid = null) {
        $this->modconfig = get_config('assignsubmission_noto');
        if ($this->modconfig->ethz) {
            if (empty($this->modconfig->apiurl)) {
                throw new \moodle_exception('"assignsubmission_noto | apiurl" not configured, see Site adm - Plugins - Activity modules - Assignment - Submission plugins - Jupiter Notebooks');
            } else {
                if (is_null($courseid)) {
                    $courseid = '';
                }
                $this->modconfig->apiurl = str_replace('[courseid]', $courseid, $this->modconfig->apiurl);
            }
            if (empty($this->modconfig->apiusername)) {
                throw new \moodle_exception('"assignsubmission_noto | apiusername" not configured, see Site adm - Plugins - Activity modules - Assignment - Submission plugins - Jupiter Notebooks');
            } else {
                $this->modconfig->apiuser = $this->modconfig->apiusername;
            }
            if (empty($this->modconfig->apisecretkey)) {
                throw new \moodle_exception('"assignsubmission_noto | apisecretkey" not configured, see Site adm - Plugins - Activity modules - Assignment - Submission plugins - Jupiter Notebooks');
            }
        } else {
            if (empty($this->modconfig->apiserver)) {
                throw new \moodle_exception('"assignsubmission_noto | apiserver" not configured, see Site adm - Plugins - Activity modules - Assignment - Submission plugins - Jupiter Notebooks');
            }
            if (empty($this->modconfig->apiwspath)) {
                throw new \moodle_exception('"assignsubmission_noto | apiwspath" not configured, see Site adm - Plugins - Activity modules - Assignment - Submission plugins - Jupiter Notebooks');
            }

            if (empty($this->modconfig->apiuser)) {
                throw new \moodle_exception('"assignsubmission_noto | apiuser" not configured, see Site adm - Plugins - Activity modules - Assignment - Submission plugins - Jupiter Notebooks');
            }
            if (empty($this->modconfig->apikey)) {
                throw new \moodle_exception('"assignsubmission_noto | apikey" not configured, see Site adm - Plugins - Activity modules - Assignment - Submission plugins - Jupiter Notebooks');
            }
            $this->modconfig->apiurl = sprintf('%s/%s', trim($this->modconfig->apiserver, '/'), trim($this->modconfig->apiwspath, '/'));
        }
        if (!empty($this->modconfig->connectiontimeout)) {
            $this->curloptions['CURLOPT_CONNECTTIMEOUT'] = $this->modconfig->connectiontimeout;
        }
        if (!empty($this->modconfig->executiontimeout)) {
            $this->curloptions['CURLOPT_TIMEOUT'] = $this->modconfig->executiontimeout;
        }
    }
    /**
     * /uzu API call (UnZip)
     * @param string $path
     * @param store_file $file - the seed zip of the Noto notebook
     * @param stdClass $user
     * @return array response
     */
    public function uzu (string $path, \stored_file $file, \stdClass $user = null) : \stdClass {
        global $USER;
        if (!$user) {
            $user = $USER;
        }
        $url = sprintf('%s/uzu', $this->modconfig->apiurl);
        $timestamp = time();
        $payload_user = [
            'id' => self::noto_userid($user),
            'primary_email' => $user->email,
            'auth_method' => empty($this->modconfig->authmethod) ? 'test' : $this->modconfig->authmethod,
        ];
        $payload = json_encode([
            'user'=> $payload_user,
            'destination' => $path,
        ]);
        $md5_payload = base64_encode(md5($payload, true));
        $key = $this->hash_payload($payload, $md5_payload, $timestamp);
        $param = [
            'user' => $this->modconfig->apiuser,
            'timestamp' => $timestamp,
            'payload' => $payload,
            'md5_payload' => $md5_payload,
            'key' => $key,
        ];
        $url = $url . '?' . http_build_query($param, '', '&');
        $curl = new \curl;
        $resp = $curl->post($url, ['file' => $file], $this->curloptions);
        $resp = json_decode($resp);
        if (!$resp) {
            throw new \moodle_exception('uzu(): Empty response');
        }
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \moodle_exception("uzu(): JSON decoding error (response): " . json_last_error_msg() . ' (code: ' . json_last_error() . ')');
        }
        if (isset($resp->return->code) && $resp->return->code !== 0) {
            if (isset($resp->return->status)) {
                throw new \moodle_exception(sprintf('uzu(): Error code: %d status: %s', $resp->return->code, $resp->return->status));
            } else {
                throw new \moodle_exception('uzu(): Error code: ' . $resp->return->code);
            }
        }
        if (!$resp->payload) {
            throw new \moodle_exception('uzu(): Empty response payload');
        }
        if (!$this->verify_payload($resp->payload, $resp->md5_payload)) {
            throw new \moodle_exception("uzu(): verification of payload failed");
        }
        $payload = json_decode($resp->payload);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \moodle_exception("uzu(): JSON decoding error (payload): " . json_last_error_msg() . ' (code: ' . json_last_error() . ')');
        }
        return $payload;
    }

    /**
     * /lof API call (List Of Files). It's an exect copy of lod(), it only outputs additionally files in directories
     * @param string $path
     * @param stdClass $user
     * @return array response
     */
    public function lof (string $path, \stdClass $user = null) : array {
        global $USER;
        if (!$user) {
            $user = $USER;
        }
        $url = sprintf('%s/lof', $this->modconfig->apiurl);
        $timestamp = time();
        $payload_user = [
            'id' => self::noto_userid($user),
            'primary_email' => $user->email,
            'auth_method' => empty($this->modconfig->authmethod) ? 'test' : $this->modconfig->authmethod,
        ];
        $payload = json_encode([
            'user'=> $payload_user,
            'path' => $path,
        ]);
        $md5_payload = base64_encode(md5($payload, true));
        $key = $this->hash_payload($payload, $md5_payload, $timestamp);
        $param = [
            'user' => $this->modconfig->apiuser,
            'timestamp' => $timestamp,
            'payload' => $payload,
            'md5_payload' => $md5_payload,
            'key' => $key,
        ];
        $curl = new \curl;
        $resp = $curl->get($url, $param, $this->curloptions);
        $resp = json_decode($resp);
        if (!$resp) {
            throw new \moodle_exception('lof(): Empty response');
        }
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \moodle_exception("lof(): JSON decoding error (response): " . json_last_error_msg() . ' (code: ' . json_last_error() . ')');
        }
        if (isset($resp->return->code) && $resp->return->code !== 0) {
            if (isset($resp->return->status)) {
                throw new \moodle_exception(sprintf('lof(): Error code: %d status: %s', $resp->return->code, $resp->return->status));
            } else {
                throw new \moodle_exception('lof(): Error code: ' . $resp->return->code);
            }
        }
        if (empty($resp->payload)) {
            throw new \moodle_exception('lof(): Empty response payload');
        }
        if (!$this->verify_payload($resp->payload, $resp->md5_payload)) {
            throw new \moodle_exception("lof(): verification of payload failed");
        }
        $payload = json_decode($resp->payload);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \moodle_exception("lof(): JSON decoding error (payload): " . json_last_error_msg() . ' (code: ' . json_last_error() . ')');
        }
        return $payload;
    }
    /**
     * /lod API call (List Of Directories)
     * @param string $path
     * @param stdClass $user
     * @return array response
     */
    public function lod (string $path, \stdClass $user = null) : \stdClass {
        global $USER;
        if (!$user) {
            $user = $USER;
        }
        $url = sprintf('%s/lod', $this->modconfig->apiurl);
        $timestamp = time();
        $payload_user = [
            'id' => self::noto_userid($user),
            'primary_email' => $user->email,
            'auth_method' => empty($this->modconfig->authmethod) ? 'test' : $this->modconfig->authmethod,
        ];
        $payload = json_encode([
            'user'=> $payload_user,
            'path' => $path,
        ]);
        $md5_payload = base64_encode(md5($payload, true));
        $key = $this->hash_payload($payload, $md5_payload, $timestamp);
        $param = [
            'user' => $this->modconfig->apiuser,
            'timestamp' => $timestamp,
            'payload' => $payload,
            'md5_payload' => $md5_payload,
            'key' => $key,
        ];
        $curl = new \curl;
        $resp = $curl->get($url, $param, $this->curloptions);
        $resp = json_decode($resp);
        if (!$resp) {
            throw new \moodle_exception('lod(): Empty response');
        }
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \moodle_exception("lod(): JSON decoding error (response): " . json_last_error_msg() . ' (code: ' . json_last_error() . ')');
        }
        if (isset($resp->return->code) && $resp->return->code !== 0) {
            if (isset($resp->return->status)) {
                throw new \moodle_exception(sprintf('lod(): Error code: %d status: %s', $resp->return->code, $resp->return->status));
            } else {
                throw new \moodle_exception('lod(): Error code: ' . $resp->return->code);
            }
        }
        if (!$resp->payload) {
            throw new \moodle_exception('lod(): Empty response payload');
        }
        if (!$this->verify_payload($resp->payload, $resp->md5_payload)) {
            throw new \moodle_exception("lod(): verification of payload failed");
        }
        $payload = json_decode($resp->payload);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \moodle_exception("lod(): JSON decoding error (payload): " . json_last_error_msg() . ' (code: ' . json_last_error() . ')');
        }
        return $payload;
    }

    /**
     * /ls API call
     * @param string $path
     * @param stdClass $user
     * @return array response
     */
    public function ls (string $path, \stdClass $user = null) : array {
        global $USER;
        if (!$user) {
            $user = $USER;
        }
        $url = sprintf('%s/ls', $this->modconfig->apiurl);
        $timestamp = time();
        $payload_user = [
            'id' => self::noto_userid($user),
            'primary_email' => $user->email,
            'auth_method' => empty($this->modconfig->authmethod) ? 'test' : $this->modconfig->authmethod,
        ];
        $payload = json_encode([
            'user' => $payload_user,
            'path' => $path,
        ]);
        $md5_payload = base64_encode(md5($payload, true));
        $key = $this->hash_payload($payload, $md5_payload, $timestamp);
        $param = [
            'user' => $this->modconfig->apiuser,
            'timestamp' => $timestamp,
            'payload' => $payload,
            'md5_payload' => $md5_payload,
            'key' => $key,
        ];
        $curl = new \curl;
        $resp = $curl->get($url, $param, $this->curloptions);
        if (!$resp) {
            throw new \moodle_exception('ls(): Empty response');
        }
        $resp = json_decode($resp);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \moodle_exception("ls(): JSON decoding error: " . json_last_error_msg() . ' (code: ' . json_last_error() . ')');
        }
        if (!$resp->payload) {
            throw new \moodle_exception('ls(): Empty response payload');
        }
        if (!$this->verify_payload($resp->payload, $resp->md5_payload)) {
            throw new \moodle_exception("ls(): verification of payload failed");
        }
        return json_decode($resp->payload);     # TODO: empty result possible?
    }

    /**
     * /zfs API call (download zipped directory)
     * @param string $path
     * @param stdClass $user
     * @return array response
     */
    public function zfs (string $path, \stdClass $user = null) : \stdClass {
        global $USER;
        if (!$user) {
            $user = $USER;
        }
        $url = sprintf('%s/zfs', $this->modconfig->apiurl);
        $timestamp = time();
        $payload_user = [
            'id' => self::noto_userid($user),
            'primary_email' => $user->email,
            'auth_method' => empty($this->modconfig->authmethod) ? 'test' : $this->modconfig->authmethod,
        ];
        $payload = json_encode([
            'user'=> $payload_user,
            'folder' => $path,
        ]);
        $md5_payload = base64_encode(md5($payload, true));
        $key = $this->hash_payload($payload, $md5_payload, $timestamp);
        $param = [
            'user' => $this->modconfig->apiuser,
            'timestamp' => $timestamp,
            'payload' => $payload,
            'md5_payload' => $md5_payload,
            'key' => $key,
        ];
        $curl = new \curl;
        $resp = $curl->get($url, $param, $this->curloptions);
        $resp = json_decode($resp);
        if (!$resp) {
            throw new \moodle_exception('zfs(): Empty response');
        }
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \moodle_exception("zfs(): JSON decoding error (response): " . json_last_error_msg() . ' (code: ' . json_last_error() . ')');
        }
        if (isset($resp->return->code) && $resp->return->code !== 0) {
            if (isset($resp->return->status)) {
                throw new \moodle_exception(sprintf('zfs(): Error code: %d status: %s', $resp->return->code, $resp->return->status));
            } else {
                throw new \moodle_exception('zfs(): Error code: ' . $resp->return->code);
            }
        }
        if (!$resp->payload) {
            throw new \moodle_exception('zfs(): Empty response payload');
        }
        if (!$this->verify_payload($resp->payload, $resp->md5_payload)) {
            throw new \moodle_exception("zfs(): verification of payload failed");
        }
        $payload = json_decode($resp->payload);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \moodle_exception("zfs(): JSON decoding error (payload): " . json_last_error_msg() . ' (code: ' . json_last_error() . ')');
        }
        return $payload;
    }

    /**
     * hash payload
     * @param string $payload
     * @param string $md5_payload
     * @param int $timestamp
     * @return string hashed payload
     */
    private function hash_payload (string $payload, string $md5_payload, int $timestamp) : string {
        if ($this->modconfig->ethz) {
            return base64_encode(hash_hmac('sha256', $this->modconfig->apiusername.$timestamp.$md5_payload,
                $this->modconfig->apisecretkey, true));
        } else {
            return base64_encode(hash_hmac('sha256', $this->modconfig->apiuser.$timestamp.$md5_payload,
                $this->modconfig->apikey, true));
        }
    }

    /**
     * verify received payload
     * @param string $payload
     * @param string $md5_payload - the value from the response
     * @return bool
     */
    private function verify_payload (string $payload, string $md5_payload): bool {
        $md5_payload_calculated = base64_encode(md5($payload, true));
        return $md5_payload_calculated == $md5_payload;
    }

    /**
     * Helper function - normalize a filesystem path
     * @param string $inputpath
     * @return string $outputpath
     */
    public static function normalize_localpath (string $inputpath): string {
        $outputpath = str_replace('//', '/', $inputpath);
        return $outputpath;
    }

    /**
     * Helper function - calculate the user id for sending to NOTO
     *
     * 1. $USER->idnumber (if it has exactly 6 digits)
     * 2. 'ch-epfl-moodle-'+$USER->idnumber (otherwise)
     * 3. 'ch-epfl-moodle-'+$USER->username (if no $USER->idnumber present)
     * @param stdClass $user - user object
     * @return string user id
     */
    private static function noto_userid (\stdClass $user): string {
        $config = get_config('assignsubmission_noto');
        if ($config->ethz) {
            return $config->apiusernameparamprefix.$user->{$config->apiusernameparam};
        } else {
            if (!empty($user->idnumber)) {
                    return (string) $user->idnumber;
            }
            return 'ch-epfl-moodle-username-' . $user->username;
        }
    }
}
