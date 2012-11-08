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
 * Question type class for the rmatchtask question type.
 *
 * @package    qtype
 * @subpackage rmatchtask
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();
require_once($CFG->dirroot . '/question/type/comparetexttask/questiontype.php');

class qtype_rmatchtask extends qtype_comparetexttask {
	public function extra_question_fields() {
		return array();
	}
	public function get_question_options($question) {
		global $DB;
		parent::get_question_options($question);
		$question->options = $DB->get_record('question_match', array('question' => $question->id));
		$question->options->subquestions = $DB->get_records('question_match_sub',
				array('question' => $question->id), 'id ASC');
		return true;
	}
	public function save_question_options($question) {
		global $DB;
		$context = $question->context;
		$result = new stdClass();

		$oldsubquestions = $DB->get_records('question_match_sub',
				array('question' => $question->id), 'id ASC');

		// $subquestions will be an array with subquestion ids
		$subquestions = array();

		// Insert all the new question+answer pairs
		foreach ($question->subquestions as $key => $questiontext) {
			if ($questiontext['text'] == '' && trim($question->subanswers[$key]) == '') {
				continue;
			}
			if ($questiontext['text'] != '' && trim($question->subanswers[$key]) == '') {
				$result->notice = get_string('nomatchinganswer', 'qtype_match', $questiontext);
			}

			// Update an existing subquestion if possible.
			$subquestion = array_shift($oldsubquestions);
			if (!$subquestion) {
				$subquestion = new stdClass();
				// Determine a unique random code
				$subquestion->code = rand(1, 999999999);
				while ($DB->record_exists('question_match_sub',
						array('code' => $subquestion->code, 'question' => $question->id))) {
					$subquestion->code = rand(1, 999999999);
				}
				$subquestion->question = $question->id;
				$subquestion->questiontext = '';
				$subquestion->answertext = '';
				$subquestion->id = $DB->insert_record('question_match_sub', $subquestion);
			}

			$subquestion->questiontext = $this->import_or_save_files($questiontext,
					$context, 'qtype_match', 'subquestion', $subquestion->id);
			$subquestion->questiontextformat = $questiontext['format'];
			$subquestion->answertext = trim($question->subanswers[$key]);

			$DB->update_record('question_match_sub', $subquestion);

			$subquestions[] = $subquestion->id;
		}

		// Delete old subquestions records
		$fs = get_file_storage();
		foreach ($oldsubquestions as $oldsub) {
			$fs->delete_area_files($context->id, 'qtype_match', 'subquestion', $oldsub->id);
			$DB->delete_records('question_match_sub', array('id' => $oldsub->id));
		}

		// Save the question options.
		$options = $DB->get_record('question_match', array('question' => $question->id));
		if (!$options) {
			$options = new stdClass();
			$options->question = $question->id;
			$options->correctfeedback = '';
			$options->partiallycorrectfeedback = '';
			$options->incorrectfeedback = '';
			$options->id = $DB->insert_record('question_match', $options);
		}

		$options->subquestions = implode(',', $subquestions);
		$options->shuffleanswers = $question->shuffleanswers;
		$options = $this->save_combined_feedback_helper($options, $question, $context, true);
		$DB->update_record('question_match', $options);

		$this->save_hints($question, true);

		if (!empty($result->notice)) {
			return $result;
		}

		if (count($subquestions) < 3) {
			$result->notice = get_string('notenoughanswers', 'question', 3);
			return $result;
		}

		return true;
	}

	public function delete_question($questionid, $contextid) {
		global $DB;
		$DB->delete_records('question_match', array('question' => $questionid));
		$DB->delete_records('question_match_sub', array('question' => $questionid));

		parent::delete_question($questionid, $contextid);
	}
}
