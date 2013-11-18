<?php

/**
 * @small
 */
class DummyTest extends PHPUnit_Framework_TestCase
{
	/**
	 * setUp
	 */
	public function setUp()
	{
		$_SERVER['PATH_INFO'] = '/modulex/controllerx/actionx';
	}

	public function testRun()
	{
		$this->assertTrue(true);
	}
}
