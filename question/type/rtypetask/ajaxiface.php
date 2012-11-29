<?php
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

//define('MOODLE_INTERNAL', true);
require_once(dirname(__FILE__) . '/../../../config.php');
require_once($CFG->dirroot . '/question/type/questiontypebase.php');
require_once($CFG->dirroot . '/question/type/edit_question_form.php');
require_once($CFG->dirroot . '/question/type/rtypetask/questiontype.php');
require_once($CFG->dirroot . '/question/type/rtypetask/edit_rtypetask_form.php');

// TestURL: http://localhost/moodle23/question/type/rtypetask/ajaxiface.php?json_request_for_subquestion=1
$qid = optional_param('id', null, PARAM_INT);
$courseid = optional_param('courseid', 1, PARAM_INT);
$categoryid = optional_param('category', 0, PARAM_INT);
$num_questions = optional_param('num_questions', 1, PARAM_INT);
$currentsubquestion = optional_param('json_request_for_subquestion', 1, PARAM_INT);
require_login($courseid, false); // does initialize global variable $PAGE

// instantiate question object, @see /question/question.php
$rtypeobj = new qtype_rtypetask();
if($qid) {
	$question = $DB->get_record('question', array('id' => $qid));
	$rtypeobj->get_question_options($question);
} else {
	$question = new stdClass();
	$question->category = $categoryid;
	$question->qtype = $rtypeobj->name();
	$question->createdby = $USER->id;
}
//var_export($question);

// instantiate category object, @see /question/question.php
$category = $DB->get_record('question_categories', array('id' => $question->category));
$categorycontext = get_context_instance_by_id($category->contextid);

// handle permissions, exit if editing is not permitted
$question->formoptions = new stdClass();
$question->formoptions->canedit = question_has_capability_on($question, 'edit');
$question->formoptions->canmove = false; // ignore the rest...
$question->formoptions->cansaveasnew = false;
$question->formoptions->repeatelements = false;
$question->formoptions->movecontext = false;
//if(!$question->formoptions->canedit) die();
require_capability('moodle/question:add', $categorycontext);

// instantiate forms object, @see /question/question.php
$coursecontext = get_context_instance(CONTEXT_COURSE, $courseid);
$contexts = new question_edit_contexts($coursecontext);
$formobj = new qtype_rtypetask_edit_form("", $question, $category, $contexts);

// add only those fields we need (in this case, one answers block)
echo $formobj->get_answer_definition_html($currentsubquestion);

// that's it