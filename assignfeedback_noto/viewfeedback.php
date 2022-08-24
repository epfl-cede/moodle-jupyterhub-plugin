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
 * Students to view their own feedbacks
 *
 * @package   assignfeedback_noto
 * @copyright 2020 Enovation Solutions
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(dirname(__FILE__) . '/../../../../config.php');
require_once(dirname(__FILE__) . '/locallib.php');
require_once($CFG->libdir . '/formslib.php');
define('ASSIGNFEEDBACK_NOTO_FILEAREA',
        'feedback_noto');    # it is also a constant in class assign_submission_noto in locallib.php, but i'm not requiring it only for 1 constant

$feedbackid = required_param('id', PARAM_INT);
$feedbacknoto = $DB->get_record('assignfeedback_noto', array('id' => $feedbackid));

if (!$feedbacknoto) {
    throw new moodle_exception("Wrong feedback noto id");
}
//$cm = get_coursemodule_from_id('assign', $feedbacknoto->assignment);
$cm = get_coursemodule_from_instance('assign', $feedbacknoto->assignment);
if (!$cm) {
    throw new coding_exception("Cannot find assignment id " . $feedbacknoto->assignment);
}
$grade = $DB->get_record('assign_grades', array('id' => $feedbacknoto->grade));

$PAGE->set_url('/mod/assign/feedback/noto/viewfeedback.php', array('id' => feedbackid));

$context = context_module::instance($cm->id);
$PAGE->set_context($context);
$PAGE->set_cm($cm);
$student = $DB->get_record('user', ['id' => $grade->userid]);
if (!$student) {
    throw new \coding_exception('Cannot find student');
}
$PAGE->set_title(get_string('viewfeedback_pagetitle', 'assignfeedback_noto', fullname($student)));
$PAGE->set_heading(get_string('viewfeedback_pagetitle', 'assignfeedback_noto', fullname($student)));
$PAGE->set_pagelayout('standard');
require_login($cm->course);
$config = get_config('assignfeedbacknoto');

$noto_name = assign_feedback_noto::get_noto_config_name($cm->instance);

$form = new \assignfeedback_noto\notocopyfeedback_form(null, array('id' => $feedbackid, 'cm' => $cm));

if ($form->is_cancelled()) {
    redirect(new \moodle_url('/mod/assign/view.php', array('id' => $cm->id, 'action' => 'view')));
} else if ($data = $form->get_data()) {
    if (isset($data->cancel)) {
        redirect(new \moodle_url('/mod/assign/view.php', array('id' => $cm->id, 'action' => 'view')));
    }
    if (!$data->assignsubmission_noto_directory_h || isset($data->reload)) {
        redirect($PAGE->url);
        exit;
    }

    $fs = get_file_storage();
    $file_record = array(
            'contextid' => $context->id,
            'component' => 'assignfeedback_noto',
            'filearea' => ASSIGNFEEDBACK_NOTO_FILEAREA,
            'itemid' => $feedbacknoto->grade,
            'filepath' => '/',
            'filename' => sprintf('notebook_seed_feedback_user_%d.zip', $student->id),
    );
    $file = $fs->get_file($file_record['contextid'], $file_record['component'], $file_record['filearea'], $file_record['itemid'],
            $file_record['filepath'], $file_record['filename']);
    if (!$file) {
        throw new \moodle_exception("Feedback zip not found");
    }
    #$date_string = date('Ymd_HGs');
    $dest_path = sprintf('%s/%s/' . $noto_name . '_course%d_feedback', assignsubmission_noto\notoapi::STARTPOINT,
            $data->assignsubmission_noto_directory_h, $cm->course);
    $notoapi = new assignsubmission_noto\notoapi($cm->course);
    $upload_response = $notoapi->uzu($dest_path, $file);
    $new_directory_created = '';
    if (isset($upload_response->extractpath) && $upload_response->extractpath) {
        $strpos = strpos($upload_response->extractpath, assignsubmission_noto\notoapi::STARTPOINT);
        if ($strpos !== false) {
            $new_directory_created =
                    substr($upload_response->extractpath, strlen(assignsubmission_noto\notoapi::STARTPOINT) + $strpos);
        }
    }

    if (!$new_directory_created) {
        throw new \moodle_exception('Empty directory returned after uzu() API call');
    }
    $new_directory_created = assignsubmission_noto\notoapi::normalize_localpath($new_directory_created);
    if (!$config->ethz) {
        $apinotebookpath = sprintf('%s/%s', trim($config->apiserver, '/'), trim($config->apinotebookpath, '/'));
    }
    $stringidentifier = 'remotecopysuccessteacher';
    $params['backtoassignment'] = html_writer::link(new moodle_url("/mod/assign/view.php", ['id' => $cm->id, 'action' => 'view']),
            get_string('backtosubmissions', 'assignfeedback_noto'), ['class' => 'btn btn-primary']);
    $params['new_directory_created'] = $new_directory_created;
    if (!$config->ethz) {
        $params['redirect_link'] = html_writer::tag(
                'a',
                get_string('redirecttonoto', 'assignfeedback_noto'),
                array('href' => $apinotebookpath . $new_directory_created, 'target' => '_blank')
        );
        \core\notification::success(get_string($stringidentifier, 'assignfeedback_noto', (object) $params));
    } else {
        \core\notification::success(get_string($stringidentifier . '_ethz', 'assignsubmission_noto', (object) $params));
    }
    redirect(new \moodle_url('/mod/assign/view.php', array('id' => $cm->id, 'action' => 'view')));
    redirect($PAGE->url);
    exit;
}

print $OUTPUT->header();
$form->display();
print $OUTPUT->footer();

