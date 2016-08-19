<?php

/**
 * Created by PhpStorm.
 * User: andrey
 * Date: 21.04.16
 * Time: 15:23
 */

namespace App\Test\Fixture;

use App\Test\Fixture\AppFixture;
use Cake\Core\Configure;
use Cake\Utility\Inflector;

/**
 * @property AppFixture[] $_loaded
 */
class FixtureManager extends \Cake\TestSuite\Fixture\FixtureManager
{


	/**
	 * Переопределил метод из класса FixtureManager. Теперь не требует лишних файлов
	 *
	 * @param \Cake\TestSuite\TestCase $test The test suite to load fixtures for.
	 * @return void
	 * @throws \UnexpectedValueException when a referenced fixture does not exist.
	 */
	protected function _loadFixtures($test) {
		if (empty($test->fixtures)) {
			return;
		}
		$testCaseClass = get_class($test);
		foreach ($test->fixtures as $fixture) {
			if (isset($this->_loaded[$fixture])) {
				$this->_loaded[$fixture]->setTestCase($testCaseClass);
				continue;
			}

			list($type, $pathName) = explode('.', $fixture, 2);
			$path = explode('/', $pathName);
			$name = array_pop($path);
			$additionalPath = implode('\\', $path);

			if ($type === 'core') {
				$baseNamespace = 'Cake';
			} elseif ($type === 'app') {
				$baseNamespace = Configure::read('App.namespace');
			} elseif ($type === 'plugin') {
				list($plugin, $name) = explode('.', $pathName);
				$path = implode('\\', explode('/', $plugin));
				$baseNamespace = Inflector::camelize(str_replace('\\', '\ ', $path));
				$additionalPath = null;
			} else {
				$baseNamespace = '';
				$name = $fixture;
			}
			$tableName = $name;
			$name = Inflector::camelize($name);
			$nameSegments = [
				$baseNamespace,
				'Test\Fixture',
				$additionalPath,
				$name . 'Fixture',
			];
			$className = implode('\\', array_filter($nameSegments));


			if (class_exists($className)) {
				$this->_loaded[$fixture] = new $className(null, $testCaseClass);
				$this->_fixtureMap[$name] = $this->_loaded[$fixture];
			} else {
				$this->_loaded[$fixture] = new AppFixture($tableName, $testCaseClass);
				$this->_fixtureMap[$name] = $this->_loaded[$fixture];
			}
		}
	}
}