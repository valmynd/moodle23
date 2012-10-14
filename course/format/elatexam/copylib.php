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
 * Some hacks to enable copying Questions.
 *
 * @author C.Wilhelm
 * @license	http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * nasty hack that turns every link to an image in a row into a base64 string.
 * may be used to reduce sideffects.
 * problematic if image is very big! browsers may refuse to load it as an image, then!
 *
 * @param stdClass $record a row fetched from a table that belongs to a certain qtype
 * @param stdClass $question a row fetched from the table 'question'
 * @param array $column_names an array containing names of columns that potentially contain image links
 * @return boolean wether an update seems reasonable (that is, if $record was changed)
*/
function question_turn_images_into_base64_strings(&$record, $question, $column_names) {
	return; // DEBUG
	$update_reasonable = false; // whether to overwrite the values of this row, afterwards
	$fs = get_file_storage();
	foreach($column_names as $column_name) {
		if(isset($record->{$column_name})) {
			// @see qtype_rtypetask::get_value_from_editor_field()
			// <img src="@@PLUGINFILE@@/timeline.png" alt="" width="16" height="16">
			// component is sometimes $question->qtype, sometimes just 'question'
			$files1 = $fs->get_area_files($question->contextid, 'question', $column_name);
			$files2 = $fs->get_area_files($question->contextid, 'qtype_'.$question->qtype, $column_name);
			$files = array_merge($files1, $files2);
			//debugging(var_export($question) . " ; " . $question->contextid . " " . $question->qtype . " " . $column_name . " " . $question->id);
			foreach ($files as $file) {
				$filename = $file->get_filename();
				if($filename == '.') continue;
				$base64str = base64_encode($file->get_content());
				$needle = '@@PLUGINFILE@@/' . $filename;
				$replacement = 'data:image/gif;base64,' . $base64str;
				$record->{$column_name} = str_replace($needle, $replacement, $record->{$column_name});
				$update_reasonable = true;
			}
			// we need workarounds for some fields, especially there a problem with question_answers rows
			preg_match('/@@PLUGINFILE@@\/[^\"]*/', $record->{$column_name}, $matches);
			if($matches) { // if no occurrences of @@PLUGINFILE@@ were found, everything is fine
				$files_by_name = array(); // mapping of file names to file objects
				// i) in rows related to question_answers, some editor fields' files are related to 'answerfeedback'
				$files = $fs->get_area_files($question->contextid, 'question', 'answerfeedback');
				if($question->qtype === 'match') {
					// ii) qtype_match uses an awkyard 'subquestion' editor field
					$files = array_merge($files, $fs->get_area_files($question->contextid, 'qtype_'.$question->qtype, 'subquestion'));
				}
				foreach ($files as $file) $files_by_name[$file->get_filename()] = $file;
				foreach($matches as $match) {
					// <img src="@@PLUGINFILE@@/tumblr_lyzfata2KS1qbdwe9o1_500.jpg"
					preg_match('/[^@@PLUGINFILE@@\/].*/', $match, $filenames);
					foreach($filenames as $linked_filename) {
						if(!isset($files_by_name[$linked_filename])) debugging("NOT FOUND: $linked_filename IN ". var_export(array_keys($files_by_name)));
						$base64str = base64_encode($files_by_name[$linked_filename]->get_content());
						$needle = '@@PLUGINFILE@@/' . $linked_filename;
						$replacement = 'data:image/gif;base64,' . $base64str;
						$record->{$column_name} = str_replace($needle, $replacement, $record->{$column_name});
						$update_reasonable = true;
					}
				}
			}
		}
	}
	return $update_reasonable;
}

/**
 * get Tables for a certain qtype and dublicate the relevant rows in them
 *
 * @param string $path_to_xmlfile path to the install.xml file that belongs to the question type, potentially containing table definitions
 * @param stdClass $question the row that is fetched from the questions table (see usage in question_copy_questions_to_category())
 * @param int $id_of_dublicate the value of the primary key, that the new row should point at when this function is done
 * @param array $answer_id_mapping an assoziative array, needed for a workaround only relevant for the truefalse question type
 */
function question_copy_dependant_qtype_rows($path_to_xmlfile, $question, $id_of_dublicate, $answer_id_mapping) {
	global $DB;
	if (file_exists($path_to_xmlfile)) try {
		// gather information on the table we want to copy from
		$dom = new DomDocument();
		$dom->load($path_to_xmlfile);
		$XPath = new DOMXPath($dom);
		$elements = $dom->getElementsByTagName("TABLE");
		foreach($elements as $el) {
			$tablename = $el->getAttribute("NAME");
			$nodes = $XPath->query("//TABLE[@NAME='$tablename']/KEYS/KEY[@TYPE='primary']/@FIELDS"); // [@REFTABLE='question']
			$primarykey_name = $nodes->item(0)->value;
			$nodes = $XPath->query("//TABLE[@NAME='$tablename']/KEYS/KEY[contains(@TYPE,'foreign')]/@FIELDS");
			$foreignkey_name = $nodes->item(0)->value;
			$potential_editorfields = array(); // store columns that may contain image tags
			foreach($XPath->query("//FIELD[@TYPE='text']/@NAME") as $node) array_push($potential_editorfields, $node->value);
			// now we can use this information to dublicate this subtable
			//debugging("trying to copy: " . $tablename . " , " . $foreignkey_name . " , " . $primarykey_name);
			$records = $DB->get_records($tablename, array($foreignkey_name => $question->id));
			foreach($records as $existing) {
				question_turn_images_into_base64_strings($existing, $question, $potential_editorfields);
				if($question->qtype === 'truefalse') {
					// special treatment is needed for truefalse: has foreign keys on question_answers (see it's install.xml)
					$existing->trueanswer = $answer_id_mapping[$existing->trueanswer];
					$existing->falseanswer = $answer_id_mapping[$existing->falseanswer];
				}
				unset($existing->{$primarykey_name});
				$existing->{$foreignkey_name} = $id_of_dublicate;
				$id_of_subsequent_dublicate = $DB->insert_record($tablename, $existing);
			}
		}
	} catch(Exception $e) {
		echo "WARNING: ", $e->getMessage(), "<br/>\n";
	}
}

/**
 * @see question_move_questions_to_category (questionlib.php)
 *
 * @param array $questionids of question ids.
 * @param integer $newcategoryid the id of the category to move to.
 */
function question_copy_questions_to_category_old($questionids, $newcategoryid) {
	global $DB, $CFG;
	$newcontextid = $DB->get_field('question_categories', 'contextid', array('id' => $newcategoryid)); // this line is from question_move_questions_to_category()
	list($questionidcondition, $params) = $DB->get_in_or_equal($questionids);
	$questions = $DB->get_records_sql("
			SELECT q.id, q.qtype, qc.contextid
			FROM {question} q
			JOIN {question_categories} qc ON q.category = qc.id
			WHERE  q.id $questionidcondition", $params);
	foreach ($questions as $question) {
		// a) Table 'question'
		$existing = $DB->get_record('question', array('id' => $question->id));
		question_turn_images_into_base64_strings($existing, $question, array('questiontext', 'generalfeedback'));
		unset($existing->id);
		$existing->category = $newcategoryid;
		$id_of_dublicate = $DB->insert_record('question', $existing);
		// b) dublicate rows in affected tables that are part of moodle23/lib/db/install.xml: Tags, Answers, Hints
		// because of truefalse's behaviour, we need to do this before c) as we need the PKs of the copied question_answer entries
		$records = $DB->get_records('tag_instance', array('itemtype' => 'question', 'itemid' => $question->id));
		foreach($records as $existing) {
			unset($existing->id);
			$existing->itemid = $id_of_dublicate;
			$id_of_taginstance_dublicate = $DB->insert_record('tag_instance', $existing);
		}
		$records = $DB->get_records('question_answers', array('question' => $question->id)); // question_answers holds metadata relevant for us
		$answer_id_mapping = array(); // workaround for truefalse: we map the old ids to the new ones
		foreach($records as $existing) { // question_answers stores POSSIBLE answers for the definition of multiplechoice questions
			// note there is a notorious 'answerfeedback', that is not mentioned in install.xml
			question_turn_images_into_base64_strings($existing, $question, array('answer', 'feedback'));
			$old_answer_id = $existing->id;
			unset($existing->id);
			$existing->question = $id_of_dublicate;
			$answer_id_mapping[$old_answer_id] = $DB->insert_record('question_answers', $existing);
		}
		$records = $DB->get_records('question_hints', array('questionid' => $question->id));
		foreach($records as $existing) {
			question_turn_images_into_base64_strings($existing, $question, array('hint',));
			unset($existing->id);
			$existing->questionid = $id_of_dublicate;
			$id_of_subsequent_dublicate = $DB->insert_record('question_hints', $existing);
		}
		// c) get Tables for this qtype and dublicate the relevant rows in them
		$xml_path = $CFG->dirroot . '/question/type/' . $question->qtype . '/db/install.xml';
		question_copy_dependant_qtype_rows($xml_path, $question, $id_of_dublicate, $answer_id_mapping);
		// d) dublicate all files, see file_storage::move_area_files_to_new_context() (minus delete)
		$fs = get_file_storage();
		$oldcontextid = $question->contextid;
		//debugging("OLD: " . $question->contextid . " NEW: " . $newcontextid);
		if ($newcontextid != $oldcontextid) { // see d)
			//question_bank::get_qtype($question->qtype)->move_files($question->id, $question->contextid, $newcontextid);
			$sql = "SELECT * FROM {files} f LEFT JOIN {files_reference} r ON f.referencefileid = r.id
					WHERE f.contextid = :contextid AND f.component = 'question'";
			$oldfiles = array();
			$filerecords = $DB->get_records_sql($sql, array('contextid'=>$oldcontextid));
			foreach ($filerecords as $filerecord)
				if ($filerecord->filename !== '.')
				$oldfiles[$filerecord->pathnamehash] = $this->get_file_instance($filerecord);
			//debugging($oldcontextid . "__" . $newcontextid);
			foreach ($oldfiles as $oldfile) {
				$filerecord = new stdClass();
				$filerecord->contextid = $newcontextid;
				$fs->create_file_from_storedfile($filerecord, $oldfile);
			}
		}
		// /var/moodledata/filedir/41/82/4182a9cbaaa591b733ec62e2f72878c4f696247a
		// $CFG->dataroot.'/filedir
	}
	return true;
}

/****************
 * NEW APPROACH *
****************/

require_once($CFG->dirroot . '/lib/questionlib.php');
require_once($CFG->dirroot . '/question/format/xml/format.php');

class qformat_xml_hack extends qformat_xml {
	/*public function readquestions($lines) { // in original, this is protected
		return parent::readquestions($lines);
	}*/
	private $_xml = null;
	/**
	 * called by importprocess() to get data
	 * @see qformat_default::readdata()
	 */
	protected function readdata($filename) {
		return explode("\n", $this->_xml);
	}
	/**
	 * needing to override this is VERY unfortunate, as the only thing that is problematic in the
	 * original is the get_class() call -> thus we couldn't do this subclass without basically
	 * copy & paste this method (maybe report this as bug to moodle?)
	 *
	 * @see qformat_default::try_importing_using_qtypes()
	 */
	public function try_importing_using_qtypes($data, $question = null, $extra = null, $qtypehint = '') {
		$methodname = "import_from_xml";
		if (!empty($qtypehint)) {
			$qtype = question_bank::get_qtype($qtypehint, false);
			if (is_object($qtype) && method_exists($qtype, $methodname)) {
				$question = $qtype->$methodname($data, $question, $this, $extra);
				if ($question) {
					return $question;
				}
			}
		}
		foreach (question_bank::get_all_qtypes() as $qtype) {
			if (method_exists($qtype, $methodname)) {
				if ($question = $qtype->$methodname($data, $question, $this, $extra)) {
					return $question;
				}
			}
		}
		return false;
	}
	/**
	 * takes a string containing a question in XML and
	 * imports it to the database. it will be located
	 * where $categoryid points to.
	 *
	 * @param string $xmlstr
	 * @param int $categoryid
	 * @return boolean $success
	 */
	public function import_question_to_category($xmlstr, $categoryid) {
		global $DB;
		$this->_xml = $xmlstr; // will be used by readdata()
		// the next three lines were taken from /question/import.php
		$category = $DB->get_record("question_categories", array('id' => $categoryid));
		$categorycontext = get_context_instance_by_id($category->contextid);
		$category->context = $categorycontext;
		$this->setCategory($category);
		return $this->importprocess($category);
	}
}

/*
$xml = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n<quiz>\n" . $xml . "\n</quiz>";
$lines = explode("\n", $xml); // readquestions expects "lines"
$method = new ReflectionMethod('qformat_xml', 'readquestions');
$method->setAccessible(true);
$newquestions = $method->invoke($formatcls, $lines);
debugging(var_export($newquestions) . "\n<br><br>\n");
*/

/**
 * @see question_move_questions_to_category (questionlib.php)
 *
 * @param array $questionids of question ids.
 * @param integer $newcategoryid the id of the category to move to.
 */
function question_copy_questions_to_category($questionids, $newcategoryid) {
	$questions = question_load_questions($questionids);
	$exporter = new qformat_xml();
	$importer = new qformat_xml_hack();
	foreach ($questions as $question) {
		// 1) export the modified question into an XML string
		$success = get_question_options($question); // TODO: throw exception when false
		$xml = $exporter->writequestion($question);
		// 2) re-import (thus copy it) and retrieve the id of the new question
		$xml = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n<quiz>\n" . $xml . "\n</quiz>";
		$newquestions = $importer->import_question_to_category($xml, $newcategoryid);
		//debugging(var_export($newquestions) . "\n<br><br>\n");
	}
	return true;
}
