<?php

class folder_view {

	public static $folder_identifier;
	public static $file_identifier;

	public function get_list_html() {
		throw Exception("Not implemented in folder_view");
	}
	protected function get_per_item_html(array $indices = array('1',)) {
		throw Exception("Not implemented in folder_view");
	}

	// Helper Methods
	protected function get_indices_from_key($sourcefolder_key, $identifier) {
		return explode('_', substr($sourcefolder_key,strlen($identifier)));
	}
	protected function get_key_from_indices(array $indices, $identifier) {
		return $identifier.implode('_', $indices);
	}
	protected function get_key_of_first_folder() {
		return self::$folder_identifier.'1';
	}
	protected function not_empty() {
		return isset($_POST[$this->get_key_of_first_folder()]);
	}
	protected function next_folder_exists(array $indices, $level, $current) {
		$indices[$level] = $current + 1; // we operate on copy
		return isset($_POST[$this->get_key_from_indices($indices, self::$folder_identifier)]);
	}

	protected function count_folders_at_level(array $indices, $level, $count_files = false) {
		for($i=1; $i <= 99; $i++) {
			$indices[$level] = $i;
			if(!$count_files) $key = $this->get_key_from_indices($indices, self::$folder_identifier);
			else $key = $this->get_key_from_indices($indices, self::$file_identifier);
			if(!isset($_POST[$key]))
				return $i;
		}
	}

	protected function count_files_at_level(array $indices, $level) {
		return $this->count_folders_at_level($indices, $level, true);
	}

	protected function get_move_buttons(array $indices, $key, $level, $i) {
		// read this: http://davidwalsh.name/php-form-submission-recognize-image-input-buttons
		global $OUTPUT;
		$buttonstr = '';
		$buttonattrib = ' onclick="skipClientValidation = true;" style="height:8px;"';
		// if there is a parent category (level > 0), provide moveleft button
		if($level > 0) {
			$name = "move_left_".$key;
			$iconurl = $OUTPUT->pix_url('t/left');
			$buttonstr .= '<input type="image" src="'.$iconurl.'" name="'.$name.'"'
					.' alt="'.get_string('moveleft').'"'.$buttonattrib.'>';
		}
		// if there is a previous category, provide moveup button
		if($i > 1) {
			$name = "move_up_".$key;
			$iconurl = $OUTPUT->pix_url('t/up');
			$buttonstr .= '<input type="image" src="'.$iconurl.'" name="'.$name.'"'
					.' alt="'.get_string('moveup').'"'.$buttonattrib.'>';
		}
		// if there is an next category, provide movedown button
		if($this->next_folder_exists($indices, $level, $i)) {
			$name = "move_down_".$key;
			$iconurl = $OUTPUT->pix_url('t/down');
			$buttonstr .= '<input type="image" src="'.$iconurl.'" name="'.$name.'"'
					.' alt="'.get_string('movedown').'"'.$buttonattrib.'>';
		}
		// if there is another category, provide moveright (use as subcategory) button
		if($i > 1) {
			$name = "move_right_".$key;
			$iconurl = $OUTPUT->pix_url('t/right');
			$buttonstr .= '<input type="image" src="'.$iconurl.'" name="'.$name.'"'
					.' alt="'.get_string('moveright').'"'.$buttonattrib.'>';
		}
		return $buttonstr;
	}

	protected function handle_move_folder_children($source_key, $target_key, $_POST_COPY) {
		$source_paths = array();
		$target_paths = array();
		$source_keys = preg_grep('/^'.$source_key.'_.*/', array_keys($_POST_COPY));
		$target_keys = preg_grep('/^'.$target_key.'_.*/', array_keys($_POST_COPY));
		foreach($source_keys as $skey) array_push($source_paths, substr($skey, strlen($source_key)));
		foreach($target_keys as $tkey) array_push($target_paths, substr($tkey, strlen($target_key)));
		$common_paths = array_intersect($source_paths, $target_paths);
		foreach($common_paths as $spath) { // swap elements
			$skey = $source_key . $spath;
			$tkey = $target_key . $spath;
			$_POST[$tkey] = $_POST_COPY[$skey];
			$_POST[$skey] = $_POST_COPY[$tkey];
		}
		foreach(array_diff($target_paths, $common_paths) as $tpath) {
			$skey = $source_key . $tpath;
			$tkey = $target_key . $tpath;
			$_POST[$skey] = $_POST_COPY[$tkey];
			unset($_POST[$tkey]);
		}
		foreach(array_diff($source_paths, $common_paths) as $spath) {
			$skey = $source_key . $spath;
			$tkey = $target_key . $spath;
			$_POST[$tkey] = $_POST_COPY[$skey];
			unset($_POST[$skey]);
		}
	}
	protected function handle_move_folder($btn_name) {
		preg_match('/(up|down|left|right)/', $btn_name, $matches);
		$direction = $matches[0];
		preg_match('/[^(move_'.$direction.'_)].*[^(_x)]/', $btn_name, $matches);
		$sourcefolder_key = $matches[0];
		$indices_source = $this->get_indices_from_key($sourcefolder_key, self::$folder_identifier);
		$indices_target = $indices_source; // copy
		$level = count($indices_source)-1;
		$i = $indices_source[$level];
		// TODO: handle question selection in a different way (e.g. checkboxes instead of radio buttons)
		$_POST_COPY = $_POST; // values will be overwritten
		switch($direction) { // prepare indices
			case 'up':    $indices_target[$level] = $i-1; break;
			case 'down':  $indices_target[$level] = $i+1; break;
			case 'left': array_pop($indices_target); break; // remove last
			case 'right': // always appent into the previous sibling
				$parentid = $indices_target[$level];
				$indices_target[$level] = $parentid-1;
				$indices_target[$level+1] = $this->count_folders_at_level($indices_target, $level+1); // append to last
				break;
		}
		if($direction == 'up' || $direction == 'down') {
			// this one is simple, just swap the values
			$targetfolder_key = $this->get_key_from_indices($indices_target, self::$folder_identifier);
			$_POST[$targetfolder_key] = $_POST_COPY[$sourcefolder_key];
			$_POST[$sourcefolder_key] = $_POST_COPY[$targetfolder_key];
		} else {
			$_POST["newcategory"] = $_POST_COPY[$sourcefolder_key]; // handle it as if we would add a folder, copy subitems into it later on
			$_POST["selected_folder"] = $sourcefolder_key; // temporarily point to source
			$this->handle_delete_folder(); // will remove selected element
			$_POST["selected_folder"] = $this->get_key_from_indices($indices_target, self::$folder_identifier); // now point to target
			$targetfolder_key = $this->handle_add_folder(null, $_POST_COPY); // will put it to the final position
			//$_POST_COPY = $_POST; // need more recent copy
		}
		$_POST["selected_folder"] = $targetfolder_key;
		// swap children from $_POST[$sourcefolder_key] and $_POST[$targetfolder_key]
		$this->handle_move_folder_children($sourcefolder_key, $targetfolder_key, $_POST_COPY);
		// do the same thing for files in those folders
		$sourcefile_key = str_replace(self::$folder_identifier, self::$file_identifier, $sourcefolder_key);
		$targetfile_key = str_replace(self::$folder_identifier, self::$file_identifier, $targetfolder_key);
		$this->handle_move_folder_children($sourcefile_key, $targetfile_key, $_POST_COPY);
	}

	protected function handle_delete_folder(array $indices = null) {
		if(!$indices) $indices = $this->get_indices_from_key($_POST["selected_folder"], self::$folder_identifier);
		$level = count($indices)-1;
		// move any folder, that follows inside this level, up once, then remove the last one
		for($i=$indices[$level]; $i <= 99; $i++) {
			$currkey = $this->get_key_from_indices($indices, self::$folder_identifier);
			$indices[$level] = $i+1;
			$nextkey = $this->get_key_from_indices($indices, self::$folder_identifier);
			if(!isset($_POST[$nextkey])) break;
			// pretend, that "move_up" button was pressed upon the next item, so form a string such as move_up_2_2_x
			$this->handle_move_folder('move_up_'.$nextkey.'_x'); // last _x is because of the input type in the form
		}
		unset($_POST[$currkey]); // maybe remove children now
		//debugging("would remove ".$_POST[$currkey]);
	}

	protected function handle_add_folder(array $indices = null, $_POST_COPY = null) {
		// at the beginning, indices will contain the indices of the position of the new element
		// later on, indices will point to the children elements of the element that has to be moved
		if(!$_POST_COPY) $_POST_COPY = $_POST;
		$override_key = $_POST["selected_folder"];
		$first = false;
		$continue = true;
		if(!$indices) {
			$indices = $this->get_indices_from_key($_POST["selected_folder"], self::$folder_identifier);
			$first = $indices[count($indices)-1];
		}
		$level = count($indices)-1;
		for($i=$indices[$level]; $i <= 99 && $continue; $i++) {
			$currkey = $this->get_key_from_indices($indices, self::$folder_identifier);
			$indices[$level] = $i+1;
			$nextkey = $this->get_key_from_indices($indices, self::$folder_identifier);
			if(!isset($_POST[$currkey])) break;
			$continue = isset($_POST[$nextkey]);
			$_POST[$nextkey] = $_POST_COPY[$currkey];
			if($level <= 5) $this->handle_add_folder( array_merge($indices, array('1',)) );
			if($i == $first) $override_key = $nextkey;
		}
		if($first) $_POST[$override_key] = $_POST["newcategory"];
		$_POST["selected_folder"] = $override_key;
		return $override_key;
	}
}

class category_view extends folder_view {

	protected $exam_bank;
	protected $exam_form;

	public function category_view($examform) {
		$this->exam_form = $examform;
		$this->exam_bank = $examform->get_question_bank();
	}

	public static function get_use_in_question_button($questionid) {
		global $OUTPUT;
		$delete_icon = $OUTPUT->pix_url('t/moveleft');
		return '<input type="image" src="'.$delete_icon.'" name="use_'.self::$file_identifier.$questionid.'"'
				.' alt="'.get_string('addtoquiz', 'quiz').' onclick="skipClientValidation = true;" style="height:7px;">';
	}

	protected function get_per_item_html(array $indices = array('1',)) {
		$ret = "<ul>";
		$level = count($indices)-1;
		for($i=1; $i <= 9999; $i++) { // render folders first
			$indices[$level] = $i;
			$folderkey = $this->get_key_from_indices($indices, self::$folder_identifier);
			if(!isset($_POST[$folderkey])) break;
			$ret .= '<li>'; // without "q"
			$radiobtnattrib = '';
			if($_POST["selected_folder"] == $folderkey)
				$radiobtnattrib = ' checked="checked"';
			$ret .= '<input name="selected_folder" value="'.$folderkey.'" type="radio"'.$radiobtnattrib.'>';
			$ret .= '<input value="'.$_POST[$folderkey].'" name="'.$folderkey.'" type="text">';
			$ret .= $this->get_move_buttons($indices, $folderkey, $level, $i);
			$ret .= "</li>\n";
			if($level <= 5) $ret .= $this->get_per_item_html( array_merge($indices, array('1',)) );
		}
		for($i=1; $i <= 9999; $i++) {
			$indices[$level] = $i;
			$filekey = $this->get_key_from_indices($indices, self::$file_identifier);
			if(!isset($_POST[$filekey])) break;
			$ret .= '<li class="q">';
			//$ret .= $_POST[$filekey];
			$checkboxattrib = ' class="clearfix"';
			$ret .= $this->exam_bank->get_question_by_id($_POST[$filekey]); // TODO: Fetch when not avaiable (e.g. category changed) !!
			// the following is what the "advanced checkbox" in moodle is all about:
			$ret .= '<input name="'.$filekey.'" value="'.$_POST[$filekey].'" type="hidden">';
			$ret .= '<input name="'.$filekey.'" value="'.$_POST[$filekey].'" type="hidden">';
			$ret .= '<input name="'.$filekey.'" value="drop_'.$_POST[$filekey].'" type="checkbox"'.$checkboxattrib.'>';
			$ret .= "</li>\n";
		}
		return $ret."</ul>\n";
	}

	public function get_list_html() {
		// note that such things as set_data() will not affect <input> elements generated with toHtml()!
		$ret = '<div class="examcategorycontainer">'."\n";
		/*	<li id="c1">Category</li>
		 <li id="c2">Category</li>
		<li id="c3">Category</li>
		<ul>
		<li id="c3_1">Category</li>
		<li id="c3_2">Category</li>
		<li id="c3_3" class="q"><span id=43><img/>Question</span></li>
		<li id="c3_4" class="q"><span id=45><img/>Question</span></li>
		</ul>*/
		debugging("before/after<br>".var_export($_POST));

		////// Handle Button Actions (Add, Delete, Move) //////////

		if(isset($_POST["removecategory"])) {
			if(isset($_POST["selected_folder"]))
				$this->handle_delete_folder();
		} else if(isset($_POST["addcategory"])) {
			// at the very beginning, there will be no selectable items
			if(isset($_POST["selected_folder"]))
				$this->handle_add_folder();
			else $_POST[$this->get_key_of_first_folder()] = $_POST["newcategory"];
		} else if($matches = preg_grep('/^move_.*_.*_x/', array_keys($_POST))) {
			$this->handle_move_folder(array_shift($matches));
		} else if($matches = preg_grep('/^use_.*_x/', array_keys($_POST))) {
			$this->handle_use_question(array_shift($matches));
		}

		if(!isset($_POST["selected_folder"])) {
			$_POST["selected_folder"] = $this->get_key_of_first_folder();
		}

		////// Add Action Buttons (Add, Delete, Move) //////////

		$ret .= 'Add category to exam: ';
		$ret .= '<input name="newcategory" type="text" />';
		$ret .= '<input onclick="skipClientValidation = true;" name="addcategory" value="Add" type="submit" />';
		$ret .= '<input onclick="skipClientValidation = true;" name="removecategory" value="Remove selected" type="submit" />';

		////// Generate Fields for each Category / Question (by now $_POST should not be changed anymore!) //////////

		debugging(var_export($_POST));
		$ret .= $this->get_per_item_html(); // works recursively

		return $ret.'</div>'."\n";
	}

	protected function handle_use_question($btn_name) {
		if(!isset($_POST["selected_folder"])) {
			echo '<div class="box warning">You must create a category first (on the left), before you can assign questions to it.</div>';
			// TODO: not show anything else than add category
			return;
		}
		$matches = array();
		preg_match('/[^(use_)].*[^(_x)]/', $btn_name, $matches);
		$questionid = substr($matches[0],strlen(self::$file_identifier));
		// put below selected category
		$indices = $this->get_indices_from_key($_POST["selected_folder"], self::$folder_identifier);
		$level = count($indices)-1;
		$indices[$level+1] = 1; // put into that category
		$indices[$level+1] = $this->count_files_at_level($indices, $level+1);
		$key = $nextkey = $this->get_key_from_indices($indices, self::$file_identifier);
		// use the data still avaiable in the questionbank, so we don't have to fetch them each time
		$_POST[$key] = $questionid;
	}
}

folder_view::$folder_identifier = "folder";
folder_view::$file_identifier = "file";
category_view::$folder_identifier = "category";
category_view::$file_identifier = "question";