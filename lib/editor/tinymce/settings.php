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
 * TinyMCE admin settings
 *
 * @package    editor
 * @subpackage tinymce
 * @copyright  2009 Petr Skoda (http://skodak.org)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

if ($ADMIN->fulltree) {
    $options = array(
        'PSpell'=>'PSpell',
        'GoogleSpell'=>'Google Spell',
        'PSpellShell'=>'PSpellShell');
    $settings->add(new admin_setting_configselect('editor_tinymce/spellengine',
            get_string('spellengine', 'admin'), '', 'GoogleSpell', $options));
    $settings->add(new admin_setting_configtextarea('editor_tinymce/spelllanguagelist',
            get_string('spelllanguagelist', 'admin'), '',
            '+English=en,Danish=da,Dutch=nl,Finnish=fi,French=fr,German=de,Italian=it,Polish=pl,' .
            'Portuguese=pt,Spanish=es,Swedish=sv', PARAM_RAW));
    $settings->add(new admin_setting_configtextarea('editor_tinymce/fontselectlist',
        get_string('fontselectlist', 'editor_tinymce'), '',
        'Trebuchet=Trebuchet MS,Verdana,Arial,Helvetica,sans-serif;Arial=arial,helvetica,sans-serif;Courier New=courier new,courier,monospace;Georgia=georgia,times new roman,times,serif;Tahoma=tahoma,arial,helvetica,sans-serif;Times New Roman=times new roman,times,serif;Verdana=verdana,arial,helvetica,sans-serif;Impact=impact;Wingdings=wingdings', PARAM_RAW));
}
