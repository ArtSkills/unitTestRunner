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
			->singleCall();

		$this->post('/tests', ['payload' => file_get_contents(__DIR__ . '/pull_request.json')]);

		$phpTests = PhpTestsTable::instance();
		$newTest = $phpTests->find()
			->where(['repository' => 'ArtSkills/site', 'ref' => 'selectFix', 'status' => PhpTestsTable::STATUS_NEW])
			->first();

		self::assertNotEmpty($newTest, 'Не добавилась запись в бд');
		$this->assertJsonOKEquals(['id' => $newTest->id], 'Некорректный результат при добавлении записи');
	}

	/**
	 * Секретный ключ для GitHub
	 */
	private function _setSecretHeader() {
		$gitHub = new GitHub('1');

		$this->_request['headers'] = [
			TestsController::GITHUB_SECRET_HEADER => $gitHub->buildSecret(file_get_contents('php://input'), Configure::read('gitSecret'))
		];
	}
}