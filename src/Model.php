<?php
namespace ArtSkills\TestRunner;

use \PDO;

class Model extends PDO
{
	const DEFAULT_HOST = 'localhost';

	/**
	 * Настройки
	 *
	 * @var null|array
	 */
	private $_config = null;

	/**
	 * Model constructor.
	 *
	 * @param array $config {
	 * @var string $name
	 * @var string $login
	 * @var string $password
	 * }
	 */
	public function __construct($config) {
		$this->_config = $config;
		parent::__construct('mysql:host=' . self::DEFAULT_HOST . ';dbname=' . $config['name'], $config['login'], $config['password']);

		$this->prepare('SET FOREIGN_KEY_CHECKS = 0')->execute();
	}

	/**
	 * Исполняем sql файл через команду mysql
	 *
	 * @param string $file
	 * @return string
	 */
	public function executeSqlFile($file) {
		if (mb_substr($file, -3) == '.gz') {
			$catCmd = 'zcat';
		} else {
			$catCmd = 'cat';
		}

		$configFile = __DIR__.'/../tmp/'.$this->_config['name'].'.cnf';
		file_put_contents($configFile, "[mysql]\n".
			"user=".$this->_config['login']."\n".
			"password=".$this->_config['password']."\n".
			"database=".$this->_config['name']."\n");

		$cmd = $catCmd . ' "' . $file . '" | mysql --defaults-file="'.$configFile.'"';
		$resultStrings = System::execute($cmd);
		unlink($configFile);
		return $resultStrings;
	}

	/**
	 * Удаляем все таблицы из текущей БД
	 */
	public function dropAllTables() {
		$tablesQ = $this->prepare('SELECT table_name FROM information_schema.tables WHERE table_schema = :current_db');
		$tablesQ->execute([
				':current_db' => $this->_config['name']
			]);

		while ($tableInfo = $tablesQ->fetch()) {
			$this->prepare('DROP TABLE `'.$tableInfo['table_name'].'`')
				->execute();
		}
	}
}