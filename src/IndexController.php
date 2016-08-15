<?php
namespace ArtSkills\TestRunner;

class IndexController extends CallableEntity
{
	/**
	 * Парсер index.php
	 */
	public function main() {
		$payLoad = json_decode($_POST['payload'], true);

		if (in_array($payLoad['action'], ['reopened', 'opened'])) {
			$this->_processPullRequest($payLoad['pull_request']);
		}
	}

	/**
	 * Обработка нового Pull Request
	 *
	 * @param array $pullRequest
	 */
	private function _processPullRequest($pullRequest) {
		$repository = $pullRequest['base']['repo']['full_name'];
		$sha = $pullRequest['head']['sha'];
		$newRec = $this->_model->prepare('INSERT INTO queue (repository, ref, sha, status) VALUES (:repository, :ref, :sha, :status)');
		$newRec->execute([
			':repository' => $repository,
			':ref' => $pullRequest['head']['ref'],
			':sha' => $sha,
			':status' => QUEUE_STATUS_NEW,
		]);

		$git = new GitHub($this->_config['gitToken']);
		$git->changeCommitStatus($repository, $sha, GitHub::STATE_PROCESSING, 'Запуск юнит теста');
	}
}