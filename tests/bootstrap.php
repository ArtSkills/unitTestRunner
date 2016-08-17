<?php
use Cake\Cache\Cache;
use Cake\Database\Connection;
use Cake\Datasource\ConnectionManager;

/**
 * Test runner bootstrap.
 *
 * Add additional configuration/setup your application needs when running
 * unit tests in this file.
 */
require dirname(__DIR__) . '/config/bootstrap.php';

Cache::drop('short');
Cache::drop('long');
Cache::config([
	'short' => [
		'engine' => 'File',
		'prefix' => 'memcached_short_',
		'serialize' => true,
		'duration' => '+30 seconds',
	],

	'long' => [
		'engine' => 'File',
		'prefix' => 'memcached_long_',
		'serialize' => true,
		'duration' => '+30 seconds', //to mars
	],
]);

ini_set('soap.wsdl_cache_ttl', 1);
define('TEST_MODE', true);

\Cake\Core\Configure::write(\App\Lib\Client::CUSTOM_ADAPTER_CONFIGURE_NAME, \App\Test\Suite\HttpClientAdapter::class);

/**
 * @var Connection $testConnection
 */
$testConnection = ConnectionManager::get('test');
$dbName = $testConnection->config()['database'];
$existingTables = $testConnection->query("SELECT `table_name` FROM `information_schema`.`tables` WHERE `table_schema` = '" . $dbName . "'")->fetchAll();
if (!empty($existingTables)) {
	$existingTables = '`' . implode('`, `', array_column($existingTables, 0)) . '`';
	$testConnection->execute('DROP TABLE ' . $existingTables)->closeCursor();
}
unset($testConnection);

Cache::clear(false, '_cake_model_'); //app.php 'Cache' => ['_cake_core_'] (tmp/cache/models/)
Cache::clear(false, '_cake_core_');
