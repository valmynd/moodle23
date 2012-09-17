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
	protected function display_start($question, $rowclasses) {
		echo '<td class="' . $this->get_classes() . '" style="width:300px;white-space:normal;word-wrap:break-word;">';
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
}