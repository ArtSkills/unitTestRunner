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

		$cmd = $catCmd . ' "' . $file . '" | mysql -D' . $this->_config['name'] . ' -u' . $this->_config['login'] . ' -p' . $this->_config['password'];
		return exec($cmd);
	}

	/**
	 * Удаляем все таблицы из текущей БД
	 */
	public function dropAllTables() {
		$dropOldTablesSql = str_replace('DROP_DB_NAME', $this->_config['name'], file_get_contents(__DIR__ . '/sql/dropAllTables.sql'));
		$dropOldTablesFile = __DIR__.'/../tmp/dropAllTables-'.$this->_config['name'].'.sql';
		file_put_contents($dropOldTablesFile, $dropOldTablesSql);
		$this->executeSqlFile($dropOldTablesFile);
	}
}