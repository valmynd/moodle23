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
 * Defines the editing form for the meta question type.
 *
 * @package	qtype
 * @subpackage meta
 * @license	http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


defined('MOODLE_INTERNAL') || die();

class qtype_meta_edit_form extends question_edit_form {
	
	protected function definition_inner($mform) {
		// the same procedure as in description plugin: "We don't need this default element."
		$mform->removeElement('defaultmark');
		$mform->addElement('hidden', 'defaultmark', 0);
		$mform->setType('defaultmark', PARAM_RAW);

		////// Change some Titles of existing Fields //////////

		$mform->getElement('name')->setLabel(get_string('title', 'qtype_meta'));
		$mform->addHelpButton('name', 'title', 'qtype_meta');

		$mform->getElement('questiontext')->setLabel(get_string('description', 'qtype_meta'));
		$mform->addHelpButton('questiontext', 'description', 'qtype_meta');

		$mform->getElement('generalfeedback')->setLabel(get_string('starttext', 'qtype_meta'));
		$mform->addHelpButton('generalfeedback', 'starttext', 'qtype_meta');

		////// Prepend Fields needed for elateXam //////////

		$mform->insertElementBefore($mform->createElement('text', 'time', get_string('time', 'qtype_meta'), ' style="width:35px;"'), 'questiontext');
		$mform->setType('time', PARAM_INT);
		//$mform->setDefault('time', 0);
		$mform->addHelpButton('time', 'time', 'qtype_meta');

		$mform->insertElementBefore($mform->createElement('text', 'kindnessextensiontime', get_string('kindnessextensiontime', 'qtype_meta'), ' style="width:35px;"'), 'questiontext');
		$mform->setType('kindnessextensiontime', PARAM_INT);
		$mform->setDefault('kindnessextensiontime', 0);
		$mform->addHelpButton('kindnessextensiontime', 'kindnessextensiontime', 'qtype_meta');

		$mform->insertElementBefore($mform->createElement('text', 'tasksperpage', get_string('tasksperpage', 'qtype_meta'), ' style="width:35px;"'), 'questiontext');
		$mform->setType('tasksperpage', PARAM_INT);
		$mform->setDefault('tasksperpage', 2);
		$mform->addHelpButton('tasksperpage', 'tasksperpage', 'qtype_meta');
		
		$mform->insertElementBefore($mform->createElement('text', 'tries', get_string('tries', 'qtype_meta'), ' style="width:35px;"'), 'questiontext');
		$mform->setType('tries', PARAM_INT);
		$mform->setDefault('tries', 1);
		$mform->addHelpButton('tries', 'tries', 'qtype_meta');

		$mform->insertElementBefore($mform->createElement('advcheckbox', 'showhandlinghintsbeforestart', get_string('showhandlinghintsbeforestart', 'qtype_meta'), ""), 'generalfeedback');
		$mform->setDefault('showhandlinghintsbeforestart', true);

		////// Append Fields needed for elateXam //////////

		$mform->addElement('text', 'numberofcorrectors', get_string('numberofcorrectors', 'qtype_meta'), ' style="width:35px;"');
		$mform->setType('numberofcorrectors', PARAM_INT);
		$mform->setDefault('numberofcorrectors', 2);
		$mform->addHelpButton('numberofcorrectors', 'numberofcorrectors', 'qtype_meta');

		////// Set Fields which are required to fill out //////////
		//$mform->addRule('questiontext', null, 'required', null, 'client');
		$mform->addRule('time', null, 'required', null, 'client');
		$this->define_question_selection();
	}
	
	public function define_question_selection() {
		$this->_form->addElement('header', 'qheader', get_string('qheader', 'qtype_meta'));
		//$select = &$this->_form->addElement('select', 'colors', get_string('colors'), array('red', 'blue', 'green'), array('size', 3));
		
	}

	protected function definition() {
		parent::definition();
		// Tags etc. would need to be removed here
		//$this->_form->removeElement('tags');
		//$this->_form->removeElement('tagsheader');
	}

	public function qtype() {
		return 'meta';
	}
}
