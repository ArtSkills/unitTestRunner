<?php
namespace App\Lib;

use \Exception;

class GitHub
{
	const POOL = 'https://api.github.com';
	const REQUEST_TIMEOUT = 30;

	const STATE_PROCESSING = 'pending';
	const STATE_SUCCESS = 'success';
	const STATE_ERROR = 'error';

	const DEFAULT_STATUS_CONTENT = 'Каратель говорит';

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
		return $client->post(self::POOL . $method, json_encode($data), [
			'type' => 'json',
			'headers' => [
				'User-Agent' => 'UnitTestRunner',
				'Authorization' => 'token ' . $this->_token
			]
		])->json;
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
		$toSend = [
			'state' => $state,
			'context' => self::DEFAULT_STATUS_CONTENT,
			'description' => $description,
		];

		if ($targetUrl !== false) {
			$toSend['target_url'] = $targetUrl;
		}

		return $this->_doPostRequest('/repos/' . $repository . '/statuses/' . $sha, $toSend);
	}
}