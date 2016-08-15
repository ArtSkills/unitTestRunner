<?php
const QUEUE_STATUS_NEW = 'new';
const QUEUE_STATUS_PROCESSING = 'processing';
const QUEUE_STATUS_FINISHED = 'success';

return [
	'serverUrl' => 'http://test-runner.artskills.ru',
	'gitToken' => 'fb09cc925f55371993a5dbb98afa09d03ccba33c',
	'database' => [
		'name' => 'test_runner',
		'login' => 'test_runner',
		'password' => 'xnM9q4XsQ',
	],
	'repositories' => [
		'ArtSkills/site' => [
			'codeLocation' => '/var/www/site',
			'structureFile' => '/var/www/db.artskills.gz',
			'database' => [
				'name' => 'artskills',
				'login' => 'dblogin',
				'password' => 'dbpassword',
			],
		],
	],
];