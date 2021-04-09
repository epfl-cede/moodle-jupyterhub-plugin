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

require_login();
if (!is_siteadmin()) {
    exit();
}

$userid = optional_param('id', '0', PARAM_INT);

if (!$userid) {
    # redirect to self with the parameter so the admin does not have to guess the syntax
    redirect(new moodle_url('/mod/assign/submission/noto/userprofiles.php', array('id'=>$USER->id)));
    exit();
}

$user = $DB->get_record('user', array('id'=>$userid));
if (!$user) {
    printf("no such user id %d\n", $userid); 
    exit();
}

profile_load_custom_fields($user);

# will not show these even for the admin
if (isset($user->password)) {
    $user->password = 'no passwd';
}
if (isset($user->salt)) {
    $user->salt = 'no salt';
}

# transform to "profile_field_fieldname"
foreach ($user->profile as $fieldname=>$value) {
    $user->{'profile_field_'.$fieldname} = $value;
}
unset($user->profile);

print '<pre>'; print_r($user); print '</pre>'; 
die("\n"); 
