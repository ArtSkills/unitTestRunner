<?php

namespace ArtSkills\TestRunner;

class TestingShell extends CallableEntity
{
	/**
	 * Запуск юнит тестов
	 */
	public function main() {
		$cntQ = $this->_model->prepare('SELECT COUNT(*) cnt FROM queue WHERE status=:status');
		$cntQ->execute([':status' => QUEUE_STATUS_PROCESSING]);
		$countArr = $cntQ->fetch();

		if ($countArr['cnt'] > 0) { // уже идёт какой-то тест
			return;
		}

		$testQ = $this->_model->prepare('SELECT * FROM queue WHERE status=:status');
		$testQ->execute([':status' => QUEUE_STATUS_NEW]);
		$testInfo = $testQ->fetch();
		if ($testInfo) {
/*
			$this->_model->prepare('UPDATE queue SET status=:status WHERE id=:id')
				->execute([
					':status' => QUEUE_STATUS_PROCESSING,
					':id' => $testInfo['id'],
				]);
			$gitHub = new GitHub($this->_config['gitToken']);
			$gitHub->changeCommitStatus($testInfo['repository'], $testInfo['sha'], GitHub::STATE_PROCESSING, 'я в работе...');
*/
			$testDescription = '';
			$result = $this->_doTest($this->_config['repositories'][$testInfo['repository']], $testInfo['ref'], $testDescription);
			if ($result) {
				$saveStatus = GitHub::STATE_SUCCESS;
				$shortDescription = 'ты меня порадовал, сладенький';
			} else {
				$saveStatus = GitHub::STATE_ERROR;
				$shortDescription = 'я недоволен';
			}
/*
			$branchName = $testInfo['repository'] . '/' . $testInfo['ref'];

			$this->_model->prepare('INSERT INTO history (branch, content) VALUES (:branch, :content)')
				->execute([
					':branch' => $branchName,
					':content' => $testDescription,
				]);
			$historyId = $this->_model->lastInsertId();

			$url = $this->_config['serverUrl'] . '/history.php?' . http_build_query([
					'branch' => $branchName,
					'id' => $historyId,
				]);

			$gitHub->changeCommitStatus($testInfo['repository'], $testInfo['sha'], $saveStatus, $shortDescription, $url);

			$this->_model->prepare('UPDATE queue SET status=:status WHERE id=:id')
				->execute([
					':status' => QUEUE_STATUS_FINISHED,
					':id' => $testInfo['id'],
				]);
*/
		}
	}

	/**
	 * Тестируем код
	 *
	 * @param array $repositoryConfig
	 * @param string $ref
	 * @param string $resultText
	 * @return string
	 */
	private function _doTest($repositoryConfig, $ref, &$resultText) {
		$git = new Git($repositoryConfig['deployKey'], $repositoryConfig['repositoryLocation']);
		$git->checkout($ref);
		$git->pullCurrentBranch();

		$testModel = new Model($repositoryConfig['database']);
		$this->_fillTestStructure($testModel, $repositoryConfig['structureFile']);
		return true;
	}

	/**
	 * Заполняем таблицами тестовую базу
	 *
	 * @param Model $testModel
	 * @param string $structureFile
	 */
	private function _fillTestStructure($testModel, $structureFile) {
		$testModel->dropAllTables();
		$testModel->executeSqlFile($structureFile);
		// пускать phinx
		// пускать phpunit
		$testModel->dropAllTables();
	}
}