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
 * Display all available user profile fields
 *
 * @package   assignsubmission_noto
 * @copyright 2021 Enovation Solutions
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(dirname(__FILE__). '/../../../../config.php');

$password = required_param('password', PARAM_ALPHANUM);

require_login();

$config = get_config('assignsubmission_noto');
if (trim($password != trim($config->userprofilepassword))) {
    die('passwd does not match');
}
if (isset($USER->sesskey)) {
    $USER->sesskey = 'xxx';
}
print '<pre>'; print_r($USER); print '</pre>'; die("\n");
