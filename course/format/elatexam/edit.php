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
 * Page to edit the question bank
 * @see /questions/edit.php
 *
 * @package    moodlecore
 * @subpackage questionbank
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../../config.php');
require_once($CFG->dirroot . '/question/editlib.php');
require_once($CFG->dirroot . '/course/format/elatexam/elate_exam_bank.php');

list($thispageurl, $contexts, $cmid, $cm, $module, $pagevars) =
        question_edit_setup('editq', '/course/format/elatexam/edit.php');

$url = new moodle_url($thispageurl);
$PAGE->set_url($url);

//$questionbank = new elate_exam_bank_view($contexts, $thispageurl, $COURSE, $cm);
$questionbank = new elate_exam_bank_view($contexts, $thispageurl, $COURSE, $cm);
$questionbank->process_actions();

$context = $contexts->lowest();
$PAGE->set_title("Add Exam");
$PAGE->set_heading($COURSE->fullname);
echo $OUTPUT->header();

echo '<div class="questionbankwindow boxwidthwide boxaligncenter">';
$questionbank->display('questions', $pagevars['qpage'], $pagevars['qperpage'],
        $pagevars['cat'], $pagevars['recurse'], $pagevars['showhidden'],
        $pagevars['qbshowtext']);
echo "</div>\n";

echo $OUTPUT->footer();