<?php

function get_default_for_elatexam($qtype, $identifier) {
	// this array should not be accessed directly, e.g., we could
	// get those constants from the install.xml-files some day
	$ELATE_DEFAULTS = array(
			'essay' => array (
					'responsefieldlines' => 15,
					'responsefieldwidth' => 60,
					'initialtextfieldvalue' => '',
			),
			'multichoice' => array (
					'num_shown' => 0,
					'num_right_min' => 1,
					'num_right_max' => 1,
					'single' => 0,
					'penalty' => 1,
					'penalty_empty' => 0,
					'penalty_wrong' => 0,
					'assessmentmode' => 0,
					'shuffleanswers' => 1,
			),
			'multianswer' => array(
					'casesensitivity' => 0,
			),
			'shortanswer' => array(
					'usecase' => 0,
			),
	);
	return $ELATE_DEFAULTS[$qtype][$identifier];
}