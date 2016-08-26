## Установка
```php
php install.php
```

## Доп. параметры конфигурации
```php
    'serverUrl' => 'Текущий урл сервера',
	'gitToken' => 'Токен на чтение github',
	'gitSecret' => 'секретный ключ'

	'repositories' => [
		'имя ветки в GitHub вместе со владельцем' => [
			'repositoryLocation' => 'абсолютный путь для корня кода',
			'deployKey' => 'ключ в GitHub, только чтение',
			'structureFile' => 'дамп структуры тестовой база с исторей phinx миграций (phinxlog)', // mysqldump --opt -d -p artskills > db.artskills.sql && mysqldump --opt -d -p artskills phinxlog >> db.artskills.sql
			'phpUnitCommand' => 'команда запуска phpUnit относительно папки repositoryLocation',
			'phinxCommand' => 'команда запуска phinx относительно папки repositoryLocation',
			'database' => [
				'host' => 'хост',
				'name' => 'база тестируемого репозитория со структурой',
				'login' => 'логин',
				'password' => 'пароль',
				'port' => 'порт',
			],
		],
		...
	],
```

## Примерение
В GitHub выбираем компанию далее Settings -> Webhooks -> Add webhook:
* Payload URL: [serverUrl]/tests
* Content type: application/x-www-form-urlencoded
* Secret: [gitSecret]
* Which events would you like to trigger this webhook: выбираем Let me select individual events. -> снимаем Push и выбираем Pull request
 
 