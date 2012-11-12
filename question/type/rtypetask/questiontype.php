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
			$problem = $dom->createElement('problem', $this->get_value_from_editor_field($formdata, "problem_$i"));
			$hint = $dom->createElement('hint', $this->get_value_from_editor_field($formdata, "hint_$i"));
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
						$val = $this->prepare_value_for_editor_field($formdata, "problem_$i", $question->nodeValue);
						$formdata->{"problemtext_$i"} = $val;
						$formdata->{"num_answers_$i"} = 0;
						$j=1;
						break;
					case 'hint':
						$val = $this->prepare_value_for_editor_field($formdata, "hint_$i", $question->nodeValue);
						$formdata->{"hinttext_$i"} = $val;
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

	protected function get_value_from_editor_field($formdata, $fieldname) {
		$editor_value = $this->import_or_save_files($formdata->{$fieldname}, $formdata->context, $this->plugin_name(), $fieldname, $formdata->id);
		return htmlentities($editor_value); // the opposite is html_entity_decode()
	}
	
	protected function prepare_value_for_editor_field($question, $fieldname, $html) {
		global $DB;
		$fs = get_file_storage();
		$num_matches = preg_match_all("/data:image\/([a-z]+);base64,([^\"]+)/", $html, $matches);
		$context = parent::get_context_by_category_id($question->category);
		//debugging("prepare_value_for_editor_field() called" . $contextid);
		for($i = 0; $i < $num_matches; $i++) { // most of the time, nothing will be found
			// $matches[0] is an array of full pattern matches, $matches[1] is an array of strings
			// matched by the first parenthesized subpattern, and so on
			$type = $matches[1][$i];
			$b64s = $matches[2][$i];
			$img = base64_decode($b64s);
			$hash = sha1($img);
			// see if file already exists:
			//debugging($context->id."__ ".$hash);
			// @see http://docs.moodle.org/dev/Using_the_File_API#Moving_files_around
			/*$file_record = array('contextid'=>$context->id, 'component'=>$this->plugin_name(), 'filearea'=>$fieldname,
					'itemid'=>0, 'filepath'=>'/', 'filename'=>"imported_file.$type",
					'timecreated'=>time(), 'timemodified'=>time());
			select * from mdl_files f left join mdl_files_reference r on f.referencefileid = r.id 
				where f.contenthash = '81253d27ecec3b0903fa15cd9b41a00729050fa7' and contextid = 15
					and filearea like 'problem%' */
			$file_records = $DB->get_records_sql("select f.* from {files} f left join {files_reference} r on f.referencefileid = r.id
					where f.contenthash = ? and contextid = ? and filearea = ?;", array($hash, $context->id, $fieldname));
			$file_records = array_values($file_records);
			if(count($file_records)) {
				$first_record = array_pop($file_records);
				$storedfile = $fs->get_file_instance((object)$first_record);
				//$storedfile = $fs->get_file_by_id($first_record->id); // does the exact same as above...
				//$fs->create_file_from_storedfile($file_record, $storedfile); // create another alias?
			} else {
				// the filename can't be preserved, but actually that doesn't matter at all
				$file_record = array('contextid'=>$context->id, 'component'=>$this->plugin_name(), 'filearea'=>$fieldname,
						'itemid'=>0, 'filepath'=>'/', 'filename'=>"imported_file.$type",
						'timecreated'=>time(), 'timemodified'=>time());
				$storedfile = $fs->create_file_from_string($file_record, $img);
			}
			// -> @see https://groups.google.com/d/msg/moodlemayhem/cNjGG3ewLjI/8rzYbV5pUqoJ
			$needle = "data:image/".$type.";base64,".$b64s;
			$replacement = '@@PLUGINFILE@@/' . $storedfile->get_filename();
			$html = str_replace($needle, $replacement, $html);
		}
		return html_entity_decode($html);
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

	/**
	 * XML Import Overridden to prepare images
	 * Problem: When this method is called, ID and ContextID aren't set
	 *
	 * @see question_type::import_from_xml()
	 */
	public function import_from_xml($data, $question, qformat_xml $format, $extra=null) {
		$qo = parent::import_from_xml($data, $question, $format, $extra);
		// reverse the mechanism implemented in export_to_xml()
		// -> files must be assigned to the proper file areas!
		/*$dom = new DomDocument();
		$dom->loadXML($qo->memento);
		$XPath = new DOMXPath($dom);
		$this->extract_images_from_base64($XPath, $qo, "problem");
		$this->extract_images_from_base64($XPath, $qo, "hint");
		$qo->memento = $dom->saveXML();*/
		return $qo;
	}

	/** helper method for export_to_xml */
	protected function convert_images_to_base64(DOMXPath &$XPath, $question, $tagname) {
		$fs = get_file_storage();
		$relevanttags = $XPath->query("//question/$tagname");
		for($i = 1; $i <= $relevanttags->length; $i++) {
			$tag = $relevanttags->item($i-1);
			$html = html_entity_decode($tag->nodeValue);
			$files = $fs->get_area_files($question->contextid, $this->plugin_name(), $tagname.'_'.$i, $question->id);
			foreach ($files as $file) {
				$filename = $file->get_filename();
				if($filename == '.') continue;
				$base64str = base64_encode($file->get_content());
				$needle = '@@PLUGINFILE@@/' . $filename;
				$replacement = 'data:image/gif;base64,' . $base64str;
				$html = str_replace($needle, $replacement, $html);
				//$debug = $filename . "_" . $html;
				$tag->nodeValue = htmlentities($html);
			}
		}
	}

	/* helper method for import_from_xml
	protected function extract_images_from_base64(DOMXPath &$XPath, $question, $tagname) {
		$fs = get_file_storage();
		$relevanttags = $XPath->query("//question/$tagname");
		for($i = 1; $i <= $relevanttags->length; $i++) {
			$tag = $relevanttags->item($i-1);
			$html = html_entity_decode($tag->nodeValue);
			// Pattern pattern = Pattern.compile("data:image/([a-z]+);base64,([^\"]+)");
			// Matcher match = pattern.matcher(orig);
			// while(match.find()) try {
			// 	String type = match.group(1), base64 = match.group(2);
			// 	byte[] img = DatatypeConverter.parseBase64Binary(base64);
			// 	final BufferedImage bufferedImage = ImageIO.read(new ByteArrayInputStream(img));
			// 	String randomname = "/frombase64_" + new BigInteger(130, random).toString(32) + "." + type;
			// 	ImageIO.write(bufferedImage, type, new File(fspath + randomname)); // write to file on filesystem
			// 	ret += orig.substring(lastPos, match.start()) + svpath + randomname; // have a link to that file accessible via web
			// 	lastPos = match.end();
			// }
			// ret += orig.substring(lastPos);
			$num_matches = preg_match_all("/data:image\/([a-z]+);base64,([^\"]+)/", $html, $matches);
			for($i = 0; $i < $num_matches; $i++) {
				// $matches[0] is an array of full pattern matches, $matches[1] is an array of strings
				// matched by the first parenthesized subpattern, and so on
				$type = $matches[1][$i];
				$b64s = $matches[2][$i];
				$img = base64_decode($b64s);
				// @see http://docs.moodle.org/dev/Using_the_File_API#Moving_files_around
				$file_record = array('contextid'=>$question->contextid, 'component'=>$this->plugin_name(), 'filearea'=>$tagname.'_'.$i,
						'itemid'=>$question->id, 'filepath'=>'/', 'filename'=>"imported_file.$type",
						'timecreated'=>time(), 'timemodified'=>time());
				$storedfile = $fs->create_file_from_string($file_record, $img);
				// the filename could't be preserved, but actually that doesn't matter at all (it will compare the hashes)
				// -> @see https://groups.google.com/d/msg/moodlemayhem/cNjGG3ewLjI/8rzYbV5pUqoJ
				$needle = "data:image/".$type.";base64,".$b64s;
				$replacement = '@@PLUGINFILE@@/' . $storedfile->get_filename();
				$html = str_replace($needle, $replacement, $html);
			}
			$tag->nodeValue = htmlentities($html);
		}
	}*/
}