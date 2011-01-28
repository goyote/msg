<?php defined('SYSPATH') or die('No direct script access.');

return array(
	'session' => array(
		/**
		 * Defines the storage medium in which the message(s) will be stored,
		 * 'session' and 'cookie' are supported.
		 */
		'storage_medium' => 'session',
		
		/**
		 * Session key or cookie name in which to store the messages.
		 */
		'storage_key' => 'msg',
	),
	'cookie' => array(
		/**
		 * Defines the storage medium in which the message(s) will be stored,
		 * 'session' and 'cookie' are supported.
		 */
		'storage_medium' => 'cookie',
		
		/**
		 * Session key or cookie name in which to store the messages.
		 */
		'storage_key' => 'msg',
	),
);