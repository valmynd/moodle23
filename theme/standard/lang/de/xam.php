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
 * Strings for moodlexam extensions, language 'de', branch 'MOODLE_20_STABLE'
 *
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$string['newtag'] = 'Ein neues Schlagwort wurde hinzugefügt: {$a}<br />';
$string['default'] = 'Standard';
$string['official_text'] = 'Vorgegebenes Text-Schlagwort';
$string['official_select'] = 'Vorgegebenes Auswahl-Schlagwort';
$string['tags_count'] = 'In dieser Spalte steht die Anzahl wie oft jedes Schlagwort vorkommt. Bei den vorgegebenen Schlagwörtern wird die Anzahl der Fragen gezählt die mindestens eine Taginstanz eines vorgebenen Schlagworts haben.';
$string['add_tag'] = 'Schlagwort hinzufügen';
$string['tag_search_by_name'] = 'Tag nach Name suchen';
$string['search_refresh'] = 'Suchen/Liste aktualisieren';
$string['save_by_hand'] = 'Änderungen manuell speichern';
$string['ask_onchange'] = 'Frage zum Speichern per onChange auslösen';
$string['save_onchange'] = 'Änderungen per onChange sofort in die Datenbank schreiben';
$string['instant_del'] = 'Tag ohne Nachfrage löschen.';
$string['off_text_title'] = 'Standardwert für dieses Textfeld.';
$string['off_select_title'] = 'Auswahlmöglichkeiten. Werte (nur) durch ein Komma getrennt eingeben. Der erste Wert ist vorausgewählt.';
$string['write_to_db'] = 'Änderung in die Datenbank schreiben?';
$string['changed_tagtype_1'] = 'Tagtyp für ';
$string['changed_tagtype_2'] = ' erfolgreich aktualisiert.';
$string['saved'] = 'Gespeichert';
$string['not_saved_save_now'] = 'Noch nicht gespeichert. Jetzt speichern?';
$string['new_tagname_1'] = 'Der neue Tagname ';
$string['new_tagname_2'] = ' wurde erfolgreich gespeichert.';
$string['tag_std_desc_1'] = 'Tag-Standardbelegung für ';
$string['tag_std_desc_2'] = ' erfolgreich aktualisiert. Bitte denken Sie daran bei den Standard-Tags geänderte Ausprägungen dieses Tags zu ändern.';
$string['no_std_desc'] = 'Es ist kein Standardwert eingetragen.';
$string['no_tagname'] = 'Es wurde kein Tagname gefunden.';
$string['set_instance_1'] = 'Soll für alle Fragen eine Instanz des Tags ';
$string['set_instance_2'] = ' zugewiesen werden?\n Fragen die bereits ein Tag mit ';
$string['set_instance_3'] = ' haben, bekommen keine Instanz zugewiesen.';
$string['set_instance_ok_1'] = 'Es wurde zu allen Fragen die keine Instanz des Tags ';
$string['set_instance_ok_2'] = ' hatten ein Tag ';
$string['set_instance_ok_3'] = ' hinzugefügt. Anzahl geänderter Fragen: ';
$string['all_instance_title'] = 'Jede vorkommende Frage besitzt eine Instanz dieses Tags.';
$string['delete_tag'] = 'Tag und jedes Auftreten dieses Tags wirklich löschen?';
$string['deleted_tag'] = 'Tag wurde erfolgreich gelöscht.';
$string['new_tagname'] = 'neuer Tagname';
$string['change_tagname'] = 'Tagname ändern';
$string['questions_without_instance'] = 'Es gibt Fragen ohne eine Instanz dieses Tags. Mit einem Klick wird allen Fragen eine Instanz dieses Tags hinzugefügt.';
$string['del_this_tag'] = 'Diesen Tag löschen';
//ajax strings
$string['missing_data'] = 'ungültige Daten';
$string['not_existing_tag'] = 'Tag existiert nicht in der Datenbank.';
$string['undefined_tagtype'] = 'Tag-Typ nicht definiert';
$string['missing_tagname'] = 'Kein Tagname übergeben.';
$string['tag_del_error'] = 'Tag konnte nicht gelöscht werden.';
$string['db_error_update'] = 'Datenbankfehler beim erstellen der Instanzen.';
$string['error_create_instanz_1'] = 'Erstellen der Instanz ';
$string['error_create_instanz_2'] = ' fehlgeschlagen.';
$string['instance_missmatch'] = 'Instanz-Missmatch: Übermittelte Ausprägung stimmt nicht mit der vom Server berechneten Ausprägung des Tags überein. Übermittelt:';
$string['missing_instance'] = 'Keine Instanz des Tags übermittelt.';
$string['wrong_task'] = 'Task muss korrekt definiert sein!';