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

	const MSG_ADD_BAD_ARGS = 'Bad args';
	const MSG_ADD_BAD_SECRET = 'Bad secret';
	const MSG_ADD_BAD_REPO = 'Not configured repository';
	const MSG_ADD_BAD_ACTION = 'Incorrect action';
	const MSG_EDIT_NOT_FOUND = 'Not found';
	const MSG_EDIT_BAD_STATUS = 'Bad status';

	const GITHUB_SECRET_HEADER = 'X-Hub-Signature';

	const GITHUB_ADD_STATUSES = ['reopened', 'opened', 'synchronize'];

	/**
	 * GitHub
	 *
	 * @var null|GitHub
	 */
	private $_gitHub = null;

	/**
	 * @inheritdoc
	 */
	public function initialize() {
		parent::initialize();
		$this->loadComponent('RequestHandler');
		$this->_gitHub = new GitHub(Configure::read('gitToken'));
		$this->loadModel('PhpTests');
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
			return $this->_sendJsonError(self::MSG_ADD_BAD_ARGS);
		}


		$gitHeader = $this->request->header(self::GITHUB_SECRET_HEADER);
		if (!$gitHeader || !$this->_gitHub->checkSecret($this->request->header(self::GITHUB_SECRET_HEADER), file_get_contents('php://input'), Configure::read('gitSecret'))) {
			return $this->_sendJsonError(self::MSG_ADD_BAD_SECRET);
		}

		$payLoad = json_decode($this->request->data['payload'], true);

		if (!empty($payLoad['action']) && in_array($payLoad['action'], self::GITHUB_ADD_STATUSES)) {
			if (isset(Configure::read('repositories')[$payLoad['pull_request']['base']['repo']['full_name']])) {
				return $this->_sendJsonOk(['id' => $this->_processPullRequest($payLoad['pull_request'])]);
			} else {
				return $this->_sendJsonError(self::MSG_ADD_BAD_REPO);
			}
		} else {
			return $this->_sendJsonError(self::MSG_ADD_BAD_ACTION);
		}
	}

	/**
	 * Обработка нового Pull Request
	 *
	 * @param array $pullRequest
	 * @return int
	 */
	private function _processPullRequest($pullRequest) {
		$repository = $pullRequest['base']['repo']['full_name'];
		$sha = $pullRequest['head']['sha'];
		$ref = $pullRequest['head']['ref'];

		$existingTest = $this->PhpTests->find()
			->where([
				'repository' => $repository,
				'ref' => $ref,
				'status' => PhpTestsTable::STATUS_NEW,
			])->first();

		if ($existingTest) {
			return $existingTest->id;
		} else {
			$newRec = $this->PhpTests->saveArr([
				'repository' => $repository,
				'ref' => $ref,
				'sha' => $sha,
				'status' => PhpTestsTable::STATUS_NEW,
			]);

			$gitHub = $this->_gitHub;
			$gitHub->changeCommitStatus($repository, $sha, GitHub::STATE_PROCESSING, self::PUSH_QUEUE_MESSAGE);
			return $newRec->id;
		}
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
	 * Перезапуск теста.
	 * Параметры:
	 *    rerun_test = 1
	 *    redirect = 1 в случае необходимости редиректа, а не JSON ответа
	 *
	 * @param string $testId
	 * @return NULL
	 */
	public function edit($testId) {
		if (!isset($this->request->data['rerun_test'])) {
			return $this->_sendJsonError(self::MSG_ADD_BAD_ARGS);
		}

		$curTest = $this->PhpTests->find()
			->where(['id' => $testId])
			->first();

		if (!$curTest) {
			return $this->_sendJsonError(self::MSG_EDIT_NOT_FOUND);
		}
		if ($curTest->status !== PhpTestsTable::STATUS_FINISHED) {
			return $this->_sendJsonError(self::MSG_EDIT_BAD_STATUS);
		}

		$this->PhpTests->patchEntity($curTest, ['status' => PhpTestsTable::STATUS_NEW]);
		$this->_gitHub->changeCommitStatus($curTest->repository, $curTest->sha, GitHub::STATE_PROCESSING, self::PUSH_QUEUE_MESSAGE);
		$this->PhpTests->save($curTest);

		if (!empty($this->request->data['redirect'])) {
			return $this->redirect('https://github.com/' . $curTest->repository . '/pulls');
		} else {
			return $this->_sendJsonOk(['id' => $curTest->id]);
		}
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
