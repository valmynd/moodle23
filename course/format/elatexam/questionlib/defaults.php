<?php

$ELATE_DEFAULTS = array(
		'essay' => array (
				'responsefieldlines' => 15,
				'responsefieldwidth' => 60,
		),
		'multichoice' => array (
				'num_shown' => 0,
				'num_right_min' => 1,
				'num_right_max' => 1,
				'penalty' => 1,
				'penalty_empty' => 0,
				'penalty_wrong' => 0,
				'assessmentmode' => 0,
		),
		'multianswer' => array(
				'casesensitivity' => 0,
		),
);

/*
define('DEFAULT_ESSAY_RESPONSEFIELDLINES', 15);
define('DEFAULT_ESSAY_RESPONSEFIELDWIDTH', 60);
define('DEFAULT_MC_SINGLE', 0);
define('DEFAULT_MC_NUM_SHOWN', 0);
define('DEFAULT_MC_NUM_RIGHT_MIN', 1);
define('DEFAULT_MC_NUM_RIGHT_MAX', 1);
define('DEFAULT_MC_SHUFFLEANSWERS', 1);
define('DEFAULT_MC_PENALTY', 1);
define('DEFAULT_MC_PENALTY_EMPTY', 0);
define('DEFAULT_MC_PENALTY_WRONG', 0);
define('DEFAULT_MC_ASSESSMENTMODE', 0);
*/
