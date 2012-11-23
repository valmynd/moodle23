<?php

/**
 * Provides an Expression for String Aggregation appropriate for the underlying Database 
 * Shall make it work with PostgreSQL, MySQL and Oracle at least
 *
 * @param string $column_expression
 * @return string
 */
function listagg_expr($column_expression) {
	// http://www.postgresonline.com/journal/archives/191-String-Aggregation-in-PostgreSQL,-SQL-Server,-and-MySQL.html
	global $CFG;
	if($CFG->dbtype == 'mysql' || $CFG->dbtype == 'mysqli')
		return "GROUP_CONCAT($column_expression SEPARATOR ', ')";
	else if($CFG->dbtype == 'pgsql')
		return "STRING_AGG($column_expression, ', ')"; // Postgres
	return "LISTAGG($column_expression)"; // Oracle
}

class question_bank_fachgebiet_column extends question_bank_column_base {
    protected $metaname = 'fachgebiet';
    protected $metatitle = 'Fachgebiet';
    protected $tbl_shortcut = 'fc';
	
    public function get_name() {
		return $this->metaname;
	}
	protected function get_title() {
		return $this->metatitle;
	}
    public function get_extra_joins() {
        return array($this->tbl_shortcut => "LEFT JOIN (".
                                 "SELECT ti.itemid, ".listagg_expr("tg.rawname")." AS $this->metaname FROM {tag_instance} ti ".
                                 "LEFT JOIN {tag} tg ON ti.tagid = tg.id WHERE ti.itemtype = 'question' AND tg.name LIKE '%$this->metaname=%' GROUP BY ti.itemid".
                             ") $this->tbl_shortcut ON $this->tbl_shortcut.itemid = q.id");
    }

    public function get_required_fields() {
        return array($this->tbl_shortcut.".".$this->metaname);
    }
    public function is_sortable() {
        return $this->tbl_shortcut.".".$this->metaname;
    }
    protected function display_content($question, $rowclasses) {
        $name = $this->metaname;
        echo str_replace($this->metaname.'=','',$question->$name);
    }
}
class question_bank_autor_column extends question_bank_column_base {
    protected $metaname = 'autor';
    protected $metatitle = 'Autor';
    protected $tbl_shortcut = 'at';
	
    public function get_name() {
		return $this->metaname;
	}
	protected function get_title() {
		return $this->metatitle;
	}
    public function get_extra_joins() {
        return array($this->tbl_shortcut => "LEFT JOIN (".
                                 "SELECT ti.itemid, ".listagg_expr("tg.rawname")." AS $this->metaname FROM {tag_instance} ti ".
                                 "LEFT JOIN {tag} tg ON ti.tagid = tg.id WHERE ti.itemtype = 'question' AND tg.name LIKE '%$this->metaname=%' GROUP BY ti.itemid".
                             ") $this->tbl_shortcut ON $this->tbl_shortcut.itemid = q.id");
    }

    public function get_required_fields() {
        return array($this->tbl_shortcut.".".$this->metaname);
    }
    public function is_sortable() {
        return $this->tbl_shortcut.".".$this->metaname;
    }
    protected function display_content($question, $rowclasses) {
        $name = $this->metaname;
        echo str_replace($this->metaname.'=','',$question->$name);
    }
}
class question_bank_schwierigkeit_column extends question_bank_column_base {
    protected $metaname = 'schwierigkeit';
    protected $metatitle = 'Schwierigkeit';
    protected $tbl_shortcut = 'sc';
	public function get_name() {
		return $this->metaname;
	}
	protected function get_title() {
		return $this->metatitle;
	}
    public function get_extra_joins() {
        return array($this->tbl_shortcut => "LEFT JOIN (".
                                 "SELECT ti.itemid, ".listagg_expr("tg.rawname")." AS $this->metaname FROM {tag_instance} ti ".
                                 "LEFT JOIN {tag} tg ON ti.tagid = tg.id WHERE ti.itemtype = 'question' AND tg.name LIKE '%$this->metaname=%' GROUP BY ti.itemid".
                             ") $this->tbl_shortcut ON $this->tbl_shortcut.itemid = q.id");
    }

    public function get_required_fields() {
        return array($this->tbl_shortcut.".".$this->metaname);
    }
    public function is_sortable() {
        return $this->tbl_shortcut.".".$this->metaname;
    }
	protected function display_content($question, $rowclasses) {
        $name = $this->metaname;
        echo str_replace($this->metaname.'=','',$question->$name);
	}
}
class question_bank_tags_column extends question_bank_column_base {
	public function get_name() {
		return 'tags';
	}
	protected function get_title() {
		return "Schlagworte";
	}
    public function get_extra_joins() {
        return array('tc' => "LEFT JOIN (".
                                 "SELECT ti.itemid, ".listagg_expr("tg.rawname")." AS tags FROM {tag_instance} ti ".
                                 "LEFT JOIN {tag} tg ON ti.tagid = tg.id WHERE ti.itemtype = 'question' AND tg.name NOT LIKE '%=%' GROUP BY ti.itemid".
                             ") tc ON tc.itemid = q.id");
    }

    public function get_required_fields() {
        return array('tc.tags');
    }
	protected function display_content($question, $rowclasses) {
        echo '<div class="tags_container">'.$question->tags.'</div>';
	}
}
?>