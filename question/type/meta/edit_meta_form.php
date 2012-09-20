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
		$mform->setDefault('time', 0);
		$mform->addHelpButton('time', 'time', 'qtype_meta');

		$mform->insertElementBefore($mform->createElement('text', 'kindnessextensiontime', get_string('kindnessextensiontime', 'qtype_meta'), ' style="width:35px;"'), 'questiontext');
		$mform->setType('kindnessextensiontime', PARAM_INT);
		$mform->setDefault('kindnessextensiontime', 0);
		$mform->addHelpButton('kindnessextensiontime', 'kindnessextensiontime', 'qtype_meta');

		$mform->insertElementBefore($mform->createElement('text', 'tasksperpage', get_string('tasksperpage', 'qtype_meta'), ' style="width:35px;"'), 'questiontext');
		$mform->setType('tasksperpage', PARAM_INT);
		$mform->setDefault('tasksperpage', 0);
		$mform->addHelpButton('tasksperpage', 'tasksperpage', 'qtype_meta');
		
		$mform->insertElementBefore($mform->createElement('text', 'tries', get_string('tries', 'qtype_meta'), ' style="width:35px;"'), 'questiontext');
		$mform->setType('tries', PARAM_INT);
		$mform->setDefault('tries', 0);
		$mform->addHelpButton('tries', 'tries', 'qtype_meta');

		$mform->insertElementBefore($mform->createElement('advcheckbox', 'showhandlinghintsbeforestart', get_string('showhandlinghintsbeforestart', 'qtype_meta'), ""), 'generalfeedback');
		$mform->setDefault('showhandlinghintsbeforestart', true);

		////// Append Fields needed for elateXam //////////

		$mform->addElement('text', 'numberofcorrectors', get_string('numberofcorrectors', 'qtype_meta'), ' style="width:35px;"');
		$mform->setType('numberofcorrectors', PARAM_INT);
		$mform->setDefault('numberofcorrectors', 2);
		$mform->addHelpButton('numberofcorrectors', 'numberofcorrectors', 'qtype_meta');
		
		/* OLD VARIANT, MAYBE WANT THAT LATER:
		$radioarray = array();
		$radioarray[] =& $mform->createElement('radio', 'corrmode', '', get_string('multipleCorrectors', 'qtype_meta'), 'multipleCorrectors');
		$radioarray[] =& $mform->createElement('radio', 'corrmode', '', get_string('regular', 'qtype_meta'), 'regular');
		$radioarray[] =& $mform->createElement('radio', 'corrmode', '', get_string('correctOnlyProcessedTasks', 'qtype_meta'), 'correctOnlyProcessedTasks');
		$mform->addGroup($radioarray, 'radioar', get_string('correctionMode', 'qtype_meta'), '', true);
		$mform->addHelpButton('correctionMode', 'correctionMode', 'qtype_meta');
		// construct from template based on the output from above
		$rbtn_html = <<<EOT
		<div class="fitem fitem_fgroup" id="fgroup_id_radioar">
		<div class="fitemtitle"><div class="fgrouplabel"><label>%s</label></div></div>
		<fieldset class="felement fgroup">
		<div><input id="id_radioar_corrmode_multipleCorrectors" name="radioar[corrmode]" type="radio" value="multipleCorrectors"/><label for="id_radioar_corrmode_multipleCorrectors">%s</label></div>
		<div><input id="id_radioar_corrmode_regular" name="radioar[corrmode]" type="radio" value="regular"/><label for="id_radioar_corrmode_regular">%s</label></div>
		<div><input id="id_radioar_corrmode_correctOnlyProcessedTasks" name="radioar[corrmode]" type="radio" value="correctOnlyProcessedTasks"/><label for="id_radioar_corrmode_correctOnlyProcessedTasks">%s</label></div>
		</fieldset>
		</div>
		EOT;
		$rbtn_html = sprintf($rbtn_html,
				get_string('correctionMode', 'qtype_meta'),
				get_string('multipleCorrectors', 'qtype_meta'),
				get_string('regular', 'qtype_meta'),
				get_string('correctOnlyProcessedTasks', 'qtype_meta'));
		$mform->addElement('html', $rbtn_html);*/

		////// Set Fields which are required to fill out //////////
		$mform->addRule('questiontext', null, 'required', null, 'client');
	}

	public function qtype() {
		return 'meta';
	}
}
