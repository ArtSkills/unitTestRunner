<?php
/**
 * Created by PhpStorm.
 * User: vladimirtunikov
 * Date: 17.08.16
 * Time: 12:06
 */

namespace App\Model\Table;


/**
 * @method \App\Model\Entity\PhpTestActivity newEntity(array|null $data = null, array $options = [])
 * @method \App\Model\Entity\PhpTestActivity[] newEntities(array $data, array $options = [])
 * @method \App\Model\Entity\PhpTestActivity patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method \App\Model\Entity\PhpTestActivity[] patchEntities($entities, array $data, array $options = [])
 * @method \App\Model\Entity\PhpTestActivity|false save(\App\Model\Entity\PhpTestActivity $entity, array|\ArrayAccess $options = [])
 * @method \App\Model\Entity\PhpTestActivity|false saveArr(array $saveData, \App\Model\Entity\PhpTestActivity|null $entity = null, array $options = [])
 * @method \App\Model\Query\PhpTestActivityQuery find(string $type = "all", array|\ArrayAccess $options = [])
 * @method \App\Model\Entity\PhpTestActivity get($primaryKey, array|\ArrayAccess $options = [])
 * @method \App\Model\Entity\PhpTestActivity|false getEntity(\App\Model\Entity\PhpTestActivity|int $entity, array|\ArrayAccess $options = [])
 * @method \App\Model\Entity\PhpTestActivity|null updateWithLock(\App\Model\Query\PhpTestActivityQuery|array $queryData, array $updateData)
 */
class PhpTestActivityTable extends AppTable
{
	/**
	 * @inheritdoc
	 */
	public function initialize(array $config) {
		$this->belongsTo(PHP_TESTS);
		parent::initialize($config);
	}
}