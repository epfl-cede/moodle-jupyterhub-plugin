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
 * Teacher to upload Feedback
 *
 * @package   assignfeedback_noto
 * @copyright 2020 Enovation Solutions
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use core\notification;

require_once(dirname(__FILE__) . '/../../../../config.php');
require_once(dirname(__FILE__) . '/locallib.php');
require_once($CFG->libdir . '/formslib.php');
define('ASSIGNFEEDBACK_NOTO_FILEAREA', 'feedback_noto');

$cmid = required_param('id', PARAM_INT);

$coursemodule = $DB->get_record('course_modules', array('id' => $cmid));

$course = $DB->get_record('course', array('id' => $coursemodule->course));

$cm = get_coursemodule_from_instance('assign', $coursemodule->instance);

if (!$cm) {
    throw new coding_exception("Cannot find course id " . $cm);
}

$PAGE->set_url('/mod/assign/feedback/noto/uploadfeedback.php', array('id' => $cmid));
$context = context_module::instance($cm->id);
$PAGE->set_context($context);
$PAGE->set_cm($cm);
$PAGE->set_title(get_string('viewuploadfeedback_pagetitle', 'assignfeedback_noto', fullname($student)));
$PAGE->set_heading(get_string('viewuploadfeedback_pagetitle', 'assignfeedback_noto', fullname($student)));
$PAGE->set_pagelayout('standard');
require_login($cm->course);
$config = get_config('assignfeedbacknoto');

require_capability('mod/assign:grade', $context);

$noto_name = assign_feedback_noto::get_noto_config_name($cm->instance);

$form = new \assignfeedback_noto\notocopy_form(null, array('id' => $cmid, 'cm' => $cm));

if ($form->is_cancelled()) {
    redirect(new moodle_url('/mod/assign/view.php', array('id' => $cmid, 'action' => 'grading')));
} else if ($data = $form->get_data()) {
    if (isset($data->cancel)) {
        redirect(new moodle_url('/mod/assign/view.php', array('id' => $cmid, 'action' => 'grading')));
    }
    if (!$data->assignsubmission_noto_directory_h || isset($data->reload)) {
        redirect($PAGE->url);
        exit;
    }

    // Iterate over the list of folders selected

    $directories = explode(",", $data->assignsubmission_noto_directory_h);
    foreach ($directories as $dir) {
        // extract last part to get details
        $filename = basename($dir);

        $posstart = strpos($filename, 'course');
        $posend = strpos($filename, '_', $posstart);
        $lenid = $posend - $posstart - 6;
        $courseid = substr($filename, $posstart + 6, $lenid);

        $posstart = strpos($filename, 'student');
        $posend = strpos($filename, '_', $posstart);
        $lenid = $posend - $posstart - 7;
        $studentid = substr($filename, $posstart + 7, $lenid);

        $posstart = strpos($filename, 'submission');
        $posend = strpos($filename, '_', $posstart);
        $lenid = $posend - $posstart - 10;
        $submissionid = substr($filename, $posstart + 10, $lenid);

        // If student id missing then skip
        if ($studentid && $submissionid) {
            $submission = $DB->get_record('assign_submission', array('id' => $submissionid));
            $student = $DB->get_record('user', array('id' => $studentid));

            $cm = get_coursemodule_from_instance('assign', $submission->assignment);

            $context = context_module::instance($cm->id);

            $assign = new assign($context, $cm, $course);

            $from_user_path = assignsubmission_noto\notoapi::STARTPOINT . $dir;
            $notoapi = new assignsubmission_noto\notoapi($data->course);

            $zfs_response = $notoapi->zfs($from_user_path);

            if (isset($zfs_response->blob) && $zfs_response->blob) {
                $zip_bin = base64_decode($zfs_response->blob);
                $fs = get_file_storage();
                $grade = $assign->get_user_grade($studentid, true);

                $file_record = array(
                        'contextid' => $assign->get_context()->id,
                        'component' => 'assignfeedback_noto',
                        'filearea' => ASSIGNFEEDBACK_NOTO_FILEAREA,
                        'itemid' => $grade->id,
                        'userid' => $USER->id,
                        'filepath' => '/',
                        'filename' => sprintf('notebook_seed_feedback_user_%d.zip', $studentid),
                );
                $file = $fs->get_file($file_record['contextid'], $file_record['component'], $file_record['filearea'],
                        $file_record['itemid'], $file_record['filepath'], $file_record['filename']);
                if ($file) {
                    $file->delete();
                }
                $fs->create_file_from_string($file_record, $zip_bin);

                // Update the number of feedback files for this user.
                $notoassign = new assign_feedback_noto($assign, 'noto');
                $notoassign->update_file_count($grade);

                // Update the last modified time on the grade which will trigger student notifications.
                $eventtype = 'assign_notification';
                $messagetype = 'feedbackavailable';
                $updatetime = time();

                $assign->send_notification(
                        $USER,
                        $student,
                        $messagetype,
                        $eventtype,
                        $updatetime);

            } else {
                throw new moodle_exception("empty or no blob returned by zfs()");
            }
        }
    }
    notification::success(get_string('feedbacksuploadedsuccessfully', 'assignfeedback_noto'));

    redirect(new moodle_url('/mod/assign/view.php', array('id' => $cmid, 'action' => 'grading')));
    return;

}

print $OUTPUT->header();
$form->display();
print $OUTPUT->footer();

