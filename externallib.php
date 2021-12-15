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
 * External assignsubmission_noto API
 *
 * @package    assignsubmission_noto
 * @since      Moodle 3.9
 * @copyright  2021 Enovation
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

require_once("$CFG->libdir/externallib.php");
require_once("locallib.php");


/**
 * Assign submission noto functions
 * @copyright 2021 Enovation
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class assignsubmission_noto_external extends external_api {

    /**
     * Returns description of method parameters
     *
     * @return external_function_parameters
     * @since  Moodle 3.9
     */
    public static function get_jstree_json_parameters() {
        return new external_function_parameters(
            array(
                'courseid' => new external_value(PARAM_INT, 'Assignment course id')
            )
        );
    }

    /**
     * @param int $courseid a required course id parameter
     * @return string with jstree HTML
     * @since  Moodle 3.9
     */
    public static function get_jstree_json($courseid) {

        $params = self::validate_parameters(
            self::get_jstree_json_parameters(),
            array(
                'courseid' => $courseid
            )
        );
        $treejson = 'null';
        $warnings = array();
        $config = get_config('assignsubmission_noto');
        $maxdepth = assignsubmission_noto\notoapi::MAXDEPTH;
        if (isset($config->maxdepth) && $config->maxdepth) {
            $maxdepth = $config->maxdepth;
        }
        $notoapi = new assignsubmission_noto\notoapi($courseid);
        $dirlist_top = new stdClass();
        $dirlist_top->name = assignsubmission_noto\notoapi::STARTPOINT;
        $dirlist_top->type = 'directory';
        try {
            $dirlist_top->children = $notoapi->lof(assignsubmission_noto\notoapi::STARTPOINT);
            $treestructure = assignsubmission_noto\nototreerenderer::display_lof_recursive($dirlist_top, assignsubmission_noto\notoapi::STARTPOINT, 0, $maxdepth);
            $treejson = json_encode($treestructure, JSON_UNESCAPED_SLASHES);
        } catch (\moodle_exception $e) {
            if ($e->errorcode == assign_submission_noto::E404) {
                $warnings[] = ['warningcode' => 404,
                    'message' => get_string('notoaccount_notfound', 'assignsubmission_noto')];
            } else {
                $warnings[] = ['warningcode' => 400,
                    'message' =>$e->getMessage()];
            }
        }
        $result = array(
            'result' => $treejson,
            'warnings' => $warnings
        );
        return $result;
    }

    /**
     * Describes the return value for assignsubmission_noto
     *
     * @return external_single_structure
     * @since Moodle 3.9
     */
    public static function get_jstree_json_returns() {
        return new external_single_structure(
            array(
                'result' => new external_value(PARAM_RAW, 'jstree data json'),
                'warnings'  => new external_warnings('item can be \'course\' (errorcode 1 or 2) or \'module\' (errorcode 1)',
                    'When item is a course then itemid is a course id. When the item is a module then itemid is a module id',
                    'errorcode can be 1 (no access rights) or 2 (not enrolled or no permissions)')
            )
        );
    }
}
