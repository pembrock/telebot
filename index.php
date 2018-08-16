<?php

include "vendor/autoload.php";
include "config.php";

$bot = new \TelegramBot\Api\Client(Config::get('token'));
$body = json_decode($bot->getRawBody(), true);

$bot->command('start', function ($message) use ($bot) {
    $answer = 'Добро пожаловать!';
    $bot->sendMessage($message->getChat()->getId(), $answer);
});
$message = mb_strtolower($body['message']['text']);

if ($message == 'ping') {
    $bot->sendMessage($body['message']['chat']['id'], "Адин\nДва", 'html', false);
}

if ($message == 'contact') {
    $bot->sendContact($body['message']['chat']['id'], '8(977)777-66-55', 'Borak Obama');
}

$bot->run();