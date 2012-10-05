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
	$update_reasonable = false; // whether to overwrite the values of this row, afterwards
	$fs = get_file_storage();
	foreach($column_names as $column_name) {
		if(isset($record->{$column_name})) {
			// @see qtype_rtypetask::get_value_from_editor_field()
			// <img src="@@PLUGINFILE@@/timeline.png" alt="" width="16" height="16">
			// component is sometimes $question->qtype, sometimes just 'question'
			$files1 = $fs->get_area_files($question->contextid, 'question', $column_name, $question->id);
			$files2 = $fs->get_area_files($question->contextid, 'qtype_'.$question->qtype, $column_name, $question->id);
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
			//debugging($record->{$column_name});
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
function question_copy_questions_to_category($questionids, $newcategoryid) {
	global $DB, $CFG;
	// TODO: // Lösungshinweis für den Studenten
	$newcontextid = $DB->get_field('question_categories', 'contextid', array('id' => $newcategoryid));
	list($questionidcondition, $params) = $DB->get_in_or_equal($questionids);
	$questions = $DB->get_records_sql("
			SELECT q.id, q.qtype, qc.contextid
			FROM {question} q
			JOIN {question_categories} qc ON q.category = qc.id
			WHERE  q.id $questionidcondition", $params);
	foreach ($questions as $question) {
		//if ($newcontextid != $question->contextid) // see d)
		//question_bank::get_qtype($question->qtype)->move_files($question->id, $question->contextid, $newcontextid);
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
		// some of this is now handled above, i'll leave that commented out for reference
		/*$oldcontextid = $question->contextid;
		$oldfiles = $fs->get_area_files($oldcontextid, 'question', false, false, 'id', false);
		$sql = "SELECT * FROM {files} f LEFT JOIN {files_reference} r ON f.referencefileid = r.id
		WHERE f.contextid = :contextid AND f.component = 'question'";
		$oldfiles = array();
		$filerecords = $DB->get_records_sql($sql, array('contextid'=>$oldcontextid));
		foreach ($filerecords as $filerecord)
			if ($filerecord->filename !== '.')
			$oldfiles[$filerecord->pathnamehash] = $this->get_file_instance($filerecord);
		debugging($oldcontextid . "__" . $newcontextid);
		foreach ($oldfiles as $oldfile) {
		$filerecord = new stdClass();
		$filerecord->contextid = $newcontextid;
		$fs->create_file_from_storedfile($filerecord, $oldfile);
		}*/
		// /var/moodledata/filedir/41/82/4182a9cbaaa591b733ec62e2f72878c4f696247a
		// $CFG->dataroot.'/filedir
	}
	return true;
}