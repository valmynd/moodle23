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
 * Defines the editing form for the comparetexttask question type.
 *
 * @package    qtype
 * @subpackage comparetexttask
 * @license    http://www.gnu.org/copyleft/gpl.html GNU Public License
 */


defined('MOODLE_INTERNAL') || die();

/**
 * comparetexttask editing form definition.
 *
 * @license    http://www.gnu.org/copyleft/gpl.html GNU Public License
*/
class qtype_comparetexttask_edit_form extends question_edit_form {
	protected function definition_inner($mform) {
		// TODO: Formularfelder definieren, testen!
		$questionstoselect = array();
		for ($i = 2; $i <= qtype_comparetexttask::MAX_SUBQUESTIONS; $i++) {
			$questionstoselect[$i] = $i;
		}

		$mform->addElement('select', 'choose',
				get_string('comparetexttasknumber', 'quiz'), $questionstoselect);
		$mform->setType('feedback', PARAM_RAW);

		$mform->addElement('hidden', 'fraction', 0);
		$mform->setType('fraction', PARAM_RAW);
	}

	protected function data_preprocessing($question) {
		// TODO: load XML here
		if (empty($question->name)) {
			$question->name = get_string('comparetexttask', 'quiz');
		}

		if (empty($question->questiontext)) {
			$question->questiontext = get_string('comparetexttaskintro', 'quiz');
		}
		return $question;
	}

	public function qtype() {
		return 'comparetexttask';
	}
}
