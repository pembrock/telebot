<?php
/**
 * Created by PhpStorm.
 * User: pembr
 * Date: 19.08.2018
 * Time: 23:25
 */

namespace Telebot\Lib\Bot;

use DateTime;
use PDO;
use Telebot\Lib\Config\Config;
use Telebot\Lib\DB\Database;
use TelegramBot\Api\Client;

class Main
{

    private $db = null;

    protected $bot;
    protected $body;

    static protected $_adminStatus = ['creator', 'administrator'];
    static protected $_words = [
        'Ты - принц, Экли, детка', 'Ты - ужас, летящий на крыльях ночи', 'Ты - чмо', 'Ты - инженер на сотню рублей', 'Ты меня бесишь', 'Ты задрот и дрищ. Ты даже кота отпиздить не сможешь', 'Ты - принцесса', 'Ты старый', 'Ты жирный', 'Ты большой молодец'
    ];
    static protected $_awesome = [
        'И ты это все сам сделал! Какой ты молодец!', 'И пенис у тебя огромный', 'Как будто были сомнения', 'Но не так круто, как крут ты', 'Тупо', 'Как задница вон той чики', 'Можно и отдохнуть', 'Это был тяжелый год...'
    ];

    static protected $_commands = [
        'кто я' => 'whoAmI',
        'кто я?' => 'whoAmI',
        'кто свалил' => 'whoLeft',
        'кто явился' => 'whoJoin',
        'админы' => 'whoAdmin'
    ];

    public function __construct()
    {
        $this->db = Database::getInstance();
        $this->bot = new Client(Config::get('token'));
        $this->body = json_decode($this->bot->getRawBody(), true);
    }

    public function index()
    {
        $bot = $this->bot;
        $body = $this->body;

        $this->checkUser($body['message']);

        $bot->command('start', function ($message) use ($bot) {
            $answer = 'Добро пожаловать!';
            $bot->sendMessage($message->getChat()->getId(), $answer);
        });

        //добавлен новый юзер
        if (isset($body['message']['new_chat_member'])) {
            $this->userJoin($body['message']['new_chat_member']['id'], $body['message']['chat']['id'], $body['message']['new_chat_member']['username']);
            $user = "@" . $body['message']['new_chat_member']['username'];
            $bot->sendMessage($body['message']['chat']['id'], 'Новый пользователь: ' . $user);
        }

        //удален юзер
        if (isset($body['message']['left_chat_member'])) {
            $this->userLeft($body['message']['left_chat_member']['id'], $body['message']['chat']['id'], $body['message']['left_chat_member']['username']);
            $user = "@" . $body['message']['left_chat_member']['username'];
            $bot->sendMessage($body['message']['chat']['id'], 'Удален пользователь: ' . $user);
        }

//        ob_flush();
//        ob_start();
//        print_r($body);
//        file_put_contents('var_dump.txt', ob_get_flush());

        $message = mb_strtolower($body['message']['text']);

        if (isset(self::$_commands[$message])) {
            $text = $this->{self::$_commands[$message]}($body['message']['chat']['id']);
            $bot->sendMessage($body['message']['chat']['id'], $text, 'html', false);
        }

        if ($message == 'ping') {
            $bot->sendMessage($body['message']['chat']['id'], "pong", 'html', false);
//    $bot->sendMessage("@stop_tc3o_nagging", "test");
        }

        if ($message == 'contact') {
            $bot->sendContact($body['message']['chat']['id'], '8(977)777-66-55', 'Borak Obama');
        }

        if ($message == 'круто') {
            $bot->sendMessage($body['message']['chat']['id'], self::$_awesome[array_rand(self::$_awesome, 1)]);
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

    /**
     * Возвращает массив с username администраторов чата
     * @param $bot
     * @param $chatId
     * @return array
     */
    private function getAdministrators($chatId)
    {
        $admins = [];
        $administrators = $this->bot->getChatAdministrators($chatId);
        foreach($administrators as $admin) {
            if (in_array($admin->getStatus(), self::$_adminStatus)) {
                if (!$admin->getUser()->isBot()) {
                    $admins[] = $admin->getUser()->getUsername();
                }
            }
        }

        return $admins;
    }

    /**
     * Выводит список админов чата
     * @param $chatId
     * @return string
     */
    public function whoAdmin($chatId)
    {
        $admins = $this->getAdministrators($chatId);
        $text = "<b>Список админов:</b>\n\n";
        $index = 1;
        foreach ($admins as  $admin) {
            $text .= "{$index}. @{$admin}\n";
            $index++;
        }

        return $text;

    }

    public function whoAmI()
    {
        return self::$_words[array_rand(self::$_words, 1)];
    }

    /**
     * Проверяем пользователя на наличие в базе, если нет, то добавляем
     * @param $body
     */
    private function checkUser($body)
    {
        $userId = $body['from']['id'];
        $username = $body['from']['username'];
        $userFirstName = $body['from']['first_name'];
        $languageCode = $body['from']['language_code'];
        $chatId = $body['chat']['id'];

        $query = $this->db->prepare( "SELECT user_id
			 FROM users
			 WHERE user_id = :user_id AND chat_id = :chat_id" );
        $query->execute(array('user_id' => $userId, 'chat_id' => $chatId));
        if( $query->rowCount() <= 0 ) {
            $statement = $this->db->prepare("INSERT INTO users (user_id, chat_id, username, first_name, language_code) VALUES (:user_id, :chat_id, :username, :first_name, :language_code)");
            $statement->execute(array(
                'user_id' => $userId,
                'chat_id' => $chatId,
                'username' => $username,
                'first_name' => $userFirstName,
                'language_code' => $languageCode
            ));
        }
    }

    /**
     * Если человек выходит из чата, то добавляем его в базу. При этом, если он уже выходил из чата до этого,
     * просто обновляем дату
     * @param $userId
     * @param $chatId
     * @param $username
     */
    public function userLeft($userId, $chatId, $username)
    {
        $date = new DateTime();
        $query = $this->db->prepare("SELECT * FROM charts WHERE user_id = :user_id AND chat_id = :chat_id AND action_type = :action_type");
        $query->execute(array('user_id' => $userId, 'chat_id' => $chatId, 'action_type' => 'left'));
        if( $query->rowCount() > 0 ) {
            $statement = $this->db->prepare("UPDATE charts SET last_update = :last_update WHERE chat_id = :chat_id AND user_id = :user_id AND action_type = :action_type");
            $statement->execute(array(
                'chat_id' => $chatId,
                'user_id' => $userId,
                'action_type' => 'left',
                'last_update' => $date->format('Y-m-d H:i:s')
            ));
        } else {
            $statement = $this->db->prepare("INSERT INTO charts (chat_id, user_id, username, action_type, last_update) VALUES (:chat_id, :user_id, :username, :action_type, :last_update)");
            $statement->execute(array(
                'chat_id' => $chatId,
                'user_id' => $userId,
                'username' => $username,
                'action_type' => 'left',
                'last_update' => $date->format('Y-m-d H:i:s')
            ));
        }
    }

    /**
     * Если человек присоединяется к чату, то добавляем его в базу. При этом, если он уже был в этом чате, но выходил,
     * просто обновляем дату
     * @param $userId
     * @param $chatId
     * @param $username
     */
    public function userJoin($userId, $chatId, $username)
    {
        $date = new DateTime();
        $query = $this->db->prepare("SELECT * FROM charts WHERE user_id = :user_id AND chat_id = :chat_id AND action_type = :action_type");
        $query->execute(array('user_id' => $userId, 'chat_id' => $chatId, 'action_type' => 'join'));
        if( $query->rowCount() > 0 ) {
            $statement = $this->db->prepare("UPDATE charts SET last_update = :last_update WHERE chat_id = :chat_id AND user_id = :user_id AND action_type = :action_type");
            $statement->execute(array(
                'chat_id' => $chatId,
                'user_id' => $userId,
                'action_type' => 'join',
                'last_update' => $date->format('Y-m-d H:i:s')
            ));
        } else {
            $statement = $this->db->prepare("INSERT INTO charts (chat_id, user_id, username, action_type, last_update) VALUES (:chat_id, :user_id, :username, :action_type, :last_update)");
            $statement->execute(array(
                'chat_id' => $chatId,
                'user_id' => $userId,
                'username' => $username,
                'action_type' => 'join',
                'last_update' => $date->format('Y-m-d H:i:s')
            ));
        }
    }

    /**
     * Выводит список последних 10 человек зашедших в чат
     * @param $chatId
     * @return string
     */
    public function whoLeft($chatId)
    {
        $text = "<b>Список последних ливнувших:</b>\n\n";
        $query = $this->db->prepare( "SELECT username, last_update
			 FROM charts
			 WHERE action_type = :action_type AND chat_id = :chat_id ORDER BY last_update DESC LIMIT 10" );
        $query->execute(array('action_type' => 'left', 'chat_id' => $chatId));
        if( $query->rowCount() > 0 ) {
            $rows = $query->fetchAll(PDO::FETCH_ASSOC);
            $index = 1;
            foreach ($rows as  $row) {
                $text .= "{$index}. @{$row['username']} - {$row['last_update']}\n";
                $index++;
            }
        } else {
            $text .= "Пока никто не ливнул.";
        }

        return $text;
    }

    /**
     * Выводит список последних 10 человек покинувших чат
     * @param $chatId
     * @return string
     */
    public function whoJoin($chatId)
    {
        $text = "<b>Список последних прильнувших:</b>\n\n";
        $query = $this->db->prepare( "SELECT username, last_update
			 FROM charts
			 WHERE action_type = :action_type AND chat_id = :chat_id ORDER BY last_update DESC LIMIT 10" );
        $query->execute(array('action_type' => 'join', 'chat_id' => $chatId));
        if( $query->rowCount() > 0 ) {
            $rows = $query->fetchAll(PDO::FETCH_ASSOC);
            $index = 1;
            foreach ($rows as  $row) {
                $text .= "{$index}. @{$row['username']} - {$row['last_update']}\n";
                $index++;
            }
        } else {
            $text .= "Пока никто не пришел.";
        }

        return $text;
    }
}