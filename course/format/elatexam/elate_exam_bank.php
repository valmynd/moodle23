<?php

require_once($CFG->dirroot . '/question/editlib.php');
require_once($CFG->dirroot . '/course/format/elatexam/elate_question_bank.php');
/**
 * This class extends the question bank view to show only Exam special Meta Types.
 *
 * @author C.Wilhelm
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

class elate_exam_bank_view extends elate_question_bank_view {
//class elate_exam_bank_view extends question_bank_view {
	public function __construct($contexts, $pageurl, $course, $cm = null) {
		global $PAGE, $OUTPUT, $CFG;
		//$PAGE->requires->css("/course/format/elatexam/styles.css");
		$PAGE->requires->js("/course/format/elatexam/banklib.js");
		return question_bank_view::__construct($contexts, $pageurl, $course, $cm);
	}
}