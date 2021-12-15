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
 * This file contains helper functions to create a Moodle form displaying jtree
 *
 * @package assignsubmission_noto
 * @copyright 2021 Enovation {@link https://enovation.ie}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace assignsubmission_noto;

defined('MOODLE_INTERNAL') || die();

class nototreerenderer {

    /**
     * generates html prepated to be used in jtree - from a data structure obtained from the lod() API call. Currently not used
     * @param array &$dirlistgroup - the resulting array with form HTML elements added
     * @param MoodleQuickForm $mform
     * @param stdClass $directory - a node from the response of the API call
     * @param string $path - a string directory to be added as a form element ID - otherwise the function knows only the current dirname, but none of parents
     * @param int depth - the current depth in the tree
     * @param int $maxdepth - not to calculate it many times in the recursive function
     * @return array - the moodle form group array
     */
    public static function display_lod_recursive (array &$dirlistgroup, \MoodleQuickForm $mform, \stdClass $directory, string $path, int $depth, int $maxdepth): array {
        global $OUTPUT;
        if ($depth > $maxdepth) {
            return $dirlistgroup;
        }
        # the top element from $directory is not included, it's the home directory of the user
        if ($path === notoapi::STARTPOINT) {
            $path = '';     # cosmetics, not to display './/Documentation'
            # nothing else
        } else {
            if ($depth > $maxdepth) {
                $dirlistgroup[] = $mform->createElement('html', sprintf('<li id="%s" data-jstree=\'{"icon":"%s", "disabled":true}\' >%s', $path, $OUTPUT->image_url('file', 'assignsubmission_noto'), $directory->name));
            } else {
                $dirlistgroup[] = $mform->createElement('html', sprintf('<li id="%s">%s', $path, $directory->name));
            }
        }
        if (isset($directory->children) && $directory->children) {
            $dirlistgroup[] = $mform->createElement('html', '<ul>');
            foreach ($directory->children as $child) {
                self::display_lod_recursive($dirlistgroup, $mform, $child, sprintf('%s/%s', $path, $child->name), $depth +1, $maxdepth);
            }
            $dirlistgroup[] = $mform->createElement('html', '</ul>');
        } else {
            $dirlistgroup[] = $mform->createElement('html', '</li>');
        }
        return $dirlistgroup;
    }

    /**
     * generates html prepated to be used in jtree - from a data structure obtained from the lof() API call
     * @param stdClass $directory - a node from the response of the API call
     * @param string $path - a string directory to be added as a form element ID - otherwise the function knows only the current dirname, but none of parents
     * @param int depth - the current depth in the tree
     * @param int $maxdepth - not to calculate it many times in the recursive function
     * @param array $data - result array of html elements
     * @return array - output result html
     */
    public static function display_lof_recursive (\stdClass $directory, string $path, int $depth, int $maxdepth, array &$data = []): array {
        global $CFG;
        $pass = false;
        if ($depth > $maxdepth) {
            return $data;
        }
        # the top element from $directory is not included, it's the home directory of the user
        if ($path === notoapi::STARTPOINT) {
            $path = '';     # cosmetics, not to display './/Documentation'
            $pass = true;
            $dataobject = new \stdClass();
        } else {
            if ($depth > $maxdepth) {
                $dataobject = (object) ['id' => $path, "icon" => $CFG->wwwroot.'/mod/assign/submission/noto/pix/file.png',
                    'state' => (object)['disabled' => true], 'text' => $directory->name];
            } else {
                if ($directory->type == 'directory') {
                    $dataobject = (object) ['id' => $path, 'text' => $directory->name];
                } else {
                    $dataobject = (object) ['id' => $path, "icon" => $CFG->wwwroot.'/mod/assign/submission/noto/pix/file.png',
                        'state' => (object)['disabled' => true], 'text' => $directory->name];
                }
            }
        }
        if (isset($directory->children) && $directory->children) {
            $dataobject->children = [];
            foreach ($directory->children as $child) {
                if ($pass) {
                    self::display_lof_recursive($child, sprintf('%s/%s', $path, $child->name), $depth +1, $maxdepth,
                        $data);
                } else {
                    self::display_lof_recursive($child, sprintf('%s/%s', $path, $child->name), $depth +1, $maxdepth,
                        $dataobject->children);
                }

            }
        }
        if (!$pass) {
            $data[] = $dataobject;
        }

        return $data;
    }
}
