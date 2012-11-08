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
 * @license	http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();
require_once($CFG->dirroot . '/question/editlib.php');
require_once($CFG->dirroot . '/course/format/elatexam/copylib.php');
require_once('question_bank_columns.php');
/*error_reporting(E_ALL);
ini_set('display_errors', 1);*/

/**
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

class elate_question_bank_view extends question_bank_view {
    protected $new_cols = array(//Neue Klassen: 'name für qbank column' => Bezeichnung
                                'tags' => 'Schlagworte',
                                'autor' => 'Autor',
                                'schwierigkeit' => 'Schwierigkeitsgrad',
                                'fachgebiet' => 'Fachgebiet'
                              );
    protected $selectable_cols;
    protected $std_selected_cols = array('questionname','modifiername', 'autor', 'schwierigkeit');
    protected $catids;
    
	/**
     * Konstruktor
     */         
    public function __construct($contexts, $pageurl, $course, $cm = null) {
		global $PAGE, $OUTPUT, $CFG;
        $this->selectable_cols = array_merge(array( 'questiontext' => 'Fragetext',//Std
                                                    'questionname' => 'Fragename',//Std
                                                    'creatorname' => 'Erstellt von',//Std
                                                    'modifiername' => 'Zuletzt verändert von'//Std
                                                    ), $this->new_cols);
		$PAGE->requires->css("/course/format/elatexam/styles.css");
		$PAGE->requires->js("/course/format/elatexam/banklib.js");
		return parent::__construct($contexts, $pageurl, $course, $cm);
	}
    /**
     * Anpassung der Spalten die geladen werden
     */             
	protected function wanted_columns() {
        global $SESSION;
        $basetypes = array();
        $actions = array();
        $sel_columns = optional_param_array('column_select', array(), PARAM_RAW);
        if (count($sel_columns)) {
            $SESSION->sel_columns = $sel_columns;
        }elseif (isset($SESSION->sel_columns)) {
            $sel_columns = $SESSION->sel_columns;
        } else {
            $sel_columns = $this->std_selected_cols;
            $SESSION->sel_columns = $this->std_selected_cols;
        }
        $sel_columns = array_combine($sel_columns,$sel_columns);
        
        foreach ($this->knowncolumntypes as $column) {
            $colname = $column->get_name();
            switch ($colname) {//Einzelbehandlung für Spalten möglich - kann auf if/array gekürzt werden falls sich herausstellt, dass es nicht nötig ist
                case 'checkbox':
                case 'qtype':
                    $basetypes[] = $colname;
                break;
                case 'previewaction':
                case 'moveaction':
                case 'deleteaction':
                case 'editaction':
                    $actions[] = $colname;//actions an die rechte Tabellenseite
                break;
                default:
                    if (isset($sel_columns[$colname])) {
                        $basetypes[] = $colname;
                    }
                break;
            }
        }
        return array_merge($basetypes, $actions);
	}
	protected function known_field_types() {
		$basetypes = parent::known_field_types();
        $newtypes = array();
        foreach ($this->new_cols as $key => $new_column) {
            $column_name = 'question_bank_'.$key.'_column';
            $newtypes[] = new $column_name ($this);
        }
		return array_merge($basetypes, $newtypes);
	}
    
    /**
     * SQL-Anfrage aufbauen
     */
    protected function build_query_sql($category, $recurse, $showhidden) {
        global $DB, $SESSION;

    /// Get the required tables.
        $joins = array();
        foreach ($this->requiredcolumns as $column) {
            $extrajoins = $column->get_extra_joins();
            foreach ($extrajoins as $prefix => $join) {
                if (isset($joins[$prefix]) && $joins[$prefix] != $join) {
                    throw new coding_exception('Join ' . $join . ' conflicts with previous join ' . $joins[$prefix]);
                }
                $joins[$prefix] = $join;
            }
        }

    /// Get the required fields.
        $fields = array('q.hidden', 'q.category');
        foreach ($this->visiblecolumns as $column) {
            $fields = array_merge($fields, $column->get_required_fields());
        }
        foreach ($this->extrarows as $row) {
            $fields = array_merge($fields, $row->get_required_fields());
        }
        $fields = array_unique($fields);

    /// Build the order by clause.
        $sorts = array();
        foreach ($this->sort as $sort => $order) {
            list($colname, $subsort) = $this->parse_subsort($sort);
            $sorts[] = $this->requiredcolumns[$colname]->sort_expression($order < 0, $subsort);
        }

    /// Build the where clause.
        $tests = array('q.parent = 0');

        if (!$showhidden) {
            $tests[] = 'q.hidden = 0';
        }

        //$this->sqlparams = $params;
    /// Suche aufbauen
        $searchbyform = (optional_param('question_search', '', PARAM_ALPHA) == 'y');
        if ($searchbyform) {
            if (strlen(optional_param('input_question_search_text', '', PARAM_RAW))>0) {
                $this->search_question($fields, $joins, $tests, $searchbyform);
            } else {
                $this->reset_search();
            }
        } else {
            if (isset($SESSION->search_columns) && isset($SESSION->question_search_text)) {
                if (strlen($SESSION->question_search_text) > 0) {
                    $this->search_question($fields, $joins, $tests, $searchbyform);
                } else {
                    $this->reset_search();
                }
            } else {
                $this->reset_search();
            }
        }

        if (!$SESSION->search_all_cats) {
            if ($recurse) {
                $categoryids = question_categorylist($category->id);
            } else {
                $categoryids = array($category->id);
            }
            list($catidtest, $params) = $DB->get_in_or_equal($categoryids, SQL_PARAMS_NAMED, 'cat');
            $tests[] = 'q.category ' . $catidtest;
        } else {//Alle Kategorien (nur berechtigte) anzeigen
            list($catidtest, $params) = $DB->get_in_or_equal($this->catids, SQL_PARAMS_NAMED, 'cat');
            $tests[] = 'q.category ' . $catidtest;
        }
        
    /// Build the SQL.
        $sql = ' FROM {question} q ' . implode(' ', $joins);
        $sql .= ' WHERE ' . implode(' AND ', $tests);
        $this->loadsql = 'SELECT DISTINCT ' . implode(', ', $fields) . $sql . ' ORDER BY ' . implode(', ', $sorts);
        $this->countsql = 'SELECT count(DISTINCT q.id) '.$sql;
        $this->sqlparams = $params;
    }
    protected function reset_search(){
        global $SESSION;
        $SESSION->search_columns = array();
        $SESSION->question_search_text = '';
        $SESSION->search_all_cats = false;
    }
    /**
     * Funktion für eine Fragensuche
     */
    protected function search_question(&$fields, &$joins, &$tests, $searchbyform) {
        ///SUCHE IN DB
        //-->Leerzeichen als AND
        //-->'' bzw "" als kompletter Term
        //-->&,&&,and,und,|,||,or,oder
        //-->Aufgabenstellung(nur bei Fragetypen XYZ gültig) durchsuchen?/Antwortalternativen
        global $SESSION;
        $search_parts = array();
        $missing_join = array();
        if ($searchbyform) {
            $SESSION->question_search_text = str_replace("'","\"",optional_param('input_question_search_text', '', PARAM_RAW));
            $SESSION->search_columns = optional_param_array('search_select', array(), PARAM_RAW);
            $SESSION->search_all_cats = optional_param('search_all_categories',false, PARAM_BOOL);
        }
        if (strpos(",".$SESSION->question_search_text,"\"") > 0) {
            $searcharray = preg_split('"\\"([^\\"]*)\\""', "\"".$SESSION->question_search_text."\"" , -1, PREG_SPLIT_NO_EMPTY);
        } else {
            $searcharray = array();
        }
        $searcharray2 = preg_split('"\\"([^\\"]*)\\""', $SESSION->question_search_text, -1, PREG_SPLIT_NO_EMPTY);
        foreach ($searcharray2 as $sa2) {
            $sa2 = preg_replace('/  /i',' ',$sa2);
            $sa2 = preg_replace('/( )*\\|+( )*|( )+(oder|or)( )+/i','|',$sa2);
            $searcharray = array_merge($searcharray,preg_split('/(( )+(and|und)( )+|&| )/i', $sa2, -1, PREG_SPLIT_NO_EMPTY));
        }
        $searcharray = array_unique($searcharray);
        //simple://$searcharray = preg_split('/( |and|und|&)/i', $SESSION->question_search_text, -1, PREG_SPLIT_NO_EMPTY);
        $search_select = $SESSION->search_columns;

        if (count($search_select)) { 
            $all = false;
            $search_select = array_combine($search_select,$search_select);
        } else {$all = true;}
        foreach ($searcharray as $s_element) {
            $s_element = addslashes($s_element);
            $search_all = array();
            foreach ($this->knowncolumntypes as $column) {
                $name = $column->get_name();
                if ($all || isset($search_select[$name])) {
                    $search = array();
                    foreach (explode("|",$s_element) as $search_phrase) {
                        switch ($name) {
                            case 'checkbox':
                            case 'qtype':
                            case 'editaction':
                            case 'previewaction':
                            case 'moveaction':
                            case 'deleteaction':
                            break;
                            case 'questionname':
                                $search[] = 'q.name LIKE "%'.$search_phrase.'%"';                  
                            break;
                            case 'creatorname':
                                $search[] = '(uc.firstname LIKE "%'.$search_phrase.'%" OR uc.lastname LIKE "%'.$search_phrase.'%")';
                                if (!isset($this->requiredcolumns[$name]) && !isset($missing_join[$name])) {
                                    $missing_join[$name] = $name;
                                }
                            break;
                            case 'modifiername':
                                $search[] = '(um.firstname LIKE "%'.$search_phrase.'%" OR um.lastname LIKE "%'.$search_phrase.'%")';
                                if (!isset($this->requiredcolumns[$name]) && !isset($missing_join[$name])) {
                                    $missing_join[$name] = $name;
                                }  
                            break;
                            default:// questiontext & custom types wie tags, difficulty, und co
                                $search[] = $name.' LIKE "%'.$search_phrase.'%"';
                                if (!isset($this->requiredcolumns[$name]) && !isset($missing_join[$name])) {
                                    $missing_join[$name] = $name;
                                }  
                            break;
                        }
                    }
                    if (count($search)) {
                        $search_all[] = '('.implode(' OR ',$search).')';
                    }               
                }
            }
            if (count($search_all)) {
                $search_parts[] = '('.implode(' OR ',$search_all).')';
            }          
        }
        if (count($missing_join)) {
            foreach ($this->knowncolumntypes as $column) {    
                $extrajoins = $column->get_extra_joins();
                foreach ($extrajoins as $prefix => $join) {
                    if (isset($joins[$prefix]) && $joins[$prefix] != $join) {
                        throw new coding_exception('Join ' . $join . ' conflicts with previous join ' . $joins[$prefix]);
                    }
                    $joins[$prefix] = $join;
                }
                $fields = array_merge($fields, $column->get_required_fields());
            }  
        }
        $fields = array_unique($fields);
        if (count($search_parts)) {
            $tests[] = '('.implode(" AND ",$search_parts).')';
        }
    }
    /**
     * Blöcke um gruppierte Elemente
     */
    public function display($tabname, $page, $perpage, $cat,
            $recurse, $showhidden, $showquestiontext) {
        global $PAGE, $OUTPUT;

        if ($this->process_actions_needing_ui()) {
            return;
        }

        $PAGE->requires->js('/question/qbank.js');

        // Category selection form
        echo $OUTPUT->heading(get_string('questionbank', 'question'), 2);
        echo '<div class="qbank_config">';
        $this->contexts_edit = $this->contexts->having_one_edit_tab_cap($tabname);
        $this->display_category_form($this->contexts_edit,
                $this->baseurl, $cat);
        $this->display_options($recurse, $showhidden, $showquestiontext);
        echo "</div>";
        if (!$category = $this->get_current_category($cat)) {
            return;
        }
        list($categoryid, $contextid) = explode(',', $cat);
		$catcontext = get_context_instance_by_id($contextid);
        $this->create_new_question_form($category, has_capability('moodle/question:add', $catcontext));
        echo '<div class="qbank_cat_desc">';
        $this->print_category_info($category);
        echo '</div>';
        echo '<div style="clear:both;"></div>';
        
        // continues with list of questions
        $this->display_question_list($this->contexts->having_one_edit_tab_cap($tabname),
                $this->baseurl, $cat, $this->cm,
                $recurse, $page, $perpage, $showhidden, $showquestiontext,
                $this->contexts->having_cap('moodle/question:add'));
    }
	/**
	 * only changed one line in this method (Added Copy Button)
	 * so sync the rest when updating moodle!
	 * hopefully this method will be split in future moodle versions :-/
	 * @see question_bank_view::display_question_list()
	 */
	protected function display_question_list($contexts, $pageurl, $categoryandcontext, $cm = null, $recurse=1, $page=0, $perpage=100, $showhidden=false, $showquestiontext = false, $addcontexts = array()) {
        //Einstellungen der Liste
        global $CFG, $DB, $OUTPUT,$SESSION;
        $category = $this->get_current_category($categoryandcontext);
		$cmoptions = new stdClass();
		$cmoptions->hasattempts = !empty($this->quizhasattempts);
		list($categoryid, $contextid) = explode(',', $categoryandcontext);
		$catcontext = get_context_instance_by_id($contextid);
		$canadd = has_capability('moodle/question:add', $catcontext);
		$caneditall =has_capability('moodle/question:editall', $catcontext);
		$canuseall =has_capability('moodle/question:useall', $catcontext);
		$canmoveall =has_capability('moodle/question:moveall', $catcontext);
		
		$this->build_query_sql($category, $recurse, $showhidden);
		
        $this->display_column_select();
        $this->display_search();
        $totalnumber = $this->get_question_count();
        if ($totalnumber == 0) {
            echo "<div style='padding:15px;'>Kein Ergebnis für die aktuelle Suche.</div>";            
			return;
		}        
        $questions = $this->load_page_questions($page, $perpage);        
		echo '<div class="categorypagingbarcontainer">';
		$pageing_url = new moodle_url('edit.php');
		$r = $pageing_url->params($pageurl->params());
		$pagingbar = new paging_bar($totalnumber, $page, $perpage, $pageing_url);
		$pagingbar->pagevar = 'qpage';
		echo $OUTPUT->render($pagingbar);
		echo '</div>';
		echo '<form method="post" action="edit.php">';
		echo '<fieldset class="invisiblefieldset" style="display: block;">';
		echo '<input type="hidden" name="sesskey" value="'.sesskey().'" />';
		echo html_writer::input_hidden_params($pageurl);
		echo '<div class="categoryquestionscontainer">';
		$this->start_table();
		$rowcount = 0;
		foreach ($questions as $question) {
			$this->print_table_row($question, $rowcount);
			$rowcount += 1;
		}
		$this->end_table();
		echo "</div>\n";
		echo '<div class="categorypagingbarcontainer pagingbottom">';
		echo $OUTPUT->render($pagingbar);
		if ($totalnumber > DEFAULT_QUESTIONS_PER_PAGE) {
			if ($perpage == DEFAULT_QUESTIONS_PER_PAGE) {
				$url = new moodle_url('edit.php', array_merge($pageurl->params(), array('qperpage'=>1000)));
				$showall = '<a href="'.$url.'">'.get_string('showall', 'moodle', $totalnumber).'</a>';
			} else {
				$url = new moodle_url('edit.php', array_merge($pageurl->params(), array('qperpage'=>DEFAULT_QUESTIONS_PER_PAGE)));
				$showall = '<a href="'.$url.'">'.get_string('showperpage', 'moodle', DEFAULT_QUESTIONS_PER_PAGE).'</a>';
			}
			echo "<div class='paging'>$showall</div>";
		}
		echo '</div>';
		echo '<div class="modulespecificbuttonscontainer">';
		if ($caneditall || $canmoveall || $canuseall){
			echo '<strong>&nbsp;'.get_string('withselected', 'question').':</strong><br />';
			if (function_exists('module_specific_buttons')) {
				echo module_specific_buttons($this->cm->id,$cmoptions);
			}
			// print delete and move selected question
			if ($caneditall) {
				echo '<input type="submit" name="deleteselected" value="' . get_string('delete') . "\" />\n";
			}
			if ($canmoveall && count($addcontexts)) {
				// ADD OUR BUTTON HERE (Other Things Did Not Change!!)
				echo '<input type="submit" name="copy" value="'.get_string('copyto', 'format_elatexam')."\" />\n";
				echo '<input type="submit" name="move" value="'.get_string('moveto', 'question')."\" />\n";
				question_category_select_menu($addcontexts, false, 0, "$category->id,$category->contextid");
			}
			if (function_exists('module_specific_controls') && $canuseall) {
				$modulespecific = module_specific_controls($totalnumber, $recurse, $category, $this->cm->id,$cmoptions);
				if(!empty($modulespecific)){
					echo "<hr />$modulespecific";
				}
			}
		}
		echo "</div>\n";
		echo '</fieldset>';
		echo "</form>\n";
	}
    /**
     * Auswahl der Tabellenspalten
     */
    protected function display_column_select() {
        global $SESSION;       
        echo '<form method="post" action="'.$_SERVER['PHP_SELF'].'?'.$_SERVER['QUERY_STRING'].'" id="form_column_select">';
        echo '<fieldset class="invisiblefieldset">';
        echo '<div class="column_select_box"><label><strong>Tabelleninformationen anpassen: </strong>';
        echo html_writer::select($this->selectable_cols, 'column_select[]',$SESSION->sel_columns, false, array('multiple' => 'true', 'id' => 'column_select'));
        echo '</label></div>';
        echo '<noscript><div class="centerpara"><input type="submit" value="'. get_string('go') .'" />';
        echo '</div></noscript></fieldset></form>';
    }
    /**
     * Ausgabe für die Suche
     */
    protected function display_search(){
        global $SESSION;
        echo '<div class="block_search">';
        echo '<form method="post" action="'.$_SERVER['PHP_SELF'].'?'.$_SERVER['QUERY_STRING'].'" id="form_search">';
        echo '<fieldset class="invisiblefieldset">';
        
        echo '<div class="search_part">';
        echo '<input type="submit" value="suchen" /> <br /> <input type="submit" value="zurücksetzen" onclick="$(\'#input_question_search_text\').val(\'\')" title="Suche zurücksetzen" />';
        echo '<input type="hidden" name="question_search" value="y" /></div>';
        echo '<div class="search_part"><input type="text" id="input_question_search_text" size="29" title="Suchtext (jedes Wort muss für eine Frage in einer der ausgewählten Tabellenspalten vorkommen)" value="'.str_replace("\"","'",$SESSION->question_search_text).'" name="input_question_search_text" /><br />';
        echo html_writer::select($this->selectable_cols, 'search_select[]', $SESSION->search_columns, false, array('multiple' => 'true', 'id' => 'search_select'));
        echo '<br /><label><input type="checkbox" name="search_all_categories" value="1" '.($SESSION->search_all_cats ? 'checked="true"' : '') .' /> in allen Kategorien suchen </label></div>';
        
        echo '</fieldset></form>';
        echo '</div>';
        echo '<div class="clear"></div>';
    }
    /**
     * Ausgabe der Kategoriebeschreibung
     */
    protected function print_category_info($category) {
        $formatoptions = new stdClass();
        $formatoptions->noclean = true;
        $formatoptions->overflowdiv = true;
        echo '<div class="boxaligncenter">';
        echo format_text($category->info, $category->infoformat, $formatoptions, $this->course->id);
        echo "</div>\n";
    }

    /**
     * prints a form to choose categories
     */
    protected function display_category_form($contexts, $pageurl, $current) {
        global $CFG, $OUTPUT;

    /// Get all the existing categories now
        echo '<div class="choosecategory">';
        $catmenu = question_category_options($contexts, false, 0, true);
        $this->catids = array();
        foreach ($catmenu as $catcourse) {
            foreach ($catcourse as $optgroup) {
                foreach ($optgroup as $key => $cat) {
                    $catid = 0;
                    list($catid) = explode(",", $key);
                    $this->catids[] = $catid;
        }}}
        $select = new single_select($this->baseurl, 'category', $catmenu, $current, null, 'catmenu');
        $select->set_label("<strong>".get_string('selectacategory', 'question')."</strong>");
        echo $OUTPUT->render($select);
        echo "</div>\n";
    }

    protected function display_options($recurse, $showhidden, $showquestiontext) {
        echo '<form method="get" action="edit.php" id="displayoptions">';
        echo "<fieldset class='invisiblefieldset'>";
        echo html_writer::input_hidden_params($this->baseurl, array('recurse', 'showhidden', 'qbshowtext'));
        $this->display_category_form_checkbox('recurse', $recurse, get_string('includesubcategories', 'question'));
        $this->display_category_form_checkbox('showhidden', $showhidden, get_string('showhidden', 'question'));
        //nicht mehr nötig, da Spalten komplett gewählt werden
        //$this->display_category_form_checkbox('qbshowtext', $showquestiontext, get_string('showquestiontext', 'question'));
        echo '<noscript><div class="centerpara"><input type="submit" value="'. get_string('go') .'" />';
        echo '</div></noscript></fieldset></form>';
    }
	protected function create_new_question_form($category, $canadd) {
		// idea was to add row with create question button and searchbox via
		// print_table_headers() -> won't work because of table-layout:fixed; (CSS)
		// we need to have the new question button at one line with searchbox
		// this woud get messed up with some themes, so we have to modify this anyways
		echo '<span class="addbtn">';
		// call create_new_question_button() -> compare to original when upgrading moodle!
		if ($canadd) create_new_question_button($category->id, $this->editquestionurl->params(), get_string('createnewquestion', 'question'));
		else print_string('nopermissionadd', 'question');
        echo '</span>';
	}
	public function process_actions() {
		global $CFG, $DB;
		/// The following is handled very much the same as the 'move' part of parent::process_actions() in
		// Moodle 2.3 (EXCEPT FOR ONE LINE!), so compare to original when upgrading moodle!
		if (optional_param('copy', false, PARAM_BOOL) and confirm_sesskey()) {
			$category = required_param('category', PARAM_SEQUENCE);
			list($tocategoryid, $contextid) = explode(',', $category);
			if (! $tocategory = $DB->get_record('question_categories', array('id' => $tocategoryid, 'contextid' => $contextid)))
				print_error('cannotfindcate', 'question');
			$tocontext = get_context_instance_by_id($contextid);
			require_capability('moodle/question:add', $tocontext);
			$rawdata = (array) data_submitted();
			$questionids = array();
			foreach ($rawdata as $key => $value) {
				if (preg_match('!^q([0-9]+)$!', $key, $matches)) {
					$key = $matches[1];
					$questionids[] = $key;
				}
			}
			if ($questionids) {
				list($usql, $params) = $DB->get_in_or_equal($questionids);
				$sql = "";
				$questions = $DB->get_records_sql("
						SELECT q.*, c.contextid
						FROM {question} q
						JOIN {question_categories} c ON c.id = q.category
						WHERE q.id $usql", $params);
				foreach ($questions as $question){
					question_require_capability_on($question, 'move');
				}
				// THIS IS THE LINE THAT WAS CHANGED: (original: question_move_questions_to_category($questionids, $tocategory->id);)
				question_copy_questions_to_category($questionids, $tocategory->id);
				redirect($this->baseurl->out(false, array('category' => "$tocategoryid,$contextid")));
			}
		}
		parent::process_actions();
	}
}