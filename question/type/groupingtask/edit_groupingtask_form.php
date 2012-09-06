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
 * Defines the editing form for the groupingtask question type.
 *
 * @package    qtype
 * @subpackage groupingtask
 * @license    http://www.gnu.org/copyleft/gpl.html GNU Public License
 */


defined('MOODLE_INTERNAL') || die();

/**
 * groupingtask editing form definition.
 *
 * @license    http://www.gnu.org/copyleft/gpl.html GNU Public License
*/
class qtype_groupingtask_edit_form extends question_edit_form {
	protected function definition_inner($mform) {
		// this method is called by question_edit_form.definition() 
		global $CFG;
		global $PAGE;
		// a) We need a Corrector Feedback Field for all GroupingTask questions, see question_edit_form.definition()
		$element = $mform->addElement('editor', 'correctorfeedback', "Feedback for the Corrector", array('rows' => 10), $this->editoroptions);
		//$mform->setDefault('correctorfeedback', ...); // doesn't work for "editor" fields (blame moodle for this)
		$element->setValue(array('text'=>$this->get_correctorfeedback())); // see https://github.com/colchambers/moodle/commit/MDL-31726
		$mform->setType('correctorfeedback', PARAM_RAW);

		// b) Java Applet
		$jarfile = "complexTask.jar";
		$jarpath = $CFG->wwwroot . "/question/type/" . $this->qtype() . "/lib/" . $jarfile;
		$innerpath = "com/spiru/dev/groupingTaskProfessor_addon/GroupingTaskAddOnApplet.class"; // TODO: Configurable!
		$appletstr = "\n\n<applet "
				. 'archive="' . $jarpath . '" ' . 'code="'. $innerpath . '" '
				. 'id="appletField"'
				. 'width="600" height="400">\n'
			. '<param name="memento" value="' . $this->get_memento() . '">\n'
			. "</applet>\n\n";

		// Trick to place it at the same position as the <input> elements above it (+ nice label)
		$appletstr = '<div class="fitem fitem_feditor" id="fitem_id_questiontext"><div class="fitemtitle">'
				.'<label for="appletField">Settings for '. "GroupingTask" .'</label></div>'
				.'<div class="felement feditor"><div><div>'.$appletstr.'</div></div></div></div>';

		// Hidden Elements to put in the Applet output via module.js
		$failstr = "Error: Applet Content was not send!"; // result when javascript didn't execute properly 
		$mform->addElement('textarea', 'memento', '', 'style="display:none;"');
		$mform->setDefault('memento', $failstr);

		// Finaly add Applet to form
		$mform->addElement('html', $appletstr);

		// c) Add Module.js
		$PAGE->requires->js("/question/type/" . $this->qtype() . "/jquery-1.8.0.min.js");
		$PAGE->requires->js("/question/type/" . $this->qtype() . "/module.js");
	}

	public function qtype() {
		return 'groupingtask';
	}

	protected function get_correctorfeedback() {
		if (property_exists($this->question, "options")) // when updating
			return $this->question->options->correctorfeedback;
		return ""; // when inserting
	}

	protected function get_memento() {
		if (property_exists($this->question, "options")) // when updating
			return base64_encode($this->question->options->memento);
		return ""; // when inserting
	}
}
