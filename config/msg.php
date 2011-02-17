<?php defined('SYSPATH') or die('No direct script access.');

return array(
	'session' => array(
		/**
		 * The session key that will hold the messages.
		 */
		'storage_key' => 'msg',
		
		/**
		 * You don't need to tweak this unless you're creating a
		 * new config array, e.g. a database driver would be TRUE 
		 * because you would only want to write to the db once.
		 */
		'handicap' => FALSE,
	),
	'cookie' => array(
		/**
		 * The name of the browser cookie that will hold the messages.
		 */
		'storage_key' => 'msg',
		
		/**
		 * You don't need to tweak this unless you're creating a
		 * new config array, e.g. a database driver would be TRUE 
		 * because you would only want to write to the db once.
		 */
		'handicap' => TRUE,
	),
);