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
 * Question type class for the comparetexttask question type.
 *
 * @package    qtype
 * @subpackage comparetexttask
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * The comparetexttask question type class.
 *
 * @author	C.Wilhelm
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
*/
class qtype_comparetexttask extends question_type {
	/**
	 * this method needs to be overridden in subtypes
	 * @see question_type::extra_question_fields()
	 */
	public function extra_question_fields() {
		return array('question_comparetexttask', 'correctorfeedback', 'memento');
	}

	/**
	 * will store input (which can be from a web formular or from an XML import)
	 * @see question_type::save_question_options()
	 */
	public function save_question_options($formdata) {
		//debugging(var_export($formdata));
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
		//debugging(var_export($data));
		// compare to parent::import_from_xml($data, $question, $format, $extra);
		$question_type = $data['@']['type'];
		if ($question_type != $this->name()) return false;
		$qo = $format->import_headers($data);
		$qo->qtype = $question_type;
		$qo->memento = $format->getpath($data, array('#', 'memento', 0, '#'), '');
		// compare to import_essay() in /question/format/xml/format.php
		// also see here: https://github.com/maths/moodle-qtype_stack/blob/master/questiontype.php
		$qo->correctorfeedback['text'] = $format->getpath($data, array('#', 'correctorfeedback', 0, '#', 'text', 0, '#'), '', true);
		$qo->correctorfeedback['format'] = $format->trans_format($format->getpath($data, array('#', 'correctorfeedback', 0, '@', 'format'), 'moodle_auto_format'));
		$qo->correctorfeedback['files'] = $format->import_files($format->getpath($data, array('#', 'correctorfeedback', 0, '#', 'file'), array(), false));
		return $qo;
	}

	/** Export question to the Moodle XML format */
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
