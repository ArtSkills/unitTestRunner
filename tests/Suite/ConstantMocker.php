<?php
namespace App\Test\Suite;

use \Exception;

/**
 * Мокалка констант в классах
 */
class ConstantMocker
{
    /**
     * Список мокнутых констант
     *
     * @var array
     */
    private static $_constantList = [];

    /**
     * Заменяем значение константы
     *
     * @param string $className
     * @param string $constantName
     * @param mixed $newValue
     * @throws Exception
     */
    public static function mock($className, $constantName, $newValue) {
        $name = strlen($constantName)? $className.'::'.$constantName: $className;
        $origValue =@ constant($name);
        if ($origValue === null) {
            throw new Exception('Constant '.$name.' is not defined!');
        }
        if (isset(self::$_constantList[$name])) {
            throw new Exception('Constant '.$name.' is already mocked!');
        }

        self::$_constantList[$name] = $origValue;
        if (!runkit_constant_redefine($name, $newValue)) {
            throw new Exception("Can't redefine constant $name!");
        }
    }

    /**
     * Возвращаем все обратно
     */
    public static function restore() {
        foreach (self::$_constantList as $name => $origValue) {
            runkit_constant_redefine($name, $origValue);
        }
        self::$_constantList = [];
    }
}
