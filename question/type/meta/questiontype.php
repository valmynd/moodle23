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
 * Question type class for the meta 'question' type.
 *
 * @package	qtype
 * @subpackage meta
 * @copyright  1999 onwards Martin Dougiamas  {@link http://moodle.com}
 * @license	http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/questionlib.php');

class qtype_meta extends question_type {

	public function extra_question_fields() {
		return array('question_meta', 'time', 'kindnessextensiontime', 'tasksperpage', 'tries', 'showhandlinghintsbeforestart', 'numberofcorrectors');
	}

	/* SEEMS LIE MOST OF THIS IS HANDLED BY PARENT CLASS ALREADY! (at least in moodle 2.x)
	public function get_question_options($question) {
		global $DB;
		$question->options = $DB->get_record('question_meta', array('questionid' => $question->id), '*', MUST_EXIST);
		return parent::get_question_options($question);
	}

	public function save_question_options($question) {
		global $DB;
		$existing = $DB->get_record('question_meta', array('questionid' => $question->id));
		$options = new stdClass(); // such an object is required by update_record() / insert_record()
		$options->time = $question->time;
		$options->kindnessextensiontime = $question->kindnessextensiontime;
		$options->tasksperpage = $question->tasksperpage;
		$options->tries = $question->tries;
		$options->showhandlinghintsbeforestart = $question->showhandlinghintsbeforestart;
		$options->numberofcorrectors = $question->numberofcorrectors;

		// set foreign key question_meta.questionid to question.id
		$options->questionid = $question->id;
		if ($existing) {
			$options->id = $existing->id;
			$DB->update_record('question_meta', $options);
		} else {
			$DB->insert_record('question_meta', $options);
		}
		return true;
	}*/

	/// IMPORT/EXPORT FUNCTIONS /////////////////
	
	/*
	 * Imports question from the Moodle XML format
	*/
	public function import_from_xml($data, $question, qformat_xml $format, $extra=null) {
		return parent::import_from_xml($data, $question, $format, $extra);
	}
	
	/*
	 * Export question to the Moodle XML format
	*/
	public function export_to_xml($question, qformat_xml $format, $extra=null) {
		return parent::export_to_xml($question, $format, $extra);
	}

	////// the following is borrowed from qtype_description -> compare to original when upgrading moodle! //////////
	public function is_real_question_type() {
		return false;
	}
	public function is_usable_by_random() {
		return false;
	}
	public function can_analyse_responses() {
		return false;
	}
	public function save_question($question, $form) {
		$form->defaultmark = 0;
		return parent::save_question($question, $form);
	}
	public function actual_number_of_questions($question) {
		return 0;
	}
	public function get_random_guess_score($questiondata) {
		return null;
	}
}
