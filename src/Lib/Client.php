<?php


namespace App\Lib;

use Cake\Core\Configure;
use Cake\Log\Log;

class Client extends \Cake\Http\Client
{
	const CUSTOM_ADAPTER_CONFIGURE_NAME = 'httpClientAdapter';

	/**
	 * Client constructor.
	 *
	 * @param array $config
	 */
	public function __construct($config = ['redirect' => 2]) {
		// возможность глобального переопределения адаптора отправки запросов
		$adapter = Configure::check(self::CUSTOM_ADAPTER_CONFIGURE_NAME);
		if ($adapter !== false) {
			$config['adapter'] = Configure::read(self::CUSTOM_ADAPTER_CONFIGURE_NAME);
		}

		parent::__construct($config);
	}
}