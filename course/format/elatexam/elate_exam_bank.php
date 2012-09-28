<?php

require_once($CFG->dirroot . '/question/editlib.php');
require_once($CFG->dirroot . '/course/format/elatexam/elate_question_bank.php');
/**
 * This class extends the question bank view to show only Exam special Meta Types.
 *
 * @author C.Wilhelm
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
*/

class elate_exam_bank_view extends question_bank_view {
	// TODO: alle klausuren kommen in bestimmte kategorie, welche angelegt wird, wenn es sie noch nicht gibt
	// TODO: Link (Button?) um elate_question_bank_view (in neuem Fenster?) zu öffnen
	// auf beiden Seiten Fragen mit den jeweiligen Kategorien, beides in unterschiedlichen Tabellen organisiert
	// über den Tabellen Felder aus qtype_meta
	protected function known_field_types() {
		return array(
				new question_bank_checkbox_column($this),
				new question_bank_question_type_column($this),
				new question_bank_question_name_column($this),
				new question_bank_question_text_row($this),
		);
	}
	protected function wanted_columns() {
		return array('checkbox', 'qtype', 'questionname');
	}
	protected function display_question_list($contexts, $pageurl, $categoryandcontext, $cm = null, $recurse=1, $page=0, $perpage=100, $showhidden=false, $showquestiontext=false, $addcontexts = array()) {
		$recurse = 0;
		$perpage = 10000;
		$showhidden = false;
		$showquestiontext = false;
		$addcontexts = array();
		//return parent::display_question_list($contexts, $pageurl, $categoryandcontext, $cm, $recurse, $page, $perpage, $showhidden, $showquestiontext, $addcontexts);
		global $CFG, $DB, $OUTPUT;
		$category = $this->get_current_category($categoryandcontext);
		$cmoptions = new stdClass();
		$cmoptions->hasattempts = !empty($this->quizhasattempts);
		$strselectall = get_string('selectall');
		$strselectnone = get_string('deselectall');
		$strdelete = get_string('delete');
		list($categoryid, $contextid) = explode(',', $categoryandcontext);
		$catcontext = get_context_instance_by_id($contextid);
		$this->build_query_sql($category, $recurse, $showhidden);
		$totalnumber = $this->get_question_count();
		if ($totalnumber == 0) {
			return;
		}
		$questions = $this->load_page_questions($page, $perpage);

		/*echo '<div class="categorypagingbarcontainer">';
		$pageing_url = new moodle_url('edit.php');
		$r = $pageing_url->params($pageurl->params());
		$pagingbar = new paging_bar($totalnumber, $page, $perpage, $pageing_url);
		$pagingbar->pagevar = 'qpage';
		echo $OUTPUT->render($pagingbar);
		echo '</div>';*/

		echo '<form method="post" action="edit.php">';
		echo '<fieldset class="invisiblefieldset" style="display: block;">';
		echo '<input type="hidden" name="sesskey" value="'.sesskey().'" />';
		echo html_writer::input_hidden_params($pageurl);

		echo '<div class="categoryquestionscontainer">';
		$this->start_table();
		$rowcount = 0;
		foreach ($questions as $question) {
			$this->print_table_row($question, $rowcount);
			$rowcount += 1;
		}
		$this->end_table();
		echo "</div>\n";

		/*echo '<div class="categorypagingbarcontainer pagingbottom">';
		echo $OUTPUT->render($pagingbar);
		if ($totalnumber > DEFAULT_QUESTIONS_PER_PAGE) {
			if ($perpage == DEFAULT_QUESTIONS_PER_PAGE) {
				$url = new moodle_url('edit.php', array_merge($pageurl->params(), array('qperpage'=>1000)));
				$showall = '<a href="'.$url.'">'.get_string('showall', 'moodle', $totalnumber).'</a>';
			} else {
				$url = new moodle_url('edit.php', array_merge($pageurl->params(), array('qperpage'=>DEFAULT_QUESTIONS_PER_PAGE)));
				$showall = '<a href="'.$url.'">'.get_string('showperpage', 'moodle', DEFAULT_QUESTIONS_PER_PAGE).'</a>';
			}
			echo "<div class='paging'>$showall</div>";
		}
		echo '</div>';*/

		echo '<div class="modulespecificbuttonscontainer">';
		echo '<strong>&nbsp;'.get_string('withselected', 'question').':</strong><br />';
		if (function_exists('module_specific_buttons')) {
			echo module_specific_buttons($this->cm->id,$cmoptions);
			// "Use in Exam" | "Remove from Exam"
		}
		echo "</div>\n";

		echo '</fieldset>';
		echo "</form>\n";
	}
	protected function display_options($recurse, $showhidden, $showquestiontext) {
		// we don't want those options in the mini-view
	}
	protected function print_category_info($category) {
		// we don't want those options in the mini-view
	}
	protected function create_new_question_form($category, $canadd) {
		// we don't want those options in the mini-view
	}
}