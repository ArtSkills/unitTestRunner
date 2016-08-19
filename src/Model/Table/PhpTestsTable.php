<?php
/**
 * Created by PhpStorm.
 * User: vladimirtunikov
 * Date: 17.08.16
 * Time: 12:07
 */

namespace App\Model\Table;


class PhpTestsTable extends AppTable
{
	const STATUS_NEW = 'new';
	const STATUS_PROCESSING = 'processing';
	const STATUS_FINISHED = 'success';
}