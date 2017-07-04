## PhpTestActivity
### Поля:
* int `id`
* int `php_test_id`
* float `elapsed_seconds`
* \Cake\I18n\Time `created` = 'CURRENT_TIMESTAMP'
* array `content`
### Связи:
* PhpTests `$PhpTests` PhpTests.php_test_id => PhpTestActivity.id

## PhpTests
### Поля:
* int `id`
* string `repository`
* string `ref`
* string `sha`
* string `status` = 'new'
* \Cake\I18n\Time `created` = 'CURRENT_TIMESTAMP'
* \Cake\I18n\Time `updated` = 'CURRENT_TIMESTAMP'
* string `server_ip` = NULL
### Связи:
* PhpTestActivity[] `$PhpTestActivity` PhpTestActivity.php_test_id => PhpTests.id

