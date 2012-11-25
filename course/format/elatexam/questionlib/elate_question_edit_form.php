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
 * A base class for question editing forms.
 *
 * @package    moodlecore
 * @subpackage questiontypes
 * @copyright  2006 The Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU Public License
 */


defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot.'/question/type/edit_question_form.php');

/**
 * Modified Form definition base class for all question used by ElateXam.
 * It must be used in conjunction with elate_questiontype_base class,
 * as we remove thertain fields which result in question_type complaining.
 *
 * @author	C.Wilhelm
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
abstract class elate_question_edit_form extends question_edit_form {

	protected function definition() {
		parent::definition();
		// definition_inner(), which is implemented by subclasses, is called by now
		// -> unwanted fields can now be removed via $mform->removeElement()
		$mform = $this->_form;
		switch($this->qtype()) {
			case 'essay':
				$mform->removeElement('attachments');
				$mform->addElement('hidden', 'attachments', 0);
				break;
			case 'multichoice':
				$mform->removeElement('answernumbering');
				$mform->addElement('hidden', 'answernumbering', 'abc');
				break;
			case 'truefalse':
				$mform->removeElement('feedbacktrue');
				$mform->removeElement('feedbackfalse');
				$mform->addElement('hidden', 'feedbacktrue', '');
				$mform->addElement('hidden', 'feedbacktrue', '');
				// TODO: Abzug für falschen Versuch
				break;
		}
		$mform->getElement('generalfeedback')->setLabel(get_string('generalfeedback', 'format_elatexam'));
	}

	protected function get_per_answer_fields($mform, $label, $gradeoptions, &$repeatedoptions, &$answersoption) {
		$repeated = array();
		$repeated[] = $mform->createElement('header', 'answerhdr', $label);
		// multichoice overrides this method, uses editor fields
		//-> this is only used by shortanswer!
		$repeated[] = $mform->createElement('text', 'answer', get_string('answer', 'question'), array('size' => 80));
		// instead of percentages, we want integers to be entered
		//$repeated[] = $mform->createElement('select', 'fraction', get_string('grade'), $gradeoptions);
		//$repeated[] = $mform->createElement('text', 'fraction', get_string('grade'), array('size' => 3));
		$repeated[] = $mform->createElement('hidden', 'fraction', 1);
		// we don't need the 'feedback' fields
		//$repeated[] = $mform->createElement('editor', 'feedback', get_string('feedback', 'question'), array('rows' => 5), $this->editoroptions);
		$repeated[] = $mform->createElement('hidden', 'feedback', "");
		$repeatedoptions['answer']['type'] = PARAM_RAW;
		$repeatedoptions['fraction']['default'] = 0;
		$answersoption = 'answers';
		return $repeated;
	}

	protected function add_combined_feedback_fields($withshownumpartscorrect = false) {
		$mform = $this->_form;
		// overridden because we don't need them at all
		foreach (array('correctfeedback', 'partiallycorrectfeedback', 'incorrectfeedback') as $fieldname) {
			$mform->addElement('hidden', $fieldname, 0);
			$mform->setType($fieldname, PARAM_RAW);
		}
	}

	protected function add_interactive_settings($withclearwrong = false,
			$withshownumpartscorrect = false) {
		$mform = $this->_form;
		// we want a textfield instead, the rest ain't needed
		$mform->addElement('text', 'penalty', get_string('penaltyforeachincorrecttry', 'format_elatexam'), array('size' => 3));
		$mform->setType('penalty', PARAM_INT);
		$mform->setDefault('penalty', 0);
	}

	protected function data_preprocessing_combined_feedback($question,
			$withshownumcorrect = false) {
		return $question;
	}

	protected function data_preprocessing_hints($question, $withclearwrong = false,
			$withshownumpartscorrect = false) {
		return $question;
	}

	/**
	 * Overridden data_preprocessing_answers() to avoid errors as we removed
	 * any "Feedback" fields from answers
	 *
	 * @see question_edit_form::data_preprocessing_answers()
	 */
	protected function data_preprocessing_answers($question, $withanswerfiles = false) {
		if(!empty($question->options->answers)) {
			$question = parent::data_preprocessing_answers($question, $withanswerfiles);
			$key = 0;
			foreach ($question->options->answers as $answer) {
				$question->feedback[$key] = "";
				$key++;
			}
		}
		return $question;
	}

	/**
	 * Addon types may use the 'Feedback for Corrector' field
	 */
	protected function add_corrector_feedback() {
		// we won't use any applets
		$element = $this->_form->addElement('editor', 'correctorfeedback',
				get_string('correctorfeedback', 'format_elatexam'),
				array('rows' => 10), $this->editoroptions);
		$this->_form->setType('correctorfeedback', PARAM_RAW);
	}
	protected function data_preprocessing_corrector_feedback($question) {
		// @see qtype_essay_edit_form.data_preprocessing()
		$question = parent::data_preprocessing($question);
		if (empty($question->options)) return $question;
		$draftid = file_get_submitted_draft_itemid('correctorfeedback');
		$question->correctorfeedback = array();
		$question->correctorfeedback['text'] = file_prepare_draft_area(
				$draftid,				// draftid
				$this->context->id,		// context
				'qtype_'.$this->qtype(),// component
				'correctorfeedback',	// filarea
				!empty($question->id) ? (int) $question->id : null, // itemid
				$this->fileoptions,		// options
				$question->options->correctorfeedback // text
		);
		$question->correctorfeedback['itemid'] = $draftid;
		return $question;
	}
}

/**
 * Base Class for Addon types using Java Applets
 *
 * @author	C.Wilhelm
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
abstract class elate_applet_question_edit_form extends elate_question_edit_form {

	/**
	 * this method needs to be overridden in subtypes
	 *
	 * @return string containing the path to the *.clss file inside the JAR-file
	 */
	protected abstract function get_innerpath();

	/**
	 * this method may be needed to be overridden
	 * when the name of the JAR-file is not "complexTask.jar"
	 *
	 * @return string containing the name of the JAR-file (which should be inside the ./lib folder)
	*/
	protected abstract function get_jarname();

	protected function definition_inner($mform) {
		// this method is called by question_edit_form.definition()
		global $CFG;
		global $PAGE;
		// a) We need a Corrector Feedback Field for all CompareTextTask questions
		$this->add_corrector_feedback();

		// b) Java Applet
		$jarpath = $CFG->wwwroot . "/question/type/" . $this->qtype() . "/lib/" . $this->get_jarname();
		$appletstr = "\n\n<applet "
				. 'archive="' . $jarpath . '" ' . 'code="'. $this->get_innerpath() . '" '
				. 'id="appletField" '
				. 'width="600" height="400">\n'
				. '<param name="memento" value="' . $this->get_memento() . '">\n'
				. "</applet>\n\n";

		// Trick to place it at the same position as the <input> elements above it (+ nice label)
		$appletstr = '<div class="fitem fitem_feditor" id="fitem_id_questiontext"><div class="fitemtitle">'
				.'<label for="appletField">Settings for '. get_string('pluginname', 'qtype_'.$this->qtype()) .'</label></div>'
				.'<div class="felement feditor"><div><div>'.$appletstr.'</div></div></div></div>';

		// Hidden Elements to put in the Applet output via module.js
		$failstr = "Error: Applet Content was not send!"; // result when javascript didn't execute properly
		$mform->addElement('textarea', 'memento', '', 'style="display:none;"');
		$mform->setDefault('memento', $failstr);

		// Finaly add Applet to form
		$mform->addElement('html', $appletstr);

		// c) Add Module.js
		//$PAGE->requires->js("/course/format/elatexam/questionlib/jquery-1.8.0.min.js"); // now global
		$PAGE->requires->js("/course/format/elatexam/questionlib/questionlib.js");
	}

	protected function data_preprocessing($question) {
		$question = parent::data_preprocessing($question);
		$question = parent::data_preprocessing_corrector_feedback($question);
		return $question;
	}

	protected function get_memento() {
		if (property_exists($this->question, "options")) // when updating
			return base64_encode($this->question->options->memento);
		return ""; // when inserting
	}
}
