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
use Telebot\Lib\Bot\Users;
use Telebot\Lib\Bot\Common;

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
    $bot->sendMessage($chatId, $result, 'html', true);
}

if ($argv[2] == 'checkbirthday') {
    $users = new Users();
    $result = $users->checkTodayBirthday($chatId);
    if ($result !== false) {
        $bot->sendMessage($chatId, $result, 'html', true);
    }
}

if ($argv[2] == 'updatestatus') {
    $users = new Users();
    $result = $users->updateUsersStatus($chatId, $bot);
    if ($result !== false && !empty($result)) {
        $bot->sendMessage($chatId, "Нас покинули:\n{$result}", 'html', true);
    }
}

if ($argv[2] == 'checkday') {
    $bot->sendMessage($chatId, Common::checkDay(), 'html', true);
}