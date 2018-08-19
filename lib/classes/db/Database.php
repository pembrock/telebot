<?php
/**
 * Created by PhpStorm.
 * User: pasikuta
 * Date: 16.08.2018
 * Time: 14:38
 */

namespace Telebot\Lib\DB;

use PDO;
use PDOException;
use Telebot\Lib\Config\Config;

class Database
{
    protected static $instance;


    protected function __construct() {}

    protected function __clone() { }

    public static function getInstance() {
        if(empty(self::$instance)) {
            $db_info = array(
                "db_host" => Config::get('db_host'),
                "db_user" => Config::get('db_user'),
                "db_pass" => Config::get('db_pass'),
                "db_name" => Config::get('db_name'),
                "db_charset" => "UTF-8");
            try {
                self::$instance = new PDO("mysql:host=".$db_info['db_host'].';dbname='.$db_info['db_name'], $db_info['db_user'], $db_info['db_pass']);
                self::$instance->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_SILENT);
                self::$instance->query('SET NAMES utf8');
                self::$instance->query('SET CHARACTER SET utf8');
            } catch(PDOException $error) {
                echo $error->getMessage();
            }
        }
        return self::$instance;
    }
}