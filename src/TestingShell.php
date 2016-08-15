<?php
/**
 * Created by PhpStorm.
 * User: vladimirtunikov
 * Date: 15.08.16
 * Time: 14:57
 */

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
			$this->_model->prepare('UPDATE queue SET status=:status WHERE id=:id')
				->execute([
					':status' => QUEUE_STATUS_PROCESSING,
					':id' => $testInfo['id'],
				]);

			$testDescription = '';
			$result = $this->_doTest($this->_config['repositories'][$testInfo['repository']], $testInfo['ref'], $testDescription);
			if ($result) {
				$saveStatus = GitHub::STATE_SUCCESS;
				$shortDescription = 'ты меня порадовал, сладенький';
			} else {
				$saveStatus = GitHub::STATE_ERROR;
				$shortDescription = 'я недоволен';
			}

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

			$git = new GitHub($this->_config['gitToken']);
			print_r($git->changeCommitStatus($testInfo['repository'], $testInfo['sha'], $saveStatus, $shortDescription, $url));

			$this->_model->prepare('UPDATE queue SET status=:status WHERE id=:id')
				->execute([
					':status' => QUEUE_STATUS_FINISHED,
					':id' => $testInfo['id'],
				]);

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
		$resultText = print_r($repositoryConfig, true) . "\n" . $ref;
		return true;
	}
}