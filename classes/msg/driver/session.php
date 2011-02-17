<?php defined('SYSPATH') or die('No direct script access.');
/**
 * Session driver for the MSG module.
 *
 * @package    MSG
 * @category   Drivers
 * @author     Gregorio Ramirez
 * @copyright  (c) 2011 Gregorio Ramirez
 * @license    MIT
 * @see        http://kohanaftw.com/modules/msg/
 */
class MSG_Driver_Session extends MSG {

	/**
	 * @var  Session  session instance
	 */
	protected $_session;

	/**
	 * Initiate the session.
	 *
	 * @param  array  configuration settings
	 */
	protected function __construct(array $config)
	{
		$this->_session = Session::instance();
		
		parent::__construct($config);
	}

	/**
	 * Save messages (will override the existing array.)
	 *
	 * @param  array  messages
	 */
	protected function _set(array $messages)
	{
		$this->_session->set($this->_config['storage_key'], $messages);
	}

	/**
	 * Get messages.
	 *
	 * @return  array
	 */
	protected function _get()
	{
		return $this->_session->get($this->_config['storage_key']);
	}

	/**
	 * Delete messages.
	 */
	protected function _delete()
	{
		$this->_session->delete($this->_config['storage_key']);
	}

} // End MSG_Driver_Session
