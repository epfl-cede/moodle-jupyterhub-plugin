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
 * Create a remote notebook copy
 *
 * @package   assignsubmission_noto
 * @copyright 2020 Enovation Solutions
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(dirname(__FILE__). '/../../../../config.php');
require_once($CFG->libdir . '/formslib.php');
define('FILEAREA', 'noto_zips');    # it is also a constant in class assign_submission_noto in locallib.php, but i'm not requiring it only for 1 constant

class notocopy_form extends moodleform {
    /**
     * Form definition
     */
    function definition() {
        global $CFG, $PAGE, $DB;
        $dirlist_top = $this->_customdata['dirlist_top'];
        $submission = $this->_customdata['submission'];
        $config = $this->_customdata['config'];
        $PAGE->requires->js_call_amd('assignsubmission_noto/directorytree', 'init');
        $mform = $this->_form;
        $maxdepth = assignsubmission_noto\notoapi::MAXDEPTH;
        if (isset($config->maxdepth) && $config->maxdepth) {
            $maxdepth = $config->maxdepth;
        }
        $mform->addElement('static', 'submissiondate', get_string('submissiondate', 'assignsubmission_noto'), date('D M j G:i:s T Y', $submission->timemodified));
        $teacher_copy = $DB->get_record('assignsubmission_noto_tcopy', ['studentid'=>$submission->userid, 'assignmentid'=>$submission->assignment]);
        if ($teacher_copy) {
#            $apinotebookpath = sprintf('%s/%s', trim($config->apiserver, '/'), trim($config->apinotebookpath, '/'));
#            $mform->addElement(
#                'static', 
#                'recentsubmission', 
#                get_string('recentsubmission', 'assignsubmission_noto'), 
#                html_writer::tag(
#                    'a',
#                    $teacher_copy->path,
#                    array('href'=>$apinotebookpath . $teacher_copy->path, 'target'=>'_blank')
#                )
#            );
#
#            $mform->addHelpButton('recentsubmission', 'recentsubmission', 'assignsubmission_noto');
            # is this the most recent submission? 
            if ($teacher_copy->timecreated > $submission->timemodified) {
                #$mform->addElement('static', 'pagehelp', get_string('info', 'assignsubmission_noto'), get_string('viewsubmissions_recentcopy', 'assignsubmission_noto'));
                #return;
            } else {
                $mform->addElement('static', 'pagehelp', get_string('attention', 'assignsubmission_noto'), get_string('viewsubmissions_diffcopy', 'assignsubmission_noto'));
            }
        }
        $mform->addElement('static', 'submitnotoforgrading_tree_teacherlabel', '', get_string('submitnotoforgrading_tree_teacherlabel', 'assignsubmission_noto'));
        $mform->addElement('text', 'assignsubmission_noto_directory', get_string('assignsubmission_noto_directory_destination', 'assignsubmission_noto'), array('id'=>'assignsubmission_noto_directory', 'size'=>80));
        $mform->setType('assignsubmission_noto_directory', PARAM_URL);
        $mform->addHelpButton('assignsubmission_noto_directory', 'assignsubmission_noto_createcopy', 'assignsubmission_noto');
        $mform->freeze('assignsubmission_noto_directory');
        $mform->addElement('hidden', 'assignsubmission_noto_directory_h', '', array('id'=>'assignsubmission_noto_directory_h'));  # _h is for "hidden" if you're wondering
        $mform->setType('assignsubmission_noto_directory_h', PARAM_TEXT);
        $mform->addElement('hidden', 'id', $submission->id);
        $mform->setType('id', PARAM_INT);
        $dirlistgroup = array();
        $dirlistgroup[] = $mform->createElement('html', '<div id="jstree">');
        $dirlistgroup = assignsubmission_noto\nototreerenderer::display_lof_recursive($dirlistgroup, $mform, $dirlist_top, assignsubmission_noto\notoapi::STARTPOINT, 0, $maxdepth);
        $dirlistgroup[] = $mform->createElement('html', '</div>');
        $mform->addGroup($dirlistgroup, 'assignsubmission_noto_dirlist_group', '', ' ', false);
        #$this->add_action_buttons(true, get_string('copysubmission', 'assignsubmission_noto'));
        $buttonarray=array();
        $buttonarray[] =& $mform->createElement('submit', 'reload', get_string('reloadtree', 'assignsubmission_noto'), ['id'=>'assignsubmission_noto_reloadtree_submit']);
        $buttonarray[] =& $mform->createElement('submit', 'submitbutton', get_string('createcopy', 'assignsubmission_noto'));
        $buttonarray[] =& $mform->createElement('submit', 'cancel', get_string('cancel'));
        $mform->addGroup($buttonarray, 'buttonar', '', array(' '), false);
    }
    /**
     * generates html prepated to be used in jtree
     * @param array &$dirlistgroup - the resulting array with form HTML elements added
     * @param MoodleQuickForm $mform
     * @param stdClass $directory - a node from the response of the API call
     * @param string $directory - a string directory to be added as a form element ID - otherwise the function knows only the current dirname, but none of parents
     * @param int depth - the current depth in the tree
     * @param int $maxdepth - not to calculate it many times in the recursive function
     * @return array - the moodle form group array
     */
    private function display_directory_recursive (array &$dirlistgroup, MoodleQuickForm $mform, stdClass $directory, string $path, int $depth, int $maxdepth): array {
        global $OUTPUT;
        if ($depth > $maxdepth) {
            return $dirlistgroup;
        }
        # the top element from $directory is not included, it's the home directory of the user
        if ($path === assignsubmission_noto\notoapi::STARTPOINT) {
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
                $this->display_directory_recursive($dirlistgroup, $mform, $child, sprintf('%s/%s', $path, $child->name), $depth +1, $maxdepth);
            }
            $dirlistgroup[] = $mform->createElement('html', '</ul>');
        } else {
            $dirlistgroup[] = $mform->createElement('html', '</li>');
        }
        return $dirlistgroup;
    }
}

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
if (!has_capability('mod/assign:grade', $context)) {
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

$notoapi = new assignsubmission_noto\notoapi();
$dirlist_top = new stdClass();
$dirlist_top->name = assignsubmission_noto\notoapi::STARTPOINT;
$dirlist_top->type = 'directory';
$dirlist_top->children = $notoapi->lof(assignsubmission_noto\notoapi::STARTPOINT);

$form = new notocopy_form(null, array('dirlist_top'=>$dirlist_top, 'submission'=>$submission, 'config'=>$config));

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
        'filename'=>sprintf('submission_assignment%d_user%s.zip', $submission->assignment, $submission->userid),
    );
    $file = $fs->get_file($file_record['contextid'], $file_record['component'], $file_record['filearea'], $file_record['itemid'], $file_record['filepath'], $file_record['filename']);
    if (!$file) {
        throw new \moodle_exception("Submissison zip not found");
    }
    #$date_string = date('Ymd_HGs');
    $dest_path = sprintf('%s/%s/course%d_assignment%d_student%d', assignsubmission_noto\notoapi::STARTPOINT, $data->assignsubmission_noto_directory_h, $cm->course, $cm->id, $submission->userid);
    $notoapi = new assignsubmission_noto\notoapi();
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
    $apinotebookpath = sprintf('%s/%s', trim($config->apiserver, '/'), trim($config->apinotebookpath, '/'));
    $notoremotecopy = $DB->get_record('assignsubmission_noto_tcopy', array('studentid'=>$submission->userid, 'assignmentid'=>$submission->assignment));
    if ($notoremotecopy) {
        #$notoremotecopy->paths = isset($notoremotecopy->paths) ? sprintf("%s\n%s", $notoremotecopy->paths, $new_directory_created) : $new_directory_created;
        $notoremotecopy->path = $new_directory_created;    # only one path here
        $notoremotecopy->timecreated = time();
        $updatestatus = $DB->update_record('assignsubmission_noto_tcopy', $notoremotecopy);
        $params['new_directory_created'] = $new_directory_created;
        $params['redirect_link'] = html_writer::tag(
            'a', 
            get_string('redirecttonoto', 'assignsubmission_noto'), 
            array('href'=>$apinotebookpath . $new_directory_created, 'target'=>'_blank')
        );
        \core\notification::success(get_string('remotecopysuccess', 'assignsubmission_noto', (object)$params));
    } else {
        $notoremotecopy = new stdClass();
        $notoremotecopy->path = $new_directory_created;
        $notoremotecopy->studentid = $submission->userid;
        $notoremotecopy->assignmentid = $submission->assignment;
        #$notoremotecopy->assignment = $cm->instance;
        $notoremotecopy->timecreated = time();
        $notoremotecopy->id = $DB->insert_record('assignsubmission_noto_tcopy', $notoremotecopy);
        $params['new_directory_created'] = $new_directory_created;
        $params['redirect_link'] = html_writer::tag(
            'a', 
            get_string('redirecttonoto', 'assignsubmission_noto'), 
            array('href'=>$apinotebookpath . $new_directory_created, 'target'=>'_blank')
        );
        \core\notification::success(get_string('remotecopysuccessteacher', 'assignsubmission_noto', (object)$params));
    }
    # re-calculate the remote structure with the new created directory
    #    $dirlist_top = $notoapi->lod(assignsubmission_noto\notoapi::STARTPOINT);
    #    $form = new notocopy_form(null, array('dirlist_top'=>$dirlist_top, 'submission'=>$submission));
    redirect($PAGE->url);
    exit;
} 

print $OUTPUT->header();
$form->display();
print $OUTPUT->footer();

