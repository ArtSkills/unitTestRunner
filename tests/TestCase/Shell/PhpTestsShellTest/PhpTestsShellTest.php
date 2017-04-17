<?php
namespace App\Test\TestCase\Shell\PhpTestsShellTest;

use App\Lib\Git;
use App\Lib\GitHub;
use App\Lib\MySql;
use App\Lib\System;
use App\Model\Table\PhpTestActivityTable;
use App\Model\Table\PhpTestsTable;
use App\Shell\PhpTestsShell;
use App\Test\TestCase\AppTestCase;
use ArtSkills\TestSuite\Mock\MethodMocker;
use Cake\ORM\Table;

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
	public $fixtures = [PHP_TESTS, PHP_TEST_ACTIVITY];

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
			null,
			PhpTestsTable::STATUS_FINISHED,
		];
		$statusesIndex = 0;
		MethodMocker::sniff(Table::class, 'patchEntity', function ($args) use (
			$expectedStatuses, &$statusesIndex
		) {
			if (isset($args[1]['status'])) {
				self::assertEquals($args[1]['status'], $expectedStatuses[$statusesIndex]);
			}
			$statusesIndex++;
		});

		$unitTestResult = [
			'success' => true,
			'elapsedSeconds' => 1,
			'activity' => [['report' => '2']],
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
		self::assertEquals($unitTestResult['activity'], $historyRec->content, 'Не добавилась запись истории');
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
			'activity' => [['report' => '2']],
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
			['cmd' => Git::GIT_COMMAND_TEST, 'result' => 'OK'],
			['cmd' => $this->_repository['composerUpdateCommand'], 'result' => 'Composer ok'],
			['cmd' => $this->_repository['phinxCommand'], 'result' => 'All Done. Took 1s'],
			['cmd' => $this->_repository['phpUnitCommand'], 'result' => 'OK (4 tests, 18 assertions)'],
		];

		$this->_makeDoDestMocks($testBranch, $executeResults);

		$result = MethodMocker::callPrivate($this->PhpTestsShell, '_doTest', [
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
			['cmd' => Git::GIT_COMMAND_TEST, 'result' => 'OK'],
			['cmd' => $this->_repository['composerUpdateCommand'], 'result' => 'Composer ok'],
			['cmd' => $this->_repository['phinxCommand'], 'result' => 'Bad result'],
		];

		$this->_makeDoDestMocks($testBranch, $executeResults);

		$result = MethodMocker::callPrivate($this->PhpTestsShell, '_doTest', [
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
			['cmd' => Git::GIT_COMMAND_TEST, 'result' => 'OK'],
		];

		$this->_makeDoDestMocks($testBranch, $executeResults, 'Import error!!!');
		$result = MethodMocker::callPrivate($this->PhpTestsShell, '_doTest',  [
			$this->_repository,
			$testBranch,
		]);
		self::assertFalse($result['success'], 'Некорректный результат');
	}

	/**
	 * PHPUnit отвалился
	 */
	public function testDoTestBadCake() {
		$testBranch = 'SITE-23';

		$executeResults = [
			['cmd' => Git::GIT_COMMAND_TEST, 'result' => 'master'],
			['cmd' => Git::GIT_COMMAND_TEST, 'result' => 'OK'],
			['cmd' => $this->_repository['composerUpdateCommand'], 'result' => 'Composer ok'],
			['cmd' => $this->_repository['phinxCommand'], 'result' => 'All Done. Took 1s'],
			[
				'cmd' => $this->_repository['phpUnitCommand'],
				'result' => "<div class='cake-error'>Warning (2): mkdir(): No such file or directory [APP/Console/Command/SyncOrdersShell.php, line 251]</div>\nOK (4 tests, 18 assertions)",
			],
		];

		$this->_makeDoDestMocks($testBranch, $executeResults);

		$result = MethodMocker::callPrivate($this->PhpTestsShell, '_doTest', [
			$this->_repository,
			$testBranch,
		]);
		self::assertFalse($result['success'], 'Некорректный результат');
	}

	/** Ошибка PHP */
	public function testDoTestBadPHP() {
		$testBranch = 'SITE-23';

		$errorResult = <<<'PHPUNITOUT'
		............................................................... 315 / 359 ( 87%)<br />
		...<pre class=\"cake-error\"><a href=\"javascript:void(0);\" onclick=\"document.getElementById('cakeErr5875014e92cb8-trace').style.display = (document.getElementById('cakeErr5875014e92cb8-trace').style.display == 'none' ? '' : 'none');\"><b>Warning</b> (512)</a>: SplFileInfo::openFile(/var/www/site/app/tmp/cache/persistent/0dev_myapp_query_cache_item#get_itemcfa588ee80907056a2f12f2074df3244): failed to open stream: No space left on device [<b>CORE/Cake/Cache/Engine/FileEngine.php</b>, line <b>356</b>]<div id=\"cakeErr5875014e92cb8-trace\" class=\"cake-stack-trace\" style=\"display: none;\"><a href=\"javascript:void(0);\" onclick=\"document.getElementById('cakeErr5875014e92cb8-code').style.display = (document.getElementById('cakeErr5875014e92cb8-code').style.display == 'none' ? '' : 'none')\">Code</a> <a href=\"javascript:void(0);\" onclick=\"document.getElementById('cakeErr5875014e92cb8-context').style.display = (document.getElementById('cakeErr5875014e92cb8-context').style.display == 'none' ? '' : 'none')\">Context</a><pre id=\"cakeErr5875014e92cb8-code\" class=\"cake-code-dump\" style=\"display: none;\"><code><span style=\"color: #000000\"><span style=\"color: #0000BB\"></span></span></code><br />
		<code><span style=\"color: #000000\"><span style=\"color: #0000BB\">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</span><span style=\"color: #007700\">if&nbsp;(</span><span style=\"color: #0000BB\">$this</span><span style=\"color: #007700\">-&gt;</span><span style=\"color: #0000BB\">existingHandler&nbsp;</span><span style=\"color: #007700\">!==&nbsp;</span><span style=\"color: #0000BB\">null</span><span style=\"color: #007700\">)&nbsp;{</span></span></code><br />
		<span class=\"code-highlight\"><code><span style=\"color: #000000\"><span style=\"color: #0000BB\">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</span><span style=\"color: #007700\">return&nbsp;</span><span style=\"color: #0000BB\">call_user_func</span><span style=\"color: #007700\">(</span><span style=\"color: #0000BB\">$this</span><span style=\"color: #007700\">-&gt;</span><span style=\"color: #0000BB\">existingHandler</span><span style=\"color: #007700\">,&nbsp;</span><span style=\"color: #0000BB\">$code</span><span style=\"color: #007700\">,&nbsp;</span><span style=\"color: #0000BB\">$message</span><span style=\"color: #007700\">,&nbsp;</span><span style=\"color: #0000BB\">$file</span><span style=\"color: #007700\">,&nbsp;</span><span style=\"color: #0000BB\">$line</span><span style=\"color: #007700\">,&nbsp;</span><span style=\"color: #0000BB\">$context</span><span style=\"color: #007700\">);</span></span></code></span></pre><pre id=\"cakeErr5875014e92cb8-context\" class=\"cake-context\" style=\"display: none;\">$key = &#039;0dev_myapp_query_cache_item#get_itemcfa588ee80907056a2f12f2074df3244&#039;<br />
		$createKey = true<br />
		$groups = null<br />
		$dir = &#039;/var/www/site/app/tmp/cache/persistent/&#039;<br />
		$path = object(SplFileInfo) {<br />
		<br />
		}<br />
		$exists = false<br />
		$e = object(RuntimeException) {<br />
			xdebug_message =&gt; &#039;<br />
		RuntimeException: SplFileInfo::openFile(/var/www/site/app/tmp/cache/persistent/0dev_myapp_query_cache_item#get_itemcfa588ee80907056a2f12f2074df3244): failed to open stream: No space left on device in /var/www/site/lib/Cake/Cache/Engine/FileEngine.php on line 354<br />
		<br />
		Call Stack:<br />
		    0.0003     224432   1. {main}() /var/www/site/vendor/phpunit/phpunit/phpunit:0<br />
		    0.0151     618320   2. PHPUnit_TextUI_Command::main() /var/www/site/vendor/phpunit/phpunit/phpunit:55<br />
		    0.0151     618936   3. PHPUnit_TextUI_Command-&gt;run() /var/www/site/vendor/phpunit/phpunit/src/TextUI/Command.php:132<br />
		    0.8519   18743424   4. PHPUnit_TextUI_TestRunner-&gt;doRun() /var/www/site/vendor/phpunit/phpunit/src/TextUI/Command.php:179<br />
		    0.8587   19124056   5. PHPUnit_Framework_TestSuite-&gt;run() /var/www/site/vendor/phpunit/phpunit/src/TextUI/TestRunner.php:425<br />
		  360.7693  209513816   6. PHPUnit_Framework_TestSuite-&gt;run() /var/www/site/vendor/phpunit/phpunit/src/Framework/TestSuite.php:675<br />
		  360.7787  209516440   7. AppTestCase-&gt;run() /var/www/site/vendor/phpunit/phpunit/src/Framework/TestSuite.php:675<br />
		  360.7788  209516528   8. AppTestCase-&gt;runCase() /var/www/site/app/Test/Case/AppTestCase.php:18<br />
		  360.8391  209781160   9. CakeTestCase-&gt;run() /var/www/site/app/Test/Case/TestCaseTrait.php:41<br />
		  361.5262  209399064  10. PHPUnit_Framework_TestCase-&gt;run() /var/www/site/lib/Cake/TestSuite/CakeTestCase.php:82<br />
		  361.5263  209399472  11. PHPUnit_Framework_TestResult-&gt;run() /var/www/site/vendor/phpunit/phpunit/src/Framework/TestCase.php:754<br />
		  361.5265  209401544  12. PHPUnit_Framework_TestCase-&gt;runBare() /var/www/site/vendor/phpunit/phpunit/src/Framework/TestResult.php:686<br />
		  361.8848  209520336  13. PHPUnit_Framework_TestCase-&gt;runTest() /var/www/site/vendor/phpunit/phpunit/src/Framework/TestCase.php:818<br />
		  361.8848  209521168  14. ReflectionMethod-&gt;invokeArgs() /var/www/site/vendor/phpunit/phpunit/src/Framework/TestCase.php:952<br />
		  361.8848  209521536  15. ItemTest-&gt;testGetItem() /var/www/site/vendor/phpunit/phpunit/src/Framework/TestCase.php:952<br />
		  361.9757  209409064  16. Item-&gt;getItem() /var/www/site/app/Test/Case/Model/ItemTest/ItemTest.php:49<br />
		  361.9757  209410216  17. Cache::remember() /var/www/site/app/Model/Item.php:113<br />
		  361.9817  209446064  18. Cache::write() /var/www/site/lib/Cake/Cache/Cache.php:577<br />
		  361.9818  209446200  19. FileEngine-&gt;write() /var/www/site/lib/Cake/Cache/Cache.php:317<br />
		  361.9818  209446248  20. FileEngine-&gt;_setKey() /var/www/site/lib/Cake/Cache/Engine/FileEngine.php:116<br />
		  361.9819  209451104  21. SplFileInfo-&gt;openFile() /var/www/site/lib/Cake/Cache/Engine/FileEngine.php:354<br />
		&#039;<br />
			severity =&gt; (int) 2<br />
PHPUNITOUT;

		$executeResults = [
			['cmd' => Git::GIT_COMMAND_TEST, 'result' => 'master'],
			['cmd' => Git::GIT_COMMAND_TEST, 'result' => 'OK'],
			['cmd' => $this->_repository['composerUpdateCommand'], 'result' => 'Composer ok'],
			['cmd' => $this->_repository['phinxCommand'], 'result' => 'All Done. Took 1s'],
			[
				'cmd' => $this->_repository['phpUnitCommand'],
				'result' => $errorResult,
			],
		];

		$this->_makeDoDestMocks($testBranch, $executeResults);

		$result = MethodMocker::callPrivate($this->PhpTestsShell, '_doTest', [
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
			->expectArgs($testBranch, null)
			->singleCall()
			->willReturnValue(true);

		MethodMocker::mock(Git::class, 'pullCurrentBranch')
			->singleCall()
			->willReturnValue(true);


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

	/**
	 * Определяем финальный статус теста
	 */
	public function testGetFinalStatus() {
		$testStatus = MethodMocker::callPrivate($this->PhpTestsShell, '_getFinalStatus', [json_decode(file_get_contents(__DIR__.'/reportWithCrash.json'), true)]);
		self::assertEquals(PhpTestsTable::STATUS_NEW, $testStatus);

		$testStatus = MethodMocker::callPrivate($this->PhpTestsShell, '_getFinalStatus', [json_decode(file_get_contents(__DIR__.'/reportGood.json'), true)]);
		self::assertEquals(PhpTestsTable::STATUS_FINISHED, $testStatus);
	}
}
