<?php
namespace App\Test\TestCase\Controller;

use App\Test\TestCaseTrait;
use ArtSkills\TestSuite\IntegrationTestCase;


abstract class AppControllerTestCase extends IntegrationTestCase
{
	use TestCaseTrait;

	/**
	 * @inheritdoc
	 */
	public function setUp() {
		$this->configApplication(null, [CONFIG]);
		parent::setUp();
		$this->_setUp();
	}

	/** @inheritdoc */
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
