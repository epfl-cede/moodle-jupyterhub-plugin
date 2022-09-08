# moodle-jupyterhub-plugin
Jupyterhub plugins for Moodle

This repository contains two plugins made for Moodle 3.9, and a file management API that needs to be running on the Jupyter file server.
All three are available from this git repository as submodules.

Those plugins are designed to make the communication from Moodle to a JupyterHub installation possible, allowing Moodle users to access their Jupyter files from Moodle - and more.

User documentation is available from this repository.
For technical documentation, please check to the submodules.

## moodle-mod-assign-submission-noto plugin

This plugin adds a new type of Assignments: Jupyter notebooks.
- Teachers can select the assignment's material (notebooks, data, images, etc.) from their own Jupyter workspace
- Students can upload the assignment's material to their own Jupyter workspace, and submit their work

## moodle-mod-assign-feedback-noto plugin

This plugin is still under development ; the current version allows teachers to download all students' submissions in one click into the teacher's Jupyter workspace.

# Installation

Moodle plugins need to be copied over to:
```
[moodle_root]/mod/assign/submission/noto
```
and
```
[moodle_root]/mod/assign/feedback/noto
```
respectively, on the Moodle server.

Please use the git repository URLs directly for the deployment:
* [epfl-cede/moodle-mod-assign-feedback-noto](https://github.com/epfl-cede/moodle-mod-assign-feedback-noto)
* [epfl-cede/moodle-mod-assign-submission-noto](https://github.com/epfl-cede/moodle-mod-assign-submission-noto)

# File Server API

On the JupyterHub side, an API needs to be deployed on a server that has access to all user's files - typically the File Server of the JupyterHub deployment.

See this repository for the API: [epfl-cede/jupyterhub-fileserver-api](https://github.com/epfl-cede/jupyterhub-fileserver-api)
