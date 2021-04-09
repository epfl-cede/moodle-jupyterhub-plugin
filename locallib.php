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

    /**
     * Get the name of the online text submission plugin
     * @return string
     */
    public function get_name() {
        return get_string('noto', 'assignsubmission_noto');
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
            'filename'=>sprintf('submission_assignment%d_user%s.zip', $assignmentid, $USER->id),
        );
        $file = $fs->get_file($file_record['contextid'], $file_record['component'], $file_record['filearea'], $file_record['itemid'], $file_record['filepath'], $file_record['filename']);
        if ($file) {
            $file->delete();
        }
        return true;
    }

    /**
     * generates html prepated to be used in jtree - from a data structure obtained from processing stored notebook paths
     * @param array &$dirlistgroup - the resulting array with form HTML elements added
     * @param MoodleQuickForm $mform
     * @param array $directories - the array formed from stored notebook paths
     * @param string $path - a string directory to be added as a form element ID - otherwise the function knows only the current dirname, but none of parents
     * @param int depth - the current depth in the tree (currently not used)
     * @param int $maxdepth - not to calculate it many times in the recursive function (currently not used)
     * @return array - the moodle form group array
     */
    private function display_submitdirectory_recursive (array &$dirlistgroup, MoodleQuickForm $mform, array $directories, string $path, int $depth, int $maxdepth): array {
        global $OUTPUT;
        if ($path === assignsubmission_noto\notoapi::STARTPOINT) {
            $path = '';     # cosmetics, not to display './/Documentation'
        }
        foreach ($directories as $directory => $subdirectories) {
            $dirlistgroup[] = $mform->createElement('html', '<ul>');
            if ($subdirectories) {
                $dirlistgroup[] = $mform->createElement('html', sprintf('<li id="%s/%s" data-jstree=\'{"disabled":true}\'>%s', $path, $directory, $directory));
                $this->display_submitdirectory_recursive($dirlistgroup, $mform, $subdirectories, sprintf('%s/%s', $path, $directory), $depth +1, $maxdepth);
            } else {
                $dirlistgroup[] = $mform->createElement('html', sprintf('<li id="%s/%s">%s', $path, $directory, $directory));
                $dirlistgroup[] = $mform->createElement('html', '</li>');
            }
            $dirlistgroup[] = $mform->createElement('html', '</ul>');
        }
        return $dirlistgroup;
    }

    /**
     * Get the settings for noto submission plugin
     *
     * @param MoodleQuickForm $mform The form to add elements to
     * @return void
     */

    public function get_settings(MoodleQuickForm $mform) {
        global $CFG, $COURSE, $PAGE;

        if ($this->assignment->get_default_instance()) {    # this should be empty if a new instance is being created
           $noto_config = $this->get_config();
            if (isset($noto_config->noto_enabled) && $noto_config->noto_enabled && isset($noto_config->directory_h) && $noto_config->directory_h) {
                $mform->addElement('text', 'assignsubmission_noto_directory', get_string('assignsubmission_noto_directory', 'assignsubmission_noto'), array('id'=>'assignsubmission_noto_directory', 'size'=>80));
                $mform->setType('assignsubmission_noto_directory', PARAM_PATH);
                $mform->addHelpButton('assignsubmission_noto_directory', 'assignsubmission_noto_directory', 'assignsubmission_noto');
                $formdata = array('assignsubmission_noto_directory' => $noto_config->directory_h);
                $mform->setDefaults($formdata);
                $mform->freeze('assignsubmission_noto_directory');
                $mform->hideIf('assignsubmission_noto_directory', 'assignsubmission_noto_enabled', 'notchecked');
                return;
            }
        }
        $notoapi = new assignsubmission_noto\notoapi();
        $dirlist_top = null;
        try {
            #$dirlist_top = $notoapi->lod(assignsubmission_noto\notoapi::STARTPOINT);
            ## for lof() the top level dir must be a bit rebuilt
            $dirlist_top = new stdClass();
            $dirlist_top->name = assignsubmission_noto\notoapi::STARTPOINT;
            $dirlist_top->type = 'directory';
            $dirlist_top->children = $notoapi->lof(assignsubmission_noto\notoapi::STARTPOINT);

        } catch (\moodle_exception $e) {
            $message = get_string('cannotaddnoto', 'assignsubmission_noto', $e->getMessage());
            \core\notification::warning($message);
            return;
        }

        $PAGE->requires->js_call_amd('assignsubmission_noto/directorytree', 'init');

        $mform->addElement('text', 'assignsubmission_noto_directory', get_string('assignsubmission_noto_directory', 'assignsubmission_noto'), array('id'=>'assignsubmission_noto_directory', 'size'=>80));
        $mform->setType('assignsubmission_noto_directory', PARAM_PATH);
        $mform->addHelpButton('assignsubmission_noto_directory', 'assignsubmission_noto_directory', 'assignsubmission_noto');
        $mform->freeze('assignsubmission_noto_directory');

        $mform->addElement('hidden', 'assignsubmission_noto_directory_h', '', array('id'=>'assignsubmission_noto_directory_h'));  # _h is for "hidden" if you're wondering
        $mform->setType('assignsubmission_noto_directory_h', PARAM_TEXT);

        $config = get_config('assignsubmission_noto');
        $maxdepth = assignsubmission_noto\notoapi::MAXDEPTH;
        if (isset($config->maxdepth) && $config->maxdepth) {
            $maxdepth = $config->maxdepth;
        }

        $staticlabel = [];
        $staticlabel[] = $mform->createElement('static', 'assignsubmission_noto_directory_label', '', get_string('assignsubmission_noto_directory_label', 'assignsubmission_noto'));
        $mform->addGroup($staticlabel, 'assignsubmission_noto_directory_label', '', ' ', false);

        $dirlistgroup = array();
        $dirlistgroup[] = $mform->createElement('html', '<div id="jstree">');
        $dirlistgroup = assignsubmission_noto\nototreerenderer::display_lof_recursive($dirlistgroup, $mform, $dirlist_top, assignsubmission_noto\notoapi::STARTPOINT, 0, $maxdepth);
        $dirlistgroup[] = $mform->createElement('html', '</div>');

        $mform->addGroup($dirlistgroup, 'assignsubmission_noto_dirlist_group', '', ' ', false);
        $mform->hideIf('assignsubmission_noto_directory_label', 'assignsubmission_noto_enabled', 'notchecked');
        $mform->hideIf('assignsubmission_noto_dirlist_group', 'assignsubmission_noto_enabled', 'notchecked');
        $mform->hideIf('assignsubmission_noto_directory', 'assignsubmission_noto_enabled', 'notchecked');
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

        if (isset($data->assignsubmission_noto_enabled) && $data->assignsubmission_noto_enabled && isset($data->assignsubmission_noto_directory_h) && $data->assignsubmission_noto_directory_h) {
            $assignsubmission_noto_directory_h = $data->assignsubmission_noto_directory_h;
            $assignsubmission_noto_enabled = 1;
        }

        if (!$assignsubmission_noto_enabled) {
            return true;    # this covers also the situation when "Jupiter notebooks" is enabled but no directory is chosen. We just dont save it
        }
        $this->set_config('directory_h', $assignsubmission_noto_directory_h);
        $this->set_config('noto_enabled', $assignsubmission_noto_enabled);

        $notoapi = new assignsubmission_noto\notoapi();
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
                'filename'=>sprintf('notebook_seed_assignment%d.zip', $this->assignment->get_instance()->id),
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
        global $PAGE, $DB, $USER;

#        $fs = get_file_storage();
#        $file_record = array(
#            'contextid'=>$this->assignment->get_context()->id,
#            'component'=>'assignsubmission_noto',
#            'filearea'=>self::FILEAREA,
#            'itemid'=>$this->assignment->get_instance()->id,
#            'userid'=>$USER->id,
#            'filepath'=>'/',
#            'filename'=>sprintf('notebook_seed_assignment%d.zip', $this->assignment->get_instance()->id),
#        );
#        $file = $fs->get_file($file_record['contextid'], $file_record['component'], $file_record['filearea'], $file_record['itemid'], $file_record['filepath'], $file_record['filename']);
#        if ($file) {

            $notoapi = new assignsubmission_noto\notoapi();
            $dirlist_top = new stdClass();
            $dirlist_top->name = assignsubmission_noto\notoapi::STARTPOINT;
            $dirlist_top->type = 'directory';
            $dirlist_top->children = $notoapi->lof(assignsubmission_noto\notoapi::STARTPOINT);
            $PAGE->requires->js_call_amd('assignsubmission_noto/directorytree', 'init');
            $config = get_config('assignsubmission_noto');
            $maxdepth = assignsubmission_noto\notoapi::MAXDEPTH;
            if (isset($config->maxdepth) && $config->maxdepth) {
                $maxdepth = $config->maxdepth;
            }
            $existing_submissions = $DB->get_record('assignsubmission_noto', array('assignment'=>$this->assignment->get_instance()->id, 'submission'=>$submission->id));
            if ($existing_submissions && $existing_submissions->directory) {
                $directories = explode("\n", $existing_submissions->directory);
                $links = '';
                $apinotebookpath = sprintf('%s/%s', trim($config->apiserver, '/'), trim($config->apinotebookpath, '/'));
                foreach ($directories as $d) {
                    $links = date('D M j G:i:s T Y', $submission->timemodified);
                    #$links = sprintf("%s &nbsp;&nbsp;&nbsp;&nbsp; %s", date('D M j G:i:s T Y', $submission->timemodified), html_writer::tag('a', $d, array('href'=>$apinotebookpath.$d, 'target'=>'_blank')));
                    #$links .= html_writer::tag('a', $d, array('href'=>$apinotebookpath.$d, 'target'=>'_blank'));
                    #$links .= "<br/>\n";
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

            $dirlistgroup = array();
            $dirlistgroup[] = $mform->createElement('html', '<div id="jstree">');
            $dirlistgroup = assignsubmission_noto\nototreerenderer::display_lof_recursive($dirlistgroup, $mform, $dirlist_top, assignsubmission_noto\notoapi::STARTPOINT, 0, $maxdepth);
            $dirlistgroup[] = $mform->createElement('html', '</div>');
            $mform->addGroup($dirlistgroup, 'assignsubmission_noto_dirlist_group', '', ' ', false);
            $cm = get_coursemodule_from_instance('assign', $this->assignment->get_instance()->id);
            $mform->addElement(
                'static',
                'refreshtreebutton',
                '',
                html_writer::tag(
                    'a',
                    get_string('reloadtree', 'assignsubmission_noto'),
                    ['href'=>new \moodle_url('/mod/assign/view.php', ['id'=>$cm->id, 'action'=>'editsubmission']), 'class'=>'btn', 'id'=>'assignsubmission_noto_reloadtree_submit'])
            );
#        } else {
#            $mform->addElement('html', '<p>'.get_string('assignmentnotready', 'assignsubmission_noto').'</p>');
#        }
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
        $notoapi = new assignsubmission_noto\notoapi();
        $zfs_response = $notoapi->zfs(assignsubmission_noto\notoapi::STARTPOINT . $submit_dir);
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
                'filename'=>sprintf('submission_assignment%d_user%s.zip', $this->assignment->get_instance()->id, $USER->id),
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
        // all done, everyone's happy
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
        $return = '';
        if (has_capability('mod/assign:grade', $context)) {
            $notosubmission = $this->get_noto_submission($submission->id);
            if ($notosubmission) {
                $return = html_writer::tag(
                    'a',
                    get_string('viewsubmissionsteacher', 'assignsubmission_noto'),
                    ['href'=>(string) new moodle_url('/mod/assign/submission/noto/viewsubmissions.php', ['id'=>$submission->id])]
                );
            }
        } else {
            # display the "get copy" link only if a teacher uploaded a seed folder
            $fs = get_file_storage();
            $file_record = array(
                'contextid'=>$this->assignment->get_context()->id,
                'component'=>'assignsubmission_noto',
                'filearea'=>self::FILEAREA,
                'itemid'=>$this->assignment->get_instance()->id,
                'userid'=>$USER->id,
                'filepath'=>'/',
                'filename'=>sprintf('notebook_seed_assignment%d.zip', $this->assignment->get_instance()->id),
            );
            $file = $fs->get_file($file_record['contextid'], $file_record['component'], $file_record['filearea'], $file_record['itemid'], $file_record['filepath'], $file_record['filename']);
            if ($file) {
                $return = html_writer::tag('a', get_string('get_copy_assignment', 'assignsubmission_noto'), array('href'=>(string) new \moodle_url('/mod/assign/submission/noto/notocopy.php', array('id'=>$submission->id))));
            } else {
                $return = get_string('no_notebook_provided', 'assignsubmission_noto');
            }
            $notosubmission = $this->get_noto_submission($submission->id);
            if ($notosubmission) {
                if ($return) {
                    $return .= "<br/>\n";
                }
#                $return .= html_writer::tag(
#                    'a',
#                    get_string('viewsubmissions', 'assignsubmission_noto'),
#                    ['href'=> new moodle_url('/mod/assign/view.php', ['id'=>$cm->id, 'action'=>'editsubmission'])]
#                );
                $return .= html_writer::tag('a', get_string('viewsubmissions', 'assignsubmission_noto'), ['href'=>(string) new moodle_url('/mod/assign/submission/noto/submissioncopy.php', ['id'=>$submission->id])]);
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

        $file_record = array(
            'contextid'=>$this->assignment->get_context()->id,
            'component'=>'assignsubmission_noto',
            'filearea'=>self::FILEAREA,
            'itemid'=>$this->assignment->get_instance()->id,
            'userid'=>$user->id,
            'filepath'=>'/',
            'filename'=>sprintf('submission_assignment%d_user%s.zip', $this->assignment->get_instance()->id, $user->id),
        );
        $file = $fs->get_file($file_record['contextid'], $file_record['component'], $file_record['filearea'], $file_record['itemid'], $file_record['filepath'], $file_record['filename']);

        if ($file) {
            return array($file->get_filename()=>$file);
        }
    }
}

