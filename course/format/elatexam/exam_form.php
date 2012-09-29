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
		
		$mform->addElement('editor', "questiontext", get_string('description', 'qtype_meta'), array('rows' => 8));
		$mform->addHelpButton('questiontext', 'description', 'qtype_meta');

		$mform->addElement('advcheckbox', 'showhandlinghintsbeforestart', get_string('showhandlinghintsbeforestart', 'qtype_meta'), "");
		$mform->setDefault('showhandlinghintsbeforestart', true);
		
		$mform->addElement('editor', "generalfeedback", get_string('starttext', 'qtype_meta'), array('rows' => 6));
		$mform->addHelpButton('generalfeedback', 'starttext', 'qtype_meta');

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
		$ret = '<div class="examcategorycontainer">'."<ul>\n";
		/*return '
			<li id="c1">Category</li>
			<li id="c2">Category</li>
			<li id="c3">Category</li>
			<ul>
				<li id="c3_1">Category</li>
				<li id="c3_2">Category</li>
				<li id="q3_3" class="q">Category</li>
				<li id="q3_4" class="q">Category</li>
			</ul>*/
		debugging(var_export($_POST));
		if(isset($_POST["removecategory"])) {
			if(isset($_POST["selected_category"]))
				$this->handle_category_deletion();
		} else if(isset($_POST["addcategory"])) {
			// on start, there will be no selectable items, later on it must be
			// prevented to happen, that there is no selected item
			if(isset($_POST["selected_category"]))
				$this->handle_category_addition();
			else $_POST["c1"] = $_POST["newcategory"];
		}
		if(!isset($_POST["selected_category"])) {
			$_POST["selected_category"] = "c1";
		}
		for($i=1; $i <= 9999; $i++) {
			if(isset($_POST["c$i"])) { // category
				$input = $this->_form->createElement('text', "c$i", null, ' value="'.$_POST["c$i"].'"');
				$radiobtn = $this->_form->createElement('radio', 'selected_category', '', $input->toHtml(), "c$i");
				$ret .= '<li id="c'.$i.'">';
				$ret .= $radiobtn->toHtml();
				$ret .= '</li>';
			} else if(isset($_POST["q$i"])) { // question

			} else break;
		}
		$element = $this->_form->createElement('text', 'newcategory');
		$ret .= get_string('addcategory', 'question') . $element->toHtml();
		$this->_form->registerNoSubmitButton('addcategory');
		$element = $this->_form->createElement('submit', 'addcategory', get_string('add'), ' onclick="skipClientValidation = true;"');
		$ret .= $element->toHtml();
		$this->_form->registerNoSubmitButton('removecategory');
		$element = $this->_form->createElement('submit', 'removecategory', get_string('removeselected', 'quiz'), ' onclick="skipClientValidation = true;"');
		$ret .= $element->toHtml();
		return $ret.'</ul></div>'."\n";
	}

	protected function handle_category_addition(array $indices = null) {
		// at the beginning, indices will contain the indices of the position of the new element
		// later on, indices will point to the children elements of the element that has to be moved
		$first = false;
		$override_key = null;
		if(!$indices) {
			$indices = explode('_', substr($_POST["selected_category"],1));
			$first = true;
		}
		$level = count($indices)-1;
		$continue = true;
		if($first) $first = $indices[$level];
		$_POST_COPY = $_POST; // values will be overwritten
		for($i=$indices[$level]; $i <= 99 && $continue; $i++) {
			$currkey = "c".implode('_', $indices);
			$indices[$level] = $i+1;
			$nextkey = "c".implode('_', $indices);
			if(!isset($_POST[$currkey])) break;
			$continue = isset($_POST[$nextkey]);
			$_POST[$nextkey] = $_POST_COPY[$currkey];
			if($level <= 5) $this->handle_category_addition( array_merge($indices, array('1',)) );
			if($first && $i == $first) $override_key = $nextkey;
		}
		if($first) $_POST[$override_key] = $_POST["newcategory"];
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
}