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
 * Web service for assignsubmission_noto
 * @package    assignsubmission_noto
 * @subpackage db
 * @since      Moodle 2.4
 * @copyright  2021 Enovation
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$functions = array(
        'assignsubmission_noto_get_jstree_json' => array(
                'classname'     => 'assignsubmission_noto_external',
                'methodname'    => 'get_jstree_json',
                'classpath'     => 'mod/assign/submission/noto/externallib.php',
                'description'   => 'Get the html of the jstree',
                'type'          => 'read',
                'ajax'          => true,
                'capabilities'  => 'mod/assign:view',
                'loginrequired' => true,
        ),
);
