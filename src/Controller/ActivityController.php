<?php
/**
 * Created by PhpStorm.
 * User: vladimirtunikov
 * Date: 22.08.16
 * Time: 10:06
 */

namespace App\Controller;
use App\Model\Table\PhpTestActivityTable;

/**
 * @param PhpTestActivityTable $PhpTestActivity
 */
class ActivityController extends AppController
{
	const MSG_NOT_FOUND = 'Not found';

	/**
	 * @inheritdoc
	 */
	public function initialize() {
		parent::initialize();
		$this->loadComponent('RequestHandler');
	}

	/**
	 * Список
	 */
	public function index() {
		$this->_sendJsonError('Not realized');
	}

	/**
	 * Отображение лога
	 *
	 * @param string $id
	 */
	public function view($id) {
		$testId = $this->request->param('test_id');

		$this->loadModel('PhpTestActivity');

		$activity = $this->PhpTestActivity->find()
			->where(['PhpTestActivity.id' => $id, 'php_test_id' => $testId])
			->contain('PhpTests')
			->first();
		if (!$activity) {
			$this->_sendJsonError(self::MSG_NOT_FOUND);
		} else {
			$this->set(compact('activity'));
		}
	}

	/**
	 * Редактирование лога
	 *
	 * @param string $id
	 */
	public function edit($id) {
		$this->_sendJsonError('Not realized');
	}

	/**
	 * Удаление лога
	 *
	 * @param string $id
	 */
	public function delete($id) {
		$this->_sendJsonError('Not realized');
	}
}