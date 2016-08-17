<?php
namespace App\Test\TestCase\Suite\ConstantMockerTest;

use App\Test\TestCase\AppTestCase;
use App\Test\Suite\ConstantMocker;
use \Exception;

class ConstantMockerTest extends AppTestCase
{
    const TEST_CLASS = testConstantClass::class;
    const TEST_CLASS_CONST = 'TEST_CONSTANT';
    const TEST_GLOBAL_CONST = __NAMESPACE__ . '\SINGLE_TEST_CONST';

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
     * Мок константы в классе
     */
    public function testClassMock() {
        $mockConstant = 'qqq';
        ConstantMocker::mock(self::TEST_CLASS, self::TEST_CLASS_CONST, $mockConstant);
        $this->assertEquals($mockConstant, testConstantClass::TEST_CONSTANT);

        ConstantMocker::restore();
        $this->assertEquals(123, testConstantClass::TEST_CONSTANT);
    }

    /**
     * Мок константы вне класса
     */
    public function testSingleMock() {
        $mockConstant = 'qqq';
        ConstantMocker::mock(self::TEST_GLOBAL_CONST, '', $mockConstant);
        $this->assertEquals($mockConstant, SINGLE_TEST_CONST);

        ConstantMocker::restore();
        $this->assertEquals('666', SINGLE_TEST_CONST);
    }

    /**
     * Проверка на существование константы
     *
     * @expectedException Exception
     */
    public function testConstantExists() {
        ConstantMocker::mock('BAD_CONST', '', 'bad');
    }

    /**
     * Дважды одно и то же мокнули
     *
     * @expectedException Exception
     */
    public function testConstantDoubleMock() {
        ConstantMocker::mock('SINGLE_TEST_CONST', '', '1');
        ConstantMocker::mock('SINGLE_TEST_CONST', '', '2');
    }
}

class testConstantClass {
    const TEST_CONSTANT = 123;
}

const SINGLE_TEST_CONST = '666';
