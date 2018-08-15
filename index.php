<?php

include "vendor/autoload.php";

$token = "<token>";

$bot = new \TelegramBot\Api\Client($token);
$body = json_decode($bot->getRawBody(), true);
//ob_flush();
//ob_start();
//print_r($body);
//file_put_contents('var_dump.txt', ob_get_flush());
// команда для start
$bot->command('start', function ($message) use ($bot) {
    $answer = 'Добро пожаловать!';
    $bot->sendMessage($message->getChat()->getId(), $answer);
});
$message = mb_strtolower($body['message']['text']);

if ($message == 'ping') {
    $bot->sendMessage($body['message']['chat']['id'], 'pong');
}

// команда для помощи
//$bot->command('help', function ($message) use ($bot) {
//    $answer = 'Что это?
//Это мой бот, тупая ты скотина)';
//    $bot->sendMessage($message->getChat()->getId(), $answer);
//});
//

$bot->run();