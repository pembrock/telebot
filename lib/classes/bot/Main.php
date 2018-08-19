<?php
/**
 * Created by PhpStorm.
 * User: pembr
 * Date: 19.08.2018
 * Time: 23:25
 */

namespace Telebot\Lib\Bot;

use Telebot\Lib\Config\Config;
use Telebot\Lib\DB\Database;
use TelegramBot\Api\Client;

class Main
{

    private $db = null;

    protected $bot;
    protected $body;

    static protected $_commands = [

    ];

    public function __construct()
    {
        $this->db = Database::getInstance();
        $this->bot = new Client(Config::get('token'));
        $this->body = json_decode($this->bot->getRawBody(), true);
    }

    public function index()
    {
//        $bot = new Client(Config::get('token'));
//        $body = json_decode($bot->getRawBody(), true);

        //ob_flush();
        //ob_start();
        //print_r(Config::get('123'));
        //file_put_contents('body.txt', ob_get_flush());

        $bot = $this->bot;
        $body = $this->body;

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
            $bot->sendMessage($body['message']['chat']['id'], "pong", 'html', false);
//    $bot->sendMessage("@stop_tc3o_nagging", "test");

            $this->db->exec('INSERT INTO test SET value="aaa"');
        }

        if ($message == 'contact') {
            $bot->sendContact($body['message']['chat']['id'], '8(977)777-66-55', 'Borak Obama');
        }

        if ($message == 'круто') {
            $bot->sendMessage($body['message']['chat']['id'], 'И ты это все сам сделал! Какой ты молодец!');
        }

        if ($message == 'сука') {
            $bot->sendMessage($body['message']['chat']['id'], 'Запрягай коней!');
        }
        //$update = $bot->getUpdates();
        //ob_flush();
        //ob_start();
        //print_r($update->update->message);
        //file_put_contents('var_dump.txt', ob_get_flush());

        $bot->run();
    }
}