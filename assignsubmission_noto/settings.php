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
 * This file defines the admin settings for this plugin
 *
 * @package   assignsubmission_noto
 * @copyright 2012 NetSpot {@link http://www.netspot.com.au}
 * @copyright 2020 Enovation {@link https://enovation.ie}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$settings->add(new admin_setting_configcheckbox('assignsubmission_noto/default',
                   new lang_string('default', 'assignsubmission_noto'),
                   new lang_string('default_help', 'assignsubmission_noto'), 0));
$settings->add(new admin_setting_configcheckbox('assignsubmission_noto/ethz',
                   new lang_string('ethzinstallation', 'assignsubmission_noto'),
                   new lang_string('ethzinstallation_help', 'assignsubmission_noto'), 0));
$settings->add(new admin_setting_configtext('assignsubmission_noto/apiserver',
                   new lang_string('apiserver', 'assignsubmission_noto'),
                   new lang_string('apiserver_help', 'assignsubmission_noto'), 'https://test-noto.epfl.ch', PARAM_URL, 60));
$settings->add(new admin_setting_configtext('assignsubmission_noto/apiwspath',
                   new lang_string('apiwspath', 'assignsubmission_noto'),
                   new lang_string('apiwspath_help', 'assignsubmission_noto'), '/api/assignment', PARAM_PATH));
$settings->add(new admin_setting_configtext('assignsubmission_noto/apinotebookpath',
                   new lang_string('apinotebookpath', 'assignsubmission_noto'),
                   new lang_string('apinotebookpath_help', 'assignsubmission_noto'), '/user-redirect/lab/tree', PARAM_PATH));
$settings->add(new admin_setting_configtext('assignsubmission_noto/apiuser',
                   new lang_string('apiuser', 'assignsubmission_noto'),
                   new lang_string('apiuser_help', 'assignsubmission_noto'), '', PARAM_TEXT));
$settings->add(new admin_setting_configtext('assignsubmission_noto/apikey',
                   new lang_string('apikey', 'assignsubmission_noto'),
                   new lang_string('apikey_help', 'assignsubmission_noto'), '', PARAM_TEXT));
$settings->add(new admin_setting_configtext('assignsubmission_noto/maxdepth',
                   new lang_string('maxdepth', 'assignsubmission_noto'),
                   new lang_string('maxdepth_help', 'assignsubmission_noto'), 10, PARAM_INT));
$settings->add(new admin_setting_configtext('assignsubmission_noto/userprofilepassword',
                   new lang_string('userprofilepassword', 'assignsubmission_noto'),
                   new lang_string('userprofilepassword_help', 'assignsubmission_noto'), '', PARAM_ALPHANUM));
$paramsoptions = ['test' => 'test', 'noto' => 'noto'];
$settings->add(new admin_setting_configselect('assignsubmission_noto/authmethod',
    new lang_string('authmethod', 'assignsubmission_noto'),
    new lang_string('authmethod_help', 'assignsubmission_noto'), 'test', $paramsoptions));

/* ===================== ETHZ installation settings =================================== */
$settings->add(new admin_setting_configtext('assignsubmission_noto/apiurl',
                   new lang_string('apiurl', 'assignsubmission_noto'),
                   new lang_string('apiurl_help', 'assignsubmission_noto'), 'https://web2-xxx-[courseid].vvv.ethz.ch', PARAM_RAW, 60));
$settings->add(new admin_setting_configtext('assignsubmission_noto/apiusername',
                   new lang_string('apiusername', 'assignsubmission_noto'),
                   new lang_string('apiusername_help', 'assignsubmission_noto'), '', PARAM_TEXT));
$settings->add(new admin_setting_configtext('assignsubmission_noto/apisecretkey',
                   new lang_string('apisecretkey', 'assignsubmission_noto'),
                   new lang_string('apisecretkey_help', 'assignsubmission_noto'), '', PARAM_TEXT));
$paramsoptions = ['username' => 'username', 'idnumber' => 'idnumber'];
$settings->add(new admin_setting_configselect('assignsubmission_noto/apiusernameparam',
                   new lang_string('apiusernameparam', 'assignsubmission_noto'),
                   new lang_string('apiusernameparam_help', 'assignsubmission_noto'), '', $paramsoptions));
$settings->add(new admin_setting_configtext('assignsubmission_noto/apiusernameparamprefix',
                   new lang_string('apiusernameparamprefix', 'assignsubmission_noto'),
                   new lang_string('apiusernameparamprefix_help', 'assignsubmission_noto'), '', PARAM_ALPHANUM));
/* ==================================================================================== */
$settings->add(new admin_setting_configtext('assignsubmission_noto/connectiontimeout',
    new lang_string('connectiontimeout', 'assignsubmission_noto'),
    new lang_string('connectiontimeout_help', 'assignsubmission_noto'), 3, PARAM_INT));
$settings->add(new admin_setting_configtext('assignsubmission_noto/executiontimeout',
    new lang_string('executiontimeout', 'assignsubmission_noto'),
    new lang_string('executiontimeout_help', 'assignsubmission_noto'), 10, PARAM_INT));

$PAGE->requires->js_call_amd('assignsubmission_noto/settings', 'init');
