<?php

/**
 * @see question_move_questions_to_category
 *
 * @param array $questionids of question ids.
 * @param integer $newcategoryid the id of the category to move to.
 */
function question_copy_questions_to_category($questionids, $newcategoryid) {
	global $DB, $CFG;
	$newcontextid = $DB->get_field('question_categories', 'contextid',
			array('id' => $newcategoryid));
	list($questionidcondition, $params) = $DB->get_in_or_equal($questionids);
	$questions = $DB->get_records_sql("
			SELECT q.id, q.qtype, qc.contextid
			FROM {question} q
			JOIN {question_categories} qc ON q.category = qc.id
			WHERE  q.id $questionidcondition", $params);
	foreach ($questions as $question) {
		if ($newcontextid != $question->contextid) {
			question_bank::get_qtype($question->qtype)->move_files($question->id, $question->contextid, $newcontextid);
			// DUPLICATE ROWS OF ALL RELEVANT TABLES (until here nothing was changed)
			// a) Table 'question'
			$existing = $DB->get_record('question', array('id' => $question->id));
			unset($existing->id);
			$existing->category = $newcategoryid;
			$id_of_dublicate = $DB->insert_record('question', $existing);
			// b) get Tables for this qtype and dublicate them
			$xml_path = $CFG->dirroot . '/question/type/' . $question->qtype . '/db/install.xml';
			if (file_exists($xml_path)) try {
				// gather information on the table we want to copy
				$dom = new DomDocument();
				$dom->load($xml_path);
				$XPath = new DOMXPath($dom);
				$elements = $dom->getElementsByTagName("TABLE");
				foreach($elements as $el) {
					$tablename = $el->getAttribute("NAME");
					$nodes = $XPath->query("//TABLE[@NAME='$tablename']/KEYS/KEY[@TYPE='foreign']/@NAME");
					$foreignkey_name = $nodes->item(0)->value;
					$nodes = $XPath->query("//TABLE[@NAME='$tablename']/KEYS/KEY[@TYPE='primary']/@NAME");
					$primarykey_name = $nodes->item(0)->value;
					// now we can use this information to dublicate this subtable
					$existing = $DB->get_record($tablename, array($foreignkey_name => $question->id));
					unset($existing->{$primarykey_name});
					$existing->{$foreignkey_name} = $id_of_dublicate;
					$id_of_subsequent_dublicate = $DB->insert_record($tablename, $existing);
				}
			} catch(Exception $e) {
				echo "WARNING: ", $e->getMessage(), "\n";
			}
			// c) dublicate other Tables that are affected: Tags, ..., ???
			$records = $DB->get_records('tag_instance', array('itemtype' => 'question', 'itemid' => $question->id));
			foreach($records as $existing) {
				unset($existing->id);
				$existing->itemid = $id_of_dublicate;
				$id_of_taginstance_dublicate = $DB->insert_record('tag_instance', $existing);
			}
		}
	}

	// Move the questions themselves.
	//$DB->set_field_select('question', 'category', $newcategoryid, "id $questionidcondition", $params);
	// Move any subquestions belonging to them.
	//$DB->set_field_select('question', 'category', $newcategoryid, "parent $questionidcondition", $params);
	return true;
}

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
		$PAGE->requires->css("/question/elate_question_bank.css");
		$PAGE->requires->js("/question/elate_question_bank.js");
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
	public function display($tabname, $page, $perpage, $cat, $recurse, $showhidden, $showquestiontext) {
		//! $perpage = 10000; // disable pagination
		// suppress echoes to be sent to the client, see http://www.tuxradar.com/practicalphp/13/3/0
		ob_start();
		parent::display($tabname, $page, $perpage, $cat, $recurse, $showhidden, $showquestiontext);
		$htmlstr = ob_get_contents();
		ob_end_clean();
		// Prepare XPath object
		$dom = new DomDocument();
		$htmlstr = utf8_decode($htmlstr); // utf-8 bug in PHP
		$dom->loadHTML($htmlstr);
		$XPath = new DOMXPath($dom);
		// add button "Copy To..." -> compare to original when upgrading moodle!
		$nodes = $XPath->query("//input[@name='move']");
		$movebtn_node = $nodes->item(0);
		if($movebtn_node) {
			$copybtn_node = $dom->createElement("input", "hello");
			$copybtn_node->setAttribute("type", "submit");
			$copybtn_node->setAttribute("name", "copy");
			$copybtn_node->setAttribute("value", "Copy to >>");
			$movebtn_node->parentNode->insertBefore($copybtn_node, $movebtn_node);
		}
		// remove pagination options
		//! $nodes = $XPath->query("//*[contains(@class, 'categorypagingbarcontainer')]");
		//! foreach($nodes as $node)
		//!	$node->parentNode->removeChild($node);
		echo utf8_encode($dom->saveHTML()); // utf-8 bug in PHP
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