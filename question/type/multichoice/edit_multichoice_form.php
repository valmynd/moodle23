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
 * Defines the editing form for the multiple choice question type.
 *
 * @package    qtype
 * @subpackage multichoice
 * @copyright  2007 Jamie Pratt
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


defined('MOODLE_INTERNAL') || die();
global $CFG;
require_once($CFG->dirroot.'/course/format/elatexam/questionlib/elate_question_edit_form.php');

/**
 * Multiple choice editing form definition.
 *
 * @copyright  2007 Jamie Pratt
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class qtype_multichoice_edit_form extends elate_question_edit_form {
    /**
     * Add question-type specific form fields.
     * 
     * Zusätzliche Eingabefelder für
     *  Anzahl anzuzeigender richtiger Antworten
     *  Anzahl anzuzeigender Antworten
     * Antwortalternativen mittels Checkbox als korrekt markieren (keine abgestuften %-Angaben mehr)
     *
     * @param object $mform the form being built.
     */
    protected function definition_inner($mform) {
    	$mform = $this->_form; // use it because of Eclipse introspection
        $menu = array(
            get_string('answersingleno', 'qtype_multichoice'),
            get_string('answersingleyes', 'qtype_multichoice'),
        );
        $mform->addElement('select', 'single',
                get_string('answerhowmany', 'qtype_multichoice'), $menu);
        $mform->setDefault('single', 1);

        // minimal anzuzeigende antwortalternativen (default jeweils 1), insgesamt anzuzeigende antwortalternativen (wenn nichts eingetragen alle)
        // auswahlfeld ob singlechoice oder multichoice muss rein (der server muss wissen ob er die radiobuttons oder die checkboxen nimmt)
        // wenn singlechoice: angeben wie viele antworten insgesamt angezeigt werden sollen
        // wenn multichoice: min/max/insgesamt eingeben lassen; MC: default für negative punkte: 1
        $mform->addElement('text', 'num_shown', get_string('num_shown', 'format_elatexam'), array('size' => 3));
        $mform->addElement('text', 'num_right_min', get_string('num_right_min', 'format_elatexam'), array('size' => 3));
        $mform->addElement('text', 'num_right_max', get_string('num_right_max', 'format_elatexam'), array('size' => 3));
        $mform->addHelpButton('num_shown', 'num_shown', 'format_elatexam');
        $mform->setType('num_right_min', PARAM_INT);
        $mform->setType('num_right_max', PARAM_INT);
        $mform->setType('num_shown', PARAM_INT);
        $mform->setDefault('num_right_min', 1);
        $mform->setDefault('num_right_max', 1);
        $mform->setDefault('num_shown', 0); // default(0):all
        $mform->disabledIf('num_right_min', 'single', 'eq', 1);
        $mform->disabledIf('num_right_max', 'single', 'eq', 1);

        $mform->addElement('advcheckbox', 'shuffleanswers',
                get_string('shuffleanswers', 'qtype_multichoice'), null, null, array(0, 1));
        $mform->addHelpButton('shuffleanswers', 'shuffleanswers', 'qtype_multichoice');
        $mform->setDefault('shuffleanswers', 1);

        // not needed in ElateXam
        /*$mform->addElement('select', 'answernumbering',
                get_string('answernumbering', 'qtype_multichoice'),
                qtype_multichoice::get_numbering_styles());
        $mform->setDefault('answernumbering', 'abc');*/
        $mform->addElement('hidden', 'answernumbering', 'abc');

        $this->add_per_answer_fields($mform, get_string('choiceno', 'qtype_multichoice', '{no}'),
                question_bank::fraction_options_full(), max(5, QUESTION_NUMANS_START));

        $this->add_combined_feedback_fields(true);
        //$mform->disabledIf('shownumcorrect', 'single', 'eq', 1);

        $this->add_interactive_settings(true, true); // call it earlier?
    }

    /** Bewertungsmodus:
     *       *  Reguläre Bewertung
     *              “Negative Punkte für falsche Antworten” (Zahl eingeben, default 0)
     *       *  Unterschiedliche Bewertung:
     *              “negative Punkte für nicht gewählte richtige Antworten” (Zahl eingeben, default 0)
     *              “negative Punkte für gewählte Falschantwort” (Zahl eingeben, default 0)
     * @see elate_question_edit_form::add_interactive_settings()
     */
    protected function add_interactive_settings($withclearwrong = false, $withshownumpartscorrect = false) {
    	$mform = $this->_form;
    	$mform->addElement('header', 'penaltyheader', get_string('penaltyheader', 'format_elatexam'));
    	$mform->addElement('radio', 'assessmentmode', get_string('assessment_reg', 'format_elatexam'), '', 0);
    	$mform->addElement('text', 'penalty', get_string('penaltyforeachincorrecttry', 'format_elatexam'), array('size' => 3));
    	$mform->addElement('radio', 'assessmentmode', get_string('assessment_dif', 'format_elatexam'), '', 1);
    	$mform->addElement('text', 'penalty_empty', get_string('penalty_empty', 'format_elatexam'), array('size' => 3));
    	$mform->addElement('text', 'penalty_wrong', get_string('penalty_wrong', 'format_elatexam'), array('size' => 3));
    	//$mform->insertElementBefore($x, 'generalfeedback'); // we want it at the top
    	$mform->setType('penalty', PARAM_FLOAT);
    	$mform->setType('penalty_empty', PARAM_FLOAT);
    	$mform->setType('penalty_wrong', PARAM_FLOAT);
    	$mform->disabledIf('penalty', 'assessmentmode', 'checked');
    	$mform->disabledIf('penalty_empty', 'assessmentmode', 'nonchecked');
    	$mform->disabledIf('penalty_wrong', 'assessmentmode', 'nonchecked');
    	$mform->setDefault('penalty', 1);
    	$mform->setDefault('penalty_empty', 0);
    	$mform->setDefault('penalty_wrong', 0);
    }

    /**
     * overhaul: overridden as we handle 'penalty' points differently
     * @see elate_question_edit_form::get_per_answer_fields()
     */
    protected function get_per_answer_fields($mform, $label, $gradeoptions, &$repeatedoptions, &$answersoption) {
    	$mform = $this->_form;
        $repeated = array();
        $repeated[] = $mform->createElement('header', 'answerhdr', $label);
        $repeated[] = $mform->createElement('editor', 'answer', get_string('answer', 'question'), array('rows' => 1), $this->editoroptions);
        // instead of percentages, we only want to know whether an answer is correct (100%) or not (0%)
		//$repeated[] = $mform->createElement('select', 'fraction', get_string('grade'), $gradeoptions);
        /*$repeated[] = $mform->createElement('group', '', get_string('grade'), array(
        		0 => $mform->createElement('radio', 'fraction', '', get_string('right', 'format_elatexam'), 100),
        		1 => $mform->createElement('radio', 'fraction', '', get_string('wrong', 'format_elatexam'), 0),
        ));*/
        $repeated[] = $mform->createElement('advcheckbox', 'fraction', get_string('grade'), get_string('right', 'format_elatexam'), array('group' => 1), array(0, 1));
		// we don't need the 'feedback' fields;
        //$repeated[] = $mform->createElement('editor', 'feedback', get_string('feedback', 'question'), array('rows' => 1), $this->editoroptions);
		//$repeated[] = $mform->createElement('hidden', 'feedback', "");
        $this->create_editor_field_replacement('feedback', $repeated);
        $repeatedoptions['answer']['type'] = PARAM_RAW;
        $repeatedoptions['fraction']['default'] = 0;
        $answersoption = 'answers';
        return $repeated;
    }

    protected function data_preprocessing($question) {
        $question = parent::data_preprocessing($question);
        $question = $this->data_preprocessing_answers($question, true);
        $question = $this->data_preprocessing_combined_feedback($question, true);
        $question = $this->data_preprocessing_hints($question, true, true);

        if (!empty($question->options)) {
            $question->single = $question->options->single;
            $question->shuffleanswers = $question->options->shuffleanswers;
            $question->answernumbering = $question->options->answernumbering;
        }

        return $question;
    }

    public function validation($data, $files) {
        $errors = parent::validation($data, $files);
        $answers = $data['answer'];
        $answercount = 0;

        $totalfraction = 0;
        $maxfraction = -1;

        foreach ($answers as $key => $answer) {
            //check no of choices
            $trimmedanswer = trim($answer['text']);
            $fraction = (float) $data['fraction'][$key];
            if (empty($trimmedanswer) && empty($fraction)) {
                continue;
            }
            if (empty($trimmedanswer)) {
                $errors['fraction['.$key.']'] = get_string('errgradesetanswerblank', 'qtype_multichoice');
            }

            $answercount++;

            //check grades
            if ($data['fraction'][$key] > 0) {
                $totalfraction += $data['fraction'][$key];
            }
            if ($data['fraction'][$key] > $maxfraction) {
                $maxfraction = $data['fraction'][$key];
            }
        }

        if ($answercount == 0) {
            $errors['answer[0]'] = get_string('notenoughanswers', 'qtype_multichoice', 2);
            $errors['answer[1]'] = get_string('notenoughanswers', 'qtype_multichoice', 2);
        } else if ($answercount == 1) {
            $errors['answer[1]'] = get_string('notenoughanswers', 'qtype_multichoice', 2);

        }

        /// Perform sanity checks on fractional grades
        if ($data['single']) {
            if ($maxfraction != 1) {
                $errors['fraction[0]'] = get_string('errfractionsnomax', 'qtype_multichoice',
                        $maxfraction * 100);
            }
        } else {
            $totalfraction = round($totalfraction, 2);
            // we need to disable those checks, as ElateXam can only handle correct (100%) and incorrect (0%)
            /*if ($totalfraction != 1) {
                $errors['fraction[0]'] = get_string('errfractionsaddwrong', 'qtype_multichoice',
                        $totalfraction * 100);
            }*/
        }
        return $errors;
    }

    public function qtype() {
        return 'multichoice';
    }
}
