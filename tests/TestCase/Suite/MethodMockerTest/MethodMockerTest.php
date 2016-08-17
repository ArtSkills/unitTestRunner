<?php
namespace App\Test\TestCase\Suite\MethodMockerTest;

use App\Test\TestCase\AppTestCase;
use App\Test\Suite\MethodMocker;
use \PHPUnit_Framework_ExpectationFailedException;
use \Exception;

class MethodMockerTest extends AppTestCase
{
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
        MethodMocker::restore();
    }

    /**
     * Тест без аргументов
     */
    public function testNoArgs() {
        $mockResult = 'mock';
        $mock = MethodMocker::mock(testMockSecondClass::class, 'methodNoArgs');
        $mock->singleCall()->willReturnValue($mockResult);

        $testClass = new testMockSecondClass();
        $this->assertEquals($mockResult, $testClass->methodNoArgs());
        $this->assertEquals(1, $mock->getCallCount());

        MethodMocker::restore();
        $this->assertEquals('original', $testClass->methodNoArgs());
    }

    /**
     * Тест с аргументами
     */
    public function testArgs() {
        $mock = MethodMocker::mock(testMockSecondClass::class, 'methodArgs');
        $mock->anyCall()
            ->expectArgs(1, 2)
            ->willReturnAction(function ($args) {
                return 'mocked: ' . $args[0] . ' ' . $args[1];
            });

        $testClass = new testMockSecondClass();
        $this->assertEquals('mocked: 1 2', $testClass->methodArgs(1, 2));

        $testClass->methodArgs(1, 2); // еще раз проверка на anycall
        $this->assertEquals(2, $mock->getCallCount());

        MethodMocker::restore();
        $this->assertEquals('1 2', $testClass->methodArgs(1, 2));
    }

    /**
     * Единичный вызов мока
     *
     * @expectedException PHPUnit_Framework_ExpectationFailedException
     */
    public function testSingleCallCheck() {
        $mock = MethodMocker::mock(testMockSecondClass::class, 'methodNoArgs');
        $mock->singleCall();

        $testClass = new testMockSecondClass();
        $testClass->methodNoArgs();
        $testClass->methodNoArgs();
    }

    /**
     * Проверка аргументов
     *
     * @expectedException PHPUnit_Framework_ExpectationFailedException
     */
    public function testArgsCheck() {
        $mock = MethodMocker::mock(testMockSecondClass::class, 'methodArgs');
        $mock->anyCall()->expectArgs(1, 2);

        $testClass = new testMockSecondClass();
        $testClass->methodArgs(3, 4);
    }

    /**
     * Проверка на вызов с явным заданием отсутствия аргументов
     *
     * @expectedException PHPUnit_Framework_ExpectationFailedException
     */
    public function testEmptyArgsBadCheck() {
        $mock = MethodMocker::mock(testMockSecondClass::class, 'methodArgs');
        $mock->anyCall()->expectArgs(false);

        $testClass = new testMockSecondClass();
        $testClass->methodArgs(3, 4);
    }

    /**
     * Вызов с заданием отсутствия праметров
     */
    public function testEmptyArgsGoodCheck() {
        $mock = MethodMocker::mock(testMockSecondClass::class, 'methodNoArgs');
        $mock->anyCall()->expectArgs(false);

        $testClass = new testMockSecondClass();
        $this->assertNull($testClass->methodNoArgs());
    }

    /**
     * Дважды замокали один метов
     *
     * @expectedException Exception
     */
    public function testDuplicateMock() {
        MethodMocker::mock(testMockSecondClass::class, 'methodNoArgs', 'return 1;');
        MethodMocker::mock(testMockSecondClass::class, 'methodNoArgs', 'return 1;');
    }
    
    /**
     * Вызвали несуществующий запмоканый метод
     *
     * @expectedException Exception
     */
    public function testNotExistsMockCall() {
        MethodMocker::doAction('notExists', []);
    }

    /**
     * Сниф метода без аргументов
     */
    public function testSniffNoActionNoArgs() {
        $mock = MethodMocker::sniff(testMockSecondClass::class, 'methodNoArgs');
        $testClass = new testMockSecondClass();
        $this->assertEquals('original', $testClass->methodNoArgs());
        $this->assertEquals(1, $mock->getCallCount());
    }

    /**
     * Сниф статического метода с аргументами
     */
    public function testSniffWithArgsAndAction() {
        $origResult = 'static 1 2';
        $testSuite = $this;
        MethodMocker::sniff(testMockSecondClass::class, 'staticMethodArgs', function (
            $args, $orgResult) use ($testSuite, $origResult) {
            $this->assertEquals(1, $args[0]);
            $this->assertEquals(2, $args[1]);
            $this->assertEquals($origResult, $orgResult);
        });
        $this->assertEquals($origResult, testMockSecondClass::staticMethodArgs(1, 2));
    }

    /**
     * Делаем приватную статичную функцию доступной
     */
    public function testCallPrivateOrProtectedMethodForPrivate() {
        $this->assertEquals('private', MethodMocker::callPrivateOrProtectedMethod(testMockSecondClass::class, 'privateMethod'));
    }

    /**
     * Делаем доступным protected метод
     */
    public function testCallPrivateOrProtectedMethodForProtected() {
        $testClass = new testMockSecondClass();
        $this->assertEquals('protected 1', MethodMocker::callPrivateOrProtectedMethod(testMockSecondClass::class, 'protectedMethod', $testClass, [1]));
    }

    /**
     * Несуществующий класс
     *
     * @expectedException Exception
     */
    public function testCallPrivateOrProtectedMethodBadClass() {
        MethodMocker::callPrivateOrProtectedMethod('BadClass', 'BlaBla');
    }

    /**
     * Несуществующий метод
     *
     * @expectedException Exception
     */
    public function testCallPrivateOrProtectedMethodBadMethodName() {
        MethodMocker::callPrivateOrProtectedMethod(testMockSecondClass::class, 'BlaBla');
    }

    /**
     * Несуществующий метод
     *
     * @expectedException Exception
     */
    public function testCallPrivateOrProtectedMethodBadMethodType() {
        MethodMocker::callPrivateOrProtectedMethod(testMockSecondClass::class, 'methodNoArgs');
    }
}

/**
 * Тестовый класс
 */
class testMockSecondClass
{
    /**
     * Тестовый метод
     *
     * @return string
     */
    public function methodNoArgs() {
        return 'original';
    }

    /**
     * Тестовый метод
     *
     * @param mixed $first
     * @param mixed $second
     * @return string
     */
    public function methodArgs($first, $second) {
        return $first . ' ' . $second;
    }

    /**
     * Тестовый метод
     *
     * @param mixed $first
     * @param mixed $second
     * @return string
     */
    public static function staticMethodArgs($first, $second) {
        return 'static ' . $first . ' ' . $second;
    }

    /**
     * Тестовый метод
     *
     * @return string
     */
    private static function privateMethod() {
        return 'private';
    }

    /**
     * Тестовый метод
     *
     * @param string $arg
     * @return string
     */
    protected function protectedMethod($arg) {
        return 'protected ' . $arg;
    }
}


