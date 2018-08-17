<?php

include "vendor/autoload.php";
include "config.php";

$bot = new \TelegramBot\Api\Client(Config::get('token'));
$body = json_decode($bot->getRawBody(), true);

//ob_flush();
//ob_start();
//print_r($body);
//file_put_contents('var_dump.txt', ob_get_flush());

$bot->command('start', function ($message) use ($bot) {
    $answer = 'Добро пожаловать!';
    $bot->sendMessage($message->getChat()->getId(), $answer);
});

//добавлен новый юзер
if (isset($body['message']['new_chat_member'])) {
    $user = "@" . $body['message']['new_chat_member']['username'];
    $bot->sendMessage($body['message']['chat']['id'], 'Новый пользователь: ' . $user);
}

//удален юзер
if (isset($body['message']['left_chat_member'])) {
    $user = "@" . $body['message']['left_chat_member']['username'];
    $bot->sendMessage($body['message']['chat']['id'], 'Удален пользователь: ' . $user);
}

$message = mb_strtolower($body['message']['text']);

if ($message == 'ping') {
    $bot->sendMessage($body['message']['chat']['id'], "Адин\nДва", 'html', false);
//    $bot->sendMessage("@stop_tc3o_nagging", "test");
}

if ($message == 'contact') {
    $bot->sendContact($body['message']['chat']['id'], '8(977)777-66-55', 'Borak Obama');
}

if ($message == 'круто') {
    $bot->sendMessage($body['message']['chat']['id'], 'И ты это все сам сделал! Какой ты молодец!');
}
//$update = $bot->getUpdates();
//ob_flush();
//ob_start();
//print_r($update->update->message);
//file_put_contents('var_dump.txt', ob_get_flush());

$bot->run();