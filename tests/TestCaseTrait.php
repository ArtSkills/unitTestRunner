<?php

namespace App\Test;

use ArtSkills\TestSuite\HttpClientMock\HttpClientAdapter;
use Cake\I18n\Time;

/**
 * Тестовое окружение
 *
 * @package App\Test
 */
trait TestCaseTrait
{
	use \ArtSkills\TestSuite\TestCaseTrait;

	/**
	 * Задать тестовое время
	 * Чтоб можно было передавать строку
	 *
	 * @param Time|string $time
	 */
	protected function _setTestNow($time) {
		if (!($time instanceof Time)) {
			$time = new Time($time);
		}
		Time::setTestNow($time);
	}
}
