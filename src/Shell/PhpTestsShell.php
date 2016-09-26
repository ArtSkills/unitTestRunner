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
	const PHP_WARNING_REGEXP = '/Warning\s\(([0-9]+)\)/';

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
			'content' => json_encode($result['activity']),
			'elapsed_seconds' => $result['elapsedSeconds'],
		]);

		$url = Configure::read('serverUrl') . '/tests/' . $phpTest->id . '/activity/' . $historyRec->id;
		$gitHub->changeCommitStatus($phpTest->repository, $phpTest->sha, $saveStatus, $shortDescription, $url);

		$this->PhpTests->patchEntity($phpTest, ['status' => PhpTestsTable::STATUS_FINISHED]);
		$this->PhpTests->save($phpTest);
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
		$git->checkout($ref);
		$git->pullCurrentBranch();
		$resultArr[] = $this->_formatReport('Checkout to branch ' . $repositoryConfig['repositoryLocation'], '', $checkoutStartTime);

		MySql::dropDbTables($repositoryConfig['database']['host'], $repositoryConfig['database']['name'], $repositoryConfig['database']['login'], $repositoryConfig['database']['password'], $repositoryConfig['database']['port']);

		$fillStartTime = microtime(true);
		$fillStrings = MySql::executeSqlFile($repositoryConfig['database']['host'], $repositoryConfig['database']['name'], $repositoryConfig['database']['login'], $repositoryConfig['database']['password'], $repositoryConfig['database']['port'], $repositoryConfig['structureFile']);
		$resultArr[] = $this->_formatReport('Fill database structure', (strlen($fillStrings) ? '<pre>' . $fillStrings . '</pre>' : ''), $fillStartTime);
		$result = false;

		if (!strlen($fillStrings)) {
			$resultArr[] = $this->_formatReport('Run composer', System::execute($repositoryConfig['composerUpdateCommand'], $repositoryConfig['repositoryLocation']), microtime(true));

			$migrationStartTime = microtime(true);
			$migrationsLog = System::execute($repositoryConfig['phinxCommand'], $repositoryConfig['repositoryLocation']);
			$resultArr[] = $this->_formatReport('Run migrations', nl2br($migrationsLog), $migrationStartTime);

			if (preg_match(self::SUCCESS_PHINX_REGEXP, $migrationsLog)) {
				$phpUnitStartTime = microtime(true);
				$unitTestLog = System::execute($repositoryConfig['phpUnitCommand'], $repositoryConfig['repositoryLocation']);
				if (preg_match(self::SUCCESS_PHPUNIT_REGEXP, $unitTestLog) && !preg_match(self::PHP_WARNING_REGEXP, $unitTestLog)) {
					$result = true;
				}
				$resultArr[] = $this->_formatReport('Run PhpUnit', nl2br($unitTestLog), $phpUnitStartTime);
			}
		}

		MySql::dropDbTables($repositoryConfig['database']['host'], $repositoryConfig['database']['name'], $repositoryConfig['database']['login'], $repositoryConfig['database']['password'], $repositoryConfig['database']['port']);

		$elapsedSeconds = microtime(true) - $testStartTime;
		return [
			'success' => $result,
			'elapsedSeconds' => $elapsedSeconds,
			'activity' => $resultArr,
		];
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
		return [
			'header' => $header,
			'report' => $text,
			'elapsedTime' => microtime(true) - $timeStart,
		];
	}
}