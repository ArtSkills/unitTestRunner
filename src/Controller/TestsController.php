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
	const CANCEL_MESSAGE = 'сделали всё не дожидаясь меня ;(';

	const MSG_ADD_BAD_ARGS = 'Bad args';
	const MSG_ADD_BAD_SECRET = 'Bad secret';
	const MSG_ADD_BAD_REPO = 'Not configured repository';
	const MSG_ADD_BAD_ACTION = 'Incorrect action';
	const MSG_EDIT_NOT_FOUND = 'Not found';
	const MSG_EDIT_BAD_STATUS = 'Bad status';

	const GITHUB_SECRET_HEADER = 'X-Hub-Signature';

	const GITHUB_ADD_STATUSES = ['reopened', 'opened', 'synchronize'];
	const GITHUB_CLOSED_STATUSES = ['closed'];

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
		$this->loadModel(PHP_TESTS);
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
		if (empty($payLoad['pull_request'])) {
			return $this->_sendJsonError(self::MSG_ADD_BAD_ACTION);
		}

		$repository = $payLoad['pull_request']['base']['repo']['full_name'];
		$sha = $payLoad['pull_request']['head']['sha'];
		$ref = $payLoad['pull_request']['head']['ref'];

		$gitAction = !empty($payLoad['action']) ? $payLoad['action'] : [];
		if (!isset(Configure::read('repositories')[$repository])) {
			return $this->_sendJsonError(self::MSG_ADD_BAD_REPO);
		}

		if (in_array($gitAction, self::GITHUB_ADD_STATUSES)) {
			return $this->_sendJsonOk(['id' => $this->_processPullRequest($repository, $ref, $sha)]);
		} elseif (in_array($gitAction, self::GITHUB_CLOSED_STATUSES)) {
			return $this->_sendJsonOk(['cancelled' => $this->_cancelPullRequest($repository, $ref, $sha)]);
		} else {
			return $this->_sendJsonError(self::MSG_ADD_BAD_ACTION);
		}
	}

	/**
	 * Обработка нового Pull Request
	 *
	 * @param string $repository
	 * @param string $ref
	 * @param string $sha
	 * @return int
	 */
	private function _processPullRequest($repository, $ref, $sha) {
		$existingTest = $this->PhpTests->find()
			->where([
				'repository' => $repository,
				'ref' => $ref,
				'status' => PhpTestsTable::STATUS_NEW,
			])->first();

		$newRec = $this->PhpTests->saveArr([
			'repository' => $repository,
			'ref' => $ref,
			'sha' => $sha,
			'status' => PhpTestsTable::STATUS_NEW,
		], $existingTest);

		$this->_gitHub->changeCommitStatus($repository, $sha, GitHub::STATE_PROCESSING, self::PUSH_QUEUE_MESSAGE);
		return $newRec->id;
	}

	/**
	 * Удаляем не начатый тест из очереди
	 *
	 * @param string $repository
	 * @param string $ref
	 * @param string $sha
	 * @return bool
	 */
	private function _cancelPullRequest($repository, $ref, $sha) {
		$existingTest = $this->PhpTests->find()
			->where([
				'repository' => $repository,
				'ref' => $ref,
				'sha' => $sha,
				'status' => PhpTestsTable::STATUS_NEW,
			])->first();

		if (!empty($existingTest)) {
			$this->PhpTests->delete($existingTest);
			$this->_gitHub->changeCommitStatus($repository, $sha, GitHub::STATE_SUCCESS, self::CANCEL_MESSAGE);
			return true;
		} else {
			return false;
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

		$this->_gitHub->changeCommitStatus($curTest->repository, $curTest->sha, GitHub::STATE_PROCESSING, self::PUSH_QUEUE_MESSAGE);
		$this->PhpTests->saveArr(['status' => PhpTestsTable::STATUS_NEW], $curTest);

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
