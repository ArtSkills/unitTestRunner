<?php
/**
 * Created by PhpStorm.
 * User: vladimirtunikov
 * Date: 17.08.16
 * Time: 12:07
 */

namespace App\Model\Table;


/**
 * @method \App\Model\Entity\PhpTests newEntity(array|null $data = null, array $options = [])
 * @method \App\Model\Entity\PhpTests[] newEntities(array $data, array $options = [])
 * @method \App\Model\Entity\PhpTests patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method \App\Model\Entity\PhpTests[] patchEntities($entities, array $data, array $options = [])
 * @method \App\Model\Entity\PhpTests|false save(\App\Model\Entity\PhpTests $entity, array|\ArrayAccess $options = [])
 * @method \App\Model\Entity\PhpTests|false saveArr(array $saveData, \App\Model\Entity\PhpTests|null $entity = null, array $options = [])
 * @method \App\Model\Query\PhpTestsQuery find(string $type = "all", array|\ArrayAccess $options = [])
 * @method \App\Model\Entity\PhpTests get($primaryKey, array|\ArrayAccess $options = [])
 * @method \App\Model\Entity\PhpTests|false getEntity(\App\Model\Entity\PhpTests|int $entity, array|\ArrayAccess $options = [])
 * @method \App\Model\Entity\PhpTests|null updateWithLock(\App\Model\Query\PhpTestsQuery|array $queryData, array $updateData)
 * @method \App\Model\Entity\PhpTests touch(\App\Model\Entity\PhpTests $entity, string $eventName = 'Model.beforeSave')
 */
class PhpTestsTable extends AppTable
{
	const STATUS_NEW = 'new';
	const STATUS_PROCESSING = 'processing';
	const STATUS_FINISHED = 'success';

	/**
	 * @inheritdoc
	 */
	public function initialize(array $config) {
		$this->hasMany(PHP_TEST_ACTIVITY);

		parent::initialize($config);
	}
}