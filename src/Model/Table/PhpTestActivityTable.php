<?php
/**
 * Created by PhpStorm.
 * User: vladimirtunikov
 * Date: 17.08.16
 * Time: 12:06
 */

namespace App\Model\Table;


class PhpTestActivityTable extends AppTable
{
	/**
	 * @inheritdoc
	 */
	public function initialize(array $config) {
		$this->belongsTo('PhpTests');
	}
}