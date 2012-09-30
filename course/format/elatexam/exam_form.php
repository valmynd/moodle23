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
 * Defines the editing form for an Exam Instance
 * 
 * @author C.Wilhelm
 * @license	http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once($CFG->dirroot . '/lib/formslib.php');
defined('MOODLE_INTERNAL') || die();

class exam_form extends moodleform {

	protected function definition() {
		$mform = &$this->_form;
		$mform->addElement('header', 'generalheader', get_string("general", 'form'));

		$mform->addElement('text', 'name', get_string('title', 'qtype_meta'));
		$mform->addHelpButton('name', 'title', 'qtype_meta');

		$mform->addElement('text', 'time', get_string('time', 'qtype_meta'), ' style="width:35px;"');
		$mform->setType('time', PARAM_INT);
		$mform->addHelpButton('time', 'time', 'qtype_meta');

		$mform->addElement('text', 'kindnessextensiontime', get_string('kindnessextensiontime', 'qtype_meta'), ' style="width:35px;"');
		$mform->setType('kindnessextensiontime', PARAM_INT);
		$mform->setDefault('kindnessextensiontime', 0);
		$mform->addHelpButton('kindnessextensiontime', 'kindnessextensiontime', 'qtype_meta');

		$mform->addElement('text', 'tasksperpage', get_string('tasksperpage', 'qtype_meta'), ' style="width:35px;"');
		$mform->setType('tasksperpage', PARAM_INT);
		$mform->setDefault('tasksperpage', 2);
		$mform->addHelpButton('tasksperpage', 'tasksperpage', 'qtype_meta');

		$mform->addElement('text', 'tries', get_string('tries', 'qtype_meta'), ' style="width:35px;"');
		$mform->setType('tries', PARAM_INT);
		$mform->setDefault('tries', 1);
		$mform->addHelpButton('tries', 'tries', 'qtype_meta');
		
		//$mform->addElement('editor', "questiontext", get_string('description', 'qtype_meta'), array('rows' => 8));
		//$mform->addHelpButton('questiontext', 'description', 'qtype_meta');

		$mform->addElement('advcheckbox', 'showhandlinghintsbeforestart', get_string('showhandlinghintsbeforestart', 'qtype_meta'), "");
		$mform->setDefault('showhandlinghintsbeforestart', true);
		
		//$mform->addElement('editor', "generalfeedback", get_string('starttext', 'qtype_meta'), array('rows' => 6));
		//$mform->addHelpButton('generalfeedback', 'starttext', 'qtype_meta');

		$mform->addElement('text', 'numberofcorrectors', get_string('numberofcorrectors', 'qtype_meta'), ' style="width:35px;"');
		$mform->setType('numberofcorrectors', PARAM_INT);
		$mform->setDefault('numberofcorrectors', 2);
		$mform->addHelpButton('numberofcorrectors', 'numberofcorrectors', 'qtype_meta');

		////// Set Fields which are required to fill out //////////
		//$mform->addRule('questiontext', null, 'required', null, 'client');
		$mform->addRule('time', null, 'required', null, 'client');
		
		////// Add Question Selector  //////////
		$mform->addElement('header', 'qheader', get_string('qheader', 'qtype_meta'));
		$mform->addElement('html', $this->get_list_html());
		$mform->addElement('html', $this->_customdata);
		//$mform->addElement('html', '<div style="clear: both;"></div>');
		
		////// Add Submit / Cancel Buttons  //////////
		$buttonarray=array();
		$buttonarray[] =& $mform->createElement('submit', 'submitbutton', get_string('savechanges'));
		$buttonarray[] =& $mform->createElement('submit', 'cancel', get_string('cancel'));
		$mform->addGroup($buttonarray, 'buttonar', '', array(' '), false);
		$mform->closeHeaderBefore('buttonar');
	}

	protected function get_list_html() {
		// note that such things as set_data() will not affect <input> elements generated with toHtml()!
		$ret = '<div class="examcategorycontainer">'."\n";
		/*return '
			<li id="c1">Category</li>
			<li id="c2">Category</li>
			<li id="c3">Category</li>
			<ul>
				<li id="c3_1">Category</li>
				<li id="c3_2">Category</li>
				<li id="i3_3" class="q">Category</li> // q is used in qbank, so we call it i
				<li id="i3_4" class="q">Category</li>
			</ul>*/
		//debugging(var_export($_POST));

		////// Handle Button Actions (Add, Delete, Move) //////////

		if(isset($_POST["removecategory"])) {
			if(isset($_POST["selected_category"]))
				$this->handle_category_deletion();
		} else if(isset($_POST["addcategory"])) {
			// at the very beginning, there will be no selectable items
			if(isset($_POST["selected_category"]))
				$this->handle_category_addition();
			else $_POST["c1"] = $_POST["newcategory"];
		} else if($matches = preg_grep('/^move_.*_.*_x/', array_keys($_POST))) {
			$this->handle_category_move(array_shift($matches));
		}

		if(!isset($_POST["selected_category"])) {
			$_POST["selected_category"] = "c1";
		}

		////// Generate Fields for each Category / Question (by now $_POST should not be changed anymore!) //////////

		$ret .= $this->get_per_item_html(); // works recursively

		////// Add Action Buttons (Add, Delete, Move) //////////

		$element = $this->_form->createElement('text', 'newcategory');
		$ret .= get_string('addcategory', 'question') . $element->toHtml();
		$element = $this->_form->createElement('submit', 'addcategory', get_string('add'), ' onclick="skipClientValidation = true;"');
		$ret .= $element->toHtml();
		$element = $this->_form->createElement('submit', 'removecategory', get_string('removeselected', 'quiz'), ' onclick="skipClientValidation = true;"');
		$ret .= $element->toHtml();
		return $ret.'</div>'."\n";
	}

	protected function get_per_item_html(array $indices = null) {
		// read this: http://davidwalsh.name/php-form-submission-recognize-image-input-buttons
		global $OUTPUT;
		$ret = "<ul>";
		if(!$indices) $indices = array('1',);
		$level = count($indices)-1;
		for($i=1; $i <= 9999; $i++) {
			$indices[$level] = $i;
			$ckey = "c".implode('_', $indices);
			$qkey = "i".implode('_', $indices);
			//if($level > 0) debugging($level . $ckey . $_POST[$ckey]);
			$radiobtnattrib = '';
			$buttonstr = '';
			$buttonattrib = ' onclick="skipClientValidation = true;" style="height:8px;"';
			// if there is a parent category (level > 0), provide moveleft button
			if($level > 0) {
				$name = "move_left_".$ckey;
				$iconurl = $OUTPUT->pix_url('t/left');
				$buttonstr .= '<input type="image" src="'.$iconurl.'" name="'.$name.'"'
						.' alt="'.get_string('moveleft').'"'.$buttonattrib.'>';
			}
			// if there is a previous category, provide moveup button
			if($i > 1) {
				$name = "move_up_".$ckey;
				$iconurl = $OUTPUT->pix_url('t/up');
				$buttonstr .= '<input type="image" src="'.$iconurl.'" name="'.$name.'"'
						.' alt="'.get_string('moveup').'"'.$buttonattrib.'>';
			}
			// if there is an next category, provide movedown button
			if($this->next_item_exists($indices, $level, $i)) {
				$name = "move_down_".$ckey;
				$iconurl = $OUTPUT->pix_url('t/down');
				$buttonstr .= '<input type="image" src="'.$iconurl.'" name="'.$name.'"'
						.' alt="'.get_string('movedown').'"'.$buttonattrib.'>';
			}
			// if there is another category, provide moveright (use as subcategory) button
			if($i > 1) {
				$name = "move_right_".$ckey;
				$iconurl = $OUTPUT->pix_url('t/right');
				$buttonstr .= '<input type="image" src="'.$iconurl.'" name="'.$name.'"'
						.' alt="'.get_string('moveright').'"'.$buttonattrib.'>';
			}
			// finally, render the item depending on whether it is a category or a question
			if(isset($_POST[$ckey])) { // category
				if($_POST["selected_category"] == $ckey)
					$radiobtnattrib = ' checked="checked"';
				$input = $this->_form->createElement('text', $ckey, null, ' value="'.$_POST[$ckey].'"');
				$radiobtn = $this->_form->createElement('radio', 'selected_category', '', $input->toHtml() . $buttonstr, $ckey, $radiobtnattrib);
				$ret .= '<li id="c'.$i.'">';
				$ret .= $radiobtn->toHtml();
				$ret .= '</li>';
				if($level <= 5) $ret .= $this->get_per_item_html( array_merge($indices, array('1',)) );
			} else if(isset($_POST[$qkey])) { // question

			} else break;
		}
		return $ret."</ul>\n";
	}

	protected function handle_category_move($btn_name) {
		preg_match('/(up|down|left|right)/', $btn_name, $matches);
		$direction = $matches[0];
		preg_match('/[^(move_'.$direction.'_)].*[^(_x)]/', $btn_name, $matches);
		$srckey = $matches[0];
		$indices_source = explode('_', substr($srckey,1));
		$indices_target = $indices_source; // copy
		$level = count($indices_source)-1;
		$i = $indices_source[$level];
		// TODO: handle question selection in a different way (e.g. checkboxes instead of radio buttons)
		$_POST_COPY = $_POST; // values will be overwritten
		switch($direction) {
			case 'up':    $indices_target[$level] = $i-1; break;
			case 'down':  $indices_target[$level] = $i+1; break;
			case 'left': array_pop($indices_target); break; // remove last
			case 'right': // always appent into the previous sibling
				$parentid = $indices_target[$level];
				$indices_target[$level] = $parentid-1;
				$indices_target[$level+1] = $this->count_categories_at_level($indices_target, $level+1); // append to last
				break;
		}
		if($direction == 'up' || $direction == 'down') {
			// this one is simple, just swap the values
			$targetkey = "c".implode('_', $indices_target);
			$_POST[$targetkey] = $_POST_COPY[$srckey];
			$_POST[$srckey] = $_POST_COPY[$targetkey];
			$finalkey = $targetkey;
		} else {
			$_POST["newcategory"] = $_POST_COPY[$srckey]; // handle it as if we would add a category, copy subitems into it later on
			$_POST["selected_category"] = $srckey; // temporarily point to source
			$this->handle_category_deletion(); // will remove selected element
			$_POST["selected_category"] = "c".implode('_', $indices_target); // now point to target
			$finalkey = $this->handle_category_addition(null, $_POST_COPY); // will put it to the final position
		}
		$_POST["selected_category"] = $finalkey;
		// switch children from $_POST_COPY[$srckey] and $_POST[$targetkey]
		$matches = preg_grep('/^'.$srckey.'_.*/', array_keys($_POST));
		foreach($matches as $oldkey) {
			$newkey = $targetkey . substr($oldkey, strlen($srckey));
			unset($_POST[$oldkey]);
			unset($_POST[$newkey]);
			if(isset($_POST_COPY[$newkey])) $_POST[$oldkey] = $_POST_COPY[$newkey];
			if(isset($_POST_COPY[$oldkey])) $_POST[$newkey] = $_POST_COPY[$oldkey];
		}
		//debugging(" remove: " . $srckey . " add: " . "c".implode('_', $indices_target) . " select: " . $_POST["selected_category"]);
	}

	protected function handle_category_deletion(array $indices = null) {
		if(!$indices) $indices = explode('_', substr($_POST["selected_category"],1));
		$level = count($indices)-1;
		for($i=$indices[$level]; $i <= 99; $i++) {
			$currkey = "c".implode('_', $indices);
			$indices[$level] = $i+1;
			$nextkey = "c".implode('_', $indices);
			if(!isset($_POST[$nextkey])) {
				unset($_POST[$currkey]);
				break;
			}
			$_POST[$currkey] = $_POST[$nextkey];
			if($level <= 5) $this->handle_category_deletion( array_merge($indices, array('1',)) );
		}
	}

	protected function handle_category_addition(array $indices = null, $_POST_COPY = null) {
		// at the beginning, indices will contain the indices of the position of the new element
		// later on, indices will point to the children elements of the element that has to be moved
		if(!$_POST_COPY) $_POST_COPY = $_POST;
		$override_key = $_POST["selected_category"];
		$first = false;
		$continue = true;
		if(!$indices) {
			$indices = explode('_', substr($_POST["selected_category"],1));
			$first = $indices[count($indices)-1];
		}
		$level = count($indices)-1;
		for($i=$indices[$level]; $i <= 99 && $continue; $i++) {
			$currkey = "c".implode('_', $indices);
			$indices[$level] = $i+1;
			$nextkey = "c".implode('_', $indices);
			if(!isset($_POST[$currkey])) break;
			$continue = isset($_POST[$nextkey]);
			$_POST[$nextkey] = $_POST_COPY[$currkey];
			if($level <= 5) $this->handle_category_addition( array_merge($indices, array('1',)) );
			if($i == $first) $override_key = $nextkey;
		}
		if($first) $_POST[$override_key] = $_POST["newcategory"];
		$_POST["selected_category"] = $override_key;
		return $override_key;
	}

	/**
	 * overridden to prevent submission when clicked on certain buttons
	 * @see moodleform::no_submit_button_pressed()
	 */
	public function no_submit_button_pressed() {
		if(count(preg_grep('/^(move_|delanswerbtn_|add|remove).*/', array_keys($_POST))) > 0)
			return true;
		return parent::no_submit_button_pressed();
	}

	private function next_item_exists(array $indices, $level, $current) {
		$indices[$level] = $current + 1; // we operate on copy
		$ckey = "c".implode('_', $indices);
		$qkey = "i".implode('_', $indices);
		return isset($_POST[$ckey]) || isset($_POST[$qkey]);
	}

	private function count_categories_at_level(array $indices, $level) {
		for($i=1; $i <= 99; $i++) {
			$indices[$level] = $i;
			$key = "c".implode('_', $indices);
			if(!isset($_POST[$key]))
				return $i;
		}
	}
}