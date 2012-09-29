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

		////// Change some Titles of existing Fields //////////

		$mform->addElement('text', 'name', get_string('title', 'qtype_meta'));
		$mform->addHelpButton('name', 'title', 'qtype_meta');

		/*$mform->getElement('questiontext')->setLabel(get_string('description', 'qtype_meta'));
		$mform->addHelpButton('questiontext', 'description', 'qtype_meta');

		$mform->getElement('generalfeedback')->setLabel(get_string('starttext', 'qtype_meta'));
		$mform->addHelpButton('generalfeedback', 'starttext', 'qtype_meta');*/

		////// Prepend Fields needed for elateXam //////////

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

		////// Append Fields needed for elateXam //////////

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
		$ret = '<div class="examcategorycontainer">'."\n";
		/*return '
		<ul>
			<li id="1">Category</li>
			<li id="2">Category</li>
			<li id="3">Category</li>
			<ul>
				<li id="3_1">Category</li>
				<li id="3_2">Category</li>
				<li id="3_3" class="q">Category</li>
				<li id="3_4" class="q">Category</li>
			</ul>
		</ul>
		'.get_string('addcategory', 'question').'<input type="text"></input>'.get_string('add');*/
		$element = $this->_form->createElement('text', 'addcategory');
		$ret .= get_string('addcategory', 'question') . $element->toHtml();
		$this->_form->registerNoSubmitButton('addcategory');
		$element = $this->_form->createElement('submit', 'addcategory', get_string('add'), ' onclick="skipClientValidation = true;"');
		$ret .= $element->toHtml();
		return $ret.'</div>'."\n";
	}
}