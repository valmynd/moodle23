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
 * Defines the editing form for the qtemplate question type.
 *
 * @package    qtype
 * @subpackage qtemplate
 * @license    http://www.gnu.org/copyleft/gpl.html GNU Public License
 */


defined('MOODLE_INTERNAL') || die();


/**
 * qtemplate editing form definition.
 *
 * @license    http://www.gnu.org/copyleft/gpl.html GNU Public License
*/
class qtype_qtemplate_edit_form extends question_edit_form {
	protected function definition_inner($mform) {
		$questionstoselect = array();
		for ($i = 2; $i <= qtype_qtemplate::MAX_SUBQUESTIONS; $i++) {
			$questionstoselect[$i] = $i;
		}

		$mform->addElement('select', 'choose',
				get_string('qtemplatenumber', 'quiz'), $questionstoselect);
		$mform->setType('feedback', PARAM_RAW);

		$mform->addElement('hidden', 'fraction', 0);
		$mform->setType('fraction', PARAM_RAW);
	}

	protected function data_preprocessing($question) {
		if (empty($question->name)) {
			$question->name = get_string('qtemplate', 'quiz');
		}

		if (empty($question->questiontext)) {
			$question->questiontext = get_string('qtemplateintro', 'quiz');
		}
		return $question;
	}

	public function qtype() {
		return 'qtemplate';
	}

	public function validation($data, $files) {
		global $DB;
		$errors = parent::validation($data, $files);
		if (isset($data->categorymoveto)) {
			list($category) = explode(',', $data['categorymoveto']);
		} else {
			list($category) = explode(',', $data['category']);
		}
		$saquestions = question_bank::get_qtype('qtemplate')->get_sa_candidates($category);
		$numberavailable = count($saquestions);
		if ($saquestions === false) {
			$a = new stdClass();
			$a->catname = $DB->get_field('question_categories', 'name', array('id' => $category));
			$errors['choose'] = get_string('nosaincategory', 'qtype_qtemplate', $a);

		} else if ($numberavailable < $data['choose']) {
			$a = new stdClass();
			$a->catname = $DB->get_field('question_categories', 'name', array('id' => $category));
			$a->nosaquestions = $numberavailable;
			$errors['choose'] = get_string('notenoughsaincategory', 'qtype_qtemplate', $a);
		}
		return $errors;
	}
}
