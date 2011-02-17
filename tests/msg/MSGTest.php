<?php defined('SYSPATH') or die('No direct script access.');
/**
 * Unit tests for the MSG module.
 *
 * @group      kohana
 * @group      kohana.module
 * @group      kohana.module.msg
 * @package    Unittest
 * @category   Tests
 * @author     Gregorio Ramirez
 * @copyright  (c) 2011 Gregorio Ramirez
 * @license    http://kohanaphp.com/license
 */
class MSGTest extends Kohana_Unittest_TestCase {

	/**
	 * Start fresh each test.
	 */
	public function setUp()
	{
		Session::instance()->destroy();
		Cookie::delete('msg');
		MSG::instance(MSG::SESSION)->delete();
		MSG::instance(MSG::COOKIE)->delete();
		parent::setUp();
	}

	/**
	 * @test
	 * @covers  MSG::instance
	 */
	public function test_getting_a_msg_instance()
	{
		$msg = MSG::instance();
		$this->assertInstanceOf('MSG_Core', $msg);
		$this->assertInstanceOf('MSG_Driver_Session', $msg);
		$this->assertSame($msg, MSG::instance());
		$msg_cookie = MSG::instance(MSG::COOKIE);
		$this->assertInstanceOf('MSG_Core', $msg_cookie);
		$this->assertInstanceOf('MSG_Driver_Cookie', $msg_cookie);
		$this->assertNotSame($msg, $msg_cookie);
		$this->assertSame($msg, MSG::instance());
		$this->assertSame($msg_cookie, MSG::instance(MSG::COOKIE));
		$this->assertSame($msg, MSG::instance(MSG::SESSION));
	}

	/**
	 * @test
	 * @covers  MSG::instance
	 */
	public function test_overriding_the_default_driver()
	{
		MSG::$default = 'cookie';
		$this->assertInstanceOf('MSG_Driver_Cookie', MSG::instance());
	}

	/**
	 * @test
	 * @covers  MSG::set
	 * @expectedException  ErrorException
	 */
	public function test_passing_no_arguments_to_set()
	{
		MSG::instance()->set();
	}

	/**
	 * @test
	 * @covers  MSG::set
	 * @expectedException  ErrorException 
	 */
	public function test_passing_no_message_to_set()
	{
		MSG::instance()->set(MSG::ALERT);
	}

	/**
	 * @test
	 * @covers  MSG:set
	 */
	public function test_method_chaining_with_set()
	{
		$this->assertInstanceOf('MSG_Core', MSG::instance()->set(MSG::SUCCESS, 'foo'));
	}

	/**
	 * @test
	 * @covers  MSG:delete
	 */
	public function test_method_chaining_with_delete()
	{
		$this->assertInstanceOf('MSG_Core', MSG::instance()->delete());
	}

	/**
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
	 * @test
	 * @dataProvider  provider_test_set_and_get
	 * @covers  MSG::get
	 * @covers  MSG::set
	 */
	public function test_set_and_get($type, $text, $values, $data)
	{
		$messages = MSG::instance()->set($type, $text, $values, $data)->get_once();
		$this->assertType('array', $messages);
		$this->assertSame($type, $messages[0]['type']);
		$this->assertSame($text, $messages[0]['text']);
		$this->assertSame($data, $messages[0]['data']);
	}

	/**
	 * @test
	 * @covers  MSG::get
	 * @covers  MSG::set
	 */
	public function test_embedding_values_with_sprintf()
	{
		$expected_outcome = 'You are 2 dorky';
		$messages = MSG::instance()->set(MSG::ACCESS, 'You are %d %s', array(2, 'dorky'))->get_once();
		$this->assertSame($expected_outcome, $messages[0]['text']);
	}

	/**
	 * @test
	 * @covers  MSG::get
	 * @covers  MSG::get_once	
	 */
	public function test_getting_null_when_no_messages_are_set()
	{
		$this->assertNULL(MSG::instance()->get());
		MSG::instance()->set(MSG::ERROR, '%s', array('lol'), 'custom data')->delete();
		$this->assertNULL(MSG::instance()->get());
		MSG::instance()->set(MSG::ERROR, '%s', array('lol'), 'custom data')->delete(MSG::ERROR);
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
	 * @test
	 * @covers  MSG::get
	 * @covers  MSG::get_once	
	 */
	public function test_overriding_the_default_return_value()
	{
		$my_default = 'OMFG';
		$this->assertSame($my_default, MSG::instance()->get(NULL, $my_default));
		MSG::instance()->set(MSG::ERROR, '%s', array('lol'), 'custom data')->delete();
		$this->assertSame($my_default, MSG::instance()->get(MSG::NOTICE, $my_default));
		$this->assertSame($my_default, MSG::instance()->get_once(NULL, $my_default));
		$this->assertSame($my_default, MSG::instance()->get_once(array(MSG::NOTICE, MSG::ERROR), $my_default));
		MSG::instance()->set(MSG::ERROR, '%s', array('lol'), 'custom data')->delete(MSG::ERROR);
		$this->assertNotSame(NULL, MSG::instance()->get(NULL, $my_default));
		$this->assertNotSame(NULL, MSG::instance()->get_once(NULL, $my_default));
	}

	/**
	 * @test
	 * @covers  MSG::get
	 * @covers  MSG::get_once	
	 */
	public function test_message_count()
	{
		MSG::instance()
			->set(MSG::ERROR, '%s', array('lol'), 'custom data')
			->set(MSG::ERROR, '%s', array('lol'), 'custom data')
			->set(MSG::ALERT, '%s', array('lol'), 'custom data')
			->set(MSG::ALERT, '%s', array('lol'), 'custom data')
			->set(MSG::ACCESS, '%s', array('lol'), 'custom data')
			->set(MSG::ACCESS, '%s', array('lol'), 'custom data');	
		$this->assertSame(6, count(MSG::instance()->get()));
		MSG::instance()->delete(MSG::ERROR);
		$this->assertSame(4, count(MSG::instance()->get()));
		MSG::instance()->get_once(MSG::ALERT);
		$this->assertSame(2, count(MSG::instance()->get_once()));
		MSG::instance()->delete(MSG::ACCESS);
		$this->assertNULL(MSG::instance()->get());
	}

	/**
	 * @test
	 * @covers  MSG::get_once
	 */
	public function testGettingAMessageOnlyOnce()
	{
		MSG::instance()
			->set(MSG::ACCESS, 'LOL to you! 1')
			->set(MSG::ACCESS, 'LOL to you! 2');
		$this->assertSame(2, count(MSG::instance()->get_once(MSG::ACCESS)));
		$this->assertNULL(MSG::instance()->get_once());
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