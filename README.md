# moodle-jupyterhub-plugin
Jupyterhub plugin for Moodle

This repository contains 2 plugins made for Moodle 3.9
Those plugins are designed to make the communication from Moodle to a JupyterHub installation possible, allowing Moodle users to access their Jupyter files from Moodle - and more.

## assignsubmission plugin

This plugin adds a new type of Assignments: Jupyter notebooks.
- Teachers can select the assignment's material (notebooks, data, images, etc.) from their own Jupyter workspace
- Students can upload the assignment's material to their own Jupyter workspace, and submit their work

## assignfeedback plugin

This plugin is still under development ; the current version allows teachers to download all students' submissions in one click into the teacher's Jupyter workspace.

# Installation

Plugins' content need to be copied over to:
```
[moodle_root]/mod/assign/submission/noto
```
and
```
[moodle_root]/mod/assign/feedback/noto
```
respectively, on the Moodle server.

# API

On the JupyterHub side, an API needs to be deployed on a server that has access to all user's files - typically the file server of the JupyterHub installation.

See this repository for the API: [epfl-cede/jupyterhub-fileserver-api](https://github.com/epfl-cede/jupyterhub-fileserver-api)
