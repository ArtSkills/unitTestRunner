<?php
/**
 * Created by PhpStorm.
 * User: vladimirtunikov
 * Date: 19.08.16
 * Time: 16:47
 */

namespace App\Test\Fixture;


class FixtureInjector extends \Cake\TestSuite\Fixture\FixtureInjector
{
	/**
	 * @inheritdoc
	 */
	public function startTestSuite(\PHPUnit_Framework_TestSuite $suite) {
		// сделано специально
	}

	/**
	 * @inheritdoc
	 */
	public function endTestSuite(\PHPUnit_Framework_TestSuite $suite) {
		$this->_fixtureManager->shutDown();
	}
}