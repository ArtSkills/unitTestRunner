<?php

namespace App\Test\TestCase\Controller\TestsControllerTest;

use App\Controller\TestsController;
use App\Lib\GitHub;
use App\Model\Table\PhpTestsTable;
use App\Test\Suite\MethodMocker;
use App\Test\TestCase\Controller\AppControllerTestCase;
use Cake\Core\Configure;

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
		$this->assertJsonErrorEquals(TestsController::MSG_ADD_BAD_ARGS, $this->_response->body());
	}

	/**
	 * Отправка некорректного события
	 */
	public function testAddBadSecret() {
		$this->post('/tests', ['payload' => json_encode(['action' => 'incorrect'])]);
		$this->assertJsonErrorEquals(TestsController::MSG_ADD_BAD_SECRET, $this->_response->body());
	}

	/**
	 * Отправка некорректного события
	 */
	public function testAddBadAction() {
		$this->_setSecretHeader();

		$this->post('/tests', ['payload' => json_encode(['action' => 'incorrect'])]);
		$this->assertJsonErrorEquals(TestsController::MSG_ADD_BAD_ACTION, $this->_response->body());
	}

	/**
	 * Не настроенный репозиторий
	 */
	public function testAddBadRepository() {
		$this->_setSecretHeader();

		$this->post('/tests', ['payload' => file_get_contents(__DIR__ . '/pull_request_bad_repo.json')]);
		$this->assertJsonErrorEquals(TestsController::MSG_ADD_BAD_REPO, $this->_response->body());
	}

	/**
	 * Добавление на тест
	 */
	public function testAdd() {
		$this->_setSecretHeader();

		MethodMocker::mock(GitHub::class, 'changeCommitStatus')
			->expectCall(3);

		$payload = file_get_contents(__DIR__ . '/pull_request.json');

		$this->post('/tests', ['payload' => $payload]);

		$phpTests = PhpTestsTable::instance();
		$conditions = ['repository' => 'ArtSkills/site', 'ref' => 'selectFix', 'status' => PhpTestsTable::STATUS_NEW];
		$newTest = $phpTests->find()
			->where($conditions)
			->first();

		self::assertNotEmpty($newTest, 'Не добавилась запись в бд');
		$this->assertJsonOKEquals(['id' => $newTest->id], 'Некорректный результат при добавлении записи');

		// повторно добавили тот же запрос
		$this->_setSecretHeader();
		$this->post('/tests', ['payload' => $payload]);
		$this->assertJsonOKEquals(['id' => $newTest->id], 'Некорректный результат при добавлении повторной записи');
		self::assertEquals(1, $phpTests->find()->where($conditions)->count(), 'Добавился дубликат одного и того же запроса');

		// Отменяем уже запущенный тест
		$phpTests->saveArr(['status' => PhpTestsTable::STATUS_PROCESSING], $newTest);
		$this->_setSecretHeader();
		$this->post('/tests', ['payload' => file_get_contents(__DIR__ . '/pull_request_closed.json')]);
		$this->assertJsonOKEquals(['cancelled' => false]);
		self::assertTrue($phpTests->exists(['id' => $newTest->id]), 'Удалился запущенный тест');

		// Отменяем еще не запущенный тест
		$phpTests->saveArr(['status' => PhpTestsTable::STATUS_NEW], $newTest);
		$this->_setSecretHeader();
		$this->post('/tests', ['payload' => file_get_contents(__DIR__ . '/pull_request_closed.json')]);
		$this->assertJsonOKEquals(['cancelled' => true]);
		self::assertFalse($phpTests->exists(['id' => $newTest->id]), 'Не удалился тест');
	}

	/**
	 * Секретный ключ для GitHub
	 */
	private function _setSecretHeader() {
		$gitHub = new GitHub('1');

		$this->_request['headers'] = [
			TestsController::GITHUB_SECRET_HEADER => $gitHub->buildSecret(file_get_contents('php://input'), Configure::read('gitSecret')),
		];
	}

	/**
	 * Правка с некорректными данными
	 */
	public function testEditBadData() {
		$this->put('/tests/1', ['1' => '2']);
		$this->assertJsonErrorEquals(TestsController::MSG_ADD_BAD_ARGS, $this->_response->body());

		$this->put('/tests/1', ['rerun_test' => 'true']);
		$this->assertJsonErrorEquals(TestsController::MSG_EDIT_NOT_FOUND, $this->_response->body());

		$testId = 31;
		$phpTests = PhpTestsTable::instance();
		$phpTests->updateAll(['status' => PhpTestsTable::STATUS_NEW], ['id' => $testId]);
		$this->put('/tests/' . $testId, ['rerun_test' => 'true']);
		$this->assertJsonErrorEquals(TestsController::MSG_EDIT_BAD_STATUS, $this->_response->body());
	}

	/**
	 * Правка с JSON ответом
	 */
	public function testEdit() {
		$testId = 31;
		$phpTests = PhpTestsTable::instance();
		$toUpdateTest = $phpTests->find()->where(['id' => $testId])->first();

		MethodMocker::mock(GitHub::class, 'changeCommitStatus')
			->expectArgs($toUpdateTest->repository, $toUpdateTest->sha, GitHub::STATE_PROCESSING, TestsController::PUSH_QUEUE_MESSAGE)
			->singleCall();

		$this->put('/tests/' . $testId, ['rerun_test' => '1']);
		$this->assertJsonOKEquals(['id' => $testId]);

		$updatedTest = $phpTests->find()->where(['id' => $testId])->first();
		self::assertEquals(PhpTestsTable::STATUS_NEW, $updatedTest->status);
	}

	/**
	 * Правка с редиректом
	 */
	public function testEditRedirect() {
		MethodMocker::mock(GitHub::class, 'changeCommitStatus')
			->singleCall();

		$testId = 31;
		$this->put('/tests/' . $testId, ['rerun_test' => '1', 'redirect' => 1]);
		$this->assertRedirect('https://github.com/ArtSkills/crm/pulls');
	}
}
