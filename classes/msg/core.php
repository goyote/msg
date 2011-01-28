<?php defined('SYSPATH') or die('No direct script access.');
/**
 * MSG is a robust blurb (or "flash") messaging system for KO3.
 *
 * @package    Kohana/MSG
 * @category   Base
 * @author     Gregorio Ramirez
 * @copyright  (c) 2011 Gregorio Ramirez
 * @license    http://kohanaphp.com/license
 * @see        http://kohanaftw.com/modules/msg/
 */
class MSG_Core {
	
	// Message types
	const COOKIE  = 'cookie';
	const SESSION = 'session';
	const ERROR   = 'error';
	const ALERT   = 'alert';
	const NOTICE  = 'notice';
	const WARNING = 'warning';
	const SUCCESS = 'success';
	const ACCESS  = 'access';

	/**
	 * @var  string  default driver name
	 */
	public static $default = 'session';

	/**
	 * @var  array  MSG instances
	 */
	public static $_instances;

	/**
	 * @var  Config  config array
	 */
	protected $_config;

	/**
	 * @var  Session  session instance
	 */
	protected $_session;

	/**
	 * @var  array  temporary storage medium for messages
	 */
	protected $_messages;

	/**
	 * Get a singleton MSG instance. If configuration is not specified,
	 * it will be loaded from the msg configuration file using the same
	 * group as the name.
	 *
	 *     // Get a singleton instance
	 *     $msg = MSG::instance();
	 *
	 *     // Use the cookie driver
	 *     $msg = MSG::instance(MSG::COOKIE);
	 *
	 * @param   string  storage driver
	 * @param   array   configuration parameters
	 * @return  MSG
	 */
	public static function instance($name = NULL, array $config = NULL)
	{
		if ($name === NULL)
		{
			// Use the default driver
			$name = MSG::$default;
		}

		if ( ! isset(MSG::$_instances[$name]))
		{
			if ($config === NULL)
			{
				// Load the configuration for this driver
				$config = Kohana::config('msg')->$name;
			}

			MSG::$_instances[$name] = new MSG($config);
		}

		return MSG::$_instances[$name];
	}

	/**
	 * Load the session and store the configuration options locally.
	 *
	 * @param   array  configuration options
	 * @return  void
	 */
	protected function __construct(array $config)
	{
		$this->_config = $config;

		switch ($this->_config['storage_medium'])
		{
			case 'session':
				$this->_session = Session::instance();
			break;
			case 'cookie':
				// Messages are written once at shutdown
				register_shutdown_function(array($this, 'write_cookie'));

				// This check avoids serializing a NULL value (which would return FALSE)
				if (($messages = Cookie::get($this->_config['storage_key'])) !== NULL)
				{
					$this->_messages = unserialize($messages);
				}
			break;
			default:
				throw new MSG_Exception(__('":medium" is not a valid storage medium'),
					array(':medium' => $this->_config['storage_medium']));
			break;			
		}
	}

	/**
	 * Write the all the messages once on shutdown (workaround to enable get())
	 *
	 * @return  void
	 */
	public function write_cookie()
	{
		if (empty($this->_messages))
			return;

		// Import all messages locally
		$messages = $this->_messages;

		// Reset the messages array
		$this->_messages = NULL;

		// Store the messages in a browser cookie
		Cookie::set($this->_config['storage_key'], serialize($messages));
	}

	/**
	 * Set a new message.
	 *
	 *     // Set a new error message
	 *     MSG::instance()->set(MSG::SUCCESS, __('%s now has %d monkeys'),
	 *         array($this->user->first_name, count($monkeys)));
	 *
	 * @param   string   message type (e.g. MSG::SUCCESS)
	 * @param   mixed    message text or array of messages
	 * @param   array    values to replace with sprintf
	 * @param   array    custom data
	 * @return  MSG
	 */
	public function set($type, $text, array $values = NULL, $data = NULL)
	{
		if (is_array($text))
		{
			foreach($text as $message)
			{
				// Recursively set each message
				$this->set($type, $message);
			}
			
			return $this;
		}
		
		if ($values)
		{
			// The target string goes first
			array_unshift($values, $text);

			// Insert the values into the message
			$text = call_user_func_array('sprintf', $values);
		}

		if ($this->_config['storage_medium'] === 'cookie')
		{
			// Grabbing messages from a cookie would require a browser refresh
			$messages = $this->_messages;
		}
		else
		{
			// Get messages and cast them into an array in case NULL returns
			$messages = (array) $this->get();
		}

		// Append a new message
		$messages[] = array(
			'type' => $type,
			'text' => $text,
			'data' => $data,
		);

		// Store the updated list (overrides the old)
		$this->_set($messages);

		// Enable method chaining
		return $this;
	}

	/**
	 * Save the messages (the new set will override the existing one.)
	 *
	 *     // Save the messages
	 *     $this->_set($messages);
	 *
	 * @param   array    messages
	 * @return  boolean
	 * @throws  MSG_Exception
	 */
	protected function _set(array $messages)
	{
		switch ($this->_config['storage_medium'])
		{
			case 'session':
				return (bool) $this->_session->set($this->_config['storage_key'], $messages);
			break;
			case 'cookie':
				// Workaround to enable setting & getting multiple messages
				$this->_messages = $messages;
				return TRUE;
			break;
			default:
				throw new MSG_Exception(__('":medium" is not a valid storage medium'),
					array(':medium' => $this->_config['storage_medium']));
			break;
		}
	}

	/**
	 * Get all messages, or only those of a certain type.
	 *
	 *     // Get messages
	 *     $messages = MSG::instance()->get();
	 *
	 *     // Get error messages only
	 *     $error_messages = MSG::instance()->get(MSG::ERROR);
	 *
	 *     // Get error and alert messages
	 *     MSG::instance()->get(array(MSG::ERROR, MSG::ALERT));
	 *
	 *     // Customize the default value
	 *     $error_messages = MSG::instance()->get(MSG::ERROR, 'No error messages found');
	 *
	 * @param   mixed   message type (e.g. MSG::SUCCESS)
	 * @param   mixed    default value to return
	 * @param   boolean  delete the messages?
	 * @return  mixed
	 */
	public function get($type = NULL, $default = NULL, $delete = FALSE)
	{
		// Get the whole stack of messages
		$messages = $this->_get();

		if ($messages === NULL)
		{
			// Nothing to return
			return $default;
		}

		if ($type !== NULL)
		{
			// Will hold the filtered set of messages to return
			$return = array();
			
			// Store the remainder in case delete or get_once is called
			$remainder = array();
			
			foreach($messages as $message)
			{
				if ($message['type'] === $type)
				{
					$return[] = $message;
				}
				elseif (is_array($type) AND in_array($message['type'], $type))
				{
					$return[] = $message;
				}
				elseif (is_array($type) AND Arr::is_assoc($type) AND ! in_array($message['type'], $type[1]))
				{
					$return[] = $message;
				}
				else
				{
					$remainder[] = $message;
				}
			}

			// No messages of '$type' found
			if (empty($return))
				return $default;

			$messages = $return;
		}

		if ($delete === TRUE)
		{
			if ($type === NULL OR empty($remainder))
			{
				// Nothing to save, delete the key from memory
				$this->delete();
			}
			else
			{
				// Override the messages with the remainder to simulate a deletion
				$this->_set($remainder);
			}
		}

		return $messages;
	}

	/**
	 * Get stored messages.
	 *
	 *     // Get messages
	 *     $messages = $this->_get();
	 *
	 * @return  array
	 * @throws  MSG_Exception
	 */
	protected function _get()
	{
		switch ($this->_config['storage_medium'])
		{
			case 'session':
				return $this->_session->get($this->_config['storage_key']);
			break;
			case 'cookie':
				// Workaround to enable setting & getting multiple messages
				return $this->_messages;
			break;
			default:
				throw new MSG_Exception(__('":medium" is not a valid storage medium'),
					array(':medium' => $this->_config['storage_medium']));
			break;
		}
	}

	/**
	 * Get messages once (deletes them after retrieval.)
	 *
	 *     // Get messages once
	 *     $messages = MSG::instance()->get_once();
	 *
	 *     // Get error messages once
	 *     $error_messages = MSG::instance()->get_once(MSG::ERROR);
	 *
	 *     // Customize the default value
	 *     $error_messages = MSG::instance()->get_once(MSG::ERROR, 'No error messages found');
	 *
	 * @param   string  message type (e.g. MSG::SUCCESS)
	 * @param   mixed   default value to return
	 * @return  mixed
	 */
	public function get_once($type = NULL, $default = NULL)
	{
		return $this->get($type, $default, TRUE);
	}

	/**
	 * Delete all messages, or only those of a certain type.
	 *
	 *     // Delete messages
	 *     MSG::instance()->delete();
	 *
	 *     // Delete all error messages only
	 *     MSG::instance()->delete(MSG::ERROR);
	 *
	 * @param   string  message type (e.g. MSG::SUCCESS)
	 * @return  MSG
	 * @throws  MSG_Exception
	 */
	public function delete($type = NULL)
	{
		if ($type === NULL)
		{
			// Delete everything!
			switch ($this->_config['storage_medium'])
			{
				case 'session':
					$this->_session->delete($this->_config['storage_key']);
				break;
				case 'cookie':
					$this->_messages = NULL;
					Cookie::delete($this->_config['storage_key']);
				break;
				default:
					throw new MSG_Exception(__('":medium" is not a valid storage medium'),
						array(':medium' => $this->_config['storage_medium']));
				break;
			}
		}
		else
		{
			// Deletion by type happens in get(), too weird?
			$this->get($type, NULL, TRUE);
		}

		// Enable method chaining
		return $this;
	}
	
	/**
	 * Renders the message(s), and by default deletes them too.
	 *
	 * @param   mixed   message type (e.g. MSG::SUCCESS)
	 * @param   bool    set to FALSE to not delete messages
	 * @param   mixed   string of the view to use, or a Kohana_View object 	
	 * @return  string  message output (HTML)
	 */
	public function render($type = NULL, $delete = TRUE, $view = NULL)
	{
		if (($messages = $this->get($type, NULL, $delete)) === NULL)
		{
			// Nothing to render
			return '';			
		}

		if ($view === NULL)
		{
			// Use the default view
			$view = 'msg/all';
		}

		if ( ! $view instanceof Kohana_View)
		{
			// Load the view file
			$view = new View($view);
		}

		// Return the rendered view
		return $view->set('messages', $messages)->render();
	}

} // End MSG_Core
