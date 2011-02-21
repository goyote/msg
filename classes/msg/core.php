<?php defined('SYSPATH') or die('No direct script access.');
/**
 * MSG is a blurb (or "flash") messaging system for the Kohana framework.
 *
 * @package    MSG
 * @category   Base
 * @author     Gregorio Ramirez
 * @copyright  (c) 2011 Gregorio Ramirez
 * @license    MIT
 * @see        http://kohanaftw.com/modules/msg/
 */
abstract class MSG_Core {

	// Message types
	const SESSION  = 'session';
	const COOKIE   = 'cookie';
	const WARNING  = 'warning';
	const SUCCESS  = 'success';
	const NOTICE   = 'notice';
	const ACCESS   = 'access';
	const ERROR    = 'error';
	const ALERT    = 'alert';
	const CRITICAL = 'critical';

	/**
	 * @var  string  default driver
	 */
	public static $default = 'session';

	/**
	 * @var  string  default view
	 */
	public static $default_view = 'msg/all';

	/**
	 * @var  array  MSG instances
	 */
	protected static $_instances;

	/**
	 * @var  array  configuration settings
	 */
	protected $_config;

	/**
	 * @var  array  temporary storage medium
	 */
	protected $_messages;

	/**
	 * Get a singleton instance.
	 *
	 *     $msg = MSG::instance();
	 *
	 *     // Use the cookie driver
	 *     $msg = MSG::instance(MSG::COOKIE);
	 *
	 * @param   string  storage driver
	 * @param   array   configuration settings
	 * @return  MSG
	 */
	public static function instance($name = NULL, array $settings = NULL)
	{
		if ($name === NULL)
		{
			// Use the default driver
			$name = MSG::$default;
		}

		if ( ! isset(MSG::$_instances[$name]))
		{
			// Load the configuration
			$config = Kohana::config('msg.'.$name);
			
			if ($settings)
			{
				// Overload the default configuration
				$config += $settings;
			}

			// Add the driver prefix
			$class = 'MSG_Driver_'.ucfirst($name);
			
			MSG::$_instances[$name] = new $class($config);
		}

		return MSG::$_instances[$name];
	}

	/**
	 * Save the config.
	 *
	 * @param  array  configuration settings
	 */
	protected function __construct(array $config)
	{
		$this->_config = $config;
	}

	/**
	 * Set a new message.
	 *
	 *     // Set a success message
	 *     MSG::instance()->set(MSG::SUCCESS, '%s now has %d monkeys',
	 *         array($this->user->first_name, count($monkeys)));
	 *
	 * @param   string  message type (e.g. MSG::SUCCESS)
	 * @param   mixed   message text or array of messages
	 * @param   array   values to replace with sprintf
	 * @param   mixed   custom data
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
		}
		else
		{
			if ($values)
			{
				// $text goes first
				array_unshift($values, $text);

				// Insert the values into the message
				$text = call_user_func_array('sprintf', $values);
			}

			if ($this->_config['handicap'])
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

			// Save the updated array (overrides the current)
			$this->_set($messages);
		}

		return $this;
	}
	
	/**
	 * Get messages.
	 *
	 *     $messages = MSG::instance()->get();
	 *
	 *     // Get error messages only
	 *     $error_messages = MSG::instance()->get(MSG::ERROR);
	 *
	 *     // Get error and alert messages
	 *     $messages = MSG::instance()->get(array(MSG::ERROR, MSG::ALERT));
	 *
	 *     // Customize the default value
	 *     $error_messages = MSG::instance()->get(MSG::ERROR, 'No error messages found');
	 *
	 * @param   mixed  message type (e.g. MSG::SUCCESS, array(MSG::ERROR, MSG::ALERT))
	 * @param   mixed  default value to return
	 * @param   bool   delete the messages?
	 * @return  mixed
	 */
	public function get($type = NULL, $default = NULL, $delete = FALSE)
	{
		// Get the messages
		$messages = $this->_get();

		if ($messages === NULL)
		{
			// No messages to return
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
				if (($message['type'] === $type)
					OR (is_array($type) AND in_array($message['type'], $type))
					OR (is_array($type) AND Arr::is_assoc($type) AND ! in_array($message['type'], $type[1])))
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
				$this->_delete();
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
	 * Get messages once.
	 *
	 *     $messages = MSG::instance()->get_once();
	 *
	 *     // Get error messages
	 *     $error_messages = MSG::instance()->get_once(MSG::ERROR);
	 *
	 *     // Get error and alert messages
	 *     $error_messages = MSG::instance()->get_once(array(MSG::ERROR, MSG::ALERT));
	 *
	 *     // Customize the default value
	 *     $error_messages = MSG::instance()->get_once(MSG::ERROR, 'No error messages found');
	 *
	 * @param   mixed  message type (e.g. MSG::SUCCESS, array(MSG::ERROR, MSG::ALERT))
	 * @param   mixed  default value to return
	 * @return  mixed
	 */
	public function get_once($type = NULL, $default = NULL)
	{
		return $this->get($type, $default, TRUE);
	}

	/**
	 * Delete messages.
	 *
	 *     MSG::instance()->delete();
	 *
	 *     // Delete error messages
	 *     MSG::instance()->delete(MSG::ERROR);
	 *
	 *     // Delete error and alert messages
	 *     MSG::instance()->delete(array(MSG::ERROR, MSG::ALERT));
	 *
	 * @param   mixed  message type (e.g. MSG::SUCCESS, array(MSG::ERROR, MSG::ALERT))
	 * @return  MSG
	 */
	public function delete($type = NULL)
	{
		if ($type === NULL)
		{
			// Delete everything!
			$this->_delete();
		}
		else
		{
			// Deletion by type happens in get(), too weird?
			$this->get($type, NULL, TRUE);
		}

		return $this;
	}
	
	/**
	 * Render messages (deletes them by default.)
	 *
	 *     <div id="wrapper">
	 *         ...
	 *         <?php echo MSG::instance()->render() ?>
	 *
	 * @param   mixed   message type (e.g. MSG::SUCCESS, array(MSG::ERROR, MSG::ALERT))
	 * @param   bool    delete the messages?
	 * @param   mixed   View name or object
	 * @return  string  rendered View
	 */
	public function render($type = NULL, $delete = TRUE, $view = NULL)
	{
		if (($messages = $this->get($type, NULL, $delete)) === NULL)
		{
			// No messages
			return '';
		}

		if ($view === NULL)
		{
			// Use the default view
			$view = MSG::$default_view;
		}

		if ( ! $view instanceof Kohana_View)
		{
			// Load the view file
			$view = new View($view);
		}

		return $view
			->set('messages', $messages)
			->render();
	}
	
	abstract protected function _set(array $messages);
	
	abstract protected function _get();
	
	abstract protected function _delete();

} // End MSG_Core
