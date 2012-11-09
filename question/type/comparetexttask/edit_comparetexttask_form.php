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
 * @author	C.Wilhelm
 * @license	http://www.gnu.org/copyleft/gpl.html GNU Public License
*/
class qtype_comparetexttask_edit_form extends question_edit_form {

	/**
	 * this method needs to be overridden in subtypes
	 *
	 * @see question_edit_form::qtype()
	 */
	public function qtype() {
		return 'comparetexttask';
	}

	/**
	 * this method needs to be overridden in subtypes
	 *
	 * @return string containing the path to the *.clss file inside the JAR-file
	 */
	protected function get_innerpath() {
		return "com/spiru/dev/compareTextTask_addon/CompareTextProfessorApplet.class";
	}

	/**
	 * this method may be needed to be overridden
	 * when the name of the JAR-file is not "complexTask.jar"
	 *
	 * @return string containing the name of the JAR-file (which should be inside the ./lib folder)
	 */
	protected function get_jarname() {
		return "complexTask.jar";
	}

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
		//$PAGE->requires->js("/question/type/comparetexttask/jquery-1.8.0.min.js"); // now global
		$PAGE->requires->js("/question/type/comparetexttask/module.js");
	}

	protected function add_corrector_feedback() {
		// we won't use any applets
		$element = $this->_form->addElement('editor', 'correctorfeedback',
				get_string('correctorfeedback', 'qtype_comparetexttask'),
				array('rows' => 10), $this->editoroptions);
		$this->_form->setType('correctorfeedback', PARAM_RAW);
	}

	protected function data_preprocessing($question) {
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

	protected function get_memento() {
		if (property_exists($this->question, "options")) // when updating
			return base64_encode($this->question->options->memento);
		return ""; // when inserting
	}
}
