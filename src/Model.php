<?php
namespace ArtSkills\TestRunner;

use \PDO;

class Model extends PDO
{
	const DEFAULT_HOST = 'localhost';

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
		parent::__construct('mysql:host=' . self::DEFAULT_HOST . ';dbname=' . $config['name'], $config['login'], $config['password']);
	}
}