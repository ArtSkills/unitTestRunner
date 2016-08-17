<?php
/**
 * Created by PhpStorm.
 * User: tune
 * Date: 16.10.15
 * Time: 16:46
 */

namespace App\Test\TestCase\Controller;

use Cake\ORM\TableRegistry;
use Cake\TestSuite\IntegrationTestCase;
use App\Controller\Component\IndianAuthComponent;
use App\Test\TestCaseTrait;

/**
 * Class AppControllerTestCase
 *
 * @package App\Test\TestCase\Controller
 */
abstract class AppControllerTestCase extends IntegrationTestCase
{
	use TestCaseTrait;

	/**
	 * Сравнение строки с JSON и массива
	 *
	 * @param string $jsonString
	 * @param array $array
	 * @param string $message
	 * @param float $delta
	 * @param int $maxDepth
	 * @param bool $canonicalize
	 * @param bool $ignoreCase
	 */
	public static function assertJsonStringEqualsArray(
		$jsonString, $array, $message = '', $delta = 0.0, $maxDepth = 10, $canonicalize = false, $ignoreCase = false
	) {
		$expectedArray = json_decode($jsonString, true);
		self::assertEquals($expectedArray, $array, $message, $delta, $maxDepth, $canonicalize, $ignoreCase);
	}

	/**
	 * Проверка, что JSON-массив содержит переданный подмассив
	 *
	 * @param string|array|\ArrayAccess $subset
	 * @param string $json
	 * @param bool $strict Check for object identity
	 * @param string $message
	 * @return void
	 */
	public function assertJsonSubset($subset, $json, $strict = false, $message = '') {
		if (is_string($subset)) {
			$subset = json_decode($subset, true);
		}
		$array = json_decode($json, true);
		$this->assertArraySubset($subset, $array, $strict, $message);
	}

	/**
	 * Проверка JSON ответа
	 *
	 * @param array $expected
	 * @param string $message
	 * @param float $delta
	 * @param int $maxDepth
	 */
	public function assertJsonResponseEquals($expected, $message = '', $delta = 0.0, $maxDepth = 10) {
		$response = json_decode($this->_response->body(), true);
		self::assertNotEmpty($response, 'Получен ответ не в формате JSON');
		self::assertEquals($expected, $response, $message, $delta, $maxDepth);
	}

	/**
	 * Проверка JSON ответа
	 *
	 * @param array $subset
	 * @param bool $strict
	 * @param string $message
	 */
	public function assertJsonResponseSubset($subset, $strict = false, $message = '') {
		$response = json_decode($this->_response->body(), true);
		self::assertNotEmpty($response, 'Получен ответ не в формате JSON');
		self::assertArraySubset($subset, $response, $strict, $message);
	}

	/**
	 * Проверка JSON ответа
	 *
	 * @param string $expectedMessage
	 * @param string $message
	 * @param array $expectedData
	 * @param float $delta
	 * @param int $maxDepth
	 */
	public function assertJsonErrorEquals($expectedMessage, $message = '', $expectedData = [], $delta = 0.0, $maxDepth = 10) {
		$expectedResponse = ['status' => 'error', 'message' => $expectedMessage] + $expectedData;
		$this->assertJsonResponseEquals($expectedResponse, $message, $delta, $maxDepth);
	}

	/**
	 * Проверка JSON ответа
	 *
	 * @param array $expectedData
	 * @param string $message
	 * @param float $delta
	 * @param int $maxDepth
	 */
	public function assertJsonOKEquals($expectedData = [], $message = '', $delta = 0.0, $maxDepth = 10) {
		$expectedResponse = ['status' => 'ok'] + $expectedData;
		$this->assertJsonResponseEquals($expectedResponse, $message, $delta, $maxDepth);
	}

	/**
	 * @inheritdoc
	 */
	public function setUp() {
		$this->configApplication(null, [CONFIG]);
		parent::setUp();
		$this->setUpSuite();
	}

	/**
	 * @inheritdoc
	 */
	public function tearDown() {
		parent::tearDown();
		$this->tearDownSuite();
	}
}