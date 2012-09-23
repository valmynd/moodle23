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
 * Strings for component 'qtype_comparetexttask', language 'en', branch 'MOODLE_20_STABLE'
 *
 * @package    qtype
 * @subpackage comparetexttask
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$string['nosaincategory'] = 'There are no short answer questions in the category that you chose \'{$a->catname}\'. Choose a different category, make some questions in this category.';
$string['notenoughsaincategory'] = 'There is/are only {$a->nosaquestions} short answer questions in the category that you chose \'{$a->catname}\'. Choose a different category, make some more questions in this category or reduce the amount of questions you\'ve selected.';
$string['pluginname'] = 'CompareTextTask'; // TODO: Configure
$string['pluginname_help'] = 'From the student perspective, this looks just like a matching question. The difference is that the list of names or statements (questions) for matching are drawn randomly from the short answer questions in the current category. There should be sufficient unused short answer questions in the category, otherwise an error message will be displayed.';
$string['pluginname_link'] = 'question/type/comparetexttask';
$string['pluginnameadding'] = 'Adding a CompareTextTask question';
$string['pluginnameediting'] = 'Editing a CompareTextTask question';
$string['pluginnamesummary'] = 'Like a Matching question, but created randomly from the short answer questions in a particular category.';