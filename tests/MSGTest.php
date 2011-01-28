<?php defined('SYSPATH') or die('No direct script access.');
/**
 * Unit tests for the MSG module.
 *
 * @group      kohana
 * @group      kohana.module
 * @group      kohana.module.msg
 * @package    Unittest
 * @author     Gregorio Ramirez
 * @copyright  (c) 2011 Gregorio Ramirez
 * @license    http://kohanaphp.com/license
 */
class MSGTest extends Kohana_Unittest_TestCase {

	/**
	 * Delete the session and the 'msg' cookie before starting.
	 *
	 * @return  void
	 */
	public static function setUpBeforeClass()
	{
		// Make sure we start with no data in the session and cookie
		Session::instance()->destroy();
		Cookie::delete('msg');
	}

	/**
	 * Test retrieving a MSG instance with [MSG::instance()].
	 *
	 * @test
	 * @covers  MSG::instance
	 */
	public function test_getting_a_msg_instance()
	{
		$msg = MSG::instance();
		$this->assertInstanceOf('MSG_Core', $msg);
		
		// [MSG::instance()] should return the same object again
		$this->assertSame($msg, MSG::instance());
		
		$msg_cookie = MSG::instance(MSG::COOKIE);
		$this->assertInstanceOf('MSG_Core', $msg_cookie);
		
		// [$msg] and [$msg_cookie] should hold diffrent objects
		$this->assertNotSame($msg, $msg_cookie);
		
		$this->assertSame($msg, MSG::instance());
		$this->assertSame($msg, MSG::instance(MSG::SESSION));
		
		$this->assertSame($msg_cookie, MSG::instance(MSG::COOKIE));
		$this->assertSame($msg_cookie, MSG::instance(MSG::COOKIE, Kohana::config('msg.cookie')));
	}

	/**
	 * Passing no arguments to [set()] should throw an exception.
	 *
	 * @test
	 * @covers  MSG::set
	 * @expectedException  ErrorException
	 */
	public function test_passing_no_arguments_to_set()
	{
		MSG::instance()->set();
	}

	/**
	 * Passing no message to s[et()] should throw an exception.
	 *
	 * @test
	 * @covers  MSG::set
	 * @expectedException  ErrorException 
	 */
	public function test_passing_no_message_to_set()
	{
		MSG::instance()->set(MSG::ALERT);
	}

	/**
	 * set() should return $this to allow method chaining.
	 *
	 * @test
	 * @covers  MSG:set
	 */
	public function test_method_chaining_with_set()
	{
		$msg = MSG::instance();
		$this->assertSame($msg, MSG::instance()->set(MSG::SUCCESS, 'teh bomb'));
		$this->assertSame(MSG::instance(), MSG::instance()->set(MSG::WARNING, 'teh bomb')->set(MSG::ERROR, 'teh lol'));
	}

	/**
	 * Test data for test_set_and_get().
	 *
	 * @return  array
	 */
	public function provider_test_set_and_get()
	{
		return array(
			array(MSG::ERROR, 'teh bomb', NULL, NULL),
			array(MSG::ALERT, 23, NULL, FALSE),
			array(MSG::SUCCESS, 1.1, NULL, 'DUH'),
			array(MSG::WARNING, TRUE, NULL, array('foo' => 'bar', 'lol' => TRUE)),
		);
	}

	/**
	 * Test setting and getting messages.
	 *
	 * @test
	 * @dataProvider  provider_test_set_and_get
	 * @covers  MSG::get
	 * @covers  MSG::set
	 */
	public function test_set_and_get($type, $text, $values, $data)
	{
		$messages = MSG::instance()->set($type, $text, $values, $data)->get();
		
		// $messages should an array
		$this->assertType('array', $messages);
		
		// We should get back the same data we passed in
		$this->assertSame($type, $messages[0]['type']);
		$this->assertSame($text, $messages[0]['text']);
		$this->assertSame($data, $messages[0]['data']);
	}

	/**
	 * Test embedding values with sprintf.
	 *
	 * @test
	 * @covers  MSG::get
	 * @covers  MSG::set
	 */
	public function test_embedding_values_with_sprintf()
	{
		$expected_outcome = 'You are 2 dorky.';
		$messages = MSG::instance()->set(MSG::ACCESS, 'You are %d %s.', array(2, 'dorky'))->get();
		$this->assertSame($expected_outcome, $messages[0]['text']);
	}

	/**
	 * If no mesasges exist, get() should return NULL.
	 *
	 * @test
	 * @covers  MSG::get
	 * @covers  MSG::get_once	
	 */
	public function test_getting_null_when_no_messages_are_set()
	{
		// Teaser	
		MSG::instance()->set(MSG::ERROR, '%s', array('lol'), 'custom data')->delete();
		
		$this->assertNULL(MSG::instance()->get());
		$this->assertNULL(MSG::instance()->get(MSG::ERROR));
		
		// Teaser
		MSG::instance()->set(MSG::ERROR, '%s', array('lol'), 'custom data')->delete(MSG::ERROR);
		
		$this->assertNULL(MSG::instance()->get_once());
		$this->assertNULL(MSG::instance()->get_once(MSG::ERROR));
	}

	public function testGettingMultipleTypes()
	{
		MSG::instance()
			->set(MSG::ERROR, '%s', array('lol'), 'custom data')
			->set(MSG::ERROR, '%s', array('lol'), 'custom data')
			->set(MSG::ALERT, '%s', array('lol'), 'custom data')
			->set(MSG::ALERT, '%s', array('lol'), 'custom data')
			->set(MSG::WARNING, '%s', array('lol'), 'custom data');
		$this->assertSame(4, count(MSG::instance()->get_once(array(MSG::ERROR, MSG::ALERT))));

		$message = MSG::instance()->get_once(MSG::WARNING);
		$this->assertSame(MSG::WARNING, $message[0]['type']);
	}

	public function testGettingEverythingButACertainType()
	{
		MSG::instance()
			->set(MSG::ERROR, '%s', array('lol'), 'custom data')
			->set(MSG::ERROR, '%s', array('lol'), 'custom data')
			->set(MSG::ALERT, '%s', array('lol'), 'custom data')
			->set(MSG::ALERT, '%s', array('lol'), 'custom data')
			->set(MSG::WARNING, '%s', array('lol'), 'custom data');
		$messages = MSG::instance()->get_once(array(1 => array(MSG::ALERT, MSG::WARNING)));
		
		$this->assertSame(2, count($messages));
		$this->assertSame(MSG::ERROR, $messages[0]['type']);
		$this->assertSame(MSG::ERROR, $messages[1]['type']);

		$messages = MSG::instance()->get_once(array(1 => array(MSG::WARNING)));
		$this->assertSame(2, count($messages));
		$this->assertSame(MSG::ALERT, $messages[0]['type']);
		$this->assertSame(MSG::ALERT, $messages[1]['type']);

		$this->assertNULL(MSG::instance()->get_once(array(1 => array(MSG::WARNING))));

		$messages = MSG::instance()->get_once(array(1 => array(MSG::ERROR)));
		$this->assertSame(1, count($messages));
		$this->assertSame(MSG::WARNING, $messages[0]['type']);
	}

	/**
	 * As is the kohana convention, the default value returned by get() can be overriden.
	 *
	 * @covers  MSG::get
	 * @covers  MSG::get_once	
	 */
	public function test_overriding_the_default_return_value()
	{
		// Teaser
		MSG::instance()->set(MSG::ERROR, '%s', array('lol'), 'custom data')->delete();
		
		$my_default = 'teh bomb';
		$this->assertSame($my_default, MSG::instance()->get(NULL, $my_default));
		$this->assertSame($my_default, MSG::instance()->get(MSG::NOTICE, $my_default));
		$this->assertSame($my_default, MSG::instance()->get_once(NULL, $my_default));
		$this->assertSame($my_default, MSG::instance()->get_once(MSG::NOTICE, $my_default));
		
		// Teaser
		MSG::instance()->set(MSG::ERROR, '%s', array('lol'), 'custom data')->delete(MSG::ERROR);

		// Same deal just backwards
		// We shouldn't get NULL because we're overriding the default value
		$this->assertNotSame(NULL, MSG::instance()->get(NULL, $my_default));
		$this->assertNotSame(NULL, MSG::instance()->get_once(NULL, $my_default));
	}

	/**
	 * Test retrieving the correct amount of messages.
	 * 
	 * @test
	 * @covers  MSG::get
	 * @covers  MSG::get_once	
	 */
	public function test_messages_count()
	{
		MSG::instance()
			->set(MSG::ERROR, '%s', array('lol'), 'custom data')
			->set(MSG::ERROR, '%s', array('lol'), 'custom data')
			->set(MSG::ALERT, '%s', array('lol'), 'custom data')
			->set(MSG::ALERT, '%s', array('lol'), 'custom data')
			->set(MSG::ACCESS, '%s', array('lol'), 'custom data')
			->set(MSG::ACCESS, '%s', array('lol'), 'custom data');	
		$this->assertSame(6, count(MSG::instance()->get()));
		
		// Delete two messages
		MSG::instance()->delete(MSG::ERROR);		
		$this->assertSame(4, count(MSG::instance()->get()));
		
		// Delete two more messages
		MSG::instance()->get_once(MSG::ALERT);
		$this->assertSame(2, count(MSG::instance()->get_once()));
		
		MSG::instance()->delete(MSG::ACCESS);
		
		// get() should return 'NULL' instead of '0'
		$this->assertNULL(MSG::instance()->get());
	}

	/**
	 * Test get_once.
	 * 
	 * @test
	 * @covers  MSG::get_once
	 */
	public function testGettingAMessageOnlyOnce()
	{
		// Test by passing the type of message
		MSG::instance()
			->set(MSG::ACCESS, 'LOL to you! 1')
			->set(MSG::ACCESS, 'LOL to you! 2');
		$this->assertSame(2, count(MSG::instance()->get_once(MSG::ACCESS)));
		$this->assertNULL(MSG::instance()->get_once());
		
		// Test by grabing everything
		MSG::instance()
			->set(MSG::ACCESS, 'LOL to you! 1')
			->set(MSG::ACCESS, 'LOL to you! 2');
		$this->assertSame(2, count(MSG::instance()->get_once()));
		$this->assertNULL(MSG::instance()->get_once());		
	}

	public function testGettingAMessageOnlyOnceOfACertainType()
	{
		MSG::instance()
				->set(MSG::ACCESS, 'LOL to you! 1')
				->set(MSG::WARNING, 'LOL to you! 2')
				->set(MSG::ERROR, 'LOL to you! 3')
				->set(MSG::ACCESS, 'LOL to you! 4')
				->set(MSG::ERROR, 'LOL to you! 5');

		$this->assertSame(2, count(MSG::instance()->get_once(MSG::ERROR)));
		$this->assertNULL(MSG::instance()->get_once(MSG::ERROR));
		$this->assertNULL(MSG::instance()->get(MSG::ERROR));
		$this->assertSame(3, count(MSG::instance()->get()));
		$this->assertSame(2, count(MSG::instance()->get_once(MSG::ACCESS)));
		$this->assertNULL(MSG::instance()->get_once(MSG::ACCESS));
		$this->assertSame(1, count(MSG::instance()->get_once()));
		$this->assertNULL(MSG::instance()->get_once());
	}

	public function testRetrievingTheCorrectNumberOfArrayItems()
	{
		$this->assertSame(0, count(MSG::instance()->get()));

		MSG::instance()
			->set(MSG::ACCESS, 'LOL to you! 1')
			->set(MSG::ACCESS, 'LOL to you! 2')
			->set(MSG::ACCESS, 'LOL to you! 3')
			->set(MSG::ACCESS, 'LOL to you! 4');

		$this->assertSame(4, count(MSG::instance()->get()));
		$this->assertSame(5, count(MSG::instance()->set(MSG::NOTICE, 'LOL')->get()));
		$this->assertSame(1, count(MSG::instance()->delete(MSG::ACCESS)->get()));
		$this->assertNULL(MSG::instance()->delete(MSG::NOTICE)->get());
	}

	public function testDeletingAMessageAndDeleteChaining()
	{
		MSG::instance()->set(MSG::ERROR, 'teh bomb')->set(MSG::ERROR, 'teh bomb')->delete()->set(MSG::ERROR, 'teh bomb')->delete();
		$this->assertNULL(MSG::instance()->get());
	}

	public function testDeletingMessagesOfACertainType()
	{
		MSG::instance()
			->set(MSG::ACCESS, 'LOL to you! 1')
			->set(MSG::ACCESS, 'LOL to you! 2')
			->set(MSG::ERROR, 'LOL to you! 3')
			->set(MSG::ERROR, 'LOL to you! 4');

		$this->assertSame(2, count(MSG::instance()->delete(MSG::ACCESS)->get(MSG::ERROR)));
	}

} // End MSGTest