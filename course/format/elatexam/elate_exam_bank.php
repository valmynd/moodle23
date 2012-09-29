<?php

require_once($CFG->dirroot . '/question/editlib.php');
require_once($CFG->dirroot . '/course/format/elatexam/exam_form.php');

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
		global $OUTPUT;
		$delete_icon = $OUTPUT->pix_url('t/moveleft');
		echo '<input type="image" src="'.$delete_icon.'" alt="'.get_string('addtoquiz', 'quiz').' onclick="skipClientValidation = true;" style="height:7px;"">';
		//echo '</span>';
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
	// auf beiden Seiten Fragen mit den jeweiligen Kategorien, beides in unterschiedlichen Tabellen organisiert
	// über den Tabellen Felder aus qtype_meta
	// TODO: alles muss zu einem formular gehören
	// Listenelemente
	// - editierbar und verschiebbar für Kategorien selbst (+ löschen/hinzufügen)
	// - verschiebbare Fragen
	// - Kategorien stets vor Fragen (wie Ordner im FS)
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
		return array('checkbox', 'qtype', 'questionname', 'addtoexam');
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
		$totalnumber = $this->get_question_count();
		if ($totalnumber == 0) return;
		$questions = $this->load_page_questions($page, $perpage);

		//echo '<form method="post" action="edit.php">';
		//echo '<fieldset class="invisiblefieldset" style="display: block;">';
		//echo '<input type="hidden" name="sesskey" value="'.sesskey().'" />';
		echo html_writer::input_hidden_params($pageurl);

		echo '<div class="categoryquestionscontainer">';
		$this->start_table();
		$rowcount = 0;
		foreach ($questions as $question) {
			$this->print_table_row($question, $rowcount);
			$rowcount += 1;
		}
		$this->end_table();
		//echo "</div>\n";

		//echo '<div class="modulespecificbuttonscontainer">';
		//echo '<strong>&nbsp;'.get_string('withselected', 'question').':</strong><br />';
		//if (function_exists('module_specific_buttons')) {
		//	echo module_specific_buttons($this->cm->id,$cmoptions);
			// TODO: "Use in Exam" | "Remove from Exam" buttons
		//}
		echo "</div>\n";

		//echo '</fieldset>';
		//echo "</form>\n";
	}
	public function display($tabname, $page, $perpage, $cat, $recurse, $showhidden, $showquestiontext) {
		global $PAGE, $OUTPUT;
		if ($this->process_actions_needing_ui()) return;
		$PAGE->requires->js('/question/qbank.js');
		ob_start();
		//$this->display_category_form($this->contexts->having_one_edit_tab_cap($tabname), $this->baseurl, $cat);
		//$this->display_options($recurse, $showhidden, $showquestiontext);
		//if (!$category = $this->get_current_category($cat)) return;
		//$this->print_category_info($category);
		// continues with list of questions
		$this->display_question_list($this->contexts->having_one_edit_tab_cap($tabname),
				$this->baseurl, $cat, $this->cm,
				false, $page, 10000, false, false, // force our defaults for the mini-view
				$this->contexts->having_cap('moodle/question:add'));
		$htmlstr = ob_get_contents();
		ob_end_clean();
		$mform = new exam_form($action=null, $customdata=$htmlstr, $method='post');
		if ($mform->is_cancelled()){
			//you need this section if you have a cancel button on your form
			//here you tell php what to do if your user presses cancel
			//probably a redirect is called for!
			// PLEASE NOTE: is_cancelled() should be called before get_data(), as this may return true
		} else if ($fromform=$mform->get_data()){
			//this branch is where you process validated data.
		} else {
			// this branch is executed if the form is submitted but the data doesn't validate and the form should be redisplayed
			// or on the first display of the form.
			//put data you want to fill out in the form into array $toform here then :
			$mform->set_data($_POST);
			$mform->display();
		}
	}
}