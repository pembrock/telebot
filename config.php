<?php
/**
 * Created by PhpStorm.
 * User: pasikuta
 * Date: 16.08.2018
 * Time: 12:37
 */

class Config
{
    static $_path = 'conf/config.json';

    static function get($key)
    {
        $config = json_decode(file_get_contents(self::$_path), true);

        if (isset($config[$key])) {
            return $config[$key];
        }
    }
}