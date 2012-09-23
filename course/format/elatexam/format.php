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
 * ElateXam course format.  Display the whole course as "elatexam" made of modules.
 *
 * @package format_elatexam
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

// compare the following to /question/edit.php
require_once($CFG->dirroot . '/course/format/elatexam/elate_exam_bank.php');
$_GET["courseid"] = $_GET["id"];
list($thispageurl, $contexts, $cmid, $cm, $module, $pagevars) = exam_bank_setup('editq', '/course/view.php');
$questionbank = new elate_exam_bank_view($contexts, $thispageurl, $COURSE, $cm);
$questionbank->process_actions();
$questionbank->display('editq', $pagevars['qpage'], $pagevars['qperpage'], $pagevars['cat'], $pagevars['recurse'], $pagevars['showhidden'], $pagevars['qbshowtext']);

// ... question bank not possible on this page: problem with require_login() called by question_edit_setup()
/*echo '<ul class="topics"><li id="section-0" class="section main clearfix">';
echo 'Test';
echo '</li></ul>';*/