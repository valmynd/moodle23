<?php
$xml_output = "<?xml version=\"1.0\"?>\n<response>\n";
require_once('../config.php');
require_once('lib.php');
require_login();
$task   = optional_param('task', '', PARAM_ALPHA);
$id     = optional_param('tagid', '', PARAM_INT);
$morexml = '';
$error = '';
if (strlen($task) == 0 || $id <= 0 ) {
    $error = "ungültige Daten";
} else {
    switch ($task) {
        case 'changetype':
            $tagtype   = optional_param('tagtype', '', PARAM_ALPHAEXT);
            if (strlen($tagtype) > 0) {
                $record = new stdClass();
                $record->id         = $id;
                $record->tagtype    = $tagtype;
                if ($DB->record_exists('tag', array('id' => $record->id))) {
                    $DB->update_record('tag', $record);
                    $record = $DB->get_record('tag', array('id'=>$record->id));
                    $morexml .= "<name>".$record->rawname."</name>\n";
                    if ($record->tagtype == 'official_text' || $record->tagtype == 'official_select') {
                        $morexml .= "<description>".$record->description."</description>\n";
                    }
                    
                } else {
                    $error = "Tag existiert nicht in der Datenbank.";
                }
            } else {
                $error = "Tag-Typ nicht definiert";
            }
            break;
        case 'editname':
            $newname   = addslashes(optional_param('newname', '', PARAM_RAW));
            if (strlen($newname) > 0) {
                if ($DB->record_exists('tag', array('id' => $id))) {
                    if (!tag_rename($id, $newname) ) {
                        $error .= $newname. '-- ' . get_string('namesalreadybeeingused','tag');
                    }
                    $record = $DB->get_record('tag', array('id'=>$id));
                    $morexml .= "<name>".$record->rawname."</name>\n";
                    
                } else {
                    $error = "Tag existiert nicht in der Datenbank.";
                }
            } else {
                $error = "Kein Name übergeben";
            }
            break;
        case 'deletetag':
            if ($DB->record_exists('tag', array('id' => $id))) {
                if (!tag_delete($id) ) {
                    $error = 'Tag konnte nicht gelöscht werden.';
                }
            } else {
                $error = "Tag existiert nicht in der Datenbank.";
            }
            break;
        case 'writepredef':
            $newdesc   = addslashes(optional_param('newdesc', '', PARAM_RAW));
            $newdesc = str_replace(array(", ",",,", " ,"),array(",",",",","), $newdesc);
            $record = new stdClass();
            $record->id             = $id;
            $record->description    = $newdesc;
            if ($DB->record_exists('tag', array('id' => $record->id))) {
                $DB->update_record('tag', $record);
                $record = $DB->get_record('tag', array('id'=>$record->id));
                $morexml .= "<name>".$record->rawname."</name>\n";
            } else {
                $error = "Tag existiert nicht in der Datenbank.";
            }
            break;
        case 'setinstances':
            $instance   = addslashes(optional_param('newval', '', PARAM_RAW));
            if (strlen($instance) > 0) {
                //1.übermittelte Instanz vergleichen mit hinterlegter berechneter - instance-missmatch?
                $record = $DB->get_record('tag', array('id'=>$id));
                $komma = strpos($record->description,",");
                if ($komma > 0) {
                    $description = substr($record->description,0,$komma);
                } else {
                    $description = $record->description;
                }
                
                if ($description == $instance) {
                    //2.überprüfen ob Instanz existiert,-->id
                    if (0 >= $DB->count_records_sql('SELECT COUNT(*) FROM {tag} WHERE name = ? OR rawname = ?', array($record->name.'='.$description,$record->name.'='.$description))) {//3.instanz erstellen-->id
                        require_capability('moodle/tag:create', get_context_instance(CONTEXT_SYSTEM));
                        tag_add($record->name."=".$description, 'default');
                    }
                    $instance_record = $DB->get_record_sql('SELECT * FROM {tag} WHERE name = ? OR rawname = ?', array($record->name."=".$description,$record->name."=".$description));
                    if ($instance_record->id > 0) {
                        //4.tag_instance-update/insert für alle questions
                        $questionsbefore = $DB->count_records_sql("SELECT COUNT(DISTINCT (ti.itemid)) FROM {tag_instance} ti LEFT JOIN {tag} tg ON ti.tagid = tg.id WHERE tg.name LIKE ? AND ti.itemtype = 'question'",array($record->name."=%"));
                        $conn = mysql_connect($CFG->dbhost, $CFG->dbuser,$CFG->dbpass);
                        mysql_select_db($CFG->dbname, $conn);
                        $sql = "INSERT INTO `".$CFG->prefix."tag_instance` (tagid, itemtype, itemid, ordering)
SELECT ".$instance_record->id.", 'question', id, 0 
FROM `".$CFG->prefix."question`
WHERE id NOT IN ( 
SELECT DISTINCT (ti.itemid) 
FROM `".$CFG->prefix."tag_instance` ti LEFT JOIN `".$CFG->prefix."tag` tg ON ti.tagid = tg.id
WHERE tg.name LIKE '".$record->name."=%' AND ti.itemtype = 'question') ";
                        if (mysql_query($sql)) {
                            $questionsafter = $DB->count_records_sql("SELECT COUNT(DISTINCT (ti.itemid)) FROM {tag_instance} ti LEFT JOIN {tag} tg ON ti.tagid = tg.id WHERE tg.name LIKE ? AND ti.itemtype = 'question'",array($record->name."=%"));
                        
                            $morexml .= "<name>".$record->rawname."</name>\n";
                            $morexml .= "<instance>".$instance_record->rawname."</instance>\n";
                            $morexml .= "<created>".($questionsafter - $questionsbefore)."</created>\n";
                            $morexml .= "<newcount>".$questionsafter."</newcount>\n";
                        } else {
                            $error = "Datenbankfehler beim Updaten";
                        }
                        mysql_close($conn);
                        
                    } else {
                        $error = "Erstellen der Instanz ".$record->name."=".$description." fehlgeschlagen.";
                    }
                } else {
                    $error = "Instanz-Missmatch: Übermittelte Ausprägung stimmt nicht mit der vom Server berechneten Ausprägung des Tags überein. Übermittelt:$instance , Server:$description";
                }
            } else {
                $error = "Keine Ausprägung des Tags übermittelt.";
            }

                
            break;
        default:
            $error = "Task muss definiert sein!";
            break;
    }

}
if (strlen($error)>0) {
    $xml_output .= "<status>".$error."</status>\n";
} else {
    $xml_output .= "<status>ok</status>\n";
    $xml_output .= $morexml;
}
header("Content-type: text/xml");
echo $xml_output."</response>";
?>