<?php

require_once($CFG->dirroot . '/question/editlib.php');
require_once($CFG->dirroot . '/course/format/elatexam/elate_question_bank.php');

/**
 * @see question_edit_setup() in editlib.php
 * modified to enable our exams bank to be showed directly from the course page
 * 
 * Common setup for all pages for editing questions.
 * @param string $baseurl the name of the script calling this funciton. For examle 'qusetion/edit.php'.
 * @param string $edittab code for this edit tab
 * @param bool $requirecmid require cmid? default false
 * @param bool $requirecourseid require courseid, if cmid is not given? default true
 * @return array $thispageurl, $contexts, $cmid, $cm, $module, $pagevars
 */
function exam_bank_setup($edittab, $baseurl, $requirecmid = false, $requirecourseid = true) {
	global $DB, $PAGE;
	$thispageurl = new moodle_url($baseurl);
	$thispageurl->remove_all_params(); // We are going to explicity add back everything important - this avoids unwanted params from being retained.
	$courseid  = required_param('id', PARAM_INT);
	$thiscontext = get_context_instance(CONTEXT_COURSE, $courseid);
	$contexts = new question_edit_contexts($thiscontext);
	$contexts->require_one_edit_tab_cap($edittab);
	$module = null;
	$cmid = 0;
	$cm = null;

	// THE REST WAS NOT MODIFIED (except for whitespaces) -> compare to question_edit_setup() when updating moodle!
	$PAGE->set_pagelayout('admin');
	$pagevars['qpage'] = optional_param('qpage', -1, PARAM_INT);
	//pass 'cat' from page to page and when 'category' comes from a drop down menu
	//then we also reset the qpage so we go to page 1 of a new cat.
	$pagevars['cat'] = optional_param('cat', 0, PARAM_SEQUENCE); // if empty will be set up later
	if ($category = optional_param('category', 0, PARAM_SEQUENCE)) {
		if ($pagevars['cat'] != $category) { // is this a move to a new category?
			$pagevars['cat'] = $category;
			$pagevars['qpage'] = 0;
		}
	}
	if ($pagevars['cat'])
		$thispageurl->param('cat', $pagevars['cat']);
	if ($pagevars['qpage'] > -1)
		$thispageurl->param('qpage', $pagevars['qpage']);
	else
		$pagevars['qpage'] = 0;
	$pagevars['qperpage'] = question_get_display_preference('qperpage', DEFAULT_QUESTIONS_PER_PAGE, PARAM_INT, $thispageurl);
	for ($i = 1; $i <= question_bank_view::MAX_SORTS; $i++) {
		$param = 'qbs' . $i;
		if (!$sort = optional_param($param, '', PARAM_ALPHAEXT)) {
			break;
		}
		$thispageurl->param($param, $sort);
	}
	$defaultcategory = question_make_default_categories($contexts->all());
	$contextlistarr = array();
	foreach ($contexts->having_one_edit_tab_cap($edittab) as $context)
		$contextlistarr[] = "'$context->id'";
	$contextlist = join($contextlistarr, ' ,');
	if (!empty($pagevars['cat'])){
		$catparts = explode(',', $pagevars['cat']);
		if (!$catparts[0] || (false !== array_search($catparts[1], $contextlistarr)) ||
				!$DB->count_records_select("question_categories", "id = ? AND contextid = ?", array($catparts[0], $catparts[1]))) {
			print_error('invalidcategory', 'question');
		}
	} else {
		$category = $defaultcategory;
		$pagevars['cat'] = "$category->id,$category->contextid";
	}
	// Display options.
	$pagevars['recurse']    = question_get_display_preference('recurse',    1, PARAM_BOOL, $thispageurl);
	$pagevars['showhidden'] = question_get_display_preference('showhidden', 0, PARAM_BOOL, $thispageurl);
	$pagevars['qbshowtext'] = question_get_display_preference('qbshowtext', 0, PARAM_BOOL, $thispageurl);
	// Category list page.
	$pagevars['cpage'] = optional_param('cpage', 1, PARAM_INT);
	if ($pagevars['cpage'] != 1){
		$thispageurl->param('cpage', $pagevars['cpage']);
	}
	return array($thispageurl, $contexts, $cmid, $cm, $module, $pagevars);
}

/**
 * This class extends the question bank view to show only Exam special Meta Types.
 *
 * @author C.Wilhelm
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

class elate_exam_bank_view extends elate_question_bank_view {
//class elate_exam_bank_view extends question_bank_view {
	public function __construct($contexts, $pageurl, $course, $cm = null) {
		global $PAGE, $OUTPUT, $CFG;
		//$PAGE->requires->css("/course/format/elatexam/styles.css");
		$PAGE->requires->js("/course/format/elatexam/banklib.js");
		return question_bank_view::__construct($contexts, $pageurl, $course, $cm);
	}
}