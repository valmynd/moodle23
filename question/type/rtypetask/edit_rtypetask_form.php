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
	}

	protected function add_question_fields() {
		// we need to get the initial value of how many answer fields should be rendered ...
		$num_questions = $this->get_num_questions();
		// ... and whether "Add Question Block" was clicked
		if(optional_param('addquestionbtn', false, PARAM_TEXT)) ++$num_questions;
		$mform =&$this->_form;
		for ($i = 1; $i <= $num_questions; $i++) {
			$this->append_per_question_header($i, $num_questions);
			$this->handle_delete_question_button($i, $num_questions);
			$mform->addElement('editor', "problem_$i", get_string('questiontext', 'question'), array('rows' => 8), $this->editoroptions);
			$mform->addRule("problem_$i", null, 'required', null, 'client');
			$mform->addElement('editor', "hint_$i", get_string('correctorfeedback', 'qtype_comparetexttask'), array('rows' => 4), $this->editoroptions);
			//$mform->addElement('html', '<hr style="margin:20px 10px 20px 10px; border: 1px solid lightgrey;"/>'); // Seperator
			$mform->addElement('html', '<br /><br />');
			// each answer is a repeated item itself
			$num_answers = $this->get_num_answers($i);
			if(optional_param("addanswerbtn_$i", false, PARAM_TEXT)) ++$num_answers;
			for($j=1; $j <= $num_answers; $j++) {
				$this->append_per_answer_fields($i, $j, $num_answers);
				$this->handle_delete_answer_button($i, $j, $num_answers);
				$mform->addElement('radio', "correct_$i", '', get_string('correct', 'qtype_rtypetask'), $j, array());
			}
			// store the information about how many answers are shown for the next request
			$mform->addElement('hidden', "num_answers_$i", $num_answers);
			$mform->setType("num_answers_$i", PARAM_INT);
			$mform->setConstants(array("num_answers_$i"=>$num_answers));
		}
		// store the information about how many question blocks are shown for the next request
		$mform->addElement('hidden', 'num_questions', $num_questions);
		$mform->setType('num_questions', PARAM_INT);
		$mform->setConstants(array('num_questions'=>$num_questions));
		$mform->registerNoSubmitButton('addquestionbtn');
		$mform->addElement('submit', 'addquestionbtn', get_string('addquestionbtn', 'qtype_rtypetask'));
		$mform->closeHeaderBefore('addquestionbtn');
	}

	protected function append_per_question_header($i, $num_questions) {
		$delbtn = '';
		if($num_questions >= 1) { // 1 question is minimum
			global $OUTPUT;
			$delete_icon = $OUTPUT->pix_url('t/delete');
			//$css = "background:url('$delete_icon') no-repeat top left;padding-left:11px;height:11px;border:0;";
			//$img = '<img src="$delete_icon" />';
			$this->_form->registerNoSubmitButton("delquestionbtn_$i");
			$button = $this->_form->createElement('submit', "delquestionbtn_$i", get_string("delete"));// array('style' => $css));
			$delbtn = $button->toHtml();
		}
		$this->_form->addElement('header', "qheader_$i", get_string('qheader', 'qtype_rtypetask') . " $i " . $delbtn);
	}

	protected function append_per_answer_fields($i, $j, $num_answers) {
		$fields = array();
		$fields[] =& $this->_form->createElement('text', 'answer_'.$i.'_'.$j);
		if($num_answers > 2) { // 2 answers is minimum
			$this->_form->registerNoSubmitButton('delanswerbtn_'.$i.'_'.$j);
			$fields[] =& $this->_form->createElement('submit', 'delanswerbtn_'.$i.'_'.$j, get_string("delete"));
		}
		if($num_answers == $j) {
			$this->_form->registerNoSubmitButton("addanswerbtn_$i");
			$fields[] =& $this->_form->createElement('submit', "addanswerbtn_$i", get_string('add'));
		}
		$this->_form->addGroup($fields, "answergrp$i", get_string('answer', 'question')." $j", array(' '), false);
	}

	protected function handle_delete_answer_button($question_id, $deleted_id, $num_answers) {
		if(!isset($_POST['delanswerbtn_'.$question_id.'_'.$deleted_id])) return;
		// what if the deleted answer was selected to be correct? require to change selection first!
		//if($deleted_id == $_POST["correct_$question_id"]) debugging("complain!");
		for ($i = $question_id, $j = $deleted_id, $jpp = $deleted_id+1; $j <= $num_answers; $j++, $jpp++) {
			$_POST['answer_'.$i.'_'.$j] = $_POST['answer_'.$i.'_'.$jpp];
			//debugging('$_POST[\'answer_\'.$i.\'_\'.$j]: '.$_POST['answer_'.$i.'_'.$j]  .' $_POST[\'answer_\'.$i.\'_\'.$jpp]: '.$_POST['answer_'.$i.'_'.$jpp]);
		}
	}

	protected function handle_delete_question_button($deleted_id, $num_questions) {
		if(!isset($_POST["delquestionbtn_$deleted_id"])) return;
		for ($i = $deleted_id, $ipp=$deleted_id+1; $i <= $num_questions; $i++, $ipp++) {
			$_POST["problem_$i"] = $_POST["problem_$ipp"];
			$_POST["hint_$i"] = $_POST["hint_$ipp"];
			$_POST["num_answers_$i"] = $_POST["num_answers_$ipp"];
			$_POST["correct_$i"] = $_POST["correct_$ipp"];
			for ($j = 1; $j <= 9999; $j++) {
				$key = $i.'_'.$j;
				$newkey = $ipp.'_'.$j;
				if(!isset($_POST["answer_$key"])) break;
				$_POST["answer_$key"] = $_POST["answer_$newkey"];
			}
			//debugging('$i: '.$i.' $ipp: '.$ipp.' $_POST["problem_$ipp"]: '.$_POST["problem_$ipp"]['text']  .' $_POST["problem_$i"]: '.$_POST["problem_$i"]['text']);
		}
	}

	protected function get_num_questions() {
		if(isset($_POST["num_questions"])) {
			if(count(preg_grep('/^delquestionbtn_.*/', array_keys($_POST))) > 0)
				return $_POST["num_questions"] - 1;
			return $_POST["num_questions"];
		}
		if(isset($this->question->num_questions) && $this->question->num_questions >= 2)
			return $this->question->num_questions;
		return 1; // default
	}

	protected function get_num_answers($i) {
		if(isset($_POST["num_answers_$i"])) {
			if(count(preg_grep('/^delanswerbtn_'.$i.'_.*/', array_keys($_POST))) > 0)
				return $_POST["num_answers_$i"] - 1;
			return $_POST["num_answers_$i"];
		}
		if(isset($this->question->{"num_answers_$i"}) && $this->question->{"num_answers_$i"} >= 2)
			return $this->question->{"num_answers_$i"};
		return 2;
	}

	/**
	 * overridden to prevent submission when clicked on delete buttons on several scenarios
	 * -> after certain fields get deleted, their buttons are not registered as "no submit" anymore!
	 *
	 * @see moodleform::no_submit_button_pressed()
	 *
	 */
	public function no_submit_button_pressed() {
		if(count(preg_grep('/^(delquestionbtn_|delanswerbtn_).*/', array_keys($_POST))) > 0)
			return true;
		return parent::no_submit_button_pressed();
	}

	/**
	 * overridden to prevent form submission, if one question has no answer selected to be correct
	 *
	 * @see moodleform::is_validated()
	 */
	 public function validation($fromform, $files) {
		$errors = parent::validation($fromform, $files);
		for ($i = 1; $i <= $this->get_num_questions(); $i++) {
			if(!isset($_POST["correct_$i"]))
				$errors["answergrp$i"] = "You must select an answer to be correct for Question #".$i;
		}
		return $errors;
	}

	protected function data_preprocessing($question) {
		// this method is called after definition_inner(), thus too late for things like counting the number of
		// questions for instance -> take a look at qtype_rtypetask::get_question_options()
		$question = parent::data_preprocessing($question);
		// handle default selection
		for ($i = 1; $i <= $this->get_num_questions(); $i++) {
			if(!isset($_POST["correct_$i"]) && !isset($question->{"correct_$i"}))
				$question->{"correct_$i"} = 1;
		}
		return $question;
	}
}
