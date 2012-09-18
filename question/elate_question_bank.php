<?php

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
	public function display($tabname, $page, $perpage, $cat, $recurse, $showhidden, $showquestiontext) {
		$perpage = 10000; // disable pagination
		// suppress echoes to be sent to the client, see http://www.tuxradar.com/practicalphp/13/3/0
		ob_start();
		parent::display($tabname, $page, $perpage, $cat, $recurse, $showhidden, $showquestiontext);
		$htmlstr = ob_get_contents();
		ob_end_clean();
		// Prepare XPath object
		$dom = new DomDocument();
		$dom->loadHTML($htmlstr);
		$XPath = new DOMXPath($dom);
		// add button "Copy To..." -> compare to original when upgrading moodle!
		$nodes = $XPath->query("//input[@name='move']");
		$movebtn_node = $nodes->item(0);
		$copybtn_node = $dom->createElement("input", "hello");
		$copybtn_node->setAttribute("type", "submit");
		$copybtn_node->setAttribute("name", "copy");
		$copybtn_node->setAttribute("value", "Copy to >>");
		$movebtn_node->parentNode->insertBefore($copybtn_node, $movebtn_node);
		// remove pagination options
		$nodes = $XPath->query("//*[contains(@class, 'categorypagingbarcontainer')]");
		foreach($nodes as $node)
			$node->parentNode->removeChild($node);
		echo $dom->saveHTML();
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
}