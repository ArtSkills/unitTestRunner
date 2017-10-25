<?php
namespace App\Test\TestCase;

use Cake\Core\Configure;

abstract class AppTestCase extends \ArtSkills\TestSuite\AppTestCase
{
	/** @inheritdoc */
	public function setUp() {
		Configure::write('serverId', 'PHP7');
		parent::setUp();
	}
}