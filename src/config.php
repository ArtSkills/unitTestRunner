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
			'repositoryLocation' => '/var/www/site',
			'deployKey' => '/var/www/keys/site', // ключ в GitHub, только чтение
			'structureFile' => '/var/www/db.artskills.sql', // mysqldump --opt -d -p artskills > db.artskills.sql && mysqldump --opt -d -p artskills phinxlog >> db.artskills.sql
			'phpUnitCommand' => 'app/Vendor/phpunit.phar --bootstrap app/webroot/test.php --no-configuration app/Test/Case', // относительно корня папки
			'phinxCommand' => 'vendor/bin/phinx migrate',
			'database' => [
				'name' => 'artskills_structure',
				'login' => 'artskills',
				'password' => '6Edk7EhZc',
			],
		],
	],
];