<?php
namespace App\Test\TestCase\Shell\PhpTestsShellTest;

use App\Lib\Git;
use App\Lib\GitHub;
use App\Lib\MySql;
use App\Lib\System;
use App\Model\Table\PhpTestActivityTable;
use App\Model\Table\PhpTestsTable;
use App\Shell\PhpTestsShell;
use App\Test\Suite\MethodMocker;
use App\Test\TestCase\AppTestCase;

class PhpTestsShellTest extends AppTestCase
{
	/**
	 * PhpTestsShell
	 *
	 * @var PhpTestsShell
	 */
	private $PhpTestsShell = null;

	/**
	 * PhpTestsTable
	 *
	 * @var PhpTestsTable
	 */
	private $PhpTestsTable = null;

	/**
	 * @inheritdoc
	 */
	public $fixtures = ['app.php_tests', 'app.php_test_activity'];

	/**
	 * @inheritdoc
	 */
	public function setUp() {
		parent::setUp(); // TODO: Change the autogenerated stub

		$this->PhpTestsShell = new PhpTestsShell();
		$this->PhpTestsTable = PhpTestsTable::instance();

	}

	/**
	 * Запуск пустой очереди
	 */
	public function testEmptyQueue() {
		MethodMocker::mock(GitHub::class, 'changeCommitStatus')
			->expectCall(0);

		$this->PhpTestsTable->updateAll(['status' => PhpTestsTable::STATUS_FINISHED], ['1']);
		$this->PhpTestsShell->main();
	}

	/**
	 * Уже запущен тест на этот репозиторий
	 */
	public function testLockedQueue() {
		MethodMocker::mock(GitHub::class, 'changeCommitStatus')
			->expectCall(0);

		$this->PhpTestsTable->updateAll(['status' => PhpTestsTable::STATUS_PROCESSING], ['6']);
		$this->PhpTestsShell->main();
	}

	/**
	 * Уже запущен тест на этот репозиторий
	 */
	public function testMainGoodResult() {
		$workingRec = $this->PhpTestsTable->find()->where(['id' => 7])->first();

		$changeStatusCalls = [
			[
				'repository' => $workingRec->repository,
				'sha' => $workingRec->sha,
				'state' => GitHub::STATE_PROCESSING,
				'description' => PhpTestsShell::MSG_TEST_RUN,
			],
			[
				'repository' => $workingRec->repository,
				'sha' => $workingRec->sha,
				'state' => GitHub::STATE_SUCCESS,
				'description' => PhpTestsShell::MSG_GOOD_TEST_RESULT,
			],
		];
		$githubStatusIndex = 0;

		MethodMocker::mock(GitHub::class, 'changeCommitStatus')
			->expectCall(2)
			->willReturnAction(function ($args) use ($changeStatusCalls, &$githubStatusIndex) {
				$checkArgNames = array_keys($changeStatusCalls[$githubStatusIndex]);

				foreach ($checkArgNames as $fldIndex => $fldName) {
					self::assertEquals($changeStatusCalls[$githubStatusIndex][$fldName], $args[$fldIndex], 'Не совпало значение поля ' . $fldName);
				}
				$githubStatusIndex++;
			});

		$expectedStatuses = [
			PhpTestsTable::STATUS_PROCESSING,
			PhpTestsTable::STATUS_FINISHED,
		];
		$statusesIndex = 0;
		MethodMocker::sniff(PhpTestsTable::class, 'patchEntity', function ($args, $originalResult) use (
			$expectedStatuses, &$statusesIndex
		) {
			self::assertEquals($args[1]['status'], $expectedStatuses[$statusesIndex]);
			$statusesIndex++;
		});

		$unitTestResult = [
			'success' => true,
			'elapsedSeconds' => 1,
			'activity' => ['1' => '2'],
		];

		MethodMocker::mock(PhpTestsShell::class, '_doTest')
			->singleCall()
			->willReturnValue($unitTestResult);

		// есть какая-то другая ветка, которая тоже в работе
		$this->PhpTestsTable->updateAll([
			'status' => PhpTestsTable::STATUS_PROCESSING,
			'repository' => 'ArtSkills/crm',
		], ['id <>' => $workingRec->id]);

		$this->PhpTestsShell->main();

		$phpTestActivityTable = PhpTestActivityTable::instance();
		$historyRec = $phpTestActivityTable->find()->where(['php_test_id' => $workingRec->id])->first();
		self::assertEquals($unitTestResult['elapsedSeconds'], $historyRec->elapsed_seconds, 'Не добавилась запись истории');
		self::assertEquals($unitTestResult['activity'], json_decode($historyRec->content, true), 'Не добавилась запись истории');
	}

	/**
	 * Тест вернул ошибку
	 */
	public function testMainBadResult() {
		$workingRec = $this->PhpTestsTable->find()->where(['id' => 7])->first();
		$changeStatusCalls = [
			[
				'repository' => $workingRec->repository,
				'sha' => $workingRec->sha,
				'state' => GitHub::STATE_PROCESSING,
				'description' => PhpTestsShell::MSG_TEST_RUN,
			],
			[
				'repository' => $workingRec->repository,
				'sha' => $workingRec->sha,
				'state' => GitHub::STATE_ERROR,
				'description' => PhpTestsShell::MSG_BAD_TEST_RESULT,
			],
		];
		$changeStatusIndex = 0;

		MethodMocker::mock(GitHub::class, 'changeCommitStatus')
			->expectCall(2)
			->willReturnAction(function ($args) use ($changeStatusCalls, &$changeStatusIndex) {
				$checkArgNames = array_keys($changeStatusCalls[$changeStatusIndex]);

				foreach ($checkArgNames as $fldIndex => $fldName) {
					self::assertEquals($changeStatusCalls[$changeStatusIndex][$fldName], $args[$fldIndex], 'Не совпало значение поля ' . $fldName);
				}
				$changeStatusIndex++;
			});

		$unitTestResult = [
			'success' => false,
			'elapsedSeconds' => 1,
			'activity' => ['1' => '2'],
		];

		MethodMocker::mock(PhpTestsShell::class, '_doTest')
			->singleCall()
			->willReturnValue($unitTestResult);
		$this->PhpTestsShell->main();
	}

	/**
	 * Тестовый репозиторий
	 *
	 * @var array
	 */
	private $_repository = [
		'repositoryLocation' => __DIR__,
		'deployKey' => __FILE__,
		// ключ в GitHub, только чтение
		'structureFile' => '/var/www/db.artskills.sql',
		// mysqldump --opt -d -p artskills > db.artskills.sql && mysqldump --opt -d -p artskills phinxlog >> db.artskills.sql
		'phpUnitCommand' => 'app/Vendor/phpunit.phar --bootstrap app/webroot/test.php --no-configuration app/Test/Case',
		// относительно корня папки
		'phinxCommand' => 'vendor/bin/phinx migrate',
		'composerUpdateCommand' => 'php composer.phar update',
		'database' => [
			'host' => 'localhost',
			'name' => 'artskills_structure',
			'login' => 'artskills',
			'password' => 'pwd',
			'port' => '3307',
		],
	];

	/**
	 * Сам процесс тестирования
	 */
	public function testDoTest() {
		$testBranch = 'SITE-23';

		$executeResults = [
			['cmd' => Git::GIT_COMMAND_TEST, 'result' => 'master'],
			['cmd' => $this->_repository['composerUpdateCommand'], 'result' => 'Composer ok'],
			['cmd' => $this->_repository['phinxCommand'], 'result' => 'All Done. Took 1s'],
			['cmd' => $this->_repository['phpUnitCommand'], 'result' => 'OK (4 tests, 18 assertions)'],
		];

		$this->_makeDoDestMocks($testBranch, $executeResults);

		$result = MethodMocker::callPrivateOrProtectedMethod(PhpTestsShell::class, '_doTest', $this->PhpTestsShell, [
			$this->_repository,
			$testBranch,
		]);
		self::assertTrue($result['success'], 'Некорректный результат');
		self::assertNotEmpty($result['activity'], 'Пустой журнал активности');
	}

	/**
	 * Phinx отвалился
	 */
	public function testDoTestBadPhinx() {
		$testBranch = 'SITE-23';

		$executeResults = [
			['cmd' => Git::GIT_COMMAND_TEST, 'result' => 'master'],
			['cmd' => $this->_repository['composerUpdateCommand'], 'result' => 'Composer ok'],
			['cmd' => $this->_repository['phinxCommand'], 'result' => 'Bad result'],
		];

		$this->_makeDoDestMocks($testBranch, $executeResults);

		$result = MethodMocker::callPrivateOrProtectedMethod(PhpTestsShell::class, '_doTest', $this->PhpTestsShell, [
			$this->_repository,
			$testBranch,
		]);
		self::assertFalse($result['success'], 'Некорректный результат');
	}

	/**
	 * Импорт структуры просрал
	 */
	public function testDoTestBadStructureImport() {
		$testBranch = 'SITE-23';
		$executeResults = [
			['cmd' => Git::GIT_COMMAND_TEST, 'result' => 'master'],
		];

		$this->_makeDoDestMocks($testBranch, $executeResults, 'Import error!!!');
		$result = MethodMocker::callPrivateOrProtectedMethod(PhpTestsShell::class, '_doTest', $this->PhpTestsShell, [
			$this->_repository,
			$testBranch,
		]);
		self::assertFalse($result['success'], 'Некорректный результат');
	}

	/**
	 * PHPUnit отвалился
	 */
	public function testDoTestBadPhpUnit() {
		$testBranch = 'SITE-23';

		$executeResults = [
			['cmd' => Git::GIT_COMMAND_TEST, 'result' => 'master'],
			['cmd' => $this->_repository['composerUpdateCommand'], 'result' => 'Composer ok'],
			['cmd' => $this->_repository['phinxCommand'], 'result' => 'All Done. Took 1s'],
			['cmd' => $this->_repository['phpUnitCommand'], 'result' => 'Fail'],
		];

		$this->_makeDoDestMocks($testBranch, $executeResults);

		$result = MethodMocker::callPrivateOrProtectedMethod(PhpTestsShell::class, '_doTest', $this->PhpTestsShell, [
			$this->_repository,
			$testBranch,
		]);
		self::assertFalse($result['success'], 'Некорректный результат');
	}

	/**
	 * Мокаем все внешние вызовы метода
	 *
	 * @param string $testBranch
	 * @param array $executeResults
	 * @param string $importStructResult
	 */
	private function _makeDoDestMocks($testBranch, $executeResults, $importStructResult = '') {
		MethodMocker::mock(Git::class, 'checkout')
			->expectArgs($testBranch)
			->singleCall();

		MethodMocker::mock(Git::class, 'pullCurrentBranch')
			->singleCall();


		$executeIndex = 0;
		MethodMocker::mock(System::class, 'execute')
			->expectCall(count($executeResults))
			->willReturnAction(function ($args) use ($executeResults, &$executeIndex) {
				self::assertTrue(strpos($args[0], $executeResults[$executeIndex]['cmd']) !== false, 'Вызвалась некорректная команда');
				$result = $executeResults[$executeIndex]['result'];
				$executeIndex++;
				return $result;
			});

		MethodMocker::mock(MySql::class, 'dropDbTables')
			->expectCall(2)
			->expectArgs($this->_repository['database']['host'], $this->_repository['database']['name'], $this->_repository['database']['login'], $this->_repository['database']['password'], $this->_repository['database']['port']);

		MethodMocker::mock(MySql::class, 'executeSqlFile')
			->expectArgs($this->_repository['database']['host'], $this->_repository['database']['name'], $this->_repository['database']['login'], $this->_repository['database']['password'], $this->_repository['database']['port'], $this->_repository['structureFile'])
			->willReturnValue($importStructResult);
	}
}