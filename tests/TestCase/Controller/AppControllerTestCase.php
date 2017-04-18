<?php
namespace App\Test\TestCase\Controller;

abstract class AppControllerTestCase extends \ArtSkills\TestSuite\AppControllerTestCase
{
	/**
	 * @inheritdoc
	 */
	public function setUp() {
		$this->configApplication(null, [CONFIG]);
		parent::setUp();
	}
}
