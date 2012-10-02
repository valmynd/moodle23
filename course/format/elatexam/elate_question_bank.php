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
 * Question Bank enhanced with tag search and the ability to
 * copy questions into other categories.
 *
 * @author C.Wilhelm
 * @license	http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();
require_once($CFG->dirroot . '/question/editlib.php');
require_once($CFG->dirroot . '/course/format/elatexam/copylib.php');

/**
 * A column type showing the tags for the question.
 *
 * @author C.Wilhelm
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
*/
class question_bank_tagcloud_column extends question_bank_column_base {
	public function get_name() {
		return 'tagcloud';
	}
	protected function get_title() {
		return "Tags";//get_string('tagcloud', 'question');
	}
	protected function display_content($question, $rowclasses) {
		global $DB;
		$out = '';
		// SELECT rawname FROM mdl_tag as tg JOIN mdl_tag_instance ti ON ti.itemid = 45 and tg.id = ti.tagid and ti.itemtype = 'question'
		$tags = $DB->get_records_sql(
				"SELECT tg.rawname FROM {tag} as tg JOIN {tag_instance} ti ON ti.itemid = ? and tg.id = ti.tagid and ti.itemtype = 'question'",
				array($question->id));
		foreach ($tags as $key => $val)
			$out .= $key . ", ";
		echo rtrim($out, ", ");
	}
}

/**
 * This class extends the question bank view with a column containing
 * the Tags assigned to each question.
 *
 * @author C.Wilhelm
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

class elate_question_bank_view extends question_bank_view {
	public function __construct($contexts, $pageurl, $course, $cm = null) {
		global $PAGE, $OUTPUT, $CFG;
		$PAGE->requires->css("/course/format/elatexam/styles.css");
		$PAGE->requires->js("/course/format/elatexam/banklib.js");
		return parent::__construct($contexts, $pageurl, $course, $cm);
	}
	protected function wanted_columns() {
		$basetypes = parent::wanted_columns();
		return array_merge($basetypes, array('tagcloud'));
	}
	protected function known_field_types() {
		$basetypes = parent::known_field_types();
		return array_merge($basetypes, array(
				new question_bank_tagcloud_column($this),
		));
	}
	/**
	 * only changed one line in this method (Added Copy Button)
	 * so sync the rest when updating moodle!
	 * hopefully this method will be split in future moodle versions :-/
	 * @see question_bank_view::display_question_list()
	 */
	protected function display_question_list($contexts, $pageurl, $categoryandcontext, $cm = null, $recurse=1, $page=0, $perpage=100, $showhidden=false, $showquestiontext = false, $addcontexts = array()) {
		global $CFG, $DB, $OUTPUT;
		$category = $this->get_current_category($categoryandcontext);
		$cmoptions = new stdClass();
		$cmoptions->hasattempts = !empty($this->quizhasattempts);
		$strselectall = get_string('selectall');
		$strselectnone = get_string('deselectall');
		$strdelete = get_string('delete');
		list($categoryid, $contextid) = explode(',', $categoryandcontext);
		$catcontext = get_context_instance_by_id($contextid);
		$canadd = has_capability('moodle/question:add', $catcontext);
		$caneditall =has_capability('moodle/question:editall', $catcontext);
		$canuseall =has_capability('moodle/question:useall', $catcontext);
		$canmoveall =has_capability('moodle/question:moveall', $catcontext);
		$this->create_new_question_form($category, $canadd);
		$this->build_query_sql($category, $recurse, $showhidden);
		$totalnumber = $this->get_question_count();
		if ($totalnumber == 0) {
			return;
		}
		$questions = $this->load_page_questions($page, $perpage);
		echo '<div class="categorypagingbarcontainer">';
		$pageing_url = new moodle_url('edit.php');
		$r = $pageing_url->params($pageurl->params());
		$pagingbar = new paging_bar($totalnumber, $page, $perpage, $pageing_url);
		$pagingbar->pagevar = 'qpage';
		echo $OUTPUT->render($pagingbar);
		echo '</div>';
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
		echo '<div class="categorypagingbarcontainer pagingbottom">';
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
		echo '</div>';
		echo '<div class="modulespecificbuttonscontainer">';
		if ($caneditall || $canmoveall || $canuseall){
			echo '<strong>&nbsp;'.get_string('withselected', 'question').':</strong><br />';
			if (function_exists('module_specific_buttons')) {
				echo module_specific_buttons($this->cm->id,$cmoptions);
			}
			// print delete and move selected question
			if ($caneditall) {
				echo '<input type="submit" name="deleteselected" value="' . $strdelete . "\" />\n";
			}
			if ($canmoveall && count($addcontexts)) {
				// ADD OUR BUTTON HERE (Other Things Did Not Change!!)
				echo '<input type="submit" name="copy" value="'.get_string('copyto', 'format_elatexam')."\" />\n";
				echo '<input type="submit" name="move" value="'.get_string('moveto', 'question')."\" />\n";
				question_category_select_menu($addcontexts, false, 0, "$category->id,$category->contextid");
			}
			if (function_exists('module_specific_controls') && $canuseall) {
				$modulespecific = module_specific_controls($totalnumber, $recurse, $category, $this->cm->id,$cmoptions);
				if(!empty($modulespecific)){
					echo "<hr />$modulespecific";
				}
			}
		}
		echo "</div>\n";
		echo '</fieldset>';
		echo "</form>\n";
	}
	protected function create_new_question_form($category, $canadd) {
		// idea was to add row with create question button and searchbox via
		// print_table_headers() -> won't work because of table-layout:fixed; (CSS)
		// we need to have the new question button at one line with searchbox
		// this woud get messed up with some themes, so we have to modify this anyways
		echo '<span class="addbtn_and_searchbox">';
		// call create_new_question_button() -> compare to original when upgrading moodle!
		if ($canadd) create_new_question_button($category->id, $this->editquestionurl->params(), get_string('createnewquestion', 'question'));
		else print_string('nopermissionadd', 'question');
		// add our searchbar
		echo '<span class="searchbox">';
		echo '<label for="filter">Filter: </label>';
		echo '<input name="filter" type="text">';
		echo "<select id=\"searchbox_option\">\n";
		echo '<option selected="selected" value="tagcloud">Tags</option>';
		echo '<option value="all">All</option>';
		echo "</select></span></span>\n\n";
	}
	public function process_actions() {
		global $CFG, $DB;
		/// The following is handled very much the same as the 'move' part of parent::process_actions() in
		// Moodle 2.3 (EXCEPT FOR ONE LINE!), so compare to original when upgrading moodle!
		if (optional_param('copy', false, PARAM_BOOL) and confirm_sesskey()) {
			$category = required_param('category', PARAM_SEQUENCE);
			list($tocategoryid, $contextid) = explode(',', $category);
			if (! $tocategory = $DB->get_record('question_categories', array('id' => $tocategoryid, 'contextid' => $contextid)))
				print_error('cannotfindcate', 'question');
			$tocontext = get_context_instance_by_id($contextid);
			require_capability('moodle/question:add', $tocontext);
			$rawdata = (array) data_submitted();
			$questionids = array();
			foreach ($rawdata as $key => $value) {
				if (preg_match('!^q([0-9]+)$!', $key, $matches)) {
					$key = $matches[1];
					$questionids[] = $key;
				}
			}
			if ($questionids) {
				list($usql, $params) = $DB->get_in_or_equal($questionids);
				$sql = "";
				$questions = $DB->get_records_sql("
						SELECT q.*, c.contextid
						FROM {question} q
						JOIN {question_categories} c ON c.id = q.category
						WHERE q.id $usql", $params);
				foreach ($questions as $question){
					question_require_capability_on($question, 'move');
				}
				// THIS IS THE LINE THAT WAS CHANGED: (original: question_move_questions_to_category($questionids, $tocategory->id);)
				question_copy_questions_to_category($questionids, $tocategory->id);
				redirect($this->baseurl->out(false, array('category' => "$tocategoryid,$contextid")));
			}
		}
		parent::process_actions();
	}
}