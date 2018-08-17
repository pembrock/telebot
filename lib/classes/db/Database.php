<?php
/**
 * Created by PhpStorm.
 * User: pasikuta
 * Date: 16.08.2018
 * Time: 14:38
 */

namespace lib\Database;


class Database
{
    private static $instances = [];

    /**
     * Конструктор Одиночки всегда должен быть скрытым, чтобы предотвратить
     * прямые вызовы строительства оператором new.
     */
    protected function __construct() { }

    /**
     * Одиночки не должны быть клонируемыми.
     */
    protected function __clone() { }

    /**
     * Одиночки не должны быть восстанавливаемыми из строк.
     */
    public function __wakeup()
    {
        throw new \Exception("Cannot unserialize a singleton.");
    }

    /**
     * Статический метод, управляющий доступом к экземпляру одиночки.
     *
     * Эта реализация позволяет вам разделить класс Одиночки на подклассы,
     * сохраняя повсюду только один экземпляр каждого подкласса.
     */
    public static function getInstance(): Singleton
    {
        $cls = get_called_class();
        if (! isset(self::$instances[$cls])) {
            self::$instances[$cls] = new static;
        }

        return self::$instances[$cls];
    }

    /**
     * Наконец, любой одиночка должен содержать некоторую бизнес-логику, которая
     * может быть выполнена на его экземпляре.
     */
    public function someBusinessLogic()
    {
        // ...
    }
}