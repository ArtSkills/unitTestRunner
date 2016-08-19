<?php
/**
 * Created by PhpStorm.
 * User: alexey
 * Date: 09.06.2016
 * Time: 13:10
 */

namespace App\Model\Table;

use Cake\ORM\Entity;
use Cake\ORM\Table;
use Cake\ORM\TableRegistry;

class AppTable extends Table
{

	/**
	 * Возвращает алиас таблицы, используемый тут повсюду
	 *
	 * @return string
	 */
	public static function getAlias() {
		$classNameParts = explode('\\', static::class);
		return str_replace('Table', '', array_pop($classNameParts));
	}

	/**
	 * Обёртка для TableRegistry::get() для автодополнения
	 *
	 * @return static
	 */
	public static function instance() {
		return TableRegistry::get(static::getAlias());
	}

	/**
	 * Сохранение массивов, чтоб в одну строчку
	 *
	 * @param array $saveData
	 * @param Entity|null|int $entity null для новой записи, сущность или её id для редактирования
	 * @param array $options
	 * @return bool|Entity
	 */
	public function saveArr($saveData, $entity = null, $options = []) {
		if (empty($entity)) {
			$entity = $this->newEntity();
		} elseif (!($entity instanceof Entity)) {
			$entityId = (int)$entity;
			if (empty($entityId)) {
				return false;
			}
			$entity = $this->get($entityId);
		}
		$entity = $this->patchEntity($entity, $saveData);
		return $this->save($entity, $options);
	}


}