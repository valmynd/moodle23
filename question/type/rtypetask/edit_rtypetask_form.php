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
 * Defines the editing form for the rtypetask question type.
 *
 * @package    qtype
 * @subpackage rtypetask
 * @license    http://www.gnu.org/copyleft/gpl.html GNU Public License
 */

defined('MOODLE_INTERNAL') || die();
require_once($CFG->dirroot . '/question/type/comparetexttask/edit_comparetexttask_form.php'); // need data_preprocessing()
require_once($CFG->dirroot . '/question/type/multichoice/edit_multichoice_form.php'); // need "multiple multiple choice"

class qtype_rtypetask_edit_form extends qtype_comparetexttask_edit_form {

	public function qtype() {
		return 'rtypetask';
	}

	protected function definition_inner($mform) {
		// we won't use any applets
		$this->add_corrector_feedback();
		$this->add_question_fields();
		// remove "default mark" field
		$mform->removeElement('defaultmark');
		$mform->addElement('hidden', 'defaultmark', 0);
		$mform->setType('defaultmark', PARAM_RAW);
	}

	protected function add_question_fields() {
		// we need to get the initial value of how many answer fields should be rendered ...
		$num_questions = optional_param('num_questions', $this->get_num_questions(), PARAM_INT);
		// ... and whether "Add Question Block" was clicked
		if(optional_param('addquestionbtn', false, PARAM_TEXT)) ++$num_questions;
		$mform =&$this->_form;
		for ($i = 1; $i <= $num_questions; $i++) {
			$mform->addElement('header', "qheader_$i", get_string('qheader', 'qtype_rtypetask') . " $i");
			$mform->addElement('editor', "problem_$i", get_string('questiontext', 'question'), array('rows' => 8), $this->editoroptions);
			$mform->addElement('editor', "hint_$i", get_string('correctorfeedback', 'qtype_comparetexttask'), array('rows' => 4), $this->editoroptions);
			$mform->addElement('html', '<hr style="margin:20px 10px 20px 10px; border: 1px solid lightgrey;"/>'); // Seperator
			// each answer is a repeated item itself
			$num_answers = optional_param("num_answers_$i", $this->get_num_answers($i), PARAM_INT);
			if(optional_param("addanswerbtn_$i", false, PARAM_TEXT)) ++$num_answers;
			for($j=1; $j <= $num_answers; $j++) {
				$mform->addElement('text', 'answer_'.$i.'_'.$j, get_string('answer', 'question')." $j", array());
				$mform->addElement('radio', "correct_$i", '', get_string('correct', 'qtype_rtypetask'), $j, array());
			}
			// store the information about how many answers are shown for the next request
			$mform->addElement('hidden', "num_answers_$i", $num_answers);
			$mform->setType("num_answers_$i", PARAM_INT);
			$mform->setConstants(array("num_answers_$i"=>$num_answers));
			$mform->registerNoSubmitButton("addanswerbtn_$i");
			$mform->addElement('submit', "addanswerbtn_$i", get_string('addanswerbtn', 'qtype_rtypetask'));
		}
		// store the information about how many question blocks are shown for the next request
		$mform->addElement('hidden', 'num_questions', $num_questions);
		$mform->setType('num_questions', PARAM_INT);
		$mform->setConstants(array('num_questions'=>$num_questions));
		$mform->registerNoSubmitButton('addquestionbtn');
		$mform->addElement('submit', 'addquestionbtn', get_string('addquestionbtn', 'qtype_rtypetask'));
		$mform->closeHeaderBefore('addquestionbtn');
	}

	/*protected function data_preprocessing($formdata) { // called too late for our needs, see qtype_rtypetask::get_question_options()
		$formdata = qtype_comparetexttask_edit_form::data_preprocessing($formdata);
		$formdata = parent::data_preprocessing($formdata);
		if (empty($formdata->options)) return $formdata;
		//debugging(var_export($formdata));
		return $formdata;
	}*/

	protected function get_num_questions() {
		if(isset($this->question->num_questions) && $this->question->num_questions >= 2)
			return $this->question->num_questions;
		return 2;
	}

	protected function get_num_answers($i) {
		if(isset($this->question->{"num_answers_$i"}) && $this->question->{"num_answers_$i"} >= 2)
			return $this->question->{"num_answers_$i"};
		return 2;
	}
}
