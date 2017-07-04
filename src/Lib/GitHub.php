<?php

namespace App\Lib;

use ArtSkills\Http\Client;
use Cake\Core\Configure;
use Cake\Log\Log;
use \Exception;

class GitHub
{
	const POOL = 'https://api.github.com';
	const REQUEST_TIMEOUT = 30;

	const STATE_PROCESSING = 'pending';
	const STATE_SUCCESS = 'success';
	const STATE_ERROR = 'error';

	const DEFAULT_STATUS_CONTENT = 'Каратель говорит';

	const RESPONSE_OK = [200, 201];

	/**
	 * Токен доступа
	 *
	 * @var string|null
	 */
	private $_token = null;

	/**
	 * GitHub constructor.
	 *
	 * @param string $token
	 * @throws Exception
	 */
	public function __construct($token) {
		if (!strlen($token)) {
			throw new Exception('Empty token!');
		}

		$this->_token = $token;
	}

	/**
	 * POST запрос в GIT
	 *
	 * @param string $method
	 * @param array $data
	 * @return array
	 */
	private function _doPostRequest($method, $data) {
		$client = new Client();
		$result = $client->post(self::POOL . $method, json_encode($data), [
			'type' => 'json',
			'headers' => [
				'User-Agent' => 'UnitTestRunner',
				'Authorization' => 'token ' . $this->_token,
			],
		]);

		if (!in_array($result->getStatusCode(), self::RESPONSE_OK)) {
			Log::error('Bad GitHub response: ' . print_r($result->json, true));
		}
		return $result->json;
	}

	/**
	 * Меняем статус коммита
	 *
	 * @param string $repository
	 * @param string $sha
	 * @param string $state
	 * @param string $description
	 * @param bool|string $targetUrl
	 * @return array
	 */
	public function changeCommitStatus($repository, $sha, $state, $description, $targetUrl = false) {
		$serverId = Configure::read('serverId');
		$toSend = [
			'state' => $state,
			'context' => self::DEFAULT_STATUS_CONTENT . (!empty($serverId) ? ' (' . $serverId . ')' : ''),
			'description' => $description,
		];

		if ($targetUrl !== false) {
			$toSend['target_url'] = $targetUrl;
		}

		return $this->_doPostRequest('/repos/' . $repository . '/statuses/' . $sha, $toSend);
	}

	/**
	 * Формируем Хеш ключ
	 *
	 * @param string $data
	 * @param string $secret
	 * @param string $algo
	 * @return string
	 */
	public function buildSecret($data, $secret, $algo = 'sha1') {
		return $algo . '=' . hash_hmac($algo, $data, $secret);
	}

	/**
	 * Проверка Хеша
	 *
	 * @param string $gitHeader X-Hub-Signature
	 * @param string $rawPost file_get_contents('php://input')
	 * @param string $secret
	 * @return bool
	 */
	public function checkSecret($gitHeader, $rawPost, $secret) {
		list($algo, $hash) = explode('=', $gitHeader, 2) + ['', ''];
		return $gitHeader === $this->buildSecret($rawPost, $secret, $algo);
	}
}