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
 * Strings for component 'assignsubmission_noto', language 'en'
 *
 * @package   assignsubmission_noto
 * @copyright 2012 NetSpot {@link http://www.netspot.com.au}
 * @copyright 2020 Enovation {@link https://enovation.ie}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$string['allownotosubmissions'] = 'Enabled';
$string['default'] = 'Enabled by default';
$string['default_help'] = 'If set, this submission method will be enabled by default for all new assignments.';
$string['enabled'] = 'Jupyter notebooks';
$string['enabled_help'] = 'If enabled, students are able to upload a folder from their Jupyter workspace as their submission.';
$string['eventassessableuploaded'] = 'An Jupyter Notebook has been uploaded.';
$string['nosubmission'] = 'Nothing has been submitted for this assignment';
$string['noto'] = 'Jupyter notebooks';
$string['notofilename'] = 'noto.html';
$string['notosubmission'] = 'Allow Jupyter Notebooks submission';
$string['pluginname'] = 'Jupyter Notebooks submissions';
$string['privacy:metadata:assignmentid'] = 'Assignment ID';
$string['privacy:metadata:filepurpose'] = 'Files that are embedded in the text submission.';
$string['privacy:metadata:submissionpurpose'] = 'The submission ID that links to submissions for the user.';
$string['privacy:metadata:tablepurpose'] = 'Stores the Jupyter Notebook for each attempt.';
$string['privacy:metadata:textpurpose'] = 'The actual text submitted for this attempt of the assignment.';
$string['privacy:path'] = 'Submission Text';
$string['apiserver'] = 'Noto server URL';
$string['apiserver_help'] = '';
$string['apiwspath'] = 'Path to Noto API';
$string['apiwspath_help'] = 'Will be appended to Sevrer URL to form a full URL to NOTO API';
$string['apinotebookpath'] = 'Path to Noto Notebooks';
$string['apinotebookpath_help'] = 'Will be appended to Sevrer URL to form a full URL to redirect users to Notebooks';

$string['apiuser'] = 'Noto API username';
$string['apiuser_help'] = '';
$string['apikey'] = 'Noto API secret key';
$string['apikey_help'] = '';
$string['maxdepth'] = 'Maximum depth of filesystem tree';
$string['maxdepth_help'] = '';
$string['assignsubmission_noto_name'] = 'Assignment name';
$string['assignsubmission_noto_name_help'] = 'Please provide a name for your assignment. This name will be used when copying this assignment in the students’ workspace.';
$string['assignsubmission_noto_directory'] = 'Source folder';
$string['assignsubmission_noto_directory_destination'] = 'Destination folder';
$string['assignsubmission_noto_directory_help'] = "Select a directory from your Jupyter workspace in the tree below. Students will get a copy of this directory.\n\nPlease note: once saved, it can not be changed. Delete and re-create this assignment if a change is needed.";
$string['assignsubmission_noto_directory_label'] = 'This is your Jupyter workspace. Please select the folder containing your assignment.';
$string['assignsubmission_noto_createcopy'] = 'Create copy student form';
$string['assignsubmission_noto_createcopy_help'] = "Please click a directory in the tree below, it will automatically populate the setting field.";
$string['assignsubmission_noto_uploadparent'] = 'Assignment parent directory';
$string['assignsubmission_noto_uploadparent_help'] = 'Please select a parent directory to extract the noteboook';
$string['authmethod'] = 'Authentication method';
$string['authmethod_help'] = '"test" for the UAT platform and "noto" for the LIVE environment.';
$string['remotecopies'] = 'Remote copies created: {$a}';
$string['connectiontimeout'] = 'Connection timeout';
$string['connectiontimeout_help'] = 'The number of seconds to wait while trying to connect.';
$string['createcopy'] = 'Copy assignment';
$string['copyassignment'] = 'Copy assignment to your workspace';
$string['createcopy__link'] = 'Select target folder for the assignment copy';
$string['createcopy_help'] = 'Please click “Select target folder for the assignment copy” to choose the destination in your Jupyter workspace.';
$string['executiontimeout'] = 'Execution timeout';
$string['executiontimeout_help'] = 'The maximum number of seconds to allow API functions to execute.';
$string['assignmentnotready'] = 'Assignment is not ready: a parent Notebook directory is not configured.';
$string['notocopy_pagetitle'] = 'Create a Notebook copy';
$string['submissioncopy_pagetitle'] = 'Create a submission copy';
$string['cannotaddnoto'] = 'Error preparing the NOTO submission type: {$a}';
$string['remotecopysuccess'] = 'A copy of the assignment has been copied to "{$a->new_directory_created}".<br/>
{$a->redirect_link}<br/>
You can create another copy of the assignment or click “Cancel”.<br/><br/>
{$a->backtoassignment}
';
$string['remotecopysuccess_ethz'] = 'A copy of the assignment has been copied to "{$a->new_directory_created}".<br/>
You can create another copy of the assignment or click “Cancel”.<br/><br/>
{$a->backtoassignment}
';
$string['remotecopysuccessteacher'] = '
A copy of the student submission has been copied to "{$a->new_directory_created}".<br/>
{$a->redirect_link}<br/><br/>
{$a->backtoassignment}
';
$string['remotecopysuccessteacher_ethz'] = '
A copy of the student submission has been copied to "{$a->new_directory_created}".<br/><br/>
{$a->backtoassignment}
';
$string['submitnotoforgrading'] = 'Folder to submit';
$string['submitnotoforgrading_tree_label'] = 'This is your Jupyter workspace. Please select the folder you want to submit.';
$string['submitnotoforgrading_tree_teacherlabel'] = "Below is a view of your Jupyter workspace. Please select the folder where to copy the submission.<br/>
Feel free to create a folder in Jupyter before copying the submission.";
$string['submitnotoforgrading_help'] = "The directory tree below displays all Notebook copies created for this assignment.\n\nFind a copy you want to submit for grading and click it, it will automatically populate the setting field.";
$string['redirecttonoto'] = 'Click here to get to your Jupyter workspace.';
$string['viewnotosubmissions'] = 'View your submission in Notebook';
$string['viewsubmissions'] = 'View your submission';
$string['viewsubmissionsteacher'] = 'View submission';
$string['viewsubmissions_pagehelp'] = 'Please select a directory in the tree below to create a copy of a student notebook, and submit.';
$string['viewsubmissions_diffcopy'] = 'There exist a newer submission by the student, please use the tree below to create the most recent copy';
$string['attention'] = 'Attention';
$string['recentsubmission'] = 'Existing copy of the submission';
$string['recentsubmission_help'] = "Please follow the link to the copy of the Notebook submission created by the student";
$string['info'] = 'Info';
$string['viewsubmissions_recentcopy'] = 'This is the most recent submission by the student';
$string['viewsubmission_pagetitle'] = 'Notebook submission by {$a}';
$string['assignsubmission_noto_directory_title'] = 'Existing Notebook copies:';
$string['notocopy_pagehelp'] = 'Please select a parent directory from the tree below to extract the assignment Notebook into';
$string['download_seed_zip'] = 'Download seed zip';
$string['copysubmission'] = 'Copy submission';
$string['copysubmissions'] = 'Copy submission(s)';
$string['notocopytitle'] = 'Below is a view of your Jupyter workspace. Please select the folder where to copy the assignment.<br/>
Feel free to create a folder in Jupyter before copying the assignment.';
$string['get_copy_assignment'] = 'Get a copy of the assignment';
$string['submissiondate'] = 'Submission date';
$string['existingsubmissions'] = 'Existing submissions';
$string['reloadtree'] = 'Refresh tree';
$string['nothingchosen'] = 'Nothing was chosen';
$string['youalreadysubmitted'] = "<b>You have already made a Jupyter notebook submission.</b><br/>\nBelow you can retrieve a copy of your current submission.";
$string['submissioncopytitle'] = "Below is a view of your Jupyter workspace. Please select the folder where to copy your current submission.<br/>\nFeel free to create a folder in Jupyter before copying the submission.";
$string['no_notebook_provided'] = 'No notebooks were provided for this assignment.';
$string['notoaccount_notfound'] = 'Your Jupyter workspace could not be found. Please create your Jupyter workspace first and then reload this page.';
$string['userprofilepassword'] = 'Password to access userprofile script';
$string['userprofilepassword_help'] = 'ALPHANUM characters only';
$string[''] = '';
/* ETHZ Installation settings */
$string['ethzinstallation'] = 'ETHZ Installation';
$string['ethzinstallation_help'] = 'If checkbox is enabled, then only ETHZ installation settings will be showed.';
$string['apiurl'] = 'API URL';
$string['apiurl_help'] = 'This API URL should include course id placeholder "[courseid]" (e.g. https://web2-xxx-[courseid].vvv.ethz.ch).
It will be replaced automatically by course id.';
$string['apiusername'] = 'API username';
$string['apiusername_help'] = '';
$string['apisecretkey'] = 'API secret key';
$string['apisecretkey_help'] = '';
$string['apiusernameparam'] = 'API username parameter';
$string['apiusernameparam_help'] = 'Defines which profile field should be used for identification';
$string['apiusernameparamprefix'] = 'API username parameter prefix';
$string['apiusernameparamprefix_help'] = 'Defines prefix which should be used for username parameter';
$string['backtoassignment'] = 'Back to assignment';
$string['backtosubmissions'] = 'Back to submissions';
/* Offline gradebook */
$string['identifier'] = 'Identifier';
$string['fullname'] = 'Full name';
$string['emailaddress'] = 'Email address';
$string['status'] = 'Status';
$string['grade'] = 'Grade';
$string['maximumgrade'] = 'Maximum Grade';
$string['gradecanchange'] = 'Grade can be changed';
$string['lastmodified_submission'] = 'Last modified (submission)';
$string['lastmodified_grade'] = 'Last modified (grade)';
$string['feedback_comments'] = 'Feedback comments';

