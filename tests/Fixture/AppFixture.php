<?php
/**
 * Created by PhpStorm.
 * User: tune
 * Date: 12.10.15
 * Time: 20:16
 */

namespace App\Test\Fixture;

use Cake\Database\Connection;
use Cake\TestSuite\Fixture\TestFixture;
use Cake\Utility\Inflector;
use Cake\Datasource\ConnectionInterface;
use Cake\Datasource\ConnectionManager;
use Exception;


class AppFixture extends TestFixture
{
	/**
	 * Полноценный SQL запрос создания MYSQL таблицы без внутренних преобразований типов
	 *
	 * @var string
	 */
	protected $_createTableSqlQuery = '';

	/**
	 * Класс теста, для которого загружаются фикстуры
	 *
	 * @var string $_testCaseClass
	 */
	protected $_testCaseClass = null;

	/**
	 * Записи глобальной фикстуры
	 * Запоминаем их, чтоб не парсить файл кучу раз
	 *
	 * @var array
	 */
	protected $_defaultFixtureRecords = [];

	/**
	 * Конструктор с функцией установки таблицы
	 *
	 * @param null $table
	 * @param string $caseClass
	 */
	public function __construct($table = null, $caseClass = null) {
		$this->_testCaseClass = $caseClass;
		if (!empty($table)) {
			$this->table = $table;
		}
		parent::__construct();
	}

	/**
	 * @inheritdoc
	 */
	public function init() {
		if (!isset($this->import['table']) || !isset($this->import['connection'])) {
			$this->_getCreateQuery();
		}
		$this->_loadFixtureData();
		parent::init();
	}

	/**
	 * Получает запрос CREATE TABLE для текущей таблицы и сохраняет его в $this->_createTableSqlQuery
	 */
	private function _getCreateQuery() {
		$this->import['connection'] = 'test_structure';

		if (!empty($this->table)) {
			$this->import['table'] = $this->table;
		} else {
			list(, $class) = namespaceSplit(get_class($this));
			preg_match('/^(.*)Fixture$/', $class, $matches);
			$table = Inflector::underscore($matches[1]);
			$this->import['table'] = $table;
		}

		/**
		 * @var Connection $structureConnection
		 */
		$structureConnection = ConnectionManager::get($this->import['connection'], false);
		if (!$structureConnection->isConnected()) {
			$structureConnection->disconnect();
			$structureConnection->connect();
		}
		$createData = $structureConnection->query('SHOW CREATE TABLE ' . $this->import['table'])->fetch();
		// $createData всегда не пустой. Если таблицы нет, то запрос SHOW CREATE TABLE кинет исключение
		$this->_createTableSqlQuery = $createData[1]; //0 - название таблицы, 1 - запрос CREATE TABLE
	}

	/**
	 * Для какого теста загружаем фикстуры
	 *
	 * @param string $caseClass
	 */
	public function setTestCase($caseClass) {
		if ($this->_testCaseClass == $caseClass) {
			return;
		}
		$this->_testCaseClass = $caseClass;
		$this->_loadFixtureData();
	}

	/**
	 * Возвращает полное имя файла, из которого нужно взять фикстуры
	 *
	 * @return string
	 */
	private function _getFixtureFileName() {
		$fixtureFile = $this->import['table'] . '.xml';
		if (!empty($this->_testCaseClass)) {
			$testCaseFile = (new \ReflectionClass($this->_testCaseClass))->getFileName();
			$testCaseDirectory = dirname($testCaseFile);

			$localFixtureFile = $testCaseDirectory . DS . $fixtureFile;
			if (file_exists($localFixtureFile)) {
				return $localFixtureFile;
			}
		}
		$defaultFixtureFile = TEST_FIXTURE . $fixtureFile;
		if (file_exists($defaultFixtureFile)) {
			return $defaultFixtureFile;
		}
		return '';
	}

	/**
	 * Загружает данные из наших XML-файликов фикстур и сохраняет их в $this->records
	 */
	private function _loadFixtureData() {
		$fixtureDataFile = $this->_getFixtureFileName();
		if (empty($fixtureDataFile)) {
			return;
		}

		$isDefaultFixture = (dirname($fixtureDataFile) == TEST_FIXTURE);

		if ($isDefaultFixture && !empty($this->_defaultFixtureRecords)) {
			$this->records = $this->_defaultFixtureRecords;
			return;
		}

		$fixtureXml = simplexml_load_file($fixtureDataFile);

		if (empty($fixtureXml) || !isset($fixtureXml['statement'])) {
			throw new Exception('Incorrect import xml file ' . $fixtureDataFile);
		}

		$this->records = [];
		$recordCount = count($fixtureXml->row);
		for ($rowNum = 0; $rowNum < $recordCount; $rowNum++) {
			$recordXML = $fixtureXml->row[$rowNum];
			$recordArray = [];
			$fieldCount = count($recordXML->field);
			for ($fieldNum = 0; $fieldNum < $fieldCount; $fieldNum++) {
				$fieldName = (string)$recordXML->field[$fieldNum]['name'];
				$xsi = $recordXML->field[$fieldNum]->attributes('xsi', true);
				if (!empty($xsi['nil']) && ((string)$xsi['nil'] == 'true')) {
					$recordArray[$fieldName] = null;
				} else {
					$recordArray[$fieldName] = (string)$recordXML->field[$fieldNum];
				}
			}
			$this->records[] = $recordArray;
		}

		if ($isDefaultFixture) {
			$this->_defaultFixtureRecords = $this->records;
		}
	}

	/**
	 * @inheritdoc
	 */
	public function create(ConnectionInterface $testConnection) {
		if (empty($this->_schema)) {
			return false;
		}

		try {
			/**
			 * @var Connection $testConnection
			 */
			$testConnection->execute($this->_createTableSqlQuery);
		} catch (Exception $e) {
			throw new Exception('Не удалось создать таблицу ' . $this->table . ': ' . $e->getMessage(), 0, $e);
		}
		return true;
	}

	/**
	 * @inheritdoc
	 */
	public function insert(ConnectionInterface $testConnection) {
		try {
			$res = parent::insert($testConnection);
			return $res;
		} catch (Exception $e) {
			throw new Exception('Не удалось загрузить фикстуры для ' . $this->table . ': ' . $e->getMessage(), 0, $e);
		}
	}
}