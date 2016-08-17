<?php

namespace App\Test;

use App\Test\Suite\HttpClientMocker;
use App\Test\Suite\MethodMocker;
use App\Test\Suite\ConstantMocker;
use Cake\Cache\Cache;
use Cake\Datasource\ModelAwareTrait;
use Cake\I18n\Time;
use Cake\ORM\Locator\LocatorAwareTrait;
use Cake\Utility\Inflector;

/**
 * Тестовое окружение
 *
 * @package App\Test
 */
trait TestCaseTrait {

	use ModelAwareTrait;
	use LocatorAwareTrait;

	/**
	 * Инициализация тестового окружения
	 */
	public function setUpSuite() {
		$this->_clearCache();
		MethodMocker::mockTestSuiteMethods();
		$this->_loadFixtureModels();
	}

	/**
	 * Чиста тестового окружения
	 */
	public function tearDownSuite() {
		MethodMocker::restore($this->hasFailed());
		ConstantMocker::restore();
		HttpClientMocker::clean($this->hasFailed());

		Time::setTestNow(null); // сбрасываем тестовое время
	}

	/**
	 * Чистка кеша
	 */
	protected function _clearCache() {
		Cache::clear(false, 'short');
		Cache::clear(false, 'long');
		Cache::clear(false, 'default');
	}

	/**
	 * loadModel на все таблицы фикстур
	 */
	protected function _loadFixtureModels() {
		if (empty($this->fixtures)) {
			return;
		}
		$this->modelFactory('Table', [$this->tableLocator(), 'get']);
		foreach ($this->fixtures as $fixtureName) {
			$splitName = pluginSplit($fixtureName);
			$modelAlias = Inflector::camelize(array_pop($splitName));
			$this->loadModel($modelAlias);
		}
	}
}