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
 * Strings for component 'qtype_meta', language 'en', branch 'MOODLE_20_STABLE'
 *
 * @package	qtype
 * @subpackage meta
 * @license	http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$string['informationtext'] = 'Information text';
$string['pluginname'] = 'Meta Data for Exam';
$string['pluginname_help'] = 'A Meta Data for Exam is not really a question type. It simply enables text to be displayed without requiring any answers, similar to a label on the course page.

The question text is displayed both during the attempt and on the review page. Any general feedback is displayed on the review page only.';
$string['pluginnameadding'] = 'Adding a Meta Data for Exam';
$string['pluginnameediting'] = 'Editing a Meta Data for Exam';
$string['pluginnamesummary'] = 'This is not actually a question. Instead it is a way to add some instructions, rubric or other content to the activity. This is similar to the way that labels can be used to add content to the course page.';


////////// Strings for the Form //////////////////////

$string['title'] = 'Title';
$string['title_help'] = 'Will be the headline during the Examination.';

$string['starttext'] = 'Introduction';
$string['starttext_help'] = 'Start Text for the Exam.';

$string['description'] = 'Description';
$string['description_help'] = 'Description for the Exam.';

$string['time'] = 'Time Limit (in Minutes)';
$string['time_help'] = 'Available time in minutes to solve the task.';

$string['kindnessextensiontime'] = 'Kindness Time Extention';
$string['kindnessextensiontime_help'] = 'Additional Time (in Minutes) invisible to the Student.';

$string['tasksperpage'] = 'Questions per Page';
$string['tasksperpage_help'] = 'How many Questions should the Student see per page?';

$string['tries'] = 'Number of Tries';
$string['tries_help'] = 'Number of possible tries to solve the task. Every new try results in a random aggregation of subtasks (and possibly answer combinations)';

$string['showhandlinghintsbeforestart'] = 'Show Introduction';

/*
$string['correctionMode'] = 'Corrector Mode';
$string['correctionMode_help'] = 'TODO: help text';
$string['multipleCorrectors'] = 'More than One Corrector';
$string['multipleCorrectors_help'] = 'In this correction mode, more than one (human) corrector proceed the correction of the Tasklet, in order to determine the overall result.';
$string['correctOnlyProcessedTasks'] = 'Only correct <i>n</i> Tasks';
// Wie viele vom Student beantwortete Fragen in die Bewertung eingehen
// nach der Reihenfolge wie die Tasks auf der Page (NICHT in der Reihenfolge wie der Student sie eigentlich gespeichert hat) landen die Klausuren bei "zu korrigieren" beim Korrektor
$string['correctOnlyProcessedTasks_help'] = 'In this correction mode, only the first n processed Subtasklets wil be corrected and influence the overall result.';
$string['regular'] = 'Regular';
$string['regular_help'] = 'One Corrector';
*/
$string['numberofcorrectors'] = 'Number of Correctors';
$string['numberofcorrectors_help'] = 'Number of (human) correctors to proceed the correction of the Tasklet, in order to determine the overall result. Default is 2 (Bologna), 0 for unlimited.';

////////// Strings for the Question Selection Form //////////////////////

$string['qheader'] = 'Question Selection';
