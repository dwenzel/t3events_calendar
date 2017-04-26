<?php

/***************************************************************
 * Extension Manager/Repository config file for ext "t3events_calendar".
 *
 *
 * Manual updates:
 * Only the data in the array - everything else is removed by next
 * writing. "version" and "dependencies" must not be touched!
 ***************************************************************/

$EM_CONF[$_EXTKEY] = array (
	'title' => 'Calendar',
	'description' => 'Event Calendar',
	'category' => 'plugin',
	'version' => '0.0.0',
	'state' => 'beta',
	'uploadfolder' => 0,
	'createDirs' => '',
	'clearcacheonload' => 1,
	'author' => 'Dirk Wenzel',
	'author_email' => 't3events@gmx.de',
	'constraints' => 
	array (
		'depends' => 
		array (
			'typo3' => '7.6.0-8.99.99',
			't3events' => '0.32.0-0.0.0',
			't3calendar' => '0.3.0-0.0.0',
		),
		'conflicts' => 
		array (
		),
		'suggests' => 
		array (
		),
	),
	'_md5_values_when_last_written' => ' ',
);

