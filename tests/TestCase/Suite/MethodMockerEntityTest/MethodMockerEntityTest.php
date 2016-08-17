<?php
namespace App\Test\TestCase\Suite\MethodMockerEntityTest;

use App\Test\TestCase\AppTestCase;
use App\Test\Suite\MethodMockerEntity;
use \PHPUnit_Framework_ExpectationFailedException;
use \Exception;

class MethodMockerEntityTest extends AppTestCase
{
	/**
	 * @inheritdoc
	 */
	public $fixtures = [];

	/**
	 * @inheritdoc
	 */
	public function setUp() {
		// пустой специально
	}

	/**
	 * @inheritdoc
	 */
	public function tearDown() {
		// пустой специально
	}

	/**
	 * Сатический метод с переопределенным событием
	 */
	public function testStaticAction() {
		$mockResult = "mock";
		$mock = new MethodMockerEntity('mockid', testMockClass::class, 'staticFunc', false, 'return "'.$mockResult.'";');
		$this->assertEquals($mockResult, testMockClass::staticFunc());
		unset($mock);

		$this->assertEquals('original', testMockClass::staticFunc());
	}

	/**
	 * Публичный метод с переопределенным событием
	 */
	public function testPublicAction() {
		$mockResult = "mock";
		$mock = new MethodMockerEntity('mockid', testMockClass::class, 'publicFunc', false, 'return "'.$mockResult.'";');
		$testClass = new testMockClass();
		$this->assertEquals($mockResult, $testClass->publicFunc());
		unset($mock);

		$testClass = new testMockClass();
		$this->assertEquals('original', $testClass->publicFunc());
	}

	/**
	 * Приватный метод
	 */
	public function testPrivateAction() {
		$mockResult = "mock";
		$mock = new MethodMockerEntity('mockid', testMockClass::class, '_privateFunc', false, 'return "'.$mockResult.'";');
		$testClass = new testMockClass();
		$this->assertEquals($mockResult, $testClass->callPrivate());
		unset($mock);

		$testClass = new testMockClass();
		$this->assertEquals('original', $testClass->callPrivate());
	}

	/**
	 * Приватный метод
	 */
	public function testPrivateStaticAction() {
		$mockResult = "mock";
		$mock = new MethodMockerEntity('mockid', testMockClass::class, '_privateStaticFunc', false, 'return "'.$mockResult.'";');
		$this->assertEquals($mockResult, testMockClass::callPrivateStatic());
		unset($mock);

		$this->assertEquals('original', testMockClass::callPrivateStatic());
	}

	/**
	 * Приватный метод
	 */
	public function testProtectedAction() {
		$mockResult = "mock";
		$mock = new MethodMockerEntity('mockid', testMockClass::class, '_protectedFunc', false, 'return "'.$mockResult.'";');
		$testClass = new testMockClass();
		$this->assertEquals($mockResult, $testClass->callProtected());
		unset($mock, $testClass);

	}

	/**
	 * Мок на несуществующий класс
	 *
	 * @expectedException Exception
	 */
	public function testMockBadClass() {
		new MethodMockerEntity('mockid', 'badClass', '_protectedFunc');
	}

	/**
	 * Мок на несуществующий метод
	 *
	 * @expectedException Exception
	 */
	public function testMockBadMethod() {
		new MethodMockerEntity('mockid', testMockClass::class, 'badMethod');
	}

	/**
	 * Вызывали ли мок хотя бы раз
	 *
	 * @expectedException PHPUnit_Framework_ExpectationFailedException
	 */
	public function testMockCallCheck() {
		new MethodMockerEntity('mockid', testMockClass::class, '_protectedFunc');
	}

	/**
	 * Мок вернули, а его конфигурируют
	 *
	 * @expectedException Exception
	 */
	public function testRestoredSingleCall() {
		$mockResult = "mock";
		$mock = new MethodMockerEntity('mockid', testMockClass::class, 'staticFunc', false, 'return "'.$mockResult.'";');
		$mock->singleCall();
		testMockClass::staticFunc();

		$mock->restore();
		$mock->singleCall();
	}

	/**
	 * Мок вернули, а его конфигурируют
	 *
	 * @expectedException Exception
	 */
	public function testRestoredAnyCall() {
		$mockResult = "mock";
		$mock = new MethodMockerEntity('mockid', testMockClass::class, 'staticFunc', false, 'return "'.$mockResult.'";');
		$mock->anyCall();
		testMockClass::staticFunc();

		$mock->restore();
		$mock->anyCall();
	}

	/**
	 * Мок вернули, а его конфигурируют
	 *
	 * @expectedException Exception
	 */
	public function testRestoredExpected() {
		$mockResult = "mock";
		$mock = new MethodMockerEntity('mockid', testMockClass::class, 'staticFunc', false, 'return "'.$mockResult.'";');
		$mock->expectArgs(false);
		testMockClass::staticFunc();

		$mock->restore();
		$mock->expectArgs(true);
	}

	/**
	 * Мок вернули, а его конфигурируют
	 *
	 * @expectedException Exception
	 */
	public function testRestoredWillReturnValue() {
		$mockResult = "mock";
		$mock = new MethodMockerEntity('mockid', testMockClass::class, 'staticFunc', false, 'return "'.$mockResult.'";');
		$mock->willReturnValue(true);
		testMockClass::staticFunc();

		$mock->restore();
		$mock->willReturnValue(true);
	}

	/**
	 * Мок вернули, а его конфигурируют
	 *
	 * @expectedException Exception
	 */
	public function testRestoredWillReturnAction() {
		$mockResult = "mock";
		$mock = new MethodMockerEntity('mockid', testMockClass::class, 'staticFunc', false, 'return "'.$mockResult.'";');
		$mock->willReturnAction(function($args){
			return $args;
		});
		testMockClass::staticFunc();

		$mock->restore();
		$mock->willReturnAction(function($args){
			return $args;
		});
	}

	/**
	 * Мок вернули, а его вызывают
	 *
	 * @expectedException Exception
	 */
	public function testRestoredDoAction() {
		$mockResult = "mock";
		$mock = new MethodMockerEntity('mockid', testMockClass::class, 'staticFunc', false, 'return "'.$mockResult.'";');
		$mock->doAction([]);

		$mock->restore();
		$mock->doAction([]);
	}

	/**
	 * Метод без аргументов
	 *
	 * @expectedException Exception
	 */
	public function testExpectedArgs() {
		$mockResult = "mock";
		$mock = new MethodMockerEntity('mockid', testMockClass::class, 'staticFunc', false, 'return "'.$mockResult.'";');
		$mock->expectArgs();
	}
}

/**
 * Тестовый класс
 */
class testMockClass
{
	/**
	 * Тестовый метод
	 *
	 * @return string
	 */
	public static function staticFunc() {
		return 'original';
	}

	/**
	 * Тестовый метод
	 *
	 * @return string
	 */
	public function publicFunc() {
		return 'original';
	}

	/**
	 * Тестовый метод
	 *
	 * @return string
	 */
	private function _privateFunc() {
		return 'original';
	}

	/**
	 * Тестовый метод
	 *
	 * @return string
	 */
	private static function _privateStaticFunc() {
		return 'original';
	}

	/**
	 * Тестовый метод
	 *
	 * @return string
	 */
	protected function _protectedFunc() {
		return 'original';
	}

	/**
	 * Вызов для проверки _protectedFunc
	 *
	 * @return string
	 */
	public function callProtected() {
		return $this->_protectedFunc();
	}

	/**
	 * Вызов для проверки _privateFunc
	 *
	 * @return string
	 */
	public function callPrivate() {
		return $this->_privateFunc();
	}

	/**
	 * Вызов для проверки _privateStaticFunc
	 *
	 * @return string
	 */
	public static function callPrivateStatic() {
		return self::_privateStaticFunc();
	}
}
