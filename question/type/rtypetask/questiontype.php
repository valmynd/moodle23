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
	public function save_question_options($question) {
		if(isset($question->memento)) {
			// memento will already exist when called from import mechanism
			$dom = new DomDocument();
			$dom->loadXML($question->memento);
			$XPath = new DOMXPath($dom);
			$this->convert_images_from_base64($XPath, $question, "problem");
			$this->convert_images_from_base64($XPath, $question, "hint");
			$question->memento = $dom->saveXML();
		} else {
			// $question could be an object from the client (filled out form) or from the database
			$dom = new DOMDocument('1.0', 'UTF-8');
			$Memento = $dom->createElement('Memento');
			$mcList = $dom->createElement('mcList');
			$dom->appendChild($Memento);
			$Memento->appendChild($mcList);
			for ($i = 1; $i <= 9999; $i++) {
				if(!isset($question->{"problem_$i"})) break;
				$Question = $dom->createElement('question');
				$mcList->appendChild($Question);
				assert(isset($question->{"hint_$i"}));
				// get contents of the editor-fields, including files, e.g. uploaded images
				//$problemCData = $dom->createCDATASection($this->get_value_from_editor_field($question, "problem_$i"));
				//$hintCData = $dom->createCDATASection($this->get_value_from_editor_field($question, "hint_$i"));
				$problemv = $this->import_or_save_files($question->{"problem_$i"}, $question->context, $this->plugin_name(), "problem_$i", $question->id);
				$hintv = $this->import_or_save_files($question->{"hint_$i"}, $question->context, $this->plugin_name(), "hint_$i", $question->id);
				$problem = $dom->createElement('problem');
				$hint = $dom->createElement('hint');
				$problem->appendChild($dom->createTextNode($problemv));
				$hint->appendChild($dom->createTextNode($hintv));
				$Question->appendChild($problem);
				$Question->appendChild($hint);
				//$problem->appendChild($problemCData);
				//$hint->appendChild($hintCData);
				for ($j = 1; $j <= 9999; $j++) {
					$key = $i.'_'.$j;
					if(!isset($question->{"answer_$key"})) break;
					if(empty($question->{"answer_$key"})) continue; // validator should make shure, that this is not the answer that is selected
					$Answer = $dom->createElement('answer', $question->{"answer_$key"});
					if(isset($question->{"correct_$i"})) // validator should make that check removable
						if($question->{"correct_$i"} == $j)
						$Answer->setAttribute('correct', 'true');
					$Question->appendChild($Answer);
				}
			}
			$question->memento = $dom->saveXML();
		}
		// correctorfeedback is handled *traditionally* (import-/export-wise)
		//debugging($question->id);
		//var_export($question->correctorfeedback);
		//$question->correctorfeedback = $this->import_or_save_files($question->correctorfeedback,
		//		$question->context, $this->plugin_name(), 'correctorfeedback', $question->id);
		return parent::save_question_options($question);
	}

	public function get_question_options($question) {
		if(parent::get_question_options($question)) {
			//debugging($question->options->memento);
			$dom = new DomDocument();
			$dom->loadXML($question->options->memento);
			$XPath = new DOMXPath($dom);
			// "unpack" Memento, see qtype_rtypetask::save_question_options()
			$entries = $XPath->query("//question/*");
			for($i = 0, $j = 1, $q = 0; $q < $entries->length; $q++) {
				$entry = $entries->item($q);
				switch($entry->nodeName) {
					case 'problem':
						$i++;
						$question->{"problemtext_$i"} = $entry->nodeValue;
						$question->{"num_answers_$i"} = 0;
						$j=1;
						break;
					case 'hint':
						$question->{"hinttext_$i"} = $entry->nodeValue;
						break;
					case 'answer':
						$key = $i.'_'.$j;
						$question->{"answer_$key"} = $entry->nodeValue;
						$question->{"num_answers_$i"}++;
						if($entry->getAttribute('correct') == 'true')
							$question->{"correct_$i"} = $j;
						$j++;
						break;
				}
				//debugging($i . $entry->nodeName . $entry->nodeValue);
			}
			$question->{"num_questions"} = $i;
			return true;
		}
		return false;
	}

	/**
	 * XML Export Overridden to prepare images
	 * @see question_type::export_to_xml()
	 */
	public function export_to_xml($question, qformat_xml $format, $extra=null) {
		// change memento tag: put all images into base64-strings -> must be reversible
		$dom = new DomDocument();
		$dom->loadXML($question->options->memento);
		$XPath = new DOMXPath($dom);
		$this->convert_images_to_base64($XPath, $question, "problem");
		$this->convert_images_to_base64($XPath, $question, "hint");
		$question->options->memento = $dom->saveXML();
		return parent::export_to_xml($question, $format, $extra);
	}

	/** helper method for export_to_xml */
	protected function convert_images_to_base64(DOMXPath &$XPath, $question, $tagname) {
		$fs = get_file_storage();
		$relevanttags = $XPath->query("//question/$tagname");
		for($i = 1; $i <= $relevanttags->length; $i++) {
			$tag = $relevanttags->item($i-1);
			$files = $fs->get_area_files($question->contextid, $this->plugin_name(), $tagname.'_'.$i, $question->id);
			foreach ($files as $file) {
				$filename = $file->get_filename();
				if($filename == '.') continue;
				$base64str = base64_encode($file->get_content());
				$needle = '@@PLUGINFILE@@/' . $filename;
				$replacement = 'data:image/gif;base64,' . $base64str;
				$tag->nodeValue = str_replace($needle, $replacement, $tag->nodeValue);
			}
		}
	}

	/** XML Import Overridden to prepare images
	 * Problem: When this method is called, ID and ContextID aren't set (!)
	 * -> @see self::save_question_options()
	 */
	public function import_from_xml($data, $question, qformat_xml $format, $extra=null) {
		//$data['#']['answer'] = array(); // some bug:  Undefined index: answer in questiontypebase.php on line 930
		return parent::import_from_xml($data, $question, $format, $extra);
	}
	/** helper method, reverse to @see self::convert_images_to_base64() */
	protected function convert_images_from_base64(DOMXPath &$XPath, $question, $tagname) {
		$fs = get_file_storage();
		$relevanttags = $XPath->query("//question/$tagname");
		for($i = 1; $i <= $relevanttags->length; $i++) {
			$tag = $relevanttags->item($i-1);
			$tag->nodeValue = $this->extract_images_from_base64($question, $tagname.'_'.$i, $tag->nodeValue);
		}
	}
	
	/**
	 * Extract Images from Image-Tags in HTML containing Base64 Strings and
	 * store them in Moodle. They will be named randomly, beginning with "imported_file_*.TYPE".
	 * @see question_type::import_or_save_files() // use this when imported *traditionally*
	 * @see qtype_rtypetask::convert_images_to_base64()
	 * @see qtype_rtypetask::convert_images_from_base64()
	 *
	 * @param object $question
	 * @param string $fieldname
	 * @param string $html
	 * @return string
	 */
	public function extract_images_from_base64($question, $fieldname, $html) {
		global $DB;
		$fs = get_file_storage();
		$num_matches = preg_match_all("/data:image\/([a-z]+);base64,([^\"]+)/", $html, $matches);
		$context = parent::get_context_by_category_id($question->category);
		for($i = 0; $i < $num_matches; $i++) { // most of the time, nothing will be found
			// $matches[0] is an array of full pattern matches, $matches[1] is an array of strings
			// matched by the first parenthesized subpattern, and so on
			$type = $matches[1][$i];
			$b64s = $matches[2][$i];
			$img = base64_decode($b64s);
			$hash = sha1($img);
			/* select * from mdl_files f left join mdl_files_reference r on f.referencefileid = r.id
			 where f.contenthash = '81253d27ecec3b0903fa15cd9b41a00729050fa7' and contextid = 15
			and filearea like 'problem%' */
			$file_records = $DB->get_records_sql("select f.* from {files} f left join {files_reference} r on f.referencefileid = r.id
					where f.contenthash = ? and contextid = ? and filearea = ? and itemid = ?;", array($hash, $context->id, $fieldname, $question->id));
			$file_records = array_values($file_records);
			if(count($file_records)) { // file already exists
				$first_record = array_pop($file_records);
				$storedfile = $fs->get_file_instance((object)$first_record);
				//$storedfile = $fs->get_file_by_id($first_record->id); // does the exact same as above...
				//$fs->create_file_from_storedfile($file_record, $storedfile); // create another alias?
			} else {
				// @see http://docs.moodle.org/dev/Using_the_File_API#Moving_files_around
				// the filename can't be preserved, but actually that doesn't matter at all
				$rnd_filename = 'imported_file_'.mt_rand(1000,9999).'.'.$type;
				//$rnd_filename = 'imported_file_'.$fieldname.'.'.$type;
				$file_record = array('contextid'=>$context->id, 'component'=>$this->plugin_name(), 'filearea'=>$fieldname,
						'itemid'=>$question->id, 'filepath'=>'/', 'filename'=>$rnd_filename,
						'timecreated'=>time(), 'timemodified'=>time());
				$storedfile = $fs->create_file_from_string($file_record, $img);
			}
			// -> @see https://groups.google.com/d/msg/moodlemayhem/cNjGG3ewLjI/8rzYbV5pUqoJ
			$needle = "data:image/".$type.";base64,".$b64s;
			$replacement = '@@PLUGINFILE@@/' . $storedfile->get_filename();
			$html = str_replace($needle, $replacement, $html);
		}
		return $html;
	}

	public function move_files($questionid, $oldcontextid, $newcontextid) {
		global $DB;
		parent::move_files($questionid, $oldcontextid, $newcontextid);
		$extraquestionfields = $this->extra_question_fields();
		$question_extension_table = array_shift($extraquestionfields);
		$memento = $DB->get_field($question_extension_table, 'memento', array($this->questionid_column_name() => $questionid));
		$fs = get_file_storage();
		$dom = new DomDocument();
		$dom->loadXML($memento);
		$XPath = new DOMXPath($dom);
		foreach(array('problem', 'hint') as $tagname) {
			$relevanttags = $XPath->query("//question/$tagname");
			for($i = 1; $i <= $relevanttags->length; $i++) {
				$fs->move_area_files_to_new_context($oldcontextid, $newcontextid,
						$this->plugin_name(), $tagname.'_'.$i, $questionid);
			}
		}
	}
}