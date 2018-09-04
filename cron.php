<?php
/**
 * Created by PhpStorm.
 * User: pasikuta
 * Date: 04.09.2018
 * Time: 11:33
 */

include "vendor/autoload.php";

use Telebot\Lib\Bot\Main;
use TelegramBot\Api\Client;
use Telebot\Lib\Config\Config;

if (!isset($argv[1])) {
    die("No chat_id params");
}

if (!isset($argv[2])) {
    die("No method params");
}

$chatId = $argv[1];
//$chatId = "-1001192747562";
$testChatId = "-1001136482619";

$mainBot = new Main();
$bot = new Client(Config::get('token'));
if ($argv[2] == 'getbirthday') {
    $result = $mainBot->getNextBirthday($chatId);
    $bot->sendMessage($testChatId, $result, 'html', true);
}