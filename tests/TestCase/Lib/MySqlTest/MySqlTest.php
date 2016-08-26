<?php

namespace App\Test\TestCase\Lib\MySqlTest;

use App\Lib\MySql;
use App\Lib\System;
use App\Test\Suite\MethodMocker;
use App\Test\TestCase\AppTestCase;
use Cake\Database\Connection;
use Cake\Datasource\ConnectionManager;

class MySqlTest extends AppTestCase
{
	/**
	 * @inheritdoc
	 */
	public $fixtures = [];

	/**
	 * Удаление всех талиц
	 */
	public function testDropDbTables() {
		/**
		 * @var Connection $testConnection
		 */
		$testConnection = ConnectionManager::get('test');
		$curConfig = $testConnection->config();
		$testConnection->query('create table test1 (id INT(10));');
		$testConnection->query('create table test2 (id INT(10));');

		MySql::dropDbTables($curConfig['host'], $curConfig['database'], $curConfig['username'], $curConfig['password'], $curConfig['port']);
		$tableCount = $testConnection->query("SELECT COUNT(*) FROM `information_schema`.`tables` WHERE `table_schema` = '" . $curConfig['database'] . "'")->fetch();
		self::assertEquals(0, $tableCount[0], 'Не удалились таблицы');
	}

	/**
	 * Выполняем несуществующий файл
	 *
	 * @expectedException \Exception
	 */
	public function testExecuteSqlFileBadPath() {
		MySql::executeSqlFile('1', '2', '3', '4', '5', '6');
	}

	/**
	 * Выполняем SQL файл
	 */
	public function testExecuteSqlFile() {
		$testTableName = 'catalog_cache';
		$testConnection = ConnectionManager::get('test');
		$curConfig = $testConnection->config();
		self::assertEmpty(MySql::executeSqlFile($curConfig['host'], $curConfig['database'], $curConfig['username'], $curConfig['password'], $curConfig['port'], __DIR__ . '/insert.sql'), 'MySQL вернул что-то плохое');


		$tableCount = $testConnection->query("SELECT COUNT(*) FROM `information_schema`.`tables` WHERE `table_schema` = '" . $curConfig['database'] . "' and `table_name` = '" . $testTableName . "'")->fetch();
		self::assertEquals(1, $tableCount[0], 'Не исполнился файл на добавление');
		MySql::dropDbTables($curConfig['host'], $curConfig['database'], $curConfig['username'], $curConfig['password'], $curConfig['port']);
	}
}