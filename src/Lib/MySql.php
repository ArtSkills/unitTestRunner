<?php
/**
 * Created by PhpStorm.
 * User: vladimirtunikov
 * Date: 18.08.16
 * Time: 14:20
 */

namespace App\Lib;

use \PDO;
use \Exception;

class MySql
{
	/**
	 * Удаляем все таблицы из БД
	 *
	 * @param string $dbHost
	 * @param string $dbName
	 * @param string $dbLogin
	 * @param string $dbPassword
	 * @param int $dbPort
	 */
	public static function dropDbTables($dbHost, $dbName, $dbLogin, $dbPassword, $dbPort = 3306) {
		$connection = new PDO('mysql:host=' . $dbHost . ';dbname=' . $dbName . ';port=' . $dbPort, $dbLogin, $dbPassword);
		$connection->prepare('SET FOREIGN_KEY_CHECKS = 0')->execute();

		$tablesQ = $connection->prepare('SELECT `table_name` FROM information_schema.tables WHERE table_schema = :current_db');
		$tablesQ->execute([
			':current_db' => $dbName,
		]);

		while ($tableInfo = $tablesQ->fetch()) {
			$connection->prepare('DROP TABLE `' . $tableInfo['table_name'] . '`')
				->execute();
		}
	}

	/**
	 * Исполняем MySQL файл через системную прогу mysql
	 *
	 * @param string $dbHost
	 * @param string $dbName
	 * @param string $dbLogin
	 * @param string $dbPassword
	 * @param string $dbPort
	 * @param string $sqlFile
	 * @return string пустая строка, если всё ок, в противном случае содержимое ошибки
	 * @throws Exception
	 */
	public static function executeSqlFile($dbHost, $dbName, $dbLogin, $dbPassword, $dbPort, $sqlFile) {
		if (!is_file($sqlFile)) {
			throw new Exception('File "' . $sqlFile . '" not exists!');
		}

		if (mb_substr($sqlFile, -3) == '.gz') {
			$catCmd = 'zcat';
		} else {
			$catCmd = 'cat';
		}

		$configFile = TMP . $dbName . '.cnf';
		file_put_contents($configFile, "[mysql]\n" .
			"host=" . $dbHost . "\n" .
			"user=" . $dbLogin . "\n" .
			"port=" . $dbPort . "\n" .
			"password=" . $dbPassword . "\n" .
			"database=" . $dbName . "\n");

		$cmd = $catCmd . ' "' . $sqlFile . '" | mysql --defaults-file="' . $configFile . '"';
		$resultStrings = System::execute($cmd);
		unlink($configFile);
		return $resultStrings;
	}
}
