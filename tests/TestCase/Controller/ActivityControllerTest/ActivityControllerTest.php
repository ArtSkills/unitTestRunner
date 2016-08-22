<?php

namespace App\Test\TestCase\Controller\ActivityControllerTest;

use App\Controller\ActivityController;
use App\Test\TestCase\Controller\AppControllerTestCase;

class ActivityControllerTest extends AppControllerTestCase
{
	/**
	 * @inheritdoc
	 */
	public $fixtures = ['app.php_tests', 'app.php_test_activity'];

	/**
	 * Некорректная запись в логе
	 */
	public function testViewBadId() {
		$this->get('/tests/123/activity/456');
		$this->assertJsonErrorEquals(ActivityController::MSG_NOT_FOUND, $this->_response->body());
	}

	/**
	 * Отображение лог записи
	 */
	public function testView() {
		$testId = 7;
		$activityId = 2;

		$this->get('/tests/' . $testId . '/activity/' . $activityId);
		$viewActivity = $this->viewVariable('activity');
		self::assertEquals($activityId, $viewActivity->id);
		self::assertEquals($testId, $viewActivity->php_test->id);
	}
}