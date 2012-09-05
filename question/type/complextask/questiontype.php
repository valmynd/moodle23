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
 * Question type class for the complextask question type.
 *
 * @package    qtype
 * @subpackage complextask
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * The complextask question type class.
 *
 * TODO: Make sure short answer questions chosen by a complextask question
 * can not also be used by a random question
 *
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
*/
class qtype_complextask extends question_type {
	const MAX_SUBQUESTIONS = 10;

	public function extra_question_fields() {
		return array('question_complextask', 'correctorfeedback', 'memento');
	}

	public function get_question_options($question) {
		global $DB;
		$question->options = $DB->get_record('question_complextask', array('id' => $question->id), '*', MUST_EXIST);
		$question->options->memento = base64_encode($question->options->memento);
		$question->options->answers = array();
		debugging("§question:".var_export($question));
		return true;
	}

	public function save_question_options($question) {
		global $DB;
		//$question->options->answers = array();
		debugging("save_question_options(): §question:".var_export($question->correctorfeedback['text']));
		if(strpos($question->memento, "Error:") === 0) {
			$result = new stdClass();
			$result->error = $question->memento;
			return $result;
		}
		$existing = $DB->get_record('question_complextask', array('id' => $question->id));
		$options = new stdClass(); // such an object is required by update_record() / insert_record()
		$options->correctorfeedback = $question->correctorfeedback['text']; // "editor" fields need extra treatment in moodle formslib
		$options->memento = base64_decode($question->memento);
		if ($existing) {
			$options->id = $existing->id;
			$DB->update_record('question_complextask', $options);
		} else {
			$DB->insert_record('question_complextask', $options);
		}
		return true;
	}

	public function delete_question($questionid, $contextid) {
		// TODO: delete question-specific data, if needed
		parent::delete_question($questionid, $contextid);
	}

	/**
	 * @param object $question
	 * @return mixed either a integer score out of 1 that the average random
	 * guess by a student might give or an empty string which means will not
	 * calculate.
	 */
	public function get_random_guess_score($question) {
		return 1/$question->options->choose;
	}
}
