<?php

namespace ArtSkills\TestRunner;

class TestingShell extends CallableEntity
{
	const SUCCESS_PHINX_REGEXP = '/All\sDone\.\sTook\s([0-9\.]+)s/';
	const SUCCESS_PHPUNIT_REGEXP = '/OK\s\(([0-9]+)\stests\,\s([0-9]+)\sassertions\)/';

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
			$gitHub = new GitHub($this->_config['gitToken']);
			$gitHub->changeCommitStatus($testInfo['repository'], $testInfo['sha'], GitHub::STATE_PROCESSING, 'я в работе...');

			$testDescription = '';
			$result = $this->_doTest($this->_config['repositories'][$testInfo['repository']], $testInfo['ref'], $testDescription, $elapsedSeconds);
			if ($result) {
				$saveStatus = GitHub::STATE_SUCCESS;
				$shortDescription = 'ты меня порадовал, сладенький';
			} else {
				$saveStatus = GitHub::STATE_ERROR;
				$shortDescription = 'я недоволен';
			}


			$branchName = $testInfo['repository'] . '/' . $testInfo['ref'];

			$this->_model->prepare('INSERT INTO history (branch, content, elapsed_seconds) VALUES (:branch, :content, :elapsed_seconds)')
				->execute([
					':branch' => $branchName,
					':content' => $testDescription,
					':elapsed_seconds' => $elapsedSeconds,
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

		}
	}

	/**
	 * Тестируем код
	 *
	 * @param array $repositoryConfig
	 * @param string $ref
	 * @param string $resultText
	 * @param boolean $elapsedSeconds
	 * @return string
	 */
	private function _doTest($repositoryConfig, $ref, &$resultText, &$elapsedSeconds) {
		$resultText = '';
		$testStartTime = microtime(true);

		$git = new Git($repositoryConfig['deployKey'], $repositoryConfig['repositoryLocation']);
		$git->checkout($ref);
		$git->pullCurrentBranch();

		$testModel = new Model($repositoryConfig['database']);
		$testModel->dropAllTables();

		$fillStartTime = microtime(true);
		$resultText .= "<h2>Fill database structure</h2>\n";
		$fillStrings = $testModel->executeSqlFile($repositoryConfig['structureFile']);
		$resultText .= "<p><pre>" . $fillStrings . "</pre></p>\n<p>Elapsed: " . (microtime(true) - $fillStartTime) . "s</p>\n";

		$result = false;

		$migrationStartTime = microtime(true);
		$resultText .= "<h2>Run migrations</h2>\n";
		$migrationsLog = System::execute($repositoryConfig['phinxCommand'], $repositoryConfig['repositoryLocation']);
		$resultText .= "<p><pre>" . $migrationsLog . "</pre></p>\n<p>Elapsed: " . (microtime(true) - $migrationStartTime) . "s</p>";

		if (preg_match(self::SUCCESS_PHINX_REGEXP, $migrationsLog)) {
			$resultText .= "<h2>Run PhpUnit</h2>\n";
			$phpUnitStartTime = microtime(true);
			$unitTestLog = System::execute($repositoryConfig['phpUnitCommand'], $repositoryConfig['repositoryLocation']);
			if (preg_match(self::SUCCESS_PHPUNIT_REGEXP, $unitTestLog)) {
				$result = true;
			}
			$resultText .= "<p>" . nl2br($unitTestLog) . "</p><p>Elapsed: " . (microtime(true) - $phpUnitStartTime) . "s</p>";
		}

		$testModel->dropAllTables();

		$elapsedSeconds = microtime(true) - $testStartTime;
		return $result;
	}

}