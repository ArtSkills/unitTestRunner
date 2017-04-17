<?php
namespace App\Model\Entity;

/**
 * @property int $id
 * @property int $php_test_id
 * @property float $elapsed_seconds
 * @property \Cake\I18n\Time $created = 'CURRENT_TIMESTAMP'
 * @property PhpTests $PhpTests `php_test_id` => `id`
 * @property array $content
 */
class PhpTestActivity extends AppEntity
{
	/** @inheritdoc */
	protected $_aliases = [
	];
}