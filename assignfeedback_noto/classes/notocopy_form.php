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
 * @package assignfeedback_noto
 * @copyright 2020 Enovation {@link https://enovation.ie}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace assignfeedback_noto;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/filelib.php');

class notocopy_form extends \moodleform {
    /**
     * Form definition
     */
    function definition() {
        global $DB;
        $cm = $this->_customdata['cm'];
        $id = $this->_customdata['id'];
        $mform = $this->_form;
        $mform->addElement('static', 'submitnotoforgrading_tree_teacherlabel', '', get_string('submitnotoforgrading_tree_teacherlabel', 'assignfeedback_noto'));
        $mform->addElement('text', 'assignsubmission_noto_directory', get_string('assignsubmission_noto_directory_destination', 'assignfeedback_noto').
            '<div id="submit-moodle"></div>', array('id'=>'assignsubmission_noto_directory', 'size'=>80));
        $mform->setType('assignsubmission_noto_directory', PARAM_URL);
        $mform->addHelpButton('assignsubmission_noto_directory', 'assignsubmission_noto_createcopy', 'assignsubmission_noto');
        $mform->freeze('assignsubmission_noto_directory');
        $mform->addElement('hidden', 'assignsubmission_noto_directory_h', '', array('id'=>'assignsubmission_noto_directory_h'));  # _h is for "hidden" if you're wondering
        $mform->setType('assignsubmission_noto_directory_h', PARAM_TEXT);
        $mform->addElement('hidden', 'id', $id);
        $mform->addElement('hidden', 'course', $cm->course);
        $mform->addElement('hidden', 'operation', 'plugingradingbatchoperation_noto_uploadnoto');
        $mform->setType('operation', PARAM_ALPHAEXT);
        $mform->addElement('hidden', 'action', 'viewpluginpage');
        $mform->setType('action', PARAM_ALPHA);
        $mform->addElement('hidden', 'pluginaction', 'uploadnoto');
        $mform->setType('pluginaction', PARAM_ALPHA);
        $mform->addElement('hidden', 'plugin', 'noto');
        $mform->setType('plugin', PARAM_PLUGIN);
        $mform->addElement('hidden', 'pluginsubtype', 'assignfeedback');
        $mform->setType('pluginsubtype', PARAM_PLUGIN);
        $mform->addElement('hidden', 'selectedusers', implode(',', $this->_customdata['users']));
        $mform->setType('selectedusers', PARAM_SEQUENCE);
        $mform->setType('id', PARAM_INT);
        \assign_feedback_noto::mform_add_catalog_tree($mform, $cm->course);
        $buttonarray=array();
        $buttonarray[] =& $mform->createElement('submit', 'reload', get_string('reloadtree', 'assignsubmission_noto'), ['id'=>'assignsubmission_noto_reloadtree_submit']);
        $buttonarray[] =& $mform->createElement('submit', 'submitbutton', get_string('copyfeedbacks', 'assignfeedback_noto'));
        $buttonarray[] =& $mform->createElement('submit', 'cancel', get_string('cancel'));
        $mform->addGroup($buttonarray, 'buttonar', '', array(' '), false);
    }
}
