<?php
/**
 * Created by PhpStorm.
 * User: vladimirtunikov
 * Date: 15.08.16
 * Time: 15:34
 */

namespace ArtSkills\TestRunner;


class GitHub
{
	const POOL = 'https://api.github.com';
	const REQUEST_TIMEOUT = 30;

	const STATE_PROCESSING = 'pending';
	const STATE_SUCCESS = 'success';
	const STATE_ERROR = 'error';

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
	 */
	public function __construct($token) {
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
		$git = curl_init();
		curl_setopt($git, CURLOPT_URL, self::POOL . $method);
		curl_setopt($git, CURLOPT_HTTPHEADER, [
			'User-Agent: UnitTestRunner',
			'Authorization: token ' . $this->_token,
			'Content-Type: application/json',
		]);
		curl_setopt($git, CURLOPT_TIMEOUT, self::REQUEST_TIMEOUT);
		curl_setopt($git, CURLOPT_POST, true);
		curl_setopt($git, CURLOPT_POSTFIELDS, json_encode($data));
		curl_setopt($git, CURLOPT_RETURNTRANSFER, true);
		return json_decode(curl_exec($git), true);
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
			'context' => 'Каратель говорит',
			'description' => $description,
		];

		if ($targetUrl !== false) {
			$toSend['target_url'] = $targetUrl;
		}

		return $this->_doPostRequest('/repos/' . $repository . '/statuses/' . $sha, $toSend);
	}
}