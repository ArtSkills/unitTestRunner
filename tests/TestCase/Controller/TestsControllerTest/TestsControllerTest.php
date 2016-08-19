<?php

namespace App\Test\TestCase\Controller\TestsControllerTest;

use App\Lib\GitHub;
use App\Model\Table\PhpTestsTable;
use App\Test\Suite\MethodMocker;
use App\Test\TestCase\Controller\AppControllerTestCase;

class TestsControllerTest extends AppControllerTestCase
{
	/**
	 * @inheritdoc
	 */
	public $fixtures = ['app.php_tests'];

	/**
	 * Добавление теста
	 */
	public function testAddBadArgs() {
		$this->post('/tests', ['a' => 1]);
		$this->assertJsonErrorEquals("Bad args", $this->_response->body());
	}

	/**
	 * Отправка некорректного события
	 */
	public function testAddBadAction() {
		$this->post('/tests', ['payload' => json_encode(['action' => 'incorrect'])]);
		$this->assertJsonErrorEquals("Incorrect action", $this->_response->body());
	}

	/**
	 * Не настроенный репозиторий
	 */
	public function testAddBadRepository() {
		$this->post('/tests', ['payload' => file_get_contents(__DIR__ . '/pull_request_bad_repo.json')]);
		$this->assertJsonErrorEquals("Not configured repository", $this->_response->body());
	}

	/**
	 * Добавление на тест
	 */
	public function testAdd() {
		MethodMocker::mock(GitHub::class, 'changeCommitStatus')
			->singleCall();

		$this->post('/tests', ['payload' => file_get_contents(__DIR__ . '/pull_request.json')]);

		$phpTests = PhpTestsTable::instance();
		$newTest = $phpTests->find()
			->where(['repository' => 'ArtSkills/site', 'ref' => 'selectFix', 'status' => PhpTestsTable::STATUS_NEW])
			->first();

		self::assertNotEmpty($newTest, 'Не добавилась запись в бд');
		$this->assertJsonOKEquals(['id' => $newTest->id], 'Некорректный результат при добавлении записи');
	}
}