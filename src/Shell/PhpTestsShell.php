<?php

namespace App\Shell;

use App\Lib\Git;
use App\Lib\GitHub;
use App\Lib\MySql;
use App\Lib\System;
use App\Model\Table\PhpTestActivityTable;
use App\Model\Table\PhpTestsTable;
use Cake\Console\Shell;
use Cake\Core\Configure;

/**
 * @property PhpTestsTable $PhpTests
 * @property PhpTestActivityTable $PhpTestActivity
 */
class PhpTestsShell extends Shell
{
	const MSG_TEST_RUN = 'я в работе...';
	const MSG_BAD_TEST_RESULT = 'я недоволен';
	const MSG_GOOD_TEST_RESULT = 'ты меня порадовал, сладенький';

	const SUCCESS_PHINX_REGEXP = '/All\sDone\.\sTook\s([0-9\.]+)s/';
	const SUCCESS_PHPUNIT_REGEXP = '/OK\s\(([0-9]+)\stests\,\s([0-9]+)\sassertions\)/';
	const PHP_WARNING_REGEXP = '/(cake-error|xdebug_message|Warning Error)/';

	const PROCESS_CRASH_MESSAGE = 'zend_mm_heap corrupted';

	/**
	 * Запуск PHP тестов
	 */
	public function main() {
		$this->loadModel('PhpTests');

		$phpTest = $this->PhpTests->find()
			->where(['status' => PhpTestsTable::STATUS_NEW])
			->order(['id' => 'asc'])
			->first();

		if (!$phpTest) {
			return;
		}

		// проверка на уже запущенный тест для данной ветки
		if ($this->PhpTests->find()->where([
			'repository' => $phpTest->repository,
			'status' => PhpTestsTable::STATUS_PROCESSING,
		])->count()
		) {
			return;
		}

		$this->PhpTests->patchEntity($phpTest, ['status' => PhpTestsTable::STATUS_PROCESSING]);
		$this->PhpTests->save($phpTest);

		$gitHub = new GitHub(Configure::read('gitToken'));
		$gitHub->changeCommitStatus($phpTest->repository, $phpTest->sha, GitHub::STATE_PROCESSING, self::MSG_TEST_RUN);

		$result = $this->_doTest(Configure::read('repositories')[$phpTest->repository], $phpTest->ref);
		if ($result['success']) {
			$saveStatus = GitHub::STATE_SUCCESS;
			$shortDescription = self::MSG_GOOD_TEST_RESULT;
		} else {
			$saveStatus = GitHub::STATE_ERROR;
			$shortDescription = self::MSG_BAD_TEST_RESULT;
		}

		$this->loadModel('PhpTestActivity');

		$historyRec = $this->PhpTestActivity->saveArr([
			'php_test_id' => $phpTest->id,
			'content' => json_encode($result['activity'], JSON_PARTIAL_OUTPUT_ON_ERROR),
			'elapsed_seconds' => $result['elapsedSeconds'],
		]);

		$url = Configure::read('serverUrl') . '/tests/' . $phpTest->id . '/activity/' . $historyRec->id;
		$gitHub->changeCommitStatus($phpTest->repository, $phpTest->sha, $saveStatus, $shortDescription, $url);

		$this->PhpTests->patchEntity($phpTest, ['status' => $this->_getFinalStatus($result['activity'])]);
		$this->PhpTests->save($phpTest);
	}

	/**
	 * Определяем финальный статус теста
	 *
	 * @param array $activityReport
	 * @return string
	 */
	private function _getFinalStatus($activityReport) {
		foreach ($activityReport as $rec) {
			if (stristr($rec['report'], self::PROCESS_CRASH_MESSAGE)) {
				return PhpTestsTable::STATUS_NEW;
			}
		}

		return PhpTestsTable::STATUS_FINISHED;
	}

	/**
	 * Тестируем код
	 *
	 * @param array $repositoryConfig
	 * @param string $ref
	 * @return array
	 */
	private function _doTest($repositoryConfig, $ref) {
		$resultArr = [];
		$testStartTime = microtime(true);

		$checkoutStartTime = microtime(true);
		$git = new Git($repositoryConfig['deployKey'], $repositoryConfig['repositoryLocation']);
		$git->updateRefs();
		$gitOutput = '';
		$pullOutput = '';
		$successCheckout = $git->checkout($ref, $gitOutput);
		if ($successCheckout) {
			$git->pullCurrentBranch($pullOutput);
		}
		$resultArr[] = $this->_formatReport('Checkout to branch ' . $repositoryConfig['repositoryLocation'], nl2br($gitOutput . "\n" . $pullOutput), $checkoutStartTime);

		$result = false;
		if ($successCheckout) {
			MySql::dropDbTables($repositoryConfig['database']['host'], $repositoryConfig['database']['name'], $repositoryConfig['database']['login'], $repositoryConfig['database']['password'], $repositoryConfig['database']['port']);

			$fillStartTime = microtime(true);
			$fillStrings = MySql::executeSqlFile($repositoryConfig['database']['host'], $repositoryConfig['database']['name'], $repositoryConfig['database']['login'], $repositoryConfig['database']['password'], $repositoryConfig['database']['port'], $repositoryConfig['structureFile']);
			$resultArr[] = $this->_formatReport('Fill database structure', (strlen($fillStrings) ? '<pre>' . $fillStrings . '</pre>' : ''), $fillStartTime);

			if (!strlen($fillStrings)) {
				$resultArr[] = $this->_formatReport('Run composer', nl2br(System::execute($this->_getCmd($repositoryConfig['composerUpdateCommand']), $this->_getWorkDir($repositoryConfig['composerUpdateCommand'], $repositoryConfig))), microtime(true));

				$migrationStartTime = microtime(true);
				$migrationsLog = System::execute($this->_getCmd($repositoryConfig['phinxCommand']), $this->_getWorkDir($repositoryConfig['phinxCommand'], $repositoryConfig));
				$resultArr[] = $this->_formatReport('Run migrations', nl2br($migrationsLog), $migrationStartTime);

				if (preg_match(self::SUCCESS_PHINX_REGEXP, $migrationsLog)) {
					$phpUnitStartTime = microtime(true);
					$unitTestLog = System::execute($this->_getCmd($repositoryConfig['phpUnitCommand']), $this->_getWorkDir($repositoryConfig['phpUnitCommand'], $repositoryConfig));
					if (preg_match(self::SUCCESS_PHPUNIT_REGEXP, $unitTestLog) && !preg_match(self::PHP_WARNING_REGEXP, $unitTestLog)) {
						$result = true;
					}
					$resultArr[] = $this->_formatReport('Run PhpUnit', nl2br($unitTestLog), $phpUnitStartTime);
				}
			}

			MySql::dropDbTables($repositoryConfig['database']['host'], $repositoryConfig['database']['name'], $repositoryConfig['database']['login'], $repositoryConfig['database']['password'], $repositoryConfig['database']['port']);
		}
		$elapsedSeconds = microtime(true) - $testStartTime;
		return [
			'success' => $result,
			'elapsedSeconds' => $elapsedSeconds,
			'activity' => $resultArr,
		];
	}

	/**
	 * Определяем выполняему команду
	 *
	 * @param string|array $cmd
	 * @return string
	 */
	private function _getCmd($cmd) {
		if (is_array($cmd)) {
			return $cmd[0];
		} else {
			return $cmd;
		}
	}

	/**
	 * Определяем рабочую папку для команды
	 *
	 * @param string|array $cmd
	 * @param array $repositoryConfig
	 * @return string
	 */
	private function _getWorkDir($cmd, $repositoryConfig) {
		if (is_array($cmd)) {
			return $repositoryConfig['repositoryLocation'] . '/' . $cmd[1];
		} else {
			return $repositoryConfig['repositoryLocation'];
		}
	}

	/**
	 * Формируем отчёт
	 *
	 * @param string $header
	 * @param string $text
	 * @param string $timeStart
	 * @return array
	 */
	private function _formatReport($header, $text, $timeStart) {
		$result = [
			'header' => $header,
			'report' => iconv("UTF-8", "UTF-8//IGNORE", $text),
			'elapsedTime' => microtime(true) - $timeStart,
		];
		return $result;
	}
}
