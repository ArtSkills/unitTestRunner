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

	const GITHUB_SECRET_HEADER = 'X-Hub-Signature';

	/**
	 * GitHub
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

		if (!empty($payLoad['action']) && in_array($payLoad['action'], ['reopened', 'opened'])) {
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
		$this->loadModel('PhpTests');

		$repository = $pullRequest['base']['repo']['full_name'];
		$sha = $pullRequest['head']['sha'];

		$newRec = $this->PhpTests->saveArr([
			'repository' => $repository,
			'ref' => $pullRequest['head']['ref'],
			'sha' => $sha,
			'status' => PhpTestsTable::STATUS_NEW,
		]);

		$gitHub = $this->_gitHub;
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