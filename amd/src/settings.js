//
// This file is part of SCORM module for moodle
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
//
//
// SCORM video JS file
//
// @package    mod_scorm
// @copyright  2021 Enovation
//
// @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later.

define(['jquery'], function($) {

    function showETHZ() {
        $('#admin-apiserver').hide();
        $('#admin-apiwspath').hide();
        $('#admin-apinotebookpath').hide();
        $('#admin-apiuser').hide();
        $('#admin-apikey').hide();
        $('#admin-maxdepth').hide();
        $('#admin-userprofilepassword').hide();
        $('#admin-authmethod').hide();
        $('#admin-apiurl').show();
        $('#admin-apiusername').show();
        $('#admin-apisecretkey').show();
        $('#admin-apiusernameparam').show();
        $('#admin-apiusernameparamprefix').show();
    }

    function hideETHZ() {
        $('#admin-apiserver').show();
        $('#admin-apiwspath').show();
        $('#admin-apinotebookpath').show();
        $('#admin-apiuser').show();
        $('#admin-apikey').show();
        $('#admin-maxdepth').show();
        $('#admin-userprofilepassword').show();
        $('#admin-authmethod').show();
        $('#admin-apiurl').hide();
        $('#admin-apiusername').hide();
        $('#admin-apisecretkey').hide();
        $('#admin-apiusernameparam').hide();
        $('#admin-apiusernameparamprefix').hide();
    }

    function checkETHZ() {
        if ($('input[name=s_assignsubmission_noto_ethz]').is(':checked')) {
            showETHZ();
        } else {
            hideETHZ();
        }
    }

    return {
        init: function() {
            $(document).ready(function($) {
                checkETHZ();
                $('input[name=s_assignsubmission_noto_ethz]').on('change', function() {
                    checkETHZ();
                });
            });
        }
    };
});
