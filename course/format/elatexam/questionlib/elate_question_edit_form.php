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
 * A base class for question editing forms.
 *
 * @package    moodlecore
 * @subpackage questiontypes
 * @copyright  2006 The Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU Public License
 */


defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot.'/question/type/edit_question_form.php');

/**
 * Modified Form definition base class for all question used by ElateXam.
 */
abstract class elate_question_edit_form extends question_edit_form {

    protected function definition() {
        parent::definition();
        // definition_inner(), which is implemented by subclasses, is called by now
        // -> unwanted fields can now be removed via $mform->removeElement()
        $mform = $this->_form;
        switch($this->qtype()) {
        	case 'essay':
        		$mform->removeElement('attachments');
        		$mform->addElement('hidden', 'attachments', 0);
        		$mform->setType('attachments', PARAM_RAW);
        		break;
        }
    }

    protected function add_combined_feedback_fields($withshownumpartscorrect = false) {
        $mform = $this->_form;
        // overridden because we don't need them at all
        foreach (array('correctfeedback', 'partiallycorrectfeedback', 'incorrectfeedback') as $fieldname) {
        	$mform->addElement('hidden', $fieldname, 0);
        	$mform->setType($fieldname, PARAM_RAW);
        }
    }

    protected function add_interactive_settings($withclearwrong = false,
            $withshownumpartscorrect = false) {
        $mform = $this->_form;
        // we want a textfield instead, the rest ain't needed
        $mform->addElement('text', 'penalty', "Punkteabzug für falsche Antworten"); // TODO: get_string()
        $mform->setType('penalty', PARAM_INT);
    }

    protected function data_preprocessing_combined_feedback($question,
            $withshownumcorrect = false) {
        return $question;
    }

    protected function data_preprocessing_hints($question, $withclearwrong = false,
            $withshownumpartscorrect = false) {
        return $question;
    }
}
