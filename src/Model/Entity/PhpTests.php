<?php
namespace App\Model\Entity;

/**
 * @property int $id
 * @property string $repository
 * @property string $ref
 * @property string $sha
 * @property string $status = 'new'
 * @property \Cake\I18n\Time $created = 'CURRENT_TIMESTAMP'
 * @property \Cake\I18n\Time $updated = 'CURRENT_TIMESTAMP'
 * @property PhpTestActivity[] $PhpTestActivity `php_test_id` => `id`
 */
class PhpTests extends AppEntity
{
	/** @inheritdoc */
	protected $_aliases = [
	];
}