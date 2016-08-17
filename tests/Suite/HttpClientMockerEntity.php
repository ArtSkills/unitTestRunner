<?php
/**
 * Created by PhpStorm.
 * User: vladimirtunikov
 * Date: 16.05.16
 * Time: 12:43
 */

namespace App\Test\Suite;

use Cake\Http\Client\Request;
use \PHPUnit_Framework_ExpectationFailedException;


class HttpClientMockerEntity
{
	/**
	 * ID текущего мока в стеке HttpClientMocker
	 *
	 * @var string
	 */
	private $_id = '';

	/**
	 * Файл, в котором мокнули
	 *
	 * @var string
	 */
	private $_callerFile = '';

	/**
	 * Строка вызова к HttpClientMocker::mock
	 *
	 * @var int
	 */
	private $_callerLine = 0;

	/**
	 * Мокнутый урл
	 *
	 * @var string
	 */
	private $_url = '';

	/**
	 * Метод запроса
	 *
	 * @var string
	 */
	private $_method = Request::METHOD_GET;

	/**
	 * POST тело запроса
	 *
	 * @var array
	 */
	private $_body = [];

	/**
	 * Возвращаемый результат
	 *
	 * @var null|mixed
	 */
	private $_returnValue = null;

	/**
	 * Возвращаемое событие
	 *
	 * @var null|callable
	 */
	private $_returnAction = null;

	/**
	 * Сколько раз ожидается вызов функции
	 *
	 * @var int
	 */
	private $_expectedCallCount = MethodMockerEntity::EXPECT_CALL_ONCE;

	/**
	 * Кол-во вызовов данного мока
	 *
	 * @var int
	 */
	private $_callCounter = 0;

	/**
	 * Был ли вызов данного мока
	 *
	 * @var bool
	 */
	private $_isCalled = false;

	/**
	 * Мок отработал и все вернул в первоначальный вид
	 *
	 * @var bool
	 */
	private $_mockChecked = false;

	/**
	 * HttpClientMockerEntity constructor.
	 *
	 * @param string $mockId
	 * @param string $url
	 * @param string $method
	 */
	public function __construct($mockId, $url, $method = Request::METHOD_GET) {
		$calledFrom = debug_backtrace();
		$this->_callerFile = isset($calledFrom[1]['file']) ? $calledFrom[1]['file'] : $calledFrom[0]['file'];
		$this->_callerLine = isset($calledFrom[1]['line']) ? $calledFrom[1]['line'] : $calledFrom[0]['line'];

		$this->_id = $mockId;
		$this->_url = $url;
		$this->_method = $method;
	}

	/**
	 * Проверяем, относится ли текущий мок к запросу с данными параметрами
	 *
	 * @param string $url
	 * @param string $method
	 * @return bool
	 */
	public function check($url, $method) {
		if ($this->_url !== $url) {
			return false;
		}

		if ($this->_method !== $method) {
			return false;
		}
		return true;
	}

	/**
	 * Омечаем, что функция должна вызываться разово
	 *
	 * @return $this
	 */
	public function singleCall() {
		return $this->expectCall(1);
	}

	/**
	 * Омечаем, что функция должна вызываться как минимум 1 раз
	 *
	 * @return $this
	 */
	public function anyCall() {
		return $this->expectCall(MethodMockerEntity::EXPECT_CALL_ONCE);
	}

	/**
	 * Ограничение на количество вызовов данного мока
	 *
	 * @param int $times
	 * @return $this
	 */
	public function expectCall($times = 1) {
		$this->_expectedCallCount = $times;
		return $this;
	}

	/**
	 * Заполняем тело запроса для POST и прочих методов
	 *
	 * @param array $body
	 * @return $this
	 * @throws \Exception
	 */
	public function expectBody($body) {
		if ($this->_method == Request::METHOD_GET) {
			throw new \Exception($this->_getErrorMessage('Body for GET method is not required!'));
		}

		$this->_body = $body;
		return $this;
	}

	/**
	 * Что вернет запрос
	 *
	 * @param mixed $value
	 * @return $this
	 */
	public function willReturnString($value) {
		$this->_returnAction = null;
		$this->_returnValue = $value;
		return $this;
	}

	/**
	 * Вернет закодированный json
	 *
	 * @param array $value
	 * @return $this
	 */
	public function willReturnJson($value) {
		return $this->willReturnString(json_encode($value, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
	}

	/**
	 * Вернется результат функции $action(array Аргументы, [mixed Результат от оригинального метода])
	 * Пример:
	 * ->willReturnAction(function($request){
	 *    return 'result body';
	 * });
	 *
	 * @param callable $action
	 * @return $this
	 */
	public function willReturnAction($action) {
		$this->_returnAction = $action;
		$this->_returnValue = null;
		return $this;
	}

	/**
	 * Мок событие
	 *
	 * @param Request $request
	 * @return string
	 * @throws \Exception
	 * @throws \PHPUnit_Framework_ExpectationFailedException
	 */
	public function doAction($request) {
		if (($this->_expectedCallCount > MethodMockerEntity::EXPECT_CALL_ONCE) && ($this->_callCounter >= $this->_expectedCallCount)) {
			throw new PHPUnit_Framework_ExpectationFailedException($this->_getErrorMessage('expected ' . $this->_expectedCallCount . ' calls, but more appeared'));
		}

		if (!empty($this->_body)) {
			$reqBody = [];
			parse_str($request->body(), $reqBody);
			\PHPUnit_Framework_Assert::assertEquals($this->_body, $reqBody, 'Expected POST body data is not equals real data');
		}

		$this->_callCounter++;
		$this->_isCalled = true;

		if ($this->_returnValue !== null) {
			return $this->_returnValue;
		} elseif ($this->_returnAction !== null) {
			$action = $this->_returnAction;
			return $action($request);
		} else {
			throw new \Exception($this->_getErrorMessage('Return mock action is not defined'));
		}
	}

	/**
	 * Кол-во вызовов данного мока
	 *
	 * @return int
	 */
	public function getCallCount() {
		return $this->_callCounter;
	}

	/**
	 * Финальная проверка на вызовы
	 */
	public function callCheck() {
		if ($this->_mockChecked) {
			return;
		}

		$goodCallCount = (
			(($this->_expectedCallCount == MethodMockerEntity::EXPECT_CALL_ONCE) && $this->_isCalled)
			|| ($this->_expectedCallCount == $this->getCallCount())
		);
		$this->_mockChecked = true;

        if (!$goodCallCount) {
            throw new PHPUnit_Framework_ExpectationFailedException($this->_getErrorMessage(
				$this->_isCalled ? 'is called ' . $this->getCallCount() . ' times, expected ' . $this->_expectedCallCount : 'is not called!'
			));
        }
	}

	/**
	 * Формируем сообщение об ошибке
	 *
	 * @param string $msg
	 * @return string
	 */
	private function _getErrorMessage($msg) {
		return $this->_url . '(mocked in ' . $this->_callerFile . ' line ' . $this->_callerLine . ') - ' . $msg;
	}
}
