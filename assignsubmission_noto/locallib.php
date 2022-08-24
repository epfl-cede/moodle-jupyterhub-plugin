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
 * This file contains the definition for the library class for noto submission plugin
 *
 * This class provides all the functionality for the new assign module.
 *
 * @package assignsubmission_noto
 * @copyright 2012 NetSpot {@link http://www.netspot.com.au}
 * @copyright 2020 Enovation Solutions {@link https://enovation.ie}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/mod/assign/submissionplugin.php');
require_once($CFG->dirroot . '/mod/assign/locallib.php');
require_once($CFG->libdir.'/gradelib.php');

/**
 * library class for noto submission plugin extending submission plugin base class
 *
 * @package assignsubmission_noto
 * @copyright 2012 NetSpot {@link http://www.netspot.com.au}
 * @copyright 2020 Enovation {@link https://enovation.ie}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class assign_submission_noto extends assign_submission_plugin {

    const FILEAREA = 'noto_zips'; # also defined in notocopy.php
    const E404 = "lof(): Error code: 404 status: Directory doesn't exist";

    /**
     * Get the name of the online text submission plugin
     * @return string
     */
    public function get_name() {
        return get_string('noto', 'assignsubmission_noto');
    }

    /**
     * Get the custom header for the submission plugin
     * @return html
     */
    public function view_header() {
        global $USER;
        if ($this->assignment->can_view_grades()) {
            $fs = get_file_storage();
            $file_record = array(
                'contextid' => $this->assignment->get_context()->id,
                'component' => 'assignsubmission_noto',
                'filearea' => self::FILEAREA,
                'itemid' => $this->assignment->get_instance()->id,
                'userid' => $USER->id,
                'filepath' => '/',
                'filename' => sprintf('notebook_seed_assignment.zip', $this->assignment->get_instance()->id),
            );
            $file = $fs->get_file($file_record['contextid'], $file_record['component'], $file_record['filearea'], $file_record['itemid'],
                $file_record['filepath'], $file_record['filename']);
            if ($file) {
                $submission = $this->assignment->get_user_submission($USER->id,true);
                $return = html_writer::tag('a', get_string('get_copy_assignment', 'assignsubmission_noto'),
                    array('href' => (string)new \moodle_url('/mod/assign/submission/noto/notocopy.php', array('id' => $submission->id))));
            } else {
                $return = get_string('no_notebook_provided', 'assignsubmission_noto');
            }
            return html_writer::div(html_writer::tag('h3', $this->get_name()).$return."</br></br>");
        }
        return null;
    }

    /**
     * Get noto submission information from the database
     *
     * @param  int $submissionid
     * @return mixed
     */
    private function get_noto_submission($submissionid) {
        global $DB;

        return $DB->get_record('assignsubmission_noto', array('submission'=>$submissionid));
    }

    /**
     * Remove a submission.
     *
     * @param stdClass $submission The submission
     * @return boolean
     */
    public function remove(stdClass $submission) {
        global $DB, $USER;
        $noto_name = self::get_noto_config_name($this->assignment->get_instance()->id);
        $submissionid = $submission ? $submission->id : 0;
        $assignmentid = $this->assignment->get_instance()->id;
        if ($submissionid) {
            $DB->delete_records('assignsubmission_noto', array('submission' => $submissionid));
            #$DB->delete_records('assignsubmission_noto_copies', array('assignmentid'=>$assignmentid, 'userid'=>$submission->userid));
            #$DB->delete_records('assignsubmission_noto_tcopy', array('assignmentid'=>$assignmentid, 'studentid'=>$submission->userid));
            $DB->delete_records('assign_submission', array('id'=>$submissionid));
        }
        $fs = get_file_storage();
        $file_record = array(
            'contextid'=>$this->assignment->get_context()->id,
            'component'=>'assignsubmission_noto',
            'filearea'=>self::FILEAREA,
            'itemid'=>$assignmentid,
            'userid'=>$USER->id,
            'filepath'=>'/',
            'filename'=>sprintf($noto_name.'_user%s.zip', $USER->id),
        );
        $file = $fs->get_file($file_record['contextid'], $file_record['component'], $file_record['filearea'], $file_record['itemid'], $file_record['filepath'], $file_record['filename']);
        if ($file) {
            $file->delete();
        }
        return true;
    }

    /**
     * Get the settings for noto submission plugin
     *
     * @param MoodleQuickForm $mform The form to add elements to
     * @return void
     */

    public function get_settings(MoodleQuickForm $mform) {
        if ($this->assignment->get_default_instance()) {    # this should be empty if a new instance is being created
           $noto_config = $this->get_config();
            if (isset($noto_config->noto_enabled) && $noto_config->noto_enabled && isset($noto_config->directory_h) && $noto_config->directory_h) {
                $mform->addElement('text', 'assignsubmission_noto_name', get_string('assignsubmission_noto_name', 'assignsubmission_noto'), array('id'=>'assignsubmission_noto_name', 'size'=>80));
                $mform->addHelpButton('assignsubmission_noto_name', 'assignsubmission_noto_name', 'assignsubmission_noto');
                $mform->setType('assignsubmission_noto_name', PARAM_TEXT);
                $mform->addElement('text', 'assignsubmission_noto_directory', get_string('assignsubmission_noto_directory', 'assignsubmission_noto'), array('id'=>'assignsubmission_noto_directory', 'size'=>80));
                $mform->setType('assignsubmission_noto_directory', PARAM_PATH);
                $mform->addHelpButton('assignsubmission_noto_directory', 'assignsubmission_noto_directory', 'assignsubmission_noto');
                $formdata = array('assignsubmission_noto_name' => $noto_config->name, 'assignsubmission_noto_directory' => $noto_config->directory_h);
                $mform->setDefaults($formdata);
                $mform->freeze('assignsubmission_noto_directory');
                $mform->hideIf('assignsubmission_noto_directory', 'assignsubmission_noto_enabled', 'notchecked');
                $mform->hideIf('assignsubmission_noto_name', 'assignsubmission_noto_enabled', 'notchecked');
                return;
            }
        }

        $mform->addElement('text', 'assignsubmission_noto_name', get_string('assignsubmission_noto_name', 'assignsubmission_noto'), array('id'=>'assignsubmission_noto_name', 'size'=>80));
        $mform->addHelpButton('assignsubmission_noto_name', 'assignsubmission_noto_name', 'assignsubmission_noto');
        $mform->setType('assignsubmission_noto_name', PARAM_TEXT);

        $mform->addElement('text', 'assignsubmission_noto_directory', get_string('assignsubmission_noto_directory', 'assignsubmission_noto').
            '<div id="submit-moodle"></div>', array('id'=>'assignsubmission_noto_directory', 'size'=>80));
        $mform->setType('assignsubmission_noto_directory', PARAM_PATH);
        $mform->addHelpButton('assignsubmission_noto_directory', 'assignsubmission_noto_directory', 'assignsubmission_noto');
        $mform->freeze('assignsubmission_noto_directory');

        $mform->addElement('hidden', 'assignsubmission_noto_directory_h', '', array('id'=>'assignsubmission_noto_directory_h'));  # _h is for "hidden" if you're wondering
        $mform->setType('assignsubmission_noto_directory_h', PARAM_TEXT);

        $staticlabel = [];
        $staticlabel[] = $mform->createElement('static', 'assignsubmission_noto_directory_label', '', get_string('assignsubmission_noto_directory_label', 'assignsubmission_noto'));
        $mform->addGroup($staticlabel, 'assignsubmission_noto_directory_label', '', ' ', false);
        assign_submission_noto::mform_add_catalog_tree($mform, $this->assignment->get_course()->id);
        $mform->addElement('button', 'assignsubmission_noto_reload', get_string('reloadtree', 'assignsubmission_noto'), ['id'=>'assignsubmission_noto_reloadtree_submit']);
        $mform->hideIf('assignsubmission_noto_directory_label', 'assignsubmission_noto_enabled', 'notchecked');
        $mform->hideIf('assignsubmission_noto_dirlist_group', 'assignsubmission_noto_enabled', 'notchecked');
        $mform->hideIf('assignsubmission_noto_directory', 'assignsubmission_noto_enabled', 'notchecked');
        $mform->hideIf('assignsubmission_noto_name', 'assignsubmission_noto_enabled', 'notchecked');
        $mform->hideIf('assignsubmission_noto_reload', 'assignsubmission_noto_enabled', 'notchecked');
    }

    /**
     * Save the settings for noto submission plugin
     *
     * @param stdClass $data
     * @return bool
     */
    public function save_settings(stdClass $data) {
        global $USER;
        $assignsubmission_noto_directory_h = '';
        $assignsubmission_noto_enabled = 0;

        // Update or save assignment noto name
        if (!empty($data->assignsubmission_noto_enabled)) {
            if (!empty($data->assignsubmission_noto_name)) {
                $assignsubmission_noto_name = self::slugify($data->assignsubmission_noto_name);
                $this->set_config('name', self::slugify($assignsubmission_noto_name));
            } else if (!empty($data->assignsubmission_noto_directory_h)) { // Only for new entries as we still want to be able to access old records
                $directory = (empty($data->assignsubmission_noto_directory_h) ? $data->assignsubmission_noto_directory : $data->assignsubmission_noto_directory_h);
                $dirparts = explode('/', $directory);
                $assignsubmission_noto_name = array_pop($dirparts);
                $this->set_config('name', self::slugify($assignsubmission_noto_name));
            }
        }

        if (isset($data->assignsubmission_noto_enabled) && $data->assignsubmission_noto_enabled && isset($data->assignsubmission_noto_directory_h) && $data->assignsubmission_noto_directory_h) {
            $assignsubmission_noto_directory_h = $data->assignsubmission_noto_directory_h;
            $assignsubmission_noto_enabled = 1;
        }

        if (!$assignsubmission_noto_enabled) {
            return true;    # this covers also the situation when "Jupiter notebooks" is enabled but no directory is chosen. We just dont save it
        }

        $this->set_config('directory_h', $assignsubmission_noto_directory_h);
        $this->set_config('noto_enabled', $assignsubmission_noto_enabled);

        $notoapi = new assignsubmission_noto\notoapi($this->assignment->get_course()->id);
        $zfs_response = $notoapi->zfs(assignsubmission_noto\notoapi::STARTPOINT .$data->assignsubmission_noto_directory_h);
        if (isset($zfs_response->blob) && $zfs_response->blob) {
            $zip_bin = base64_decode($zfs_response->blob);
            $fs = get_file_storage();
            $file_record = array(
                'contextid'=>$this->assignment->get_context()->id,
                'component'=>'assignsubmission_noto',
                'filearea'=>self::FILEAREA,
                'itemid'=>$this->assignment->get_instance()->id,
                'userid'=>$USER->id,
                'filepath'=>'/',
                'filename'=>sprintf('notebook_seed_assignment.zip', $this->assignment->get_instance()->id),
            );
            $file = $fs->get_file($file_record['contextid'], $file_record['component'], $file_record['filearea'], $file_record['itemid'], $file_record['filepath'], $file_record['filename']);
            if ($file) {
                $file->delete();
            }
            $fs->create_file_from_string($file_record, $zip_bin);
        } else {
            throw new \moodle_exception("empty or no blob returned by zfs()");
        }

        return true;
    }

    /**
     * Add form elements for settings
     *
     * @param mixed $submission can be null
     * @param MoodleQuickForm $mform
     * @param stdClass $data
     * @return true if elements were added to the form
     */
    public function get_form_elements($submission, MoodleQuickForm $mform, stdClass $data) {
        global $DB;
            $existing_submissions = $DB->get_record('assignsubmission_noto', array('assignment'=>$this->assignment->get_instance()->id, 'submission'=>$submission->id));
            if ($existing_submissions && $existing_submissions->directory) {
                $directories = explode("\n", $existing_submissions->directory);
                $links = '';
                foreach ($directories as $d) {
                    $links = date('D M j G:i:s T Y', $submission->timemodified);
                }
                $mform->addElement('static', 'submitnotoforgrading_tree_label', get_string('existingsubmissions', 'assignsubmission_noto'), $links);
            }
            $mform->addElement('static', 'submitnotoforgrading_tree_label', '', get_string('submitnotoforgrading_tree_label', 'assignsubmission_noto'));
            $mform->addElement('text', 'assignsubmission_noto_directory', get_string('submitnotoforgrading', 'assignsubmission_noto'), array('id'=>'assignsubmission_noto_directory', 'size'=>80));
            $mform->setType('assignsubmission_noto_directory', PARAM_URL);
            $mform->addHelpButton('assignsubmission_noto_directory', 'submitnotoforgrading', 'assignsubmission_noto');
            $mform->freeze('assignsubmission_noto_directory');
            $mform->addElement('hidden', 'assignsubmission_noto_directory_h', '', array('id'=>'assignsubmission_noto_directory_h'));  # _h is for "hidden" if you're wondering
            $mform->setType('assignsubmission_noto_directory_h', PARAM_TEXT);
            $cm = get_coursemodule_from_instance('assign', $this->assignment->get_instance()->id);
            assign_submission_noto::mform_add_catalog_tree($mform, $this->assignment->get_course()->id);
            $mform->addElement(
                'static',
                'refreshtreebutton',
                '',
                html_writer::tag(
                    'a',
                    get_string('reloadtree', 'assignsubmission_noto'),
                    ['href'=>new \moodle_url('/mod/assign/view.php', ['id'=>$cm->id, 'action'=>'editsubmission']), 'class'=>'btn btn-primary', 'id'=>'assignsubmission_noto_reloadtree_submit'])
            );
        return true;
    }

    /**
     * Save student submission data to the database
     *
     * @param stdClass $submission
     * @param stdClass $data
     * @return bool
     */
    public function save(stdClass $submission, stdClass $data) {
        global $USER, $DB;

        $notosubmission = $this->get_noto_submission($submission->id);
        $noto_name = self::get_noto_config_name($this->assignment->get_instance()->id);
        // onlinetext legacy
        $groupname = null;
        $groupid = 0;
        // Get the group name as other fields are not transcribed in the logs and this information is important.
        if (empty($submission->userid) && !empty($submission->groupid)) {
            $groupname = $DB->get_field('groups', 'name', array('id' => $submission->groupid), MUST_EXIST);
            $groupid = $submission->groupid;
        } else {
            $params['relateduserid'] = $submission->userid;
        }

        // Unset the objectid and other field from params for use in submission events.
        unset($params['objectid']);
        unset($params['other']);
        $params['other'] = array(
            'submissionid' => $submission->id,
            'submissionattempt' => $submission->attemptnumber,
            'submissionstatus' => $submission->status,
            'groupid' => $groupid,
            'groupname' => $groupname
        );

        // download the zipped notebook and store it in filesystem
        $submit_dir = assignsubmission_noto\notoapi::normalize_localpath($data->assignsubmission_noto_directory_h);
        if (!$submit_dir) {
            # this situation is possible when a file submission is added without noto
            return true;
        }
        $notoapi = new assignsubmission_noto\notoapi($this->assignment->get_course()->id);
        $zfs_response = $notoapi->zfs(assignsubmission_noto\notoapi::STARTPOINT . $submit_dir);
        if (isset($zfs_response->blob) && $zfs_response->blob) {
            $zip_bin = base64_decode($zfs_response->blob);
            $fs = get_file_storage();
            $file_record = array(
                'contextid' => $this->assignment->get_context()->id,
                'component' => 'assignsubmission_noto',
                'filearea' => self::FILEAREA,
                'itemid' => $this->assignment->get_instance()->id,
                'userid' => $USER->id,
                'filepath' => '/',
                'filename' => sprintf($noto_name.'_user%s.zip', $USER->id),
            );
            // only one (last) submission is stored as a zip
            $file = $fs->get_file($file_record['contextid'], $file_record['component'], $file_record['filearea'], $file_record['itemid'], $file_record['filepath'], $file_record['filename']);
            if ($file) {
                $file->delete();
            }
            $fs->create_file_from_string($file_record, $zip_bin);
        } else {
            throw new \moodle_exception("empty or no blob returned by zfs()");
        }

        // insert/update a record into assignsubmission_noto and trigger an event
        $params['context'] = $this->assignment->get_context();
        $params['other']['directory'] = $submit_dir;
        if ($notosubmission) {
            $notosubmission->directory = isset($notosubmission->directory) ? sprintf("%s\n%s", $notosubmission->directory, $submit_dir) : $submit_dir;
            $params['objectid'] = $notosubmission->id;
            $updatestatus = $DB->update_record('assignsubmission_noto', $notosubmission);
            $event = \assignsubmission_noto\event\submission_updated::create($params);
            $event->set_assign($this->assignment);
            $event->trigger();
            return $updatestatus;
        } else {
            $notosubmission = new stdClass();
            $notosubmission->submission = $submission->id;
            $notosubmission->assignment = $this->assignment->get_instance()->id;
            $notosubmission->directory = $submit_dir;
            $notosubmission->id = $DB->insert_record('assignsubmission_noto', $notosubmission);
            $params['objectid'] = $notosubmission->id;
            $event = \assignsubmission_noto\event\submission_created::create($params);
            $event->set_assign($this->assignment);
            $event->trigger();
            return $notosubmission->id > 0;
        }
    }

     /**
      * Display a link to a "view submissions" page
      *
      * @param stdClass $submission
      * @param bool $showviewlink - If the summary has been truncated set this to true
      * @return string
      */
    public function view_summary(stdClass $submission, &$showviewlink) {
        global $USER;
        $cm = get_coursemodule_from_instance('assign', $submission->assignment);
        $context = context_module::instance($cm->id);
        # display the "get copy" link only if a teacher uploaded a seed folder
        $action = optional_param('action', '', PARAM_TEXT);
        if ($action !== 'grading') {
            $fs = get_file_storage();
            $file_record = array(
                'contextid' => $this->assignment->get_context()->id,
                'component' => 'assignsubmission_noto',
                'filearea' => self::FILEAREA,
                'itemid' => $this->assignment->get_instance()->id,
                'userid' => $USER->id,
                'filepath' => '/',
                'filename' => sprintf('notebook_seed_assignment.zip', $this->assignment->get_instance()->id),
            );
            $file = $fs->get_file($file_record['contextid'], $file_record['component'], $file_record['filearea'], $file_record['itemid'], $file_record['filepath'], $file_record['filename']);
            if ($file) {
                $return = html_writer::tag('a', get_string('get_copy_assignment', 'assignsubmission_noto'), array('href' => (string)new \moodle_url('/mod/assign/submission/noto/notocopy.php', array('id' => $submission->id))));
            } else {
                $return = get_string('no_notebook_provided', 'assignsubmission_noto');
            }
        }
        $notosubmission = $this->get_noto_submission($submission->id);
        if ($notosubmission) {
            if ($return) {
                $return .= "<br/>\n";
            }
            if (has_capability('mod/assign:grade', $context)) {
                $return .= html_writer::tag(
                    'a',
                    get_string('viewsubmissionsteacher', 'assignsubmission_noto'),
                    ['href' => (string)new moodle_url('/mod/assign/submission/noto/viewsubmissions.php', ['id' => $submission->id])]
                );
            } else {
                if ($return) {
                    $return .= "<br/>\n";
                }
                $return .= html_writer::tag('a', get_string('viewsubmissions', 'assignsubmission_noto'), ['href' => (string)new moodle_url('/mod/assign/submission/noto/submissioncopy.php', ['id' => $submission->id])]);

            }
        }
        return $return;
    }

    /**
     * Display the saved text content from the editor in the view table
     *
     * @param stdClass $submission
     * @return string
     */
    public function view(stdClass $submission) {
        global $DB;

        $notosubmission = $this->get_noto_submission($submission->id);

        if ($notosubmission) {
            return $notosubmission->directory;
        }

        return '';
    }

    /**
     * The assignment has been deleted - cleanup
     *
     * @return bool
     */
    public function delete_instance() {
        global $DB;
        $assignmentid = $this->assignment->get_instance()->id;
        $DB->delete_records('assignsubmission_noto_copies', array('assignmentid'=>$assignmentid));
        $DB->delete_records('assignsubmission_noto_tcopy', array('assignmentid'=>$assignmentid));
        $DB->delete_records('assignsubmission_noto', array('assignment'=>$assignmentid));
        $DB->delete_records('assign_submission', array('assignment'=>$assignmentid));

        # Delete files as well
        $fs = get_file_storage();
        $fsfiles = $fs->get_area_files(
            $this->assignment->get_context()->id,   # $contextid
            'assignsubmission_noto',                # $component
            self::FILEAREA,                         # $filearea
            $assignmentid                           # $itemid
        );
        foreach ($fsfiles as $file) {
            $file->delete();    # delete all, even directories
        }
        return true;
    }

    /**
     * No text is set for this plugin
     *
     * @param stdClass $submission
     * @return bool
     */
    public function is_empty(stdClass $submission) {
        return false;
    }

    /**
     * Determine if a submission is empty
     *
     * This is distinct from is_empty in that it is intended to be used to
     * determine if a submission made before saving is empty.
     *
     * @param stdClass $data The submission data
     * @return bool
     */
    public function submission_is_empty(stdClass $data) {
        if (isset($data->assignsubmission_noto_directory_h) && $data->assignsubmission_noto_directory_h) {
            return false;
        }
        return true;
    }

    /**
     * Get file areas returns a list of areas this plugin stores files
     * @return array - An array of fileareas (keys) and descriptions (values)
     */
    public function get_file_areas() {
        return array(self::FILEAREA=>$this->get_name());
    }


    /**
     * Produce a list of files suitable for export that represent this feedback or submission
     *
     * @param stdClass $submission The submission
     * @param stdClass $user The user record - unused
     * @return array - return an array of files indexed by filename
     */
    public function get_files(stdClass $submission, stdClass $user) {
        $result = array();
        $fs = get_file_storage();
        $noto_name = self::get_noto_config_name($this->assignment->get_instance()->id);
        $file_record = array(
            'contextid'=>$this->assignment->get_context()->id,
            'component'=>'assignsubmission_noto',
            'filearea'=>self::FILEAREA,
            'itemid'=>$this->assignment->get_instance()->id,
            'userid'=>$user->id,
            'filepath'=>'/',
            'filename'=>sprintf($noto_name.'_user%s.zip', $user->id),
        );
        $file = $fs->get_file($file_record['contextid'], $file_record['component'], $file_record['filearea'], $file_record['itemid'], $file_record['filepath'], $file_record['filename']);

        if ($file) {
            return array($file->get_filename()=>$file);
        }
    }

    /**
     * Produce content of the CSV gradebook file
     *
     * @param stdClass $submission The submission
     * @return string - return a string with csv content
     */
    private static function get_grades_csv(stdClass $submission) {
        global $DB;
        $out = "";
        $csvdata =[];
        $cm = get_coursemodule_from_instance('assign', $submission->assignment);
        $feedbackcomments = assign_submission_noto::get_noto_config($cm->instance, 'enabled', 'comments', 'assignfeedback');
        // Headers
        $headerline = [
            get_string('identifier', 'assignsubmission_noto'),
            get_string('fullname', 'assignsubmission_noto'),
            get_string('emailaddress', 'assignsubmission_noto'),
            get_string('status', 'assignsubmission_noto'),
            get_string('grade', 'assignsubmission_noto'),
            get_string('maximumgrade', 'assignsubmission_noto'),
            get_string('gradecanchange', 'assignsubmission_noto'),
            get_string('lastmodified_submission', 'assignsubmission_noto'),
            get_string('lastmodified_grade', 'assignsubmission_noto'),

        ];
        if ($feedbackcomments) {
            $headerline[] = get_string('feedback_comments', 'assignsubmission_noto');
        }
        $csvdata[] = $headerline;
        // CSV content
        $graderecord = \grade_get_grades($cm->course, 'mod', 'assign', $cm->instance, $submission->userid);
        $graderecord = array_pop($graderecord->items);
        // User data
        $user = $DB->get_record('user', ['id' => $submission->userid]);
        $uniqueid = \assign::get_uniqueid_for_user_static($submission->assignment, $submission->userid);
        $userdata = [];
        $userdata[] = get_string('hiddenuser', 'assign') .$uniqueid;
        $userdata[] = $user->firstname.' '.$user->lastname;
        $userdata[] = $user->email;
        $userdata[] = $submission->status;
        $userdata[] = $graderecord->grades[$submission->userid]->str_grade;
        $userdata[] = intval($graderecord->grademax);
        $userdata[] = ($graderecord->grades[$submission->userid]->locked ? get_string('no') : get_string('yes'));
        $submissiondate = (empty($graderecord->grades[$submission->userid]->datesubmitted) ? '-' :
            date('d/m/Y H:i:s', $graderecord->grades[$submission->userid]->datesubmitted));
        $userdata[] = $submissiondate;
        $gradeddate = (empty($graderecord->grades[$submission->userid]->dategraded) ? '-' :
            date('d/m/Y H:i:s', $graderecord->grades[$submission->userid]->dategraded));
        $userdata[] = $gradeddate;
        if ($feedbackcomments) {
            $userdata[] = (empty($graderecord->grades[$submission->userid]->feedback) ? '-' : strip_tags($graderecord->grades[$submission->userid]->feedback));
        }
        $csvdata[] = $userdata;
        foreach($csvdata as $arr) {
            $out .= implode(",", $arr) . PHP_EOL;
        }
        return $out;
    }

    /**
     * Produce a zip file with gradebook CSV
     *
     * @param stdClass $submission The submission
     * @return \stored_file - return a stored zip with gradebook csv file
     */
    public static function get_submission_results_zip(stdClass $submission) : ?\stored_file {
        $tempfolder = make_temp_directory('noto_submissions');
        $tempfile = $tempfolder . '/' . rand();
        $noto_name = self::get_noto_config_name($submission->assignment);
        $csvfilename = sprintf($noto_name.'_user%s_grading.csv', $submission->userid);
        $zipfilename = sprintf($noto_name.'_user%s_grading.zip', $submission->userid);
        $csvcontent = self::get_grades_csv($submission);
        $zip = new ZipArchive;
        $res = $zip->open($tempfile, ZipArchive::CREATE);
        if ($res === TRUE) {
            $zip->addFromString($csvfilename, $csvcontent);
            $zip->close();
            $cm = get_coursemodule_from_instance('assign', $submission->assignment);
            $context = context_module::instance($cm->id);
            $fs = get_file_storage();
            $fileinfo = array(
                'contextid' => $context->id,
                'component' => 'assignsubmission_noto',
                'filearea' => self::FILEAREA,
                'itemid' => $cm->instance,
                'filepath'=>'/',
                'filename' => $zipfilename);
            $resfile = $fs->create_file_from_pathname($fileinfo, $tempfile);
            return $resfile;
        } else {
            throw new \moodle_exception('Could not create grading file for upload.');
        }
        return null;
    }

    /**
     * Delete a zip file with gradebook CSV from local files storage
     *
     * @param stdClass $submission The submission
     * @return \stored_file - return a stored zip with gradebook csv file
     */
    public static function delete_submission_results_zip(stdClass $submission) :bool {
        $cm = get_coursemodule_from_instance('assign', $submission->assignment);
        $context = context_module::instance($cm->id);
        $noto_name = self::get_noto_config_name($submission->assignment);
        $zipfilename = sprintf($noto_name.'_user%s_grading.zip', $submission->userid);
        $fs = get_file_storage();
        $file = $fs->get_file($context->id, 'assignsubmission_noto', self::FILEAREA, $cm->instance, '/', $zipfilename);
        return $file->delete();
    }

    /* returns a HTML block arranged as a Moodle's error message block
     * @param string $message
     * @return string
     */
    public static function get_error_html_block(string $message): string {
        return sprintf('
            <span class="notifications" id="user-notifications">
                <div class="alert alert-danger alert-block fade in " role="alert" data-aria-autofocus="true">
                    <button type="button" class="close" data-dismiss="alert">×</button>
                    %s
                </div>
            </span>
        ', $message);
    }

    public static function mform_add_catalog_tree(&$mform, $course) {
        global $PAGE;

        $PAGE->requires->js_call_amd('assignsubmission_noto/directorytree', 'init', [$course]);

        $dirlistgroup = array();
        $dirlistgroup[] = $mform->createElement('html', '<div id="jstree">');
        $dirlistgroup[] = $mform->createElement('html', '</div>');
        $mform->addGroup($dirlistgroup, 'assignsubmission_noto_dirlist_group', '', ' ', false);

    }

    /**
     * Convert strings of text into simple kebab case slugs.
     *
     * @param string $input
     * @return string
     */
    public static function slugify($input) {
        // Down low
        $input = strtolower($input);

        // Replace common chars
        $input = str_replace(
            array('æ',  'ø',  'ö', 'ó', 'ô', 'Ò',  'Õ', 'Ý', 'ý', 'ÿ', 'ā', 'ă', 'ą', 'œ', 'å', 'ä', 'á', 'à', 'â', 'ã', 'ç', 'ć', 'ĉ', 'ċ', 'č', 'é', 'è', 'ê', 'ë', 'í', 'ì', 'î', 'ï', 'ú', 'ñ', 'ü', 'ù', 'û', 'ß',  'ď', 'đ', 'ē', 'ĕ', 'ė', 'ę', 'ě', 'ĝ', 'ğ', 'ġ', 'ģ', 'ĥ', 'ħ', 'ĩ', 'ī', 'ĭ', 'į', 'ı', 'ĳ',  'ĵ', 'ķ', 'ĺ', 'ļ', 'ľ', 'ŀ', 'ł', 'ń', 'ņ', 'ň', 'ŉ', 'ō', 'ŏ', 'ő', 'ŕ', 'ŗ', 'ř', 'ś', 'ŝ', 'ş', 'š', 'ţ', 'ť', 'ŧ', 'ũ', 'ū', 'ŭ', 'ů', 'ű', 'ų', 'ŵ', 'ŷ', 'ź', 'ż', 'ž', 'ſ', 'ƒ', 'ơ', 'ư', 'ǎ', 'ǐ', 'ǒ', 'ǔ', 'ǖ', 'ǘ', 'ǚ', 'ǜ', 'ǻ', 'ǽ',  'ǿ'),
            array('ae', 'oe', 'o', 'o', 'o', 'oe', 'o', 'o', 'y', 'y', 'y', 'a', 'a', 'a', 'a', 'a', 'a', 'a', 'a', 'a', 'c', 'c', 'c', 'c', 'c', 'e', 'e', 'e', 'e', 'i', 'i', 'i', 'i', 'u', 'n', 'u', 'u', 'u', 'es', 'd', 'd', 'e', 'e', 'e', 'e', 'e', 'g', 'g', 'g', 'g', 'h', 'h', 'i', 'i', 'i', 'i', 'i', 'ij', 'j', 'k', 'l', 'l', 'l', 'l', 'l', 'n', 'n', 'n', 'n', 'o', 'o', 'o', 'r', 'r', 'r', 's', 's', 's', 's', 't', 't', 't', 'u', 'u', 'u', 'u', 'u', 'u', 'w', 'y', 'z', 'z', 'z', 's', 'f', 'o', 'u', 'a', 'i', 'o', 'u', 'u', 'u', 'u', 'u', 'a', 'ae', 'oe'),
            $input);

        // Replace everything else
        $input = preg_replace('/[^a-z0-9]/', '-', $input);

        // Prevent double hyphen
        $input = preg_replace('/-{2,}/', '-', $input);

        // Prevent hyphen in beginning or end
        $input = trim($input, '-');

        // Prevent to long slug
        if (strlen($input) > 91) {
            $input = substr($input, 0, 92);
        }

        return $input;
    }

    /**
     * Get assignment noto settingss
     *
     * @param int $assignmentid
     * @param string $name
     * @return string
     */
    public static function get_noto_config($assignmentid, $name, $plugin = 'noto', $subtype = 'assignsubmission') {
        global $DB;
        $dbparams = array('assignment' => $assignmentid,
            'subtype' => $subtype,
            'plugin' => $plugin,
            'name' => $name);
        $noto_name = $DB->get_field('assign_plugin_config', 'value', $dbparams, '*', IGNORE_MISSING);

        return $noto_name;
    }

    /**
     * Get assignment noto name setting
     *
     * @param int $assignmentid
     * @param string $name
     * @return string
     */
    public static function get_noto_config_name($assignmentid) {
        global $DB;

        $noto_name = self::get_noto_config($assignmentid, 'name');
        // For old entries where noto name field did not exist
        if (empty($noto_name)) {
            $courseid = $DB->get_field('assign', 'course', ['id' => $assignmentid]);
            $noto_name = sprintf('course%s_assignment%s', $courseid, $assignmentid);
        }

        return $noto_name;
    }


}

