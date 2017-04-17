<?php
/**
 * Created by PhpStorm.
 * User: vladimirtunikov
 * Date: 01.11.16
 * Time: 17:00
 */

namespace App\Shell;

use App\Model\Entity\AppEntity;
use App\Model\Table\AppTable;
use ArtSkills\EntityBuilder\EntityBuilder;
use ArtSkills\EntityBuilder\EntityBuilderConfig;
use ArtSkills\EntityBuilder\TableDocumentation;
use Cake\Console\Shell;

class EntityBuilderShell extends Shell
{
	/**
	 * Формируем/обновляем сущности
	 */
	public function main() {
		if ($this->_buildEntityAndDoc()) {
			$this->out('Has changes, update Model folder');
		}
	}

	/**
	 * инициализация конфига
	 */
	private function _setConfig() {
		EntityBuilderConfig::create()
			->setModelFolder(APP . 'Model')
			->setBaseTableClass(AppTable::class)
			->setBaseEntityClass(AppEntity::class)
			->register();
	}

	/**
	 * Создаём класс таблицы и сущности из существующей таблицы в базе
	 */
	public function createFromDb() {
		$this->_setConfig();
		$newTableFile = EntityBuilder::createTableClass($this->args[0]);
		require_once $newTableFile;
		EntityBuilder::build();
		$this->out('Yahaa, update Model folder');
	}

	/**
	 * Генерим сущности и документацию
	 * @return bool
	 */
	private function _buildEntityAndDoc() {
		$this->_setConfig();
		$hasEntityChanges = EntityBuilder::build();
		$hasDocChanges = TableDocumentation::build();
		return $hasEntityChanges || $hasDocChanges;
	}

	/**
	 * Добавление команд и их параметров
	 *
	 * @return \Cake\Console\ConsoleOptionParser
	 */
	public function getOptionParser() {
		$parser = parent::getOptionParser();

		$parser->addSubcommand('createFromDb', [
			'help' => __('Create Table class and Entity class from existance DB table'),
			'parser' => [
				'arguments' => [
					'tableName' => ['help' => __('Real table name'), 'required' => true],
				],
			],
		]);
		return $parser;
	}
}