<?php defined('SYSPATH') or die('No direct script access.');
/**
 * Cookie driver for the MSG module.
 *
 * @package    MSG
 * @category   Drivers
 * @author     Gregorio Ramirez
 * @copyright  (c) 2011 Gregorio Ramirez
 * @license    MIT
 * @see        http://kohanaftw.com/modules/msg/
 */
class MSG_Driver_Cookie extends MSG {

	/**
	 * Load stored messages.
	 *
	 * @param  array  configuration settings
	 */
	protected function __construct(array $config)
	{
		parent::__construct($config);

		// Write messages once on shutdown
		register_shutdown_function(array($this, 'write_cookie'));

		if (($messages = Cookie::get($this->_config['storage_key'])) !== NULL)
		{
			// Avoid serializing a NULL value (which would return FALSE)
			$this->_messages = unserialize($messages);
		}
	}

	/**
	 * Write the messages to a cookie.
	 */
	public function write_cookie()
	{
		if (empty($this->_messages))
			return;

		// Import all messages locally
		$messages = $this->_messages;

		// Reset the _messages array
		$this->_messages = NULL;

		// Store the messages in a browser cookie
		Cookie::set($this->_config['storage_key'], serialize($messages));
	}

	/**
	 * Save messages (will override the existing array.)
	 *
	 * @param  array  messages
	 */
	protected function _set(array $messages)
	{
		$this->_messages = $messages;
	}

	/**
	 * Get messages.
	 *
	 * @return  array
	 */
	protected function _get()
	{
		// Workaround to enable setting & getting multiple messages
		return $this->_messages;
	}

	/**
	 * Delete messages.
	 */
	protected function _delete()
	{
		$this->_messages = NULL;
		Cookie::delete($this->_config['storage_key']);
	}

} // End MSG_Driver_Cookie
