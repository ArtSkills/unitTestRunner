<?php
namespace App\Test\TestCase;

use App\Test\TestCaseTrait;
use Cake\TestSuite\TestCase;

abstract class AppTestCase extends TestCase
{
	use TestCaseTrait;

	/**
	 * @inheritdoc
	 */
	public function setUp() {
		parent::setUp();
		$this->_setUp();
	}

	/**
	 * @inheritdoc
	 */
	public function tearDown() {
		parent::tearDown();
		$this->_tearDown();
	}

	/** @inheritdoc */
	public static function setUpBeforeClass() {
		parent::setUpBeforeClass();
		self::_setUpBeforeClass();
	}

}