<?php
namespace App\Test\TestCase\Lib\GitHubTest;

use App\Lib\GitHub;
use App\Test\Suite\HttpClientMocker;
use App\Test\Suite\MethodMocker;
use App\Test\TestCase\AppTestCase;
use Cake\Http\Client\Request;
use Cake\Http\Client\Response;
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

		HttpClientMocker::mock(GitHub::POOL . '/repos/' . $testRepository . '/statuses/' . $testSha, Response::METHOD_POST)
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
}