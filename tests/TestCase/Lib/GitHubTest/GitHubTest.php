<?php
namespace App\Test\TestCase\Lib\GitHubTest;

use App\Lib\GitHub;
use App\Test\TestCase\AppTestCase;
use ArtSkills\TestSuite\HttpClientMock\HttpClientMocker;
use Cake\Http\Client\Request;
use \Exception;

class GitHubTest extends AppTestCase
{
	/**
	 * Не указали токен
	 *
	 * @expectedException Exception
	 */
	public function testWithoutToken() {
		new GitHub('');
	}

	/**
	 * Меняем статус коммита без указания урла
	 */
	public function testChangeCommitStatusNoUrl() {
		$testRepository = 'TestRep';
		$testSha = 'shashasha';
		$testState = GitHub::STATE_ERROR;
		$testDescription = 'ggg';
		$testResult = ['ok' => true];

		HttpClientMocker::mockPost(GitHub::POOL . '/repos/' . $testRepository . '/statuses/' . $testSha)
			->willReturnAction(function ($request) use ($testState, $testDescription, $testResult) {
				/**
				 * @var Request $request
				 */
				$reqBody = json_decode($request->body(), true);
				self::assertEquals([
					'state' => $testState,
					'context' => GitHub::DEFAULT_STATUS_CONTENT,
					'description' => $testDescription,
				], $reqBody, 'Передались некорректные данные');

				self::assertTrue($request->hasHeader('User-Agent'), 'Не установились необходимые заголоки');
				self::assertTrue($request->hasHeader('Authorization'), 'Не установились необходимые заголоки');

				return json_encode($testResult);
			});

		$gitHub = new GitHub('1');
		self::assertEquals($testResult, $gitHub->changeCommitStatus($testRepository, $testSha, $testState, $testDescription));
	}

	/**
	 * Формирование секретного ключа
	 */
	public function testSecret() {
		$testSecret = '123';
		$testData = 'data';
		$expected = '4931e066f08c6a12b5a6aaeb4be339f7c2566c73';
		$testAlgo = 'sha1=';
		$gitHub = new GitHub('1');

		self::assertEquals($testAlgo . $expected, $gitHub->buildSecret($testData, $testSecret));
		self::assertTrue($gitHub->checkSecret($testAlgo . $expected, $testData, $testSecret));
	}
}