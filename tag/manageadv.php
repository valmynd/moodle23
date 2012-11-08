<?php
require_once('../config.php');
require_once($CFG->libdir.'/tablelib.php');
require_once('lib.php');

define('SHOW_ALL_PAGE_SIZE', 50000);
define('DEFAULT_PAGE_SIZE', 30);
require_login();

$action       = optional_param('action', '', PARAM_ALPHA);
$perpage      = optional_param('perpage', DEFAULT_PAGE_SIZE, PARAM_INT);
$sel_tagtypes = optional_param_array('tabletagtypes', array(), PARAM_ALPHAEXT);
$changeIm     = optional_param('changeIm', '', PARAM_ALPHA);
$search       = addslashes(optional_param('search', '', PARAM_RAW));
if (count($sel_tagtypes)>0) {
    $SESSION->search = $search;
} elseif (isset($SESSION->search)) {
    $search = $SESSION->search;
} else {
    $search = '';
}
if (count($sel_tagtypes)>0) {
    $SESSION->seltagtypes = $sel_tagtypes;
} elseif (isset($SESSION->seltagtypes)) {
    $sel_tagtypes = $SESSION->seltagtypes;
} else {
    $sel_tagtypes = array('official_text','official_select');
}
if (strlen($changeIm)>0) {
    $SESSION->changeIm = $changeIm;
} elseif (isset($SESSION->changeIm)) {
    $changeIm = $SESSION->changeIm;
} else {
    $changeIm = 'ask';
}
if (empty($CFG->usetags)) {
    print_error('tagsaredisabled', 'tag');
}

$systemcontext = get_context_instance(CONTEXT_SYSTEM);
require_capability('moodle/tag:manage', $systemcontext);

$params = array();
if ($perpage != DEFAULT_PAGE_SIZE) {
    $params['perpage'] = $perpage;
}
$PAGE->set_url('/tag/manage.php', $params);
$PAGE->set_context($systemcontext);
$PAGE->set_blocks_editing_capability('moodle/tag:editblocks');
$PAGE->navbar->add(get_string('tags', 'tag'), new moodle_url('/tag/search.php'));
$PAGE->navbar->add(get_string('managetags', 'tag'));
$PAGE->set_title(get_string('managetags', 'tag'));
$PAGE->set_heading($COURSE->fullname);
$PAGE->set_pagelayout('standard');
echo $OUTPUT->header();

$err_notice = '';
$notice = '';
if ($action == 'addtag') {
    if (!data_submitted() or !confirm_sesskey()) {
        break;
    }
    $new_otags = explode(',', optional_param('otagsadd', '', PARAM_TAG));
    foreach ( $new_otags as $new_otag ) {
        if ( $new_otag_id = tag_get_id($new_otag) ) {
            $err_notice .= $new_otag. '-- ' . get_string('namesalreadybeeingused','tag').'<br />';
        } else {
            require_capability('moodle/tag:create', get_context_instance(CONTEXT_SYSTEM));
            tag_add($new_otag, optional_param('tagtype', 'official_text', PARAM_ALPHAEXT));
        }
        $notice .= 'Ein neues Schlagwort wurde hinzugefügt: '.$new_otag. '<br />';
    }
}

if ($err_notice) {
    echo $OUTPUT->notification($err_notice, 'red');
}
if ($notice) {
    echo $OUTPUT->notification($notice, 'green');
}
// get all the possible tag types from db
$existing_tagtypes = array();
if ($ptypes = $DB->get_records_sql("SELECT DISTINCT(tagtype) FROM {tag} ORDER BY tagtype")) {
    foreach ($ptypes as $ptype) {
        $existing_tagtypes[$ptype->tagtype] = $ptype->tagtype;
    }
}

$existing_tagtypes['default'] = 'Standard';
$existing_tagtypes['official'] = get_string('tagtype_official', 'tag');
$existing_tagtypes['official_text'] = 'Vorgegebenes Text-Schlagwort';
$existing_tagtypes['official_select'] = 'Vorgegebenes Auswahl-Schlagwort';

//setup table
$tablecolumns = array('id','name','tagtype','count','timemodified');
$tableheaders = array(get_string('id', 'tag'),get_string('name', 'tag'),get_string('tagtype', 'tag'),'<span title="In dieser Spalte steht die Anzahl wie oft jedes Schlagwort vorkommt. Bei den vorgegebenen Schlagwörtern wird die Anzahl der Fragen gezählt die mindestens eine Taginstanz eines vorgebenen Schlagworts haben.">'.get_string('count', 'tag').'</span>',get_string('timemodified', 'tag'));

$table = new flexible_table('tag-management-list-'.$USER->id);

$baseurl = $CFG->wwwroot.'/tag/manage.php?perpage='.$perpage;

$table->define_columns($tablecolumns);
$table->define_headers($tableheaders);
$table->define_baseurl($baseurl);

$table->sortable(true, 'flag', SORT_DESC);

$table->set_attribute('cellspacing', '0');
$table->set_attribute('id', 'tag-management-list');
$table->set_attribute('class', 'generaltable generalbox');

$table->set_control_variables(array(
TABLE_VAR_SORT    => 'ssort',
TABLE_VAR_HIDE    => 'shide',
TABLE_VAR_SHOW    => 'sshow',
TABLE_VAR_IFIRST  => 'sifirst',
TABLE_VAR_ILAST   => 'silast',
TABLE_VAR_PAGE    => 'spage'
));

$table->setup();

if ($table->get_sql_sort()) {
    $sort = 'ORDER BY '. $table->get_sql_sort();
} else {
    $sort = '';
}

list($where, $params) = $table->get_sql_where();
if ($where) {
    $where = 'AND '. $where;
} 
$wsearch = "";
if (strlen($search)>0) {
    $wsearch = "AND tg.name LIKE '%$search%' ";
}

list($insql, $inparams) = $DB->get_in_or_equal($sel_tagtypes);
$params = array_merge($params, $inparams);
$query = "
        SELECT tg.id, tg.name, tg.rawname, tg.tagtype, tg.flag, tg.timemodified, tg.description,
               COUNT(ti.id) AS count
          FROM {tag} tg
     LEFT JOIN {tag_instance} ti ON ti.tagid = tg.id
         WHERE tg.tagtype $insql
               $where
               $wsearch
      GROUP BY tg.id, tg.name, tg.rawname, tg.tagtype, tg.flag, tg.timemodified
         $sort";

$totalcount = $DB->count_records_sql("
        SELECT COUNT(DISTINCT(tg.id))
          FROM {tag} tg
        WHERE tg.tagtype $insql
              $where
              $wsearch", $params);
$questioncount = $DB->count_records_sql("
        SELECT COUNT(DISTINCT(tg.id))
          FROM {question} tg");
//$table->initialbars(true); // always initial bars
$table->pagesize($perpage, $totalcount);
?>
<form class="tag-management-form" method="post" action="<?php echo $CFG->wwwroot ?>/tag/manage.php">
    <input type="hidden" name="action" value="addtag" />
    <div class="tag-management-form generalbox"><label class="accesshide" for="id_otagsadd">Schlagwort hinzufügen</label>
        <input name="otagsadd" id="id_otagsadd" type="text" />
            <?php echo html_writer::select($existing_tagtypes, 'tagtype', 'official_select', false) ?>
        <input type="hidden" name="sesskey" value="<?php echo sesskey() ?>" />
        <input name="addotags" value="Schlagwort hinzufügen" onclick="skipClientValidation = true;" id="id_addotags" type="submit" />
    </div>
</form>
<form class="tag-management-form" method="post" action="<?php echo $CFG->wwwroot?>/tag/manage.php"><div>
<div class="list_control">
    <div>
        <label for="search">Tag nach Name suchen</label><br />
        <input type="text" value="<?php echo $search ?>" id="search" name="search" />
        <br /><input type="submit" value="Suchen/Liste aktualisieren" />
        
    </div>
    <div>
        <?php echo  html_writer::select($existing_tagtypes, 'tabletagtypes[]',$sel_tagtypes, false, array('multiple' => 'true', 'size' => 4)) ?>
    </div>
    <div style="text-align: left;padding-left: 5px;">
        <label><input type="radio" name="changeIm" value="wait" <?php echo $changeIm == 'wait' ? 'checked="true"' : '' ?> /> Änderungen manuell speichern</label><br />
        <label><input type="radio" name="changeIm" value="ask" <?php echo $changeIm == 'ask' ? 'checked="true"' : '' ?> /> Frage zum Speichern per onChange auslösen</label><br />
        <label><input type="radio" name="changeIm" value="immediatly" <?php echo $changeIm == 'immediatly' ? 'checked="true"' : '' ?> /> Änderungen per onChange sofort in die Datenbank schreiben</label><br />
        <label><input type="checkbox" name="dwq" value="y" /> Tag ohne Nachfrage löschen.</label>
    </div>
</div>
<div class="log_container"><div id="log"></div></div>
<script type="text/javascript">
/* mehrfach genutzte Texte */
var off_text_title      = "Standardwert für dieses Textfeld.";
var off_select_title    = "Auswahlmöglichkeiten. Werte (nur) durch ein Komma getrennt eingeben. Der erste Wert ist vorausgewählt.";
var write_to_db         = "Änderung in die Datenbank schreiben?";

var lastaction          = "";

function changetype (newtype, tagid, oc) {
    if (lastaction != newtype+''+tagid) {
        var writechange = false;
        if ($('input[name=changeIm]:checked').val() == 'immediatly' || oc == 0) {
            writechange = true;
        } else {
            if ($('input[name=changeIm]:checked').val() == 'ask') {
                writechange = confirm(write_to_db);
            }
        }
        if(writechange) {
            lastaction = newtype+''+tagid;
            var log = $('#log');
            log.attr("class","");
            log.html('<image src="../pix/i/ajaxloader.gif" alt="loading..." />');
            $('#ttsave'+tagid).html('<image src="../pix/i/ajaxloader.gif" title="loading..." alt="loading..."/>');
            $.ajax({
                url: '<?php echo $CFG->wwwroot ?>/tag/manage_ajax.php',			
                data: 'task=changetype&tagid='+tagid+'&tagtype='+newtype,
                dataType: 'xml',
                cache: false,
                error: function(exhr, txtStat, errorThrown){log.html(errorThrown); log.attr("class","ajax_failed");lastaction = '';}, 
                success: function (xmlResp) {
                    $('#desc'+tagid).html('');
                    if ($(xmlResp).find('status').eq(0).text() == 'ok') {
                        log.html('Tagtyp für \''+$(xmlResp).find('name').eq(0).text()+'\' erfolgreich aktualisiert.');
                        log.attr("class","ajax_success");
                        if (newtype == 'official_text') {
                            createInput(tagid,30,$(xmlResp).find('description').eq(0).text(),off_text_title);
                        }
                        if (newtype == 'official_select') {
                            createInput(tagid,40,$(xmlResp).find('description').eq(0).text(),off_select_title);
                        }
                        $('#ttsave'+tagid).html('<image src="../pix/i/tick_green_small.gif" title="Gespeichert" alt="Gespeichert"/>');
                    } else {
                        log.html($(xmlResp).find('status').eq(0).text());
                        log.attr("class","ajax_failed");
                        lastaction = '';
                    }
                }
            });
        } else {
            $('#desc'+tagid).html('<image src="../pix/i/portfolio.gif" class="save_text" onclick="changetype(\''+newtype+'\', '+tagid+', 0)" title="Noch nicht gespeichert. Jetzt speichern?" alt="Noch nicht gespeichert. Jetzt speichern?"/>');
        }
    }
}
function newtagname(tagid, oc) {
    var newname = $('#tagedit'+tagid).val();
    if (lastaction != newname+''+tagid) {
        var writechange = false;
        if ($('input[name=changeIm]:checked').val() == 'immediatly' || oc == 0) {
            writechange = true;
        } else {
            if ($('input[name=changeIm]:checked').val() == 'ask') {
                writechange = confirm(write_to_db);
            }
        }
        if(writechange) {
            lastaction = newname+''+tagid;
            var log = $('#log');
            log.attr("class","");
            log.html('<image src="../pix/i/ajaxloader.gif" alt="loading..." />');
            $('#edited'+tagid).html('<image src="../pix/i/ajaxloader.gif" title="loading..." alt="loading..."/>');
            $.ajax({
                url: '<?php echo $CFG->wwwroot ?>/tag/manage_ajax.php',			
                data: 'task=editname&tagid='+tagid+'&newname='+escape(newname),
                dataType: 'xml',
                cache: false,
                error: function(exhr, txtStat, errorThrown){log.html(errorThrown); log.attr("class","ajax_failed"); lastaction= '';}, 
                success: function (xmlResp) {
                    if ($(xmlResp).find('status').eq(0).text() == 'ok') {
                        log.html('Der neue Tagname \''+$(xmlResp).find('name').eq(0).text()+'\' wurde erfolgreich gespeichert.');
                        log.attr("class","ajax_success");
                        $('#nametag'+tagid).val($(xmlResp).find('name').eq(0).text());
                        $('#edited'+tagid).html('<image src="../pix/i/tick_green_small.gif" title="Gespeichert" alt="Gespeichert"/>');
                    } else {
                        log.html($(xmlResp).find('status').eq(0).text());
                        log.attr("class","ajax_failed");
                        lastaction = '';
                    }
                }
            });
        }
    }
}
function writepredef (tagid, oc) {
    if (lastaction != $('#tagtext'+tagid).val()+''+tagid) {
        var writechange = false;
        if ($('input[name=changeIm]:checked').val() == 'immediatly' || oc == 0) {
            writechange = true;
        } else {
            if ($('input[name=changeIm]:checked').val() == 'ask') {
                writechange = confirm(write_to_db);
            }
        }
        if(writechange) {
            lastaction = $('#tagtext'+tagid).val()+''+tagid;
            var log = $('#log');
            log.attr("class","");
            log.html('<image src="../pix/i/ajaxloader.gif" alt="loading..." />');
            $('#ttsave'+tagid).html('<image src="../pix/i/ajaxloader.gif" title="loading..." alt="loading..."/>');
            $.ajax({
                url: '<?php echo $CFG->wwwroot ?>/tag/manage_ajax.php',			
                data: 'task=writepredef&tagid='+tagid+'&newdesc='+escape($('#tagtext'+tagid).val()),
                dataType: 'xml',
                cache: false,
                error: function(exhr, txtStat, errorThrown){log.html(errorThrown); log.attr("class","ajax_failed");lastaction = '';}, 
                success: function (xmlResp) {
                    if ($(xmlResp).find('status').eq(0).text() == 'ok') {
                        log.html('Tag-Standardbelegung für \''+$(xmlResp).find('name').eq(0).text()+'\' erfolgreich aktualisiert. Bitte denken Sie daran bei den Standard-Tags geänderte Ausprägungen dieses Tags zu ändern.');
                        log.attr("class","ajax_success");
                        $('#ttsave'+tagid).html('<image src="../pix/i/tick_green_small.gif" title="Gespeichert" alt="Gespeichert"/>');
                    } else {
                        log.html($(xmlResp).find('status').eq(0).text());
                        log.attr("class","ajax_failed");
                        lastaction = '';
                    }
                }
            });
        }
    }
}
function setinstances(tagid) {
    var name = $('#nametag'+tagid).val();
    var komma = $('#tagtext'+tagid).val().indexOf(",");
    if (komma > 0) {
        var standardwert = $('#tagtext'+tagid).val().substr(0, komma);
    } else {
        var standardwert = $('#tagtext'+tagid).val();
    }
    if (standardwert.length == 0) {
        alert('Es ist kein Standardwert eingetragen.')
    } else {
        if (name.length == 0) {
            alert('Es wurde kein Tagname gefunden.')
        } else {
            var writechange = confirm('Soll für alle Fragen eine Instanz des Tags "'+name.toLowerCase()+'='+standardwert+'" zugewiesen werden?\n Fragen die bereits ein Tag mit "'+name.toLowerCase()+'=" haben, bekommen keine Instanz zugewiesen.');
            if(writechange) {
                var log = $('#log');
                log.attr("class","");
                log.html('<image src="../pix/i/ajaxloader.gif" alt="loading..." />');
                $('#setallquest'+tagid).html('<image src="../pix/i/ajaxloader.gif" title="loading..." alt="loading..."/>');
                $.ajax({
                    url: '<?php echo $CFG->wwwroot ?>/tag/manage_ajax.php',			
                    data: 'task=setinstances&tagid='+tagid+'&newval='+escape(standardwert),
                    dataType: 'xml',
                    cache: false,
                    error: function(exhr, txtStat, errorThrown){log.html(errorThrown); log.attr("class","ajax_failed");}, 
                    success: function (xmlResp) {
                        if ($(xmlResp).find('status').eq(0).text() == 'ok') {
                            log.html('Es wurde zu allen Fragen die keine Instanz des Tags \''+$(xmlResp).find('name').eq(0).text()+'\' hatten ein Tag \''+$(xmlResp).find('instance').eq(0).text()+'\' hinzugefügt. Anzahl geänderter Fragen: '+$(xmlResp).find('created').eq(0).text());
                            log.attr("class","ajax_success");
                            $('#setallquest'+tagid).parent().html($(xmlResp).find('newcount').eq(0).text()+'<span style="float:right;"><img src="../pix/i/flagged.png" title="Jede vorkommende Frage besitzt eine Instanz dieses Tags. (Bei korrekter Ausführung der Cron-Jobs!)" /> </span>');
                        } else {
                            log.html($(xmlResp).find('status').eq(0).text());
                            log.attr("class","ajax_failed");
                        }
                    }
                });
            }
            
        }
    }
}

function deltag (tagid) {
    if ($('input[name=dwq]').is(':checked')) {
        var writechange = true;
    } else {
        var writechange = confirm('Tag und jedes Auftreten dieses Tags wirklich löschen?');
    }
    if(writechange) {
        var log = $('#log');
        log.attr("class","");
        log.html('<image src="../pix/i/ajaxloader.gif" alt="loading..." />');
        $('#deltag'+tagid).html('<image src="../pix/i/ajaxloader.gif" title="loading..." alt="loading..."/>');
        $.ajax({
            url: '<?php echo $CFG->wwwroot ?>/tag/manage_ajax.php',			
            data: 'task=deletetag&tagid='+tagid,
            dataType: 'xml',
            cache: false,
            error: function(exhr, txtStat, errorThrown){log.html(errorThrown); log.attr("class","ajax_failed");}, 
            success: function (xmlResp) {
                if ($(xmlResp).find('status').eq(0).text() == 'ok') {
                    log.html('Tag wurde erfolgreich gelöscht.');
                    log.attr("class","ajax_success");
                    $('#deltag'+tagid).closest("tr").fadeOut(1000,function(){$('#deltag'+tagid).closest("tr").remove();});
                } else {
                    log.html($(xmlResp).find('status').eq(0).text());
                    log.attr("class","ajax_failed");
                }
            }
        });
    }
}

function edittag(edtid) {
    $('#edit'+edtid).html('<input type="text" size="30" value="'+$('#nametag'+edtid).val()+'" id="tagedit'+edtid+'" title="neuer Tagname (jedes Vorkommen wird damit geändert)" onkeydown="savepic2('+edtid+')" onchange="newtagname('+edtid+', 1)" />');
}
function createInput(ciTagid, ciSize, ciValue, ciTitle) {
    $('#desc'+ciTagid).html('<input type="text" size="'+ciSize+'" value="'+ciValue+'" id="tagtext'+ciTagid+'" title="'+ciTitle+'" onkeydown="savepic('+ciTagid+')" onchange="writepredef('+ciTagid+', 1)" />');
}
function savepic(spTagid) {
    $('#ttsave'+spTagid).html('<image class="save_text" src="../pix/i/portfolio.gif" onclick="writepredef('+spTagid+', 0)" title="Noch nicht gespeichert. Jetzt speichern?" alt="Noch nicht gespeichert. Jetzt speichern?"/>');
}
function savepic2(spTagid) {
    $('#edited'+spTagid).html('<image class="save_text" src="../pix/i/portfolio.gif" onclick="newtagname('+spTagid+', 0)" title="Noch nicht gespeichert. Jetzt speichern?" alt="Noch nicht gespeichert. Jetzt speichern?"/>');
}
</script>
<?php
//retrieve tags from DB
$inputs = array();
if ($tagrecords = $DB->get_records_sql($query, $params, $table->get_page_start(),  $table->get_page_size())) {

    //populate table with data
    foreach ($tagrecords as $tag) {
        $id             =   $tag->id;
        $name           =   '<span id="edit'.$tag->id.'">'.tag_display_name($tag).'</span> <span id="edited'.
                            $tag->id.'"></span> <img src="../pix/t/edit.gif" title="Tagname ändern" style="cursor:pointer;" onclick="edittag('.
                            $tag->id.')" /><input type="hidden" id="nametag'.
                            $tag->id.'" value="'.tag_display_name($tag).'" />';
        $tagtype        =   html_writer::select($existing_tagtypes, 'tagtypes['.$tag->id.']', $tag->tagtype, false, 
                            array('onchange' => 'changetype(this.value, '.$tag->id.', 1)')).' <span id="desc'.$tag->id.'"></span><span id="ttsave'.$tag->id.'"></span>';
        if ($tag->tagtype == 'official_text') {
            $inputs[] = 'createInput('.$tag->id.','.'30,"'.$tag->description.'",off_text_title);';
        }
        if ($tag->tagtype == 'official_select') {
            $inputs[] = 'createInput('.$tag->id.','.'40,"'.$tag->description.'",off_select_title);';
        }
        $count          =   $tag->count;
        if ($tag->tagtype == 'official_select' || $tag->tagtype == 'official_text') {
            $tagcount = $DB->count_records_sql("SELECT COUNT(DISTINCT itemid) FROM {tag_instance} ti WHERE itemtype = 'question' AND ti.tagid IN (SELECT id FROM {tag} WHERE name LIKE ?)", array($tag->name.'=%') );
            if ($tagcount >= $questioncount) {
                $img = '<img src="../pix/i/flagged.png" title="Jede vorkommende Frage besitzt eine Instanz dieses Tags. (Bei korrekter Ausführung der Cron-Jobs!)" />';
                $count = $tagcount.' <span style="float:right;">'.$img.'</span>';
            } else {
                $img = '<img src="../pix/i/unflagged.png" class="save_text" onclick="setinstances('.$tag->id.')" title="Es gibt Fragen ohne eine Instanz dieses Tags. Mit einem Klick wird allen Fragen eine Instanz dieses Tags hinzugefügt." />';
                $count = $tagcount.' <span id="setallquest'.$tag->id.'" style="float:right;">'.$img.'</span>';
            }
        }
        $timemodified   =   format_time(time() - $tag->timemodified).
                            '<span id="deltag'.$tag->id.'" style="float:right;"> <image class="save_text" src="../pix/i/cross_red_big.gif" title="Diesen Tag löschen" onclick="deltag('.$tag->id.')" /></span>';
        $data = array($id,
                      $name,
                      $tagtype,
                      $count,
                      $timemodified
                      );

        $table->add_data($data);
    }

    echo '<input type="hidden" name="sesskey" value="'.sesskey().'" /> ';
}

$table->print_html();
echo '</div></form>';
?>
<script type="text/javascript">
$(function() {
    $( document ).tooltip();
    <?php foreach ($inputs as $input) { echo $input;} ?>
});
</script>
<?php

if ($perpage == SHOW_ALL_PAGE_SIZE) {
    echo '<div id="showall"><a href="'. $baseurl .'&amp;perpage='. DEFAULT_PAGE_SIZE .'">'. get_string('showperpage', '', DEFAULT_PAGE_SIZE) .'</a></div>';

} else if ($totalcount > 0 and $perpage < $totalcount) {
    echo '<div id="showall"><a href="'. $baseurl .'&amp;perpage='. SHOW_ALL_PAGE_SIZE .'">'. get_string('showall', '', $totalcount) .'</a></div>';
}

echo '<br/>';

echo $OUTPUT->footer();