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
 * Question type class for the comparetexttask question type.
 *
 * @package    qtype
 * @subpackage comparetexttask
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * The comparetexttask question type class.
 *
 * TODO: Make sure short answer questions chosen by a comparetexttask question
 * can not also be used by a random question
 *
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
*/
class qtype_comparetexttask extends question_type {
	const MAX_SUBQUESTIONS = 10;

	public function is_usable_by_random() {
		return false;
	}

	public function get_question_options($question) {
		// TODO: load options from XML/XSD
		return true;
	}

	public function save_question_options($question) {
		// TODO: store outcome somehow
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
