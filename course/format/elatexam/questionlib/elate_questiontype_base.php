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
 * Question-Type base class that can be used by ElateXam Addon-Question-Types
 *
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();
require_once($CFG->dirroot.'/question/type/questiontypebase.php');

/**
 * Modified Question-Type base class for all question used by ElateXam.
 *
 * @author	C.Wilhelm
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
*/

class elate_questiontype_base extends question_type {

	public function save_question($question, $form) {
		// parent::save_question() will call save_question_options(),
		// making this a good place to prepare some variables
		return parent::save_question($question, $form);
	}

	protected function initialise_combined_feedback(question_definition $question, $questiondata, $withparts = false) {
		// @see parent::initialise_combined_feedback($question, $questiondata);
		foreach(array('correctfeedback','partiallycorrectfeedback','incorrectfeedback') as $fieldname)
			$question->{$fieldname} = "";
	}

	/*protected function save_combined_feedback_helper($options, $formdata, $context, $withparts = false) {
		// @see question_type::save_combined_feedback_helper()
		foreach(array('correctfeedback','partiallycorrectfeedback','incorrectfeedback') as $fieldname) {
			$options->{$fieldname} = '';
			$options->{$fieldname.'format'} = '';
		}
	}*/

	protected function import_or_save_files($field, $context, $component, $filearea, $itemid) {
		// overridden because of missing exception handling in question_type::import_or_save_files()
		if(!in_array($filearea, array('answerfeedback','correctfeedback','partiallycorrectfeedback','incorrectfeedback'))) try {
			return parent::import_or_save_files($field, $context, $component, $filearea, $itemid);
		} catch(Exception $e) {
			debugging("problematic: " . $filearea);
			//debug_print_backtrace();
		}
		return "";
	}

	/**
	 * if questiontype is one of multianswer/multichoice/essay,
	 * the question object needs to get finalized here
	 *
	 * IMPORTANT: "$question" parameter is (re-)created in
	 * qformat_default::try_importing_using_qtypes, other than it's
	 * documentation suggests! thus we use the "$extra" parameter.
	 *
	 * @see qformat_xml::readquestions() in /question/format/xml/format.php
	 * @see question_type::import_from_xml()
	 */
	public function import_from_xml($data, $question, qformat_xml $format, $extra=null) {
		$qtype = $data['@']['type'];
		if($qtype == 'multianswer' || $qtype == 'multichoice' || $qtype == 'essay') {
			// called from qformat_xml::readquestions(), you shall have a look on what we've modified there
			$extraquestionfields = $this->extra_question_fields();
			array_shift($extraquestionfields);
			foreach ($extraquestionfields as $field) {
				$default = 0; // currently we have only numbers, TODO: lookup defaults in global assoziative array?
				$extra->$field = $format->getpath($data, array('#', $field, 0, '#'), $default);
			}
			return $extra;
		}
		return parent::import_from_xml($data, $question, $format, $extra);
	}

	/**
	 * we modified qformat_xml::writequestion() to be able to export
	 * additional attributes for multianswer/multichoice/essay
	 *
	 * @see question_type::export_to_xml()
	 */
	public function export_to_xml($question, qformat_xml $format, $extra='') {
		$qtype = $question->qtype;
		if($qtype == 'multianswer' || $qtype == 'multichoice' || $qtype == 'essay') {
			// @see parent::export_to_xml($question, $format, $extra);
			// @see writequestion() in /question/format/xml/format.php
			$extraquestionfields = $this->extra_question_fields();
			array_shift($extraquestionfields);
			foreach ($extraquestionfields as $field) {
				$exportedvalue = $format->xml_escape($question->options->$field);
				$extra .= "    <$field>{$exportedvalue}</$field>\n";
			}
			return $extra;
		}
		return parent::export_to_xml($question, $format, $extra);
	}
}

/**
 * Question-Type base class that can be used by ElateXam Addon-Question-Types
 *
 * @author	C.Wilhelm
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class elate_addon_questiontype_base extends question_type {
	/* extra_question_fields() should be overridden in subtypes
	 * @see question_type::extra_question_fields() */

	/**
	 * will store input (which can be from a web formular or from an XML import)
	 * @see question_type::save_question_options()
	 */
	public function save_question_options($formdata) {
		// database (and exported XML) should contain readable xml, no base64 encoded things
		if(substr($formdata->memento, 0, 5) !== "<?xml") // won't be base64-encoded on import!
			$formdata->memento = base64_decode($formdata->memento);
		// "editor" fields need extra treatment in moodle formslib + they cause problems on import!
		$formdata->correctorfeedback = $this->import_or_save_files($formdata->correctorfeedback,
				$formdata->context, $this->plugin_name(), 'correctorfeedback', $formdata->id);
		return parent::save_question_options($formdata);
	}

	////// IMPORT/EXPORT FUNCTIONS /////////////////

	/** Imports question from the Moodle XML format */
	public function import_from_xml($data, $question, qformat_xml $format, $extra=null) {
		// compare to parent::import_from_xml($data, $question, $format, $extra);
		$qtype = $data['@']['type'];
		if ($qtype != $this->name()) return false;
		$qo = $format->import_headers($data);
		$qo->qtype = $qtype;
		$qo->memento = $format->getpath($data, array('#', 'memento', 0, '#'), '');
		// compare to qformat_xml::import_essay() in /question/format/xml/format.php
		// also see here: https://github.com/maths/moodle-qtype_stack/blob/master/questiontype.php
		$qo->correctorfeedback['text'] = $format->getpath($data, array('#', 'correctorfeedback', 0, '#', 'text', 0, '#'), '', true);
		$qo->correctorfeedback['format'] = $format->trans_format($format->getpath($data, array('#', 'correctorfeedback', 0, '@', 'format'), 'moodle_auto_format'));
		$qo->correctorfeedback['files'] = $format->import_files($format->getpath($data, array('#', 'correctorfeedback', 0, '#', 'file'), array(), false));
		return $qo;
	}

	/** Exports question to the Moodle XML format */
	public function export_to_xml($question, qformat_xml $format, $extra=null) {
		// compare to parent::export_to_xml($question, $format, $extra);
		$expout = "    <memento>{$format->xml_escape($question->options->memento)}</memento>";
		$fs = get_file_storage();
		// compare to writequestion() in /question/format/xml/format.php (lousy documentation for new question types :()
		$expout .= "\n    <correctorfeedback format=\"html\">\n";
		$expout .= $format->writetext($question->options->correctorfeedback, 3) . "      ";
		$expout .= $format->write_files($fs->get_area_files($question->contextid, $this->plugin_name(), 'correctorfeedback', $question->id));
		$expout .= "\n    </correctorfeedback>\n";
		return $expout;
	}

	public function move_files($questionid, $oldcontextid, $newcontextid) {
		parent::move_files($questionid, $oldcontextid, $newcontextid);
		$fs = get_file_storage();
		$fs->move_area_files_to_new_context($oldcontextid, $newcontextid, $this->plugin_name(), 'correctorfeedback', $questionid);
	}

	////// the following is borrowed from qtype_description -> compare to original when upgrading moodle! //////////
	public function is_real_question_type() {
		return false;
	}
	public function is_usable_by_random() {
		return false;
	}
	public function can_analyse_responses() {
		return false;
	}
	public function actual_number_of_questions($question) {
		return 0;
	}
	public function get_random_guess_score($questiondata) {
		return null;
	}
}
