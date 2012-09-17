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
		return "Tags";//get_string('createdby', 'question');
	}

	protected function display_content($question, $rowclasses) {
		/*if (!empty($question->creatorfirstname) && !empty($question->creatorlastname)) {
			$u = new stdClass();
			$u->firstname = $question->creatorfirstname;
			$u->lastname = $question->creatorlastname;
			echo fullname($u);
		}*/
		echo $question->tagname;
	}

	public function get_extra_joins() {
		return array(
			'ti' => "JOIN {tag_instance} ti ON ti.itemid = q.id and ti.itemtype = 'question'",
			'tg' => "JOIN {tag} tg ON tg.id = ti.tagid",
		);
	}

	public function get_required_fields() {
		return array('tg.rawname AS tagname',);
	}

	public function is_sortable() {
		return false;
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
	protected function build_query_sql($category, $recurse, $showhidden) {
		parent::build_query_sql($category, $recurse, $showhidden);
		debugging($this->loadsql);
	}
}