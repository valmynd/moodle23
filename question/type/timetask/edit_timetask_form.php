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
 * Defines the editing form for the timetask question type.
 *
 * @package    qtype
 * @subpackage timetask
 * @license    http://www.gnu.org/copyleft/gpl.html GNU Public License
 */

defined('MOODLE_INTERNAL') || die();
require_once($CFG->dirroot.'/course/format/elatexam/questionlib/elate_question_edit_form.php');

class qtype_timetask_edit_form extends elate_applet_question_edit_form {

	public function qtype() {
		return 'timetask';
	}

	protected function get_innerpath() {
		return "com/spiru/dev/timeTaskProfessor_addon/TimeTaskAddOnApplet.class";
	}

	protected function get_jarname() {
		return "complexTask.jar";
	}
}
