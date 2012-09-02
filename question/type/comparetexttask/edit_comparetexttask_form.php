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
		// Hier werden Formularfelder definiert
		global $CFG;
		global $PAGE;

		// a) Java Applet
		$jarfile = "compareTextTask.jar";
		$jarpath = $CFG->wwwroot . "/question/type/" . $this->qtype() . "/lib/" . $jarfile;
		$innerpath = "com/spiru/dev/compareTextTask_addon/CompareTextApplet.class";

		$mform->addElement('textarea', 'applet_result', '', 'style="display:none;"'); // initial value should be: "Error: Applet Content was not send!"
		$appletstr = "\n\n<applet "
				. 'archive="' . $jarpath . '" ' . 'code="'. $innerpath . '" '
				. 'id="appletField" '
				. 'width="710" height="440">\n'
			. '<param name="initialText" value="' . $this->get_initial_text() . '">\n'
			. '<param name="xmlDef" value="' . '' . '">\n'
			. "</applet>\n\n";
		$mform->addElement('html', $appletstr);

		// b) Javascript to get Data from the Applet
		$PAGE->requires->js("/question/type/" . $this->qtype() . "/jquery-1.8.0.min.js");
		$PAGE->requires->js("/question/type/" . $this->qtype() . "/module.js");

		// c) Playground for other stuff
		//$mform->addElement('editor', 'fieldname', "hooray");
		//$mform->setType('fieldname', PARAM_RAW);
	}

	public function qtype() {
		return 'comparetexttask';
	}

	protected function get_initial_text() {
		//debugging("get_initial_text(): §question:".var_export($this->question));
		if (property_exists($this->question, "options")) // when updating
			return $this->question->options->initialtext;
		return ""; // when inserting
	}

	protected function get_xml_tags() {
		return "<some><xml></xml></some>";
	}
}
