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

		$this->setUpSuite();
	}

	/**
	 * @inheritdoc
	 */
	public function tearDown() {
		parent::tearDown();
		$this->tearDownSuite();
	}
}