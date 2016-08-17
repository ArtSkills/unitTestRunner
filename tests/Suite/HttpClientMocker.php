<?php
/**
 * Created by PhpStorm.
 * User: vladimirtunikov
 * Date: 16.05.16
 * Time: 11:46
 */

namespace App\Test\Suite;


use Cake\Http\Client\Request;
use Cake\Http\Client\Response;

class HttpClientMocker
{
	/**
	 * Коллекция мокнутых вызовов
	 *
	 * @var array
	 */
	private static $_mockCallList = [];

	/**
	 * Сниф запросов и ответов
	 *
	 * @var array
	 */
	private static $_sniffList = [];

	/**
	 * Добавляем элемент
	 *
	 * @param array $element {
	 * @var Request $request
	 * @var Response $response
	 * }
	 */
	public static function addSniff($element) {
		self::$_sniffList[] = $element;
	}

	/**
	 * Выгружаем весь список запросов
	 *
	 * @return array
	 */
	public static function getSniffList() {
		return self::$_sniffList;
	}

	/**
	 * Чистим всё
	 *
	 * @param bool $hasFailed завалился ли тест
	 */
	public static function clean($hasFailed = false) {
		self::$_sniffList = [];

		if (!$hasFailed) {
			/**
			 * @var HttpClientMockerEntity $mock
			 */
			foreach (self::$_mockCallList as $mock) {
				$mock->callCheck();
			}
		}
		self::$_mockCallList = [];
	}

	/**
	 * Мокаем HTTP запрос
	 *
	 * @param string $url
	 * @param string $method
	 * @return HttpClientMockerEntity
	 * @throws \Exception
	 */
	public static function mock($url, $method) {
		$mockId = self::_buildKey($url, $method);
		if (isset(self::$_mockCallList[$mockId])) {
			throw new \Exception($url . ' is already mocked with such args');
		}

		self::$_mockCallList[$mockId] = new HttpClientMockerEntity($mockId, $url, $method);
		return self::$_mockCallList[$mockId];
	}

	/**
	 * Проверяем на мок и возвращаем результат
	 *
	 * @param Request $request
	 * @return null|string
	 */
	public static function getMockedData(Request $request) {
		/**
		 * @var HttpClientMockerEntity $mock
		 */
		foreach (self::$_mockCallList as $mock) {
			if ($mock->check($request->url(), $request->method())) {
				return $mock->doAction($request);
			}
		}

		return null;
	}

	/**
	 * Формируем уникальный ключ
	 *
	 * @param string $url
	 * @param string $method
	 * @return string
	 */
	private static function _buildKey($url, $method) {
		return $url . '#' . $method;
	}
}