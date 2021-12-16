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
 * This file contains the definition for the library class for file feedback plugin
 *
 *
 * @package   assignfeedback_noto
 * @copyright 2021 Enovation
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

define('FILEAREA', 'noto_zips');

/**
 * Library class for noto feedback plugin extending feedback plugin base class.
 *
 * @package   assignfeedback_noto
 * @copyright 2021 Enovation
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class assign_feedback_noto extends assign_feedback_plugin {

    /**
     * Get the name of the file feedback plugin.
     *
     * @return string
     */
    public function get_name() {
        return get_string('noto', 'assignfeedback_noto');
    }

    /**
     * Return a list of the batch grading operations performed by this plugin.
     * This plugin supports batch upload files and upload zip.
     *
     * @return array The list of batch grading operations
     */
    public function get_grading_batch_operations() {
        return array('uploadnoto'=>get_string('uploadnoto', 'assignfeedback_noto'));
    }

    /**
     * If this plugin should not include a column in the grading table or a row on the summary page
     * then return false
     *
     * @return bool
     */
    public function has_user_summary() {
        return false;
    }

    /**
     * Upload files and send them to multiple users.
     *
     * @param array $users - An array of user ids
     * @return string - The response html
     */
    public function view_batch_upload_noto($users) {
        global $CFG, $DB;

        $cm = $this->assignment->get_course_module();
        $context = $this->assignment->get_context();

        $cancel = optional_param('cancel', '', PARAM_TEXT);

        require_capability('mod/assign:grade', $context);
        require_once($CFG->dirroot . '/mod/assign/renderable.php');

        $formparams = array('cm' => $cm, 'users'=>$users);
        $mform = new \assignsubmission_noto\notocopy_form(null, $formparams);
        if (!empty($cancel)) {
            redirect(new moodle_url('view.php',
                                    array('id'=>$cm->id,
                                          'action'=>'grading')));
            return;
        } else if ($data = $mform->get_data()) {
            if (!$data->assignsubmission_noto_directory_h || isset($data->reload)) {
                redirect(new moodle_url('view.php',
                    array('id'=>$cm->id,
                        'action'=>'grading')));
                return;
            }
            $config = get_config('assignsubmission_noto');
            $noto_name = assign_submission_noto::get_noto_config_name($cm->instance);
            $notoapi = new assignsubmission_noto\notoapi($cm->course);
            $fs = get_file_storage();
            foreach ($users as $userid) {
                $file_record = array(
                    'contextid'=>$context->id,
                    'component'=>'assignsubmission_noto',
                    'filearea'=>FILEAREA,
                    'itemid'=>$cm->instance,
                    'filepath'=>'/',
                    'filename'=>sprintf($noto_name.'_user%s.zip', $userid),
                );
                $file = $fs->get_file($file_record['contextid'], $file_record['component'], $file_record['filearea'], $file_record['itemid'], $file_record['filepath'], $file_record['filename']);
                if (!$file) {
                    continue;
                }
                $dest_path = sprintf('%s/%s/'.$noto_name.'_student%d', assignsubmission_noto\notoapi::STARTPOINT, $data->assignsubmission_noto_directory_h, $userid);
                $upload_response = $notoapi->uzu($dest_path, $file);
                $new_directory_created = '';
                if (isset($upload_response->extractpath) && $upload_response->extractpath) {
                    $strpos = strpos($upload_response->extractpath, assignsubmission_noto\notoapi::STARTPOINT);
                    if ($strpos !== false) {
                        $new_directory_created = substr($upload_response->extractpath,
                            strlen(assignsubmission_noto\notoapi::STARTPOINT) + $strpos);
                        // CSV with offline grading sending
                        $offlinefeedbackenabled = assign_submission_noto::get_noto_config($cm->instance, 'enabled',
                            'offline', 'assignfeedback');
                        if ($offlinefeedbackenabled) {
                            $gradebookpath = substr($upload_response->extractpath, $strpos).'/gradebook';
                            $submission = $DB->get_record('assign_submission', array('assignment' =>
                                $cm->instance, 'userid' => $userid));
                            if (!empty($submission)) {
                                $csvfile = assign_submission_noto::get_submission_results_zip($submission);
                                $csv_upload_response = $notoapi->uzu($gradebookpath, $csvfile);
                                assign_submission_noto::delete_submission_results_zip($submission);
                            }
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
                $notoremotecopy = $DB->get_record('assignsubmission_noto_tcopy', array('studentid' => $userid,
                    'assignmentid' => $cm->id));
                if (!$notoremotecopy) {
                    $notoremotecopy = new stdClass();
                    $notoremotecopy->studentid = $userid;
                    $notoremotecopy->assignmentid = $cm->id;
                }
                $notoremotecopy->path = $new_directory_created;    # only one path here
                $notoremotecopy->timecreated = time();
                if ($notoremotecopy->id) {
                    $updatestatus = $DB->update_record('assignsubmission_noto_tcopy', $notoremotecopy);
                } else {
                    $notoremotecopy->id = $DB->insert_record('assignsubmission_noto_tcopy', $notoremotecopy);
                }
            }
            \core\notification::success(get_string('usersuploadedsuccessfully', 'assignfeedback_noto'));

            redirect(new moodle_url('view.php',
                                    array('id'=>$cm->id,
                                          'action'=>'grading')));
            return;
        } else {

            $header = new assign_header($this->assignment->get_instance(),
                                        $this->assignment->get_context(),
                                        false,
                                        $this->assignment->get_course_module()->id,
                                        get_string('notouploadfiles', 'assignfeedback_noto'));
            $o = '';
            $o .= $this->assignment->get_renderer()->render($header);
            $o .= $this->assignment->get_renderer()->render(new assign_form('notouploadfiles', $mform));
            $o .= $this->assignment->get_renderer()->render_footer();
        }

        return $o;
    }

    /**
     * User has chosen a custom grading batch operation and selected some users.
     *
     * @param string $action - The chosen action
     * @param array $users - An array of user ids
     * @return string - The response html
     */
    public function grading_batch_operation($action, $users) {

        if ($action == 'uploadnoto') {
            return $this->view_batch_upload_noto($users);
        }
        return '';
    }

    /**
     * Called by the assignment module when someone chooses something from the
     * grading navigation or batch operations list.
     *
     * @param string $action - The page to view
     * @return string - The html response
     */
    public function view_page($action) {
        if ($action == 'uploadnoto') {
            $users = required_param('selectedusers', PARAM_SEQUENCE);
            return $this->view_batch_upload_noto(explode(',', $users));
        }
        return '';
    }
}
