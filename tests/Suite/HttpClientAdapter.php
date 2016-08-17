<?php
namespace App\Test\Suite;


use Cake\Http\Client\Adapter\Stream;
use Cake\Http\Client\Request;
use Cake\Http\Client\Response;

/**
 * Прослайка на отправку HTTP запросов
 *
 * @package App\Test\Suite
 */
class HttpClientAdapter extends Stream
{
	/**
	 * Полная инфа по текущему взаимодействию (запрос и ответ)
	 *
	 * @var array|null
	 */
	private $_currentRequestData = null;

	/**
	 * Все запросы проверяются на подмену, а также логипуются
	 *
	 * @param Request $request
	 * @return array
	 */
	protected function _send(Request $request) {
		$this->_currentRequestData = [
			'request' => $request,
			'response' => '',
		];

		$mockedData = HttpClientMocker::getMockedData($request);
		if ($mockedData !== null) {
			return $this->createResponses(['HTTP/1.1 200 OK', 'Server: nginx/1.2.1'], $mockedData);
		}
		else {
			return parent::_send($request);
		}
	}

	/**
	 * @inheritdoc
	 */
	public function createResponses($headers, $content) {
		$result = parent::createResponses($headers, $content);
		/**
		 * @var Response $lastResponse
		 */
		$lastResponse = $result[count($result) - 1];
		$this->_currentRequestData['response'] = $lastResponse;

		HttpClientMocker::addSniff($this->_currentRequestData);
		$this->_currentRequestData = null;

		return $result;
	}
}