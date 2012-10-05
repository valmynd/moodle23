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
 * Question type class for the rtypetask question type.
 *
 * @package    qtype
 * @subpackage rtypetask
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();
require_once($CFG->dirroot . '/question/type/comparetexttask/questiontype.php');

class qtype_rtypetask extends qtype_comparetexttask {
	public function extra_question_fields() {
		return array('question_rtypetask', 'correctorfeedback', 'memento');
	}
	/**
	 * will store input (which can be from a web formular or from an XML import)
	 * @see question_type::save_question_options()
	 */
	public function save_question_options($formdata) {
		//debugging(var_export($formdata));
		// memento will already exist when called from import mechanism
		if(isset($formdata->memento))
			return parent::save_question_options($formdata);
		$dom = new DOMDocument('1.0', 'UTF-8');
		$Memento = $dom->createElement('Memento');
		$mcList = $dom->createElement('mcList');
		$dom->appendChild($Memento);
		$Memento->appendChild($mcList);
		for ($i = 1; $i <= 9999; $i++) {
			if(!isset($formdata->{"problem_$i"})) break;
			$question = $dom->createElement('question');
			$mcList->appendChild($question);
			assert(isset($formdata->{"hint_$i"}));
			// get contents of the editor-fields, including files, e.g. uploaded images
			//$problemCData = $dom->createCDATASection($this->get_value_from_editor_field($formdata, "problem_$i"));
			//$hintCData = $dom->createCDATASection($this->get_value_from_editor_field($formdata, "hint_$i"));
			$problem = $dom->createElement('problem', htmlentities($this->get_value_from_editor_field($formdata, "problem_$i")));
			$hint = $dom->createElement('hint', htmlentities($this->get_value_from_editor_field($formdata, "hint_$i")));
			$question->appendChild($problem);
			$question->appendChild($hint);
			//$problem->appendChild($problemCData);
			//$hint->appendChild($hintCData);
			for ($j = 1; $j <= 9999; $j++) {
				$key = $i.'_'.$j;
				if(!isset($formdata->{"answer_$key"})) break;
				if(empty($formdata->{"answer_$key"})) continue; // validator should make shure, that this is not the answer that is selected
				$answer = $dom->createElement('answer', $formdata->{"answer_$key"});
				if(isset($formdata->{"correct_$i"})) // validator should make that check removable
					if($formdata->{"correct_$i"} == $j)
						$answer->setAttribute('correct', 'true');
				$question->appendChild($answer);
			}
		}
		$formdata->memento = $dom->saveXML();
		return parent::save_question_options($formdata);
	}

	protected function get_value_from_editor_field($formdata, $fieldname) {
		$editor_value = $this->import_or_save_files($formdata->{$fieldname}, $formdata->context, $this->plugin_name(), $fieldname, $formdata->id);
		// see https://github.com/kyro46/elateXam/blob/uni_leipzig/taskmodel/taskmodel-moodleTransformator/src/main/java/de/christophjobst/main/Base64Relocator.java
		// (Java) problem_string = problem_string.replaceAll("@@PLUGINFILE@@/" + fileList.get(i).getName(), "data:image/gif;base64," + fileList.get(i).getValue());
		$fs = get_file_storage();
		$files = $fs->get_area_files($formdata->context->id, $this->plugin_name(), $fieldname, $formdata->id);
		//debugging(var_export($files));
		foreach ($files as $file) {
			$filename = $file->get_filename();
			$base64str = base64_encode($file->get_content());
			$needle = '@@PLUGINFILE@@/' . $filename;
			$replacement = 'data:image/gif;base64,' . $base64str;
			//debugging($filename . "__" . $base64str);
			$editor_value = str_replace($needle, $replacement, $editor_value);
		}
		//$fs->delete_area_files($formdata->context->id, 'question'); // delete all files => this mechaninsm would need to work for all fields!
		//debugging($editor_value);
		return $editor_value;
	}

	public function get_question_options($formdata) {
		if(parent::get_question_options($formdata)) {
			//debugging($formdata->options->memento);
			$dom = new DomDocument();
			$dom->loadXML($formdata->options->memento);
			$XPath = new DOMXPath($dom);
			// "unpack" Memento, see qtype_rtypetask::save_question_options()
			$questions = $XPath->query("//question/*");
			for($i = 0, $j = 1, $q = 0; $q < $questions->length; $q++) {
				$question = $questions->item($q);
				switch($question->nodeName) {
					case 'problem':
						$i++;
						$formdata->{"problem_$i"} = array('text' => $question->nodeValue);
						$formdata->{"num_answers_$i"} = 0;
						$j=1;
						break;
					case 'hint':
						$formdata->{"hint_$i"} = array('text' => $question->nodeValue);
						break;
					case 'answer':
						$key = $i.'_'.$j;
						$formdata->{"answer_$key"} = $question->nodeValue;
						$formdata->{"num_answers_$i"}++;
						if($question->getAttribute('correct') == 'true')
							$formdata->{"correct_$i"} = $j;
						$j++;
						break;
				}
			}
			$formdata->{"num_questions"} = $i;
			return true;
		}
		return false;
	}
}
