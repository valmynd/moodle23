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
 * Question type class for the groupingtask question type.
 *
 * @package    qtype
 * @subpackage groupingtask
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * The groupingtask question type class.
 *
 * TODO: Make sure short answer questions chosen by a groupingtask question
 * can not also be used by a random question
 *
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
*/
class qtype_groupingtask extends question_type {
	public function extra_question_fields() {
		return array('question_groupingtask', 'correctorfeedback', 'memento');
	}
	public function save_question_options($question) {
		// database should contain readable xml, no base64 encoded things
		$question->memento = base64_decode($question->memento);
		// "editor" fields need extra treatment in moodle formslib + they cause problems on import!
		if (is_array($question->correctorfeedback))
			$question->correctorfeedback = $question->correctorfeedback['text'];
		return parent::save_question_options($question);
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
