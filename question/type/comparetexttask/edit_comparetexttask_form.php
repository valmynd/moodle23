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
		global $CFG;
		global $PAGE;
		// Hier Formularfelder definieren
		// a) Java Applet
		$jarfile = "compareTextTask.jar";
		$path = "com/spiru/dev/compareTextTask_addon/CompareTextApplet.class";
		$appletstr = "<applet archive=\"" . $CFG->wwwroot . "/question/type/" . $this->qtype() . "/lib/" . $jarfile . "\" "
			. "code=\"". $path . "\" "
			. "width=\"710\" height=\"540\">\n"
			. "<param name=\"initialText\" value=\"" . '' . "\">\n"
			. "<param name=\"xmlDef\" value=\"" . '' . "\">\n"
			. "</applet>\n";
		$mform->addElement('html', $appletstr);
		// b) Javascript to get Data from the Applet
		$PAGE->requires->js("/question/type/" . $this->qtype() . "/jquery-1.8.0.min.js");
		$PAGE->requires->js("/question/type/" . $this->qtype() . "/module.js");

		// c) Testing stuff
		$mform->addElement('editor', 'fieldname', "hooray");
		$mform->setType('fieldname', PARAM_RAW);
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
