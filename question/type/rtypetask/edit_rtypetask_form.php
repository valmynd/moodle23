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
		$answersoption = '';
		$repeatedoptions = array();
		// add per question (!) fields (the method name is a bit misleading, but it does exactly what we want)
		question_edit_form::add_per_answer_fields($mform, get_string('qheader', 'qtype_rtypetask', '{no}'), null, 2, 1);
		// addanswers button will look like tis: <input name="addanswers" value="..." onclick="skipClientValidation = true;" id="id_addanswers" type="submit">
		//$mform->removeElement('addanswers');
		//$mform->addElement('submit', 'addanswers', get_string('addquestionbtn', 'qtype_rtypetask'));
	}

	/**
	 * will be called by question_edit_form::add_per_answer_fields()
	 * this should be called get_per_question_fields (!), but this way we avoid redundancy
	 *
	 * @see question_edit_form::get_per_answer_fields()
	 * @return an array of repeatable elements
	 */
	protected function get_per_answer_fields($mform, $label, $gradeoptions, &$repeatedoptions, &$answersoption) {
		$question_items = array();
		$question_items[] = $mform->createElement('header', 'answerhdr', $label);
		$question_items[] = $mform->createElement('editor', 'problem', get_string('questiontext', 'question'), array('rows' => 8), $this->editoroptions);
		$question_items[] = $mform->createElement('editor', 'hint', get_string('correctorfeedback', 'qtype_comparetexttask'), array('rows' => 4), $this->editoroptions);
		//$question_items[] = $mform->createElement('textarea', 'problem', get_string('questiontext', 'question'), array('rows'=> '2', 'cols'=>'80'));
		//$question_items[] = $mform->createElement('textarea', 'hint', get_string('correctorfeedback', 'qtype_comparetexttask'), array('rows'=> '1', 'cols'=>'80'));
		$question_items[] = $mform->createElement('html', '<hr style="margin:20px 10px 20px 10px; border: 1px solid lightgrey;"/>'); // Seperator
		// each answer is a repeated item itself
		$this->add_answer_possibility_fields($mform, $question_items);
		//$question_items[] = $mform->addGroup($radioarray, 'radioar', '', array(' '), false);
		$answersoption = 'questions';
		return $question_items;
	}
	/**
	 * our variant of add_per_answer_fields(), which does exactly what its name suggests
	 */
	protected function add_answer_possibility_fields($mform, array &$question_items) {
		// we need to get the initial value of how many answer fields should be rendered
		//$repeats = optional_param($repeathiddenname, $repeats, PARAM_INT);
		//if('addanswerbtn' in ...) ++$repeats;
		// without further treatment, fields would look like this:
		//		<input name="answer[0]" type="text" id="id_answer_0" />
		// the number (0) would stand for the first question block -> we must add another identifier to see which answer we have
		// for now we will just append a number starting with 1, so it will end up looking like this:
		//		<input name="answer1[0]" id="id_answer1_0" type="text">
		for($i=1; $i<3; $i++) {
			$question_items[] = $mform->createElement('text', "answer$i", get_string('answer', 'question')." $i", array());
			$question_items[] = $mform->createElement('radio', "correct", '', get_string('correct', 'qtype_rtypetask'), $i, array());
		}
		// the result will end up looking like this: 'answer2' => array ( 0 => 'zwei', 1 => 'vier', ) which can be interpreted
		// as "secend answer for first question is 'zwei', secend answer for second question is 'vier'"
		// ... finally add a button to add another field;
		$question_items[] = $mform->createElement('submit', 'addanswerbtn', get_string('addanswerbtn', 'qtype_rtypetask'));
		$mform->registerNoSubmitButton('addanswerbtn'); // prevent client validation on submit
		//$mform->addElement('hidden', $repeathiddenname, $repeats); // will store current value
		//$mform->setType($repeathiddenname, PARAM_INT);
	}
}
