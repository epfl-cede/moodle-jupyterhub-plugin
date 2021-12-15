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
 * Create a remote notebook copy: this is a modified notocopy.php, only a student gets a copy of their ow submission instead
 *
 * @package   assignsubmission_noto
 * @copyright 2021 Enovation Solutions
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(dirname(__FILE__). '/../../../../config.php');
require_once(dirname(__FILE__). '/locallib.php');
require_once($CFG->libdir . '/formslib.php');
define('FILEAREA', 'noto_zips');    # it is also a constant in class assign_submission_noto in locallib.php, but i'm not requiring it only for 1 constant

class notocopy_form extends moodleform {
    /**
     * Form definition
     */
    function definition() {
        $cm = $this->_customdata['cm'];
        $id = $this->_customdata['id'];
        $submission = $this->_customdata['submission'];
        $mform = $this->_form; // Don't forget the underscore!
        $mform->addElement('static', 'youalreadysubmitted', '', get_string('youalreadysubmitted', 'assignsubmission_noto'));
        $mform->addElement('static', 'submissiondate', get_string('submissiondate', 'assignsubmission_noto'), date('D M j G:i:s T Y', $submission->timemodified));
        $mform->addElement('static', 'pagehelp', '', get_string('submissioncopytitle', 'assignsubmission_noto'));
        $mform->addElement('text', 'assignsubmission_noto_directory', get_string('assignsubmission_noto_directory_destination', 'assignsubmission_noto').
            '<div id="submit-jupyter"></div>', array('id'=>'assignsubmission_noto_directory', 'size'=>80));
        $mform->setType('assignsubmission_noto_directory', PARAM_URL);
        $mform->addHelpButton('assignsubmission_noto_directory', 'assignsubmission_noto_createcopy', 'assignsubmission_noto');
        $mform->freeze('assignsubmission_noto_directory');
        $mform->addElement('hidden', 'assignsubmission_noto_directory_h', '', array('id'=>'assignsubmission_noto_directory_h'));  # _h is for "hidden" if you're wondering
        $mform->setType('assignsubmission_noto_directory_h', PARAM_TEXT);
        $mform->addElement('hidden', 'id', $id);
        $mform->setType('id', PARAM_INT);
        assign_submission_noto::mform_add_catalog_tree($mform, $cm->course);
        $buttonarray=array();
        $buttonarray[] =& $mform->createElement('submit', 'reload', get_string('reloadtree', 'assignsubmission_noto'), ['id'=>'assignsubmission_noto_reloadtree_submit']);
        $buttonarray[] =& $mform->createElement('submit', 'submitbutton', get_string('copysubmission', 'assignsubmission_noto'));
        $buttonarray[] =& $mform->createElement('submit', 'cancel', get_string('cancel'));
        $mform->addGroup($buttonarray, 'buttonar', '', array(' '), false);
    }
}

require_login();

$submissionid = required_param('id', PARAM_INT);
$submission = $DB->get_record('assign_submission', array('id'=>$submissionid));

if (!$submission) {
    throw new moodle_exception("Wrong submission id");
}

$noto_submission =  $DB->get_record('assignsubmission_noto', array('assignment'=>$submission->assignment, 'submission'=>$submissionid));
if (!$noto_submission) {
    throw new moodle_exception("Not submitted");
}

$cm = get_coursemodule_from_instance('assign', $submission->assignment);
if (!$cm) {
    throw new coding_exception("Cannot find assignment id " . $submission->assignment);
}

$context = context_module::instance($cm->id);
$noto_name = assign_submission_noto::get_noto_config_name($cm->instance);

$PAGE->set_url('/mod/assign/submission/noto/submissioncopy.php', array('id'=>$submission->id));
$PAGE->set_context($context);
$PAGE->set_cm($cm);
$PAGE->set_title(get_string('submissioncopy_pagetitle', 'assignsubmission_noto'));
$PAGE->set_heading(get_string('submissioncopy_pagetitle', 'assignsubmission_noto'));
$PAGE->set_pagelayout('standard');
require_login($cm->course);
require_capability('mod/assign:submit', $context);

$form = new notocopy_form(null, array('cm'=>$cm, 'id'=>$submission->id, 'submission'=>$submission));

if ($form->is_cancelled()) {
    redirect(new \moodle_url('/mod/assign/view.php', array('id'=>$cm->id, 'action'=>'view')));
} else if ($data = $form->get_data()) {
    if (isset($data->cancel)) {
        redirect(new \moodle_url('/mod/assign/view.php', array('id'=>$cm->id, 'action'=>'view')));
    }
    if (isset($data->reload)) {
        redirect($PAGE->url);
        exit;
    }
    if (!$data->assignsubmission_noto_directory_h) {
        redirect($PAGE->url, get_string('nothingchosen', 'assignsubmission_noto'), null,  \core\output\notification::NOTIFY_ERROR);
        exit;
    }
    $fs = get_file_storage();
    # student's own submission zip
    $file_record = array(
        'contextid'=>$context->id,
        'component'=>'assignsubmission_noto',
        'filearea'=>FILEAREA,
        'itemid'=>$cm->instance,
        'filepath'=>'/',
        'filename'=>sprintf($noto_name.'_user%s.zip', $USER->id),
    );
    $file = $fs->get_file($file_record['contextid'], $file_record['component'], $file_record['filearea'], $file_record['itemid'], $file_record['filepath'], $file_record['filename']);
    if (!$file) {
        throw new \moodle_exception("Submission zip not found");
    }
    $date_string = date('Ymd_HGs');
    $dest_path = sprintf('%s/%s/'.$noto_name, assignsubmission_noto\notoapi::STARTPOINT, $data->assignsubmission_noto_directory_h);
    $notoapi = new assignsubmission_noto\notoapi($cm->course);
    $upload_response = $notoapi->uzu($dest_path, $file);
    // [extractpath] => /test2/.///dir1/course2_assignment2-V2/test0/test0.1/course2_assignment2
    $new_directory_created = '';
    if (isset($upload_response->extractpath) && $upload_response->extractpath) {
        $strpos = strpos($upload_response->extractpath, assignsubmission_noto\notoapi::STARTPOINT);
        if ($strpos!== false) {
            $new_directory_created = substr($upload_response->extractpath, strlen(assignsubmission_noto\notoapi::STARTPOINT) + $strpos);
        }
    }
    if (!$new_directory_created) {
        throw new \moodle_exception('Empty directory returned after uzu() API call');
    }
    $new_directory_created = assignsubmission_noto\notoapi::normalize_localpath($new_directory_created);
    $config = get_config('assignsubmission_noto');
    if (!$config->ethz) {
        $apinotebookpath = sprintf('%s/%s', trim($config->apiserver, '/'), trim($config->apinotebookpath, '/'));
    }
    $notoremotecopy = $DB->get_record('assignsubmission_noto_copies', array('userid'=>$USER->id, 'assignmentid'=>$submission->assignment));
    if ($notoremotecopy) {
        $notoremotecopy->paths = isset($notoremotecopy->paths) ? sprintf("%s\n%s", $notoremotecopy->paths, $new_directory_created) : $new_directory_created;
        $notoremotecopy->timecreated = time();
        $updatestatus = $DB->update_record('assignsubmission_noto_copies', $notoremotecopy);
    } else {
        $notoremotecopy = new stdClass();
        $notoremotecopy->paths = $new_directory_created;
        $notoremotecopy->userid = $USER->id;
        $notoremotecopy->assignmentid = $submission->assignment;
        $notoremotecopy->timecreated = time();
        $notoremotecopy->id = $DB->insert_record('assignsubmission_noto_copies', $notoremotecopy);
    }
    $params['new_directory_created'] = $new_directory_created;
    $params['backtoassignment'] = html_writer::link(new moodle_url("/mod/assign/view.php", ['id' => $cm->id]),
        get_string('backtoassignment','assignsubmission_noto'), ['class' => 'btn btn-primary']);
    if (!$config->ethz) {
        $params['redirect_link'] = html_writer::tag(
            'a',
            get_string('redirecttonoto', 'assignsubmission_noto'),
            array('href' => $apinotebookpath . $new_directory_created, 'target' => '_blank')
        );
        \core\notification::success(get_string('remotecopysuccess', 'assignsubmission_noto', (object)$params));
    } else {
        \core\notification::success(get_string('remotecopysuccess_ethz', 'assignsubmission_noto', (object)$params));
    }
    redirect($PAGE->url);
}

print $OUTPUT->header();
$form->display();
print $OUTPUT->footer();

