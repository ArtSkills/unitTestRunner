<?php
/**
 * Created by PhpStorm.
 * User: vladimirtunikov
 * Date: 17.08.16
 * Time: 12:04
 */

namespace App\Controller;


use App\Lib\GitHub;
use App\Model\Table\PhpTestsTable;
use Cake\Core\Configure;

/**
 * @property PhpTestsTable $PhpTests
 */
class TestsController extends AppController
{
	const PUSH_QUEUE_MESSAGE = 'в очередь!';

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
	 * Добавление теста
	 */
	public function add() {
		if (empty($this->request->data['payload'])) {
			return $this->_sendJsonError('Bad args');
		}

		$payLoad = json_decode($this->request->data['payload'], true);

		if (!empty($payLoad['action']) && in_array($payLoad['action'], ['reopened', 'opened'])) {
			if (isset(Configure::read('repositories')[$payLoad['pull_request']['base']['repo']['full_name']])) {
				return $this->_sendJsonOk(['id' => $this->_processPullRequest($payLoad['pull_request'])]);
			} else {
				return $this->_sendJsonError('Not configured repository');
			}
		} else {
			return $this->_sendJsonError('Incorrect action');
		}
	}

	/**
	 * Обработка нового Pull Request
	 *
	 * @param array $pullRequest
	 * @return int
	 */
	private function _processPullRequest($pullRequest) {
		$this->loadModel('PhpTests');

		$repository = $pullRequest['base']['repo']['full_name'];
		$sha = $pullRequest['head']['sha'];

		$newRec = $this->PhpTests->saveArr([
			'repository' => $repository,
			'ref' => $pullRequest['head']['ref'],
			'sha' => $sha,
			'status' => PhpTestsTable::STATUS_NEW,
		]);

		$gitHub = new GitHub(Configure::read('gitToken'));
		$gitHub->changeCommitStatus($repository, $sha, GitHub::STATE_PROCESSING, self::PUSH_QUEUE_MESSAGE);
		return $newRec->id;
	}

	/**
	 * Отображение теста
	 *
	 * @param string $id
	 */
	public function view($id) {
		$this->_sendJsonError('Not realized');
	}

	/**
	 * Редактирование теста
	 *
	 * @param string $id
	 */
	public function edit($id) {
		$this->_sendJsonError('Not realized');
	}

	/**
	 * Удаление теста
	 *
	 * @param string $id
	 */
	public function delete($id) {
		$this->_sendJsonError('Not realized');
	}
}