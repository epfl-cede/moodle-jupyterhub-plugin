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
 * Students to view their own submissions
 *
 * @package   assignsubmission_noto
 * @copyright 2020 Enovation Solutions
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(dirname(__FILE__). '/../../../../config.php');
require_once(dirname(__FILE__). '/locallib.php');
require_once($CFG->libdir . '/formslib.php');
define('FILEAREA', 'noto_zips');    # it is also a constant in class assign_submission_noto in locallib.php, but i'm not requiring it only for 1 constant

$submissionid = required_param('id', PARAM_INT);
$submission = $DB->get_record('assign_submission', array('id'=>$submissionid));

if (!$submission) {
    throw new moodle_exception("Wrong submission id");
}

$cm = get_coursemodule_from_instance('assign', $submission->assignment);
if (!$cm) {
    throw new coding_exception("Cannot find assignment id " . $submission->assignment);
}

if (!$DB->record_exists('assignsubmission_noto', array('assignment'=>$cm->instance, 'submission'=>$submission->id))) {
    throw new moodle_exception("Wrong NOTO submission id");
}

$PAGE->set_url('/mod/assign/submission/noto/viewsubmissions.php', array('id'=>$submission->id));
$context = context_module::instance($cm->id);
$PAGE->set_context($context);
$PAGE->set_cm($cm);
$student = $DB->get_record('user', ['id'=>$submission->userid]);
if (!$student) {
    throw new \coding_exception('Cannot find student');
}
$PAGE->set_title(get_string('viewsubmission_pagetitle', 'assignsubmission_noto', fullname($student)));
$PAGE->set_heading(get_string('viewsubmission_pagetitle', 'assignsubmission_noto', fullname($student)));
$PAGE->set_pagelayout('standard');
require_login($cm->course);
$config = get_config('assignsubmission_noto');
if (!has_capability('mod/assign:grade', $context) && !$config->ethz) {
    # this is a student, redirect them to NOTO
    $existing_submissions = $DB->get_record('assignsubmission_noto', array('assignment'=>$cm->instance, 'submission'=>$submission->id));
    $apinotebookpath = sprintf('%s/%s', trim($config->apiserver, '/'), trim($config->apinotebookpath, '/'));
    if ($existing_submissions && $existing_submissions->directory) {
        $directories = explode("\n", $existing_submissions->directory);
        $most_recent_submission = end($directories);
        redirect(new \moodle_url(sprintf('%s/%s', trim($apinotebookpath, '/'), trim($most_recent_submission, '/'))));
        exit();
    }
    # fallback, should never happen
    redirect(new \moodle_url($apinotebookpath));
    exit();
}
require_capability('mod/assign:grade', $context);

$noto_name = assign_submission_noto::get_noto_config_name($cm->instance);

$form = new \assignsubmission_noto\notocopy_form(null, array('submission'=>$submission, 'cm'=>$cm));

if ($form->is_cancelled()) {
    redirect(new \moodle_url('/mod/assign/view.php', array('id'=>$cm->id, 'action'=>'view')));
} else if ($data = $form->get_data()) {
    if (isset($data->cancel)) {
        redirect(new \moodle_url('/mod/assign/view.php', array('id'=>$cm->id, 'action'=>'view')));
    }
    if (!$data->assignsubmission_noto_directory_h || isset($data->reload)) {
        redirect($PAGE->url);
        exit;
    }

    $fs = get_file_storage();
    $file_record = array(
        'contextid'=>$context->id,
        'component'=>'assignsubmission_noto',
        'filearea'=>FILEAREA,
        'itemid'=>$cm->instance,
        'filepath'=>'/',
        'filename'=>sprintf($noto_name.'_user%s.zip', $submission->userid),
    );
    $file = $fs->get_file($file_record['contextid'], $file_record['component'], $file_record['filearea'], $file_record['itemid'], $file_record['filepath'], $file_record['filename']);
    if (!$file) {
        throw new \moodle_exception("Submissison zip not found");
    }
    #$date_string = date('Ymd_HGs');
    $sub_user = $DB->get_record("user", array("id"  => $submission->userid));
//    $dest_path = sprintf('%s/%s/'.$noto_name.'_student%d', assignsubmission_noto\notoapi::STARTPOINT, $data->assignsubmission_noto_directory_h, $submission->userid);
    $dest_path = sprintf('%s/%s/'.$noto_name.'_course%d_student%d_submission%d_%s', assignsubmission_noto\notoapi::STARTPOINT, $data->assignsubmission_noto_directory_h, $cm->course ,$submission->userid,$submissionid,$sub_user->firstname . '_' . $sub_user->lastname);
    $notoapi = new assignsubmission_noto\notoapi($cm->course);
    $upload_response = $notoapi->uzu($dest_path, $file);
    // [extractpath] => /test2/.///dir1/course2_assignment2-V2/test0/test0.1/course2_assignment2
    $new_directory_created = '';
    if (isset($upload_response->extractpath) && $upload_response->extractpath) {
        $strpos = strpos($upload_response->extractpath, assignsubmission_noto\notoapi::STARTPOINT);
        if ($strpos !== false) {
            $new_directory_created = substr($upload_response->extractpath, strlen(assignsubmission_noto\notoapi::STARTPOINT) + $strpos);
            // CSV with offline grading sending
            $offlinefeedbackenabled = assign_submission_noto::get_noto_config($cm->instance, 'enabled', 'offline', 'assignfeedback');
            if ($offlinefeedbackenabled) {
                $gradebookpath = substr($upload_response->extractpath, $strpos).'/gradebook';
                $csvfile = assign_submission_noto::get_submission_results_zip($submission);
                $csv_upload_response = $notoapi->uzu($gradebookpath, $csvfile);
                assign_submission_noto::delete_submission_results_zip($submission);
            }
        }
    }
    if (!$new_directory_created) {
        throw new \moodle_exception('Empty directory returned after uzu() API call');
    }
    $new_directory_created = assignsubmission_noto\notoapi::normalize_localpath($new_directory_created);
    if (!$config->ethz) {
        $apinotebookpath = sprintf('%s/%s', trim($config->apiserver, '/'), trim($config->apinotebookpath, '/'));
    }
    $notoremotecopy = $DB->get_record('assignsubmission_noto_tcopy', array('studentid'=>$submission->userid, 'assignmentid'=>$submission->assignment));
    if (!$notoremotecopy) {
        $notoremotecopy = new stdClass();
        $notoremotecopy->studentid = $submission->userid;
        $notoremotecopy->assignmentid = $submission->assignment;
    }
    $notoremotecopy->path = $new_directory_created;    # only one path here
    $notoremotecopy->timecreated = time();
    if ($notoremotecopy->id) {
        $updatestatus = $DB->update_record('assignsubmission_noto_tcopy', $notoremotecopy);
    } else {
        $notoremotecopy->id = $DB->insert_record('assignsubmission_noto_tcopy', $notoremotecopy);
    }
    $stringidentifier = 'remotecopysuccessteacher';
    $params['backtoassignment'] = html_writer::link(new moodle_url("/mod/assign/view.php", ['id' => $cm->id, 'action' => 'grading']),
        get_string('backtosubmissions','assignsubmission_noto'), ['class' => 'btn btn-primary']);
    $params['new_directory_created'] = $new_directory_created;
    if (!$config->ethz) {
        $params['redirect_link'] = html_writer::tag(
            'a',
            get_string('redirecttonoto', 'assignsubmission_noto'),
            array('href' => $apinotebookpath . $new_directory_created, 'target' => '_blank')
        );
        \core\notification::success(get_string($stringidentifier, 'assignsubmission_noto', (object)$params));
    } else {
        \core\notification::success(get_string($stringidentifier.'_ethz', 'assignsubmission_noto', (object)$params));
    }
    redirect($PAGE->url);
    exit;
}

print $OUTPUT->header();
$form->display();
print $OUTPUT->footer();

