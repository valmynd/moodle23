<?php

require_once($CFG->dirroot . '/question/editlib.php');
require_once($CFG->dirroot . '/course/format/elatexam/elate_question_bank.php');
/**
 * This class extends the question bank view to show only Exam special Meta Types.
 *
 * @author C.Wilhelm
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

class elate_exam_bank_view extends elate_question_bank_view {
	// TODO: alle klausuren kommen in bestimmte kategorie, welche angelegt wird, wenn es sie noch nicht gibt
	// TODO: Link (Button?) um Question Bank (in neuem Fenster?) zu öffnen
}