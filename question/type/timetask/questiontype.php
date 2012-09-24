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
 * Question type class for the timetask question type.
 *
 * @package    qtype
 * @subpackage timetask
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * The timetask question type class.
 *
 * TODO: Make sure short answer questions chosen by a timetask question
 * can not also be used by a random question
 *
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
*/
class qtype_timetask extends question_type {
	const MAX_SUBQUESTIONS = 10;

	public function extra_question_fields() {
		return array('question_timetask', 'correctorfeedback', 'memento');
	}

	public function get_question_options($question) {
		global $DB;
		$question->options = $DB->get_record('question_timetask', array('questionid' => $question->id), '*', MUST_EXIST);
		return parent::get_question_options($question);
	}

	public function save_question_options($question) {
		global $DB;
		//$question->options->answers = array();
		//debugging("save_question_options(): §question:".var_export($question->correctorfeedback['text']));
		if(strpos($question->memento, "Error:") === 0) {
			$result = new stdClass();
			$result->error = $question->memento;
			return $result;
		}
		$existing = $DB->get_record('question_timetask', array('questionid' => $question->id));
		$options = new stdClass(); // such an object is required by update_record() / insert_record()
		$options->correctorfeedback = $question->correctorfeedback['text']; // "editor" fields need extra treatment in moodle formslib
		$options->memento = base64_decode($question->memento); // database should contain readable xml, no base64 encoded things
		$options->questionid = $question->id; // set foreign key question_timetask.questionid to questions.id
		if ($existing) {
			$options->id = $existing->id;
			$DB->update_record('question_timetask', $options);
		} else {
			$DB->insert_record('question_timetask', $options);
		}
		return true;
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
