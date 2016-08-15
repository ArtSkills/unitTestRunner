<?php
/**
 * Created by PhpStorm.
 * User: vladimirtunikov
 * Date: 15.08.16
 * Time: 14:57
 */

namespace ArtSkills\TestRunner;


abstract class CallableEntity
{
	/**
	 * Базовая конфигурация
	 *
	 * @var array|null
	 */
	protected $_config = null;

	/**
	 * Модэль
	 *
	 * @var Model|null
	 */
	protected $_model = null;

	/**
	 * IndexController constructor.
	 */
	public function __construct() {
		$this->_config = require_once __DIR__ . '/config.php';
		$this->_model = new Model($this->_config['database']);
	}
}