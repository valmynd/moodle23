<?php

require_once($CFG->dirroot . '/question/editlib.php');
require_once($CFG->dirroot . '/course/format/elatexam/exam_form.php');
require_once($CFG->dirroot . '/course/format/elatexam/folder_view.php');

/**
 * A column type for the add this question to the exam.
 *
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class question_bank_add_to_exam_column extends question_bank_action_column_base {
	public function get_name() {
		return 'addtoexam';
	}
	protected function display_content($question, $rowclasses) {
		if (!question_has_capability_on($question, 'use')) return;
		// For RTL languages: switch right and left arrows.
		//if (right_to_left()) $movearrow = 't/removeright';
		//else $movearrow = 't/moveleft';
		//$this->print_icon($movearrow, get_string('addtoquiz', 'quiz')/*, $this->qbank->add_to_quiz_url($question->id)*/);
		//echo '<span style="margin:0;padding:0;height:5px">';
		//echo '<input name="useincategory" value="◄" type="submit" onclick="skipClientValidation = true;" style="margin:0;padding:0;height:16px;width:16px;">';
		/*global $OUTPUT;
		$delete_icon = $OUTPUT->pix_url('t/moveleft');
		echo '<input type="image" src="'.$delete_icon.'" name="use_q'.$question->id.'"'
				.' alt="'.get_string('addtoquiz', 'quiz').' onclick="skipClientValidation = true;" style="height:7px;">';*/
		//echo '</span>';
		echo category_view::get_use_in_question_button($question->id);
	}
	public function get_required_fields() {
		return array('q.id');
	}
}

/**
 * This class extends the question bank to be not only a question bank anymore...
 *
 * @author C.Wilhelm
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
*/

class elate_exam_bank_view extends question_bank_view {
	// TODO: alle klausuren kommen in bestimmte kategorie, welche angelegt wird, wenn es sie noch nicht gibt
	// TODO: Link (Button?) um elate_question_bank_view (in neuem Fenster?) zu öffnen
	private $questions;
	private $qbank_html;
	private $qbank_html_hidden;
	// auf beiden Seiten Fragen mit den jeweiligen Kategorien, beides in unterschiedlichen Tabellen organisiert
	// über den Tabellen Felder aus qtype_meta
	// Listenelemente
	// - editierbar und verschiebbar für Kategorien selbst (+ löschen/hinzufügen)
	// - verschiebbare Fragen
	// - Kategorien stets vor Fragen (wie Ordner im FS)
	public function __construct($contexts, $pageurl, $course, $cm = null) {
		global $PAGE, $OUTPUT, $CFG;
		$PAGE->requires->css("/course/format/elatexam/styles.css");
		$PAGE->requires->js("/course/format/elatexam/banklib.js");
		$this->qbank_html = null;
		$this->questions = array();
		return parent::__construct($contexts, $pageurl, $course, $cm);
	}
	protected function known_field_types() {
		return array(
				new question_bank_add_to_exam_column($this),
				new question_bank_checkbox_column($this),
				new question_bank_question_type_column($this),
				new question_bank_question_name_column($this),
				new question_bank_question_text_row($this),
		);
	}
	protected function wanted_columns() {
		return array('addtoexam', 'qtype', 'questionname', 'checkbox');
	}
	protected function display_category_form($contexts, $pageurl, $current) {
		echo '<div class="choosecategory">';
		$catmenu = question_category_options($contexts, false, 0, true);
		echo '<label for="selected_category">'.get_string('selectacategory', 'question').' </label>';
		echo "<select id=\"selected_category\" name=\"category\" class=\"select menucategory\">\n";
		foreach($catmenu as $toplevel) {
			foreach($toplevel as $label=>$sublevel) {
				echo '<optgroup label="'.$label.'">';
				foreach($sublevel as $id=>$title) {
					if($id != $current) $selectedstr = '';
					else $selectedstr = ' selected="selected"';
					echo '<option value="'.$id.'"'.$selectedstr.'>'.$title.'</option>';
				}
				echo "</optgroup>/n";
			}
		}
		echo "\n</select>";
		echo '<input type="submit" id="change_category" name="change_category" value="'.get_string('go').'"'.folder_view::$buttonattrib.'>';
		echo "</div>\n";
	}
	protected function display_question_list($contexts, $pageurl, $categoryandcontext, $cm = null, $recurse=1, $page=0, $perpage=100, $showhidden=false, $showquestiontext=false, $addcontexts = array()) {
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
		$questions = $this->load_page_questions($page, $perpage);
		$this->qbank_html_hidden = html_writer::input_hidden_params($pageurl);
		echo $this->qbank_html_hidden;

		echo '<div class="categoryquestionscontainer">';
		$this->start_table();
		$rowcount = 0;
		foreach ($questions as $question) {
			$this->print_table_row($question, $rowcount);
			$rowcount += 1;
			// store id=>row[] per question into $this->questions
			$this->questions[$question->id] = $question;
		}
		$this->end_table();
		// TODO: "Use in Exam" | "Remove from Exam" buttons
		echo '<div style="width:100%;text-align:right;">';
		echo '<input type="submit" id="change_category" name="change_category" value="'."<< Use in Exam".'"'.folder_view::$buttonattrib.'>';
		echo "</div>\n";
		// close div
		echo "</div>\n";
	}
	public function display($tabname, $page, $perpage, $cat, $recurse, $showhidden, $showquestiontext) {
		global $CFG, $PAGE, $OUTPUT;
		if ($this->process_actions_needing_ui()) return;
		ob_start();
		$this->display_category_form($this->contexts->having_one_edit_tab_cap($tabname), $this->baseurl, $cat);
		//$this->display_options($recurse, $showhidden, $showquestiontext);
		//if (!$category = $this->get_current_category($cat)) return;
		//$this->print_category_info($category);
		// continues with list of questions
		$this->display_question_list($this->contexts->having_one_edit_tab_cap($tabname),
				$this->baseurl, $cat, $this->cm,
				false, $page, 10000, false, false, // force our defaults for the mini-view
				$this->contexts->having_cap('moodle/question:add'));
		$this->qbank_html = ob_get_contents();
		ob_end_clean();
		$mform = new exam_form($action=null, $customdata=$this, $method='post');
		if ($mform->is_cancelled()){
			// Wollen Sie wirklich abbrechen? ...
			//redirect($CFG->wwwroot . '/course/view.php?id=' . $POST['courseid']);
		} else if ($fromform=$mform->get_data()){
			//this branch is where you process validated data.
		} else {
			// this branch is executed on the first display of the form or if the data didn't validate
			$mform->set_data($_POST);
			$mform->display();
		}
	}
	/**
	 * @return string containing the questionbank-html-representation which
	 * is to be used as Question-Chooser in exam_form
	 */
	public function get_html() {
		return $this->qbank_html;
	}
	/**
	 * @return string containing the html to be displayed if no categories
	 * where added yet
	 */
	public function get_hidden_html() {
		return $this->qbank_html_hidden;
	}
	/**
	 * @return string containing an html representation of an already fetched
	 *  question (or null if it ain't avaiable, e.g. when the category changed recently)
	 */
	public function get_question_by_id($id) {
		global $PAGE;
		if(!isset($this->questions[$id])) return null;
		$question = $this->questions[$id];
		$ret = $PAGE->get_renderer('question', 'bank')->qtype_icon($question->qtype);
		$ret .= $question->name;
		return $ret;
	}
}