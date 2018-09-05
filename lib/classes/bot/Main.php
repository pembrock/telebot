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
use Wkhooy\ObsceneCensorRus;
use Telebot\Lib\Bot\Horoscope;

class Main extends Bot
{
    private $db = null;

    private $botCreator;
    protected $bot;
    protected $body;

    public function __construct()
    {
        $this->db = Database::getInstance();
        $this->bot = new Client(Config::get('token'));
        $this->body = json_decode($this->bot->getRawBody(), true);
        $this->botCreator = Config::get('bot_creator');
    }

    public function test()
    {
        $bot = $this->bot;
        $body = $this->body;

        if (isset($body['message']['chat']['id']) && !empty($body['message']['chat']['id'])) {
            $message = mb_strtolower($body['message']['text']);
            if ($message == 'test') {
                $bot->sendMessage($body['message']['chat']['id'], 'test', 'html', true, $body['message']['message_id']);
            }

            $bot->run();
        }
    }

    public function index()
    {
        $bot = $this->bot;
        $body = $this->body;

//        $chatId = $body['message']['chat']['id'];

//            ob_flush();
//            ob_start();
//            print_r($body);
//            file_put_contents('reply_dump_test.txt', ob_get_flush(), FILE_APPEND);

//        if ($body['message']['chat']['id'] == '-1001334371435') {
//            ob_flush();
//            ob_start();
//            print_r($body);
//            file_put_contents('reply_dump.txt', ob_get_flush(), FILE_APPEND);
//        }

//        if ($this->bot->getChatMembersCount($chatId) != Chat::getCount($chatId)) {
//            $oldCount = Chat::getCount($chatId);
//            $newCount = $this->bot->getChatMembersCount($chatId);
//            $this->updateChat($chatId, true);
//            if ($oldCount > $newCount) {
//                $bot->sendMessage($body['message']['chat']['id'], "<b>ВНИМАНИЕ! ОБНАРУЖЕНО ЛИВАНИЕ!</b>\nКогда то нас было <b>{$oldCount}</b>, а теперь нас <b>{$newCount}</b>", 'html');
//                $bot->sendMessage($body['message']['chat']['id'], "Давайте попросим Дашу следопыта найти ливнувшего ублюдка!", 'html');
//            }
//        }
//
//        $this->updateChat($chatId, false);

        $this->checkUser($body['message']);

//        $this->updateUserName($body['message']['chat']['id']);

        $bot->command('start', function ($message) use ($bot) {
            $answer = 'Добро пожаловать!';
            $bot->sendMessage($message->getChat()->getId(), $answer);
        });

        //добавлен новый юзер
        if (isset($body['message']['new_chat_member'])) {
            $this->userJoin($body['message']['new_chat_member']['id'], $body['message']['chat']['id'], $body['message']['new_chat_member']['username']);
            $user = "@" . $body['message']['new_chat_member']['username'];
            $bot->sendMessage($body['message']['chat']['id'], $user . ', привет. Классная ава. Пососемся?');
        }

        //удален юзер
        if (isset($body['message']['left_chat_member'])) {
            $this->userLeft($body['message']['left_chat_member']['id'], $body['message']['chat']['id'], $body['message']['left_chat_member']['username']);
            $user = "@" . $body['message']['left_chat_member']['username'];
            $bot->sendMessage($body['message']['chat']['id'], 'Кто же нас покинул? Позор тебе, ' . $user);
        }

        if (isset($body['message']['sticker']) && $body['message']['sticker']['file_id'] == $this->congratsSticker) {
            $bot->sendMessage($body['message']['chat']['id'], self::$_congrats[array_rand(self::$_congrats, 1)] . '!', 'html', true, $body['message']['message_id']);
        }

        if (isset($body['message']['sticker']) && in_array($body['message']['sticker']['file_id'], $this->fakeCongratsSticker)) {
            $bot->sendMessage($body['message']['chat']['id'], self::$_fakeCongrats[array_rand(self::$_fakeCongrats, 1)] . '!', 'html', true, $body['message']['message_id']);
        }

        $message = isset($body['message']['text']) ? mb_strtolower($body['message']['text']) : '';
        $trigger = $this->checkTrigger($body['message']['chat']['id'], $message);

        //проверяет не установлен ли триггер на фразу
        if (!empty($trigger)) {
            switch ($trigger['type']) {
                case "animation":
                    $bot->sendDocument($body['message']['chat']['id'], $trigger['value'], null, $body['message']['message_id']);
                    break;
                case "photo":
                    $bot->sendPhoto($body['message']['chat']['id'], $trigger['value'], null, $body['message']['message_id']);
                    break;
                case "voice":
                    $bot->sendVoice($body['message']['chat']['id'], $trigger['value'], null, $body['message']['message_id']);
                    break;
                case "audio":
                    $bot->sendAudio($body['message']['chat']['id'], $trigger['value'], null, null, null, $body['message']['message_id']);
                    break;
                case "video":
                    $bot->sendVideo($body['message']['chat']['id'], $trigger['value'], null, null, $body['message']['message_id']);
                    break;
                case "sticker":
                    $bot->sendSticker($body['message']['chat']['id'], $trigger['value'], $body['message']['message_id']);
                    break;
                case "video_note":
                    $bot->sendVideoNote($body['message']['chat']['id'], $trigger['value'], null, null, $body['message']['message_id']);
                    break;
                default:
                    $bot->sendMessage($body['message']['chat']['id'], $trigger['value'], 'html', true, $body['message']['message_id']);
            }
        }

        //установка триггера на фразу
        if (preg_match($this->bindPattern, $message, $mathes) && isset($body['message']['reply_to_message'])) {

            $administrators = $this->getAdministrators($body['message']['chat']['id']);
            $administrators[] = $this->botCreator;
            if (in_array($body['message']['from']['username'], $administrators)) {
                $triggerName = $mathes[2];
                $triggerContent = $body['message']['reply_to_message'];
                $chatId = $body['message']['chat']['id'];

                $bindResult = $this->setBind($chatId, $triggerName, $triggerContent);
                $bot->sendMessage($body['message']['chat']['id'], $bindResult, 'html', true, $body['message']['message_id']);
            } else {
                $bot->sendMessage($body['message']['chat']['id'], 'Попробуй стать админом для начала', 'html', true, $body['message']['message_id']);
            }
        }

        //удаление триггера на фразу
        if (preg_match($this->unbindPattern, $message, $mathes)) {

            $administrators = $this->getAdministrators($body['message']['chat']['id']);
            $administrators[] = $this->botCreator;
            if (in_array($body['message']['from']['username'], $administrators)) {
                $triggerName = $mathes[2];
                $chatId = $body['message']['chat']['id'];

                $unbindResult = $this->unsetBind($chatId, $triggerName);
                $bot->sendMessage($body['message']['chat']['id'], $unbindResult, 'html', true, $body['message']['message_id']);
            } else {
                $bot->sendMessage($body['message']['chat']['id'], 'Попробуй стать админом для начала', 'html', true, $body['message']['message_id']);
            }
        }

        //отслеживаем брань
        if (!ObsceneCensorRus::isAllowed($message)) {
            $this->addBadWords($body['message']['from']['id'], $body['message']['chat']['id'], $body['message']['from']['username']);

            $rand = rand(1,8);
            if (in_array($rand, self::$_magicRandom)) {
                $bot->sendMessage($body['message']['chat']['id'], self::$_stopBadWords[array_rand(self::$_stopBadWords, 1)], 'html', true, $body['message']['message_id']);
            }
        }

        if (isset(self::$_commands[$message])) {
            $text = $this->{self::$_commands[$message]['method']}($body['message']['chat']['id']);
            $bot->sendMessage($body['message']['chat']['id'], $text, 'html', self::$_commands[$message]['disable_preview'], $body['message']['message_id']);
        }

        if ($message == 'ping') {
            $bot->sendMessage($body['message']['chat']['id'], "pong", 'html', true, $body['message']['message_id']);
//            $bot->sendMessage($body['message']['chat']['id'], "<a href='t.me/evgeniyapuplikova'>test</a>", 'html', true, $body['message']['message_id']);
//    $bot->sendMessage("@stop_tc3o_nagging", "test");
        }

        if (mb_strpos($message, 'отпуск') !== false && $message != "кому отпуск?") {
            $this->addVacationWord($body['message']['from']['id'], $body['message']['chat']['id'], $body['message']['from']['username']);
            $bot->sendMessage($body['message']['chat']['id'], self::$_vacation[array_rand(self::$_vacation, 1)], 'html', true, $body['message']['message_id']);
        }

        if (mb_strpos($message, 'сколько мне еще') !== false && $message != "сколько мне еще?") {
            $bot->sendMessage($body['message']['chat']['id'], $this->getRandomYear(), 'html', true, $body['message']['message_id']);
        }

        if ($message == "сколько мне еще?" || $message == "сколько мне ещё?") {
            $result = $this->howLong($body['message']['chat']['id'], $body['message']['from']['id']);
            $bot->sendMessage($body['message']['chat']['id'], $result, 'html', true, $body['message']['message_id']);
        }

        if ($message == 'круто') {
            $bot->sendMessage($body['message']['chat']['id'], self::$_awesome[array_rand(self::$_awesome, 1)], 'html', true, $body['message']['message_id']);
        }

        if ($message == 'test') {
            $bot->sendPhoto($body['message']['chat']['id'], 'AgADAgADkKkxG9BwoUvplXGlGyhEqsOxqw4ABBCbK_dONsT7VrMEAAEC');
        }

        if (in_array($message, self::$_thanks)) {
            $bot->sendMessage($body['message']['chat']['id'], self::$_thanksAnswer[array_rand(self::$_thanksAnswer, 1)], 'html', true, $body['message']['message_id']);
        }

        //изменение кармы
        if (in_array($message, self::$_carmaChange) && isset($body['message']['reply_to_message'])) {
            if (in_array($body['message']['reply_to_message']['text'], self::$_carmaChange)) {
                $bot->sendMessage($body['message']['chat']['id'], self::$_chitCarma[array_rand(self::$_chitCarma, 1)], 'html', true, $body['message']['message_id']);
            } else {
                $toUserId = $body['message']['reply_to_message']['from']['id'];
                $fromUserId = $body['message']['from']['id'];
                $username = $body['message']['reply_to_message']['from']['username'];
                if ($fromUserId != $toUserId) {
                    $carmaResult = $this->changeCarma($body['message']['chat']['id'], $fromUserId, $toUserId, $message, $username);
                    if ($carmaResult != 'OK') {
                        $bot->sendMessage($body['message']['chat']['id'], $carmaResult, 'html', true, $body['message']['message_id']);
                    }
                }
            }
        }

        if (isset(self::$_horoSigns[$message])) {
            $horoscope = new Horoscope(self::$_horoSigns[$message]);
            $horoText = $horoscope->get();
            $bot->sendMessage($body['message']['chat']['id'], $horoText, 'html', true, $body['message']['message_id']);
        }

//        if (mb_strpos($message, 'что посмотреть') !== false) {
//            $bot->sendMessage($body['message']['chat']['id'], $this->getMovie(), 'html', false, $body['message']['message_id']);
//        }

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

//    private function updateUserName($chatId)
//    {
////        $usersList = $this->getUsers($chatId);
////        $list = '';
////        foreach ($usersList as $dbUser) {
////            $user = $this->bot->getChatMember($chatId, $dbUser['user_id']);
////            $list .= $dbUser['user_id'] . " " . $user->getUser()->getLastName() . "\n";
////        }
//
//        $chat = $this->bot->getChat($chatId);
//
//
//        file_put_contents('users.txt', $chat->getUsername());
//
//    }

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
            $text .= "{$index}. <a href='t.me/{$admin}'>{$admin}</a>\n";
            $index++;
        }
        if ($this->body['message']['from']['id'] == '189747732') {
            $text .= "{$index}. <a href='t.me/evgeniyapuplikova'>evgeniyapuplikova</a>\n";
        }

        return $text;

    }

    /**
     * Отвечает кто ты
     * @return mixed
     */
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
        $userLasttName = '';
        if (isset($body['from']['last_name'])) {
            $userLasttName = $body['from']['last_name'];
        }
        if (isset($body['from']['language_code'])) {
            $languageCode = $body['from']['language_code'];
        } else {
            $languageCode = '';
        }
        $chatId = $body['chat']['id'];

        $query = $this->db->prepare( "SELECT id, user_id
			 FROM users
			 WHERE user_id = :user_id AND chat_id = :chat_id" );
        $query->execute(array('user_id' => $userId, 'chat_id' => $chatId));
        if( $query->rowCount() <= 0 ) {
            $data = [
                'user_id' => $userId,
                'chat_id' => $chatId,
                'username' => $username,
                'first_name' => $userFirstName,
                'last_name' => $userLasttName,
                'language_code' => $languageCode
            ];

            $statement = $this->db->prepare("INSERT INTO users (user_id, chat_id, username, first_name, last_name, language_code) VALUES (:user_id, :chat_id, :username, :first_name, :last_name, :language_code)");
            $statement->execute($data);
        } else {
            $row = $query->fetch(PDO::FETCH_ASSOC);
            if (isset($userLasttName)) {
                $stmt = $this->db->prepare("UPDATE users SET first_name = :first_name, last_name = :last_name WHERE id = :id");
                $stmt->execute([
                   'first_name' => $userFirstName,
                   'last_name'  => $userLasttName,
                   'id' =>  $row['id']
                ]);
            }
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
            $row = $query->fetch(PDO::FETCH_ASSOC);
            $statement = $this->db->prepare("UPDATE charts SET last_update = :last_update, counter = :counter WHERE chat_id = :chat_id AND user_id = :user_id AND action_type = :action_type");
            $statement->execute(array(
                'chat_id' => $chatId,
                'user_id' => $userId,
                'action_type' => 'join',
                'last_update' => $date->format('Y-m-d H:i:s'),
                'counter' => (int)$row['counter'] + 1,
            ));
        } else {
            $statement = $this->db->prepare("INSERT INTO charts (chat_id, user_id, username, action_type, last_update) VALUES (:chat_id, :user_id, :username, :action_type, :last_update)");
            $statement->execute(array(
                'chat_id' => $chatId,
                'user_id' => $userId,
                'username' => $username,
                'action_type' => 'join',
                'counter' => 1,
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
                $text .= "{$index}. {$row['username']} - {$row['last_update']}\n";
                $index++;
            }
        } else {
            $text .= "Пока никто не ливнул. Или ничего не работает.";
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
        $query = $this->db->prepare( "SELECT username, last_update, counter
			 FROM charts
			 WHERE action_type = :action_type AND chat_id = :chat_id ORDER BY last_update DESC LIMIT 10" );
        $query->execute(array('action_type' => 'join', 'chat_id' => $chatId));
        if( $query->rowCount() > 0 ) {
            $rows = $query->fetchAll(PDO::FETCH_ASSOC);
            $index = 1;
            foreach ($rows as  $row) {
                $text .= "{$index}. {$row['username']} ({$this->declOfNum($row['counter'], self::$_numberTitles)}) - {$row['last_update']}\n";
                $index++;
            }
        } else {
            $text .= "Пока никто не пришел.";
        }

        return $text;
    }


    public function declOfNum($number, $titles)
    {
        $cases = array (2, 0, 1, 1, 1, 2);
        return $number." ".$titles[ ($number%100 > 4 && $number %100 < 20) ? 2 : $cases[min($number%10, 5)] ];
    }

    /**
     * Записывает количество использования плохих слов
     * @param $userId
     * @param $chatId
     * @param $username
     */
    public function addBadWords($userId, $chatId, $username)
    {
        $date = new DateTime();
        $query = $this->db->prepare("SELECT * FROM charts WHERE user_id = :user_id AND chat_id = :chat_id AND action_type = :action_type");
        $query->execute(array('user_id' => $userId, 'chat_id' => $chatId, 'action_type' => 'badword'));
        if( $query->rowCount() > 0 ) {
            $row = $query->fetch(PDO::FETCH_ASSOC);
            $statement = $this->db->prepare("UPDATE charts SET last_update = :last_update, counter = :counter WHERE chat_id = :chat_id AND user_id = :user_id AND action_type = :action_type");
            $statement->execute(array(
                'chat_id' => $chatId,
                'user_id' => $userId,
                'action_type' => 'badword',
                'counter'   => $row['counter'] + 1,
                'last_update' => $date->format('Y-m-d H:i:s')
            ));
        } else {
            $statement = $this->db->prepare("INSERT INTO charts (chat_id, user_id, username, action_type, counter, last_update) VALUES (:chat_id, :user_id, :username, :action_type, :counter, :last_update)");
            $statement->execute(array(
                'chat_id' => $chatId,
                'user_id' => $userId,
                'username' => $username,
                'action_type' => 'badword',
                'counter' => 1,
                'last_update' => $date->format('Y-m-d H:i:s')
            ));
        }
    }

    /**
     * Статистика разговоров про отпуск
     * @param $userId
     * @param $chatId
     * @param $username
     */
    public function addVacationWord($userId, $chatId, $username)
    {
        $date = new DateTime();
        $query = $this->db->prepare("SELECT * FROM charts WHERE user_id = :user_id AND chat_id = :chat_id AND action_type = :action_type");
        $query->execute(array('user_id' => $userId, 'chat_id' => $chatId, 'action_type' => 'vacation'));
        if( $query->rowCount() > 0 ) {
            $row = $query->fetch(PDO::FETCH_ASSOC);
            $statement = $this->db->prepare("UPDATE charts SET last_update = :last_update, counter = :counter WHERE chat_id = :chat_id AND user_id = :user_id AND action_type = :action_type");
            $statement->execute(array(
                'chat_id' => $chatId,
                'user_id' => $userId,
                'action_type' => 'badword',
                'counter'   => $row['counter'] + 1,
                'last_update' => $date->format('Y-m-d H:i:s')
            ));
        } else {
            $statement = $this->db->prepare("INSERT INTO charts (chat_id, user_id, username, action_type, counter, last_update) VALUES (:chat_id, :user_id, :username, :action_type, :counter, :last_update)");
            $statement->execute(array(
                'chat_id' => $chatId,
                'user_id' => $userId,
                'username' => $username,
                'action_type' => 'vacation',
                'counter' => 1,
                'last_update' => $date->format('Y-m-d H:i:s')
            ));
        }
    }


    /**
     * Выводит список самых сквернословных
     * @param $chatId
     * @return string
     */
    public function whoTopBadWords($chatId)
    {
        $text = "<b>Список главных сквернословов:</b>\n\n";
        $query = $this->db->prepare( "SELECT username, counter
			 FROM charts
			 WHERE action_type = :action_type AND chat_id = :chat_id AND counter > 0 ORDER BY counter DESC LIMIT 10" );
        $query->execute(array('action_type' => 'badword', 'chat_id' => $chatId));
        if( $query->rowCount() > 0 ) {
            $rows = $query->fetchAll(PDO::FETCH_ASSOC);
            $index = 1;
            foreach ($rows as  $row) {
                $text .= "{$index}. {$row['username']} ({$row['counter']})\n";
                $index++;
            }
        } else {
            $text .= "Пока все культурные.";
        }

        return $text;
    }

    public function whoTopVacationWords($chatId)
    {
        $text = "<b>Чаще всего про отпуск говорят:</b>\n\n";
        $query = $this->db->prepare( "SELECT username, counter
			 FROM charts
			 WHERE action_type = :action_type AND chat_id = :chat_id AND counter > 0 ORDER BY counter DESC LIMIT 10" );
        $query->execute(array('action_type' => 'vacation', 'chat_id' => $chatId));
        if( $query->rowCount() > 0 ) {
            $rows = $query->fetchAll(PDO::FETCH_ASSOC);
            $index = 1;
            foreach ($rows as  $row) {
                $text .= "{$index}. <a href='t.me/{$row['username']}'>{$row['username']}</a> ({$row['counter']})\n";
                $index++;
            }
        } else {
            $text .= "Никто не хочет в отпуск. Все любят работать";
        }

        return $text;
    }

    /**
     * Выводит список ближайших 10 дней рождений
     * @param $chatId
     * @return string
     */
    public function getNextBirthday($chatId)
    {
        $text = "<b>Ближайшие ДР:</b>\n\n";
        $query = $this->db->prepare("select * from ( select *, datediff(DATE_FORMAT(birthday,concat('%',YEAR(CURDATE()),'-%m-%d')),NOW()) as no_of_days from users union select *, datediff(DATE_FORMAT(birthday,concat('%',(YEAR(CURDATE())+1),'-%m-%d')),NOW()) as no_of_days from users ) AS upcomingbirthday WHERE no_of_days>=0 AND chat_id = :chat_id GROUP BY id ORDER BY no_of_days asc LIMIT 10");
        $query->execute(array('chat_id' => $chatId));
        if( $query->rowCount() > 0 ) {
            $rows = $query->fetchAll(PDO::FETCH_ASSOC);
            $index = 1;
            foreach ($rows as  $row) {
                $user = new Users($row['user_id']);
                $nameData = [
                    'username' => $row['username'],
                    'first_name' => $row['first_name'],
                    'last_name' => $row['last_name'],
                ];
                $username = $user->getUsername(null, $nameData);
                if (empty($username)) {
                    $username = $row['username'];
                }
                $date = new DateTime($row['birthday']);
                $date_str = $date->format('j') . ' ' . self::$_monthTitle[$date->format('n')];
                if ($row['no_of_days'] == 0) {
                    $text_day = "<b>ДР уже сегодня, а вы наверное забыли!</b> /{$row['username']}_s_dr";
                } else {
                    $text_day = "(Через {$this->declOfNum($row['no_of_days'], self::$_dayNumberTitles)}) - {$date_str}";
                }
                $text .= "{$index}. <a href='t.me/{$row['username']}'>{$username}</a> {$text_day}\n";
                $index++;
            }

            $text .= "\nЕсли ты еще не скинул свой др, то скидывай сюда @NaggingFeedbackBot";
        } else {
            $text .= "Дни рождения - миф.";
        }
        return $text;
    }

    /**
     * Изменение кармы
     * @param $chatId
     * @param $fromUser
     * @param $toUser
     * @param $action
     * @param $username
     * @return string
     */
    public function changeCarma($chatId, $fromUser, $toUser, $action, $username)
    {
        $date = new DateTime();
        $canChange = false;
        $history = [];
        $result = '';
        $query = $this->db->prepare("SELECT * FROM action_history WHERE chat_id = :chat_id AND from_user = :from_user AND to_user = :to_user AND action_type = :action_type LIMIT 1");
        $query->execute(array(
            'chat_id' => $chatId,
            'from_user' => $fromUser,
            'to_user' => $toUser,
            'action_type' => 'carma'
        ));
        if ($query->rowCount() > 0) {
            $row = $query->fetch(PDO::FETCH_ASSOC);
            $str_date = $row['last_update'];
            $history['id'] = $row['id'];
            if (strtotime($str_date) <  time() - 60*30) {
                $canChange = true;
                $history['count'] = $row['count'];
            } else {
                $history['fails'] = $row['fails'];
            }
        } else {
            $canChange = true;
        }

        if ($canChange) {
            if ($action == '+') {
                $counter = isset($history['count']) ? $history['count'] + 1 : 1;
            } else {
                $counter = isset($history['count']) ? $history['count'] - 1 : -1;
            }
            if (isset($history['id'])) {
                $statement = $this->db->prepare("UPDATE action_history SET last_update = :last_update, `count` = :count, fails = :fails WHERE id = :id");
                $statement->execute(array(
                    'count' => $counter,
                    'last_update' => $date->format('Y-m-d H:i:s'),
                    'fails' =>  0,
                    'id' => $history['id']
                ));
            } else {
                $statement = $this->db->prepare("INSERT INTO action_history (chat_id, from_user, to_user, last_update, `count`, action_type) VALUES (:chat_id, :from_user, :to_user, :last_update, :count, :action_type)");
                $statement->execute(array(
                    'chat_id' => $chatId,
                    'from_user' => $fromUser,
                    'to_user' => $toUser,
                    'count' => $counter,
                    'last_update' => $date->format('Y-m-d H:i:s'),
                    'action_type' => 'carma'
                ));
            }
            $query = $this->db->prepare( "SELECT u.username, SUM(ah.count) as `count` FROM `action_history` ah INNER JOIN users u ON u.user_id = ah.to_user
			 WHERE ah.action_type = :action_type AND ah.chat_id = :chat_id AND u.chat_id = :chat_id AND ah.count != 0 AND ah.to_user : to_user GROUP BY ah.to_user");
            $query->execute(array('action_type' => 'carma', 'chat_id' => $chatId, 'to_user' => $toUser));
            if( $query->rowCount() > 0 ) {
                $row = $query->fetch(PDO::FETCH_ASSOC);
                if (in_array($row['count'], [10, 30, 50, 70, 100])) {
                    $result = "<a href='t.me/{$username}'>{$username}</a> получил {$counter} кармических лойсов";
                } else {
                    $result = 'OK';
                }
            }
        } else {
            if (isset($history['fails']) && $history['fails'] != 3) {
                $fails = $history['fails'] + 1;
                $statement = $this->db->prepare("UPDATE action_history SET fails = :fails WHERE id = :id");
                $statement->execute(array(
                    'fails' => $fails,
                    'id' => $history['id']
                ));

                if ($fails < 3) {
                    $result = self::$_carmaFailMessage['next'][array_rand(self::$_carmaFailMessage['next'], 1)];
                } else {
                    $result = self::$_carmaFailMessage['last'][array_rand(self::$_carmaFailMessage['last'], 1)];
                }
            }
        }

        return $result;
    }

    /**
     * Показывает топ по карме
     * @param $chatId
     * @return string
     */
    public function getCarmaList($chatId)
    {
        $text = "<b>Список кармических топов:</b>\n\n";
        $query = $this->db->prepare( "SELECT u.username, SUM(ah.count) as `count` FROM `action_history` ah INNER JOIN users u ON u.user_id = ah.to_user
			 WHERE ah.action_type = :action_type AND ah.chat_id = :chat_id AND u.chat_id = :chat_id AND ah.count != 0 GROUP BY ah.to_user ORDER BY count DESC LIMIT 15");
        $query->execute(array('action_type' => 'carma', 'chat_id' => $chatId));
        if( $query->rowCount() > 0 ) {
            $rows = $query->fetchAll(PDO::FETCH_ASSOC);
            $index = 1;
            foreach ($rows as  $row) {
                $text .= "{$index}. <a href='t.me/{$row['username']}'>{$row['username']}</a> (<b>{$row['count']}</b>)\n";
                $index++;
            }
        } else {
            $text .= "Пока все безкарменные.";
        }

        return $text;
    }

    /**
     * Выводит список триггеров
     * @param $chatId
     * @return string
     */
    public function getTriggersList($chatId)
    {
        $list = '';
        $query = $this->db->prepare("SELECT * FROM triggers WHERE chat_id = :chat_id ORDER BY name");
        $query->execute([
            'chat_id' => $chatId
        ]);
        if ($query->rowCount() > 0) {
            $rows = $query->fetchAll(PDO::FETCH_ASSOC);
            foreach($rows as $row) {
                $list .= "{$row['name']}\n";
            }
        }

        if (!empty($list)) {
            return $list;
        } else {
            return "Каких триггеров?";
        }
    }

    /**
     * Проверяет не является ли сообщение триггером
     * @param $chatId
     * @param $message
     * @return mixed
     */
    public function checkTrigger($chatId, $message)
    {
        $query = $this->db->prepare("SELECT * FROM triggers WHERE chat_id = :chat_id AND name = :name");
        $query->execute([
            'chat_id' => $chatId,
            'name' => $message
        ]);
        if ($query->rowCount() > 0) {
            $row = $query->fetch(PDO::FETCH_ASSOC);
            return $row;
        }
    }

    /**
     * Установка триггера на сообщение
     * @param $chatId
     * @param $triggerName
     * @param $replyMessage
     * @return string
     */
    private function setBind($chatId, $triggerName, $replyMessage)
    {
        $type = 'text';
        $value = null;
        $query = $this->db->prepare("SELECT * FROM triggers WHERE chat_id = :chat_id AND name = :name");
        $query->execute(['chat_id' => $chatId, 'name' => $triggerName]);
        if ($query->rowCount() > 0 || in_array($triggerName, self::$_excludeTriggers)) {
            $result = 'Такой триггер уже установлен';
        } else {

            if (isset($replyMessage['audio'])) {
                $type = 'audio';
                $value = $replyMessage['audio']['file_id'];
            }

            if (isset($replyMessage['video'])) {
                $type = 'video';
                $value = $replyMessage['video']['file_id'];
            }

            if (isset($replyMessage['photo'])) {
                $type = 'photo';
                $value = $replyMessage['photo'][1]['file_id'];
            }

            if (isset($replyMessage['sticker'])) {
                $type = 'sticker';
                $value = $replyMessage['sticker']['file_id'];
            }

            if (isset($replyMessage['video_note'])) {
                $type = 'video_note';
                $value = $replyMessage['video_note']['file_id'];
            }

            if (isset($replyMessage['voice'])) {
                $type = 'voice';
                $value = $replyMessage['voice']['file_id'];
            }

            if (isset($replyMessage['animation'])) {
                $type = 'animation';
                $value = $replyMessage['animation']['file_id'];
            }

            if(is_null($value)) {
                $value = $replyMessage['text'];
            }

            $statement = $this->db->prepare("INSERT INTO triggers (chat_id, `name`, `value`, `type`) VALUES (:chat_id, :name, :value, :type)");
            $statement->execute(array(
                'chat_id' => $chatId,
                'name' => $triggerName,
                'value' => $value,
                'type' => $type
            ));
            $result = 'Триггер установлен';
        }

        return $result;
    }

    /**
     * Удаление триггера
     * @param $chatId
     * @param $triggerName
     * @return string
     */
    private function unsetBind($chatId, $triggerName)
    {
        $query = $this->db->prepare("SELECT * FROM triggers WHERE chat_id = :chat_id AND name = :name");
        $query->execute(['chat_id' => $chatId, 'name' => $triggerName]);
        if ($query->rowCount() > 0) {
            $query = $this->db->prepare("DELETE FROM triggers WHERE chat_id = :chat_id AND name = :name");
            $query->execute([
                'chat_id' => $chatId,
                'name' => $triggerName
            ]);
            $result = 'Триггер удален';
        } else {
            $result = 'Триггер не найден';
        }

        return $result;
    }

    private function updateChat($chatId, $updateCount = false)
    {
        $date = new DateTime();
        $count = $this->bot->getChatMembersCount($chatId);
        $chatObj = $this->bot->getChat($chatId);
        $chat = [
            'chat_id' => $chatObj->getId(),
            'type' => $chatObj->getType(),
            'title' => $chatObj->getTitle(),
            'username' => $chatObj->getUsername(),
            'first_name' => $chatObj->getFirstName(),
            'last_name' => $chatObj->getLastName(),
            'description' => $chatObj->getDescription(),
            'invite_link' => $chatObj->getInviteLink(),
            'pinned_message' => $chatObj->getPinnedMessage(),
            'count' => $count,
            'last_update' => $date->format('Y-m-d H:i:s')
        ];

        $query = $this->db->prepare("SELECT * FROM chats WHERE chat_id = :chat_id");
        $query->execute(['chat_id' => $chatId]);
        if ($query->rowCount() > 0) {
            $row = $query->fetch(PDO::FETCH_ASSOC);
            if ((strtotime($row['last_update']) <  time() - 60*60*4) || $updateCount == true) {
                $query = $this->db->prepare("UPDATE chats SET `type` = :type, title = :title, username = :username, first_name = :first_name, last_name = :last_name, description = :description, invite_link = :invite_link, pinned_message = :pinned_message, `count` = :count, last_update = :last_update WHERE chat_id = :chat_id");
                $query->execute($chat);
            }
        } else {
            $query = $this->db->prepare("INSERT INTO chats (chat_id, `type`, title, username, first_name, last_name, description, invite_link, pinned_message, `count`, last_update) VALUES (:chat_id, :type, :title, :username, :first_name, :last_name, :description, :invite_link, :pinned_message, :count, :last_update)");
            $query->execute($chat);
        }
    }

    public function getMovie()
    {
        $query = $this->db->prepare("SELECT f.* FROM movies f JOIN ( SELECT rand() * (SELECT max(id) from movies) AS max_id ) AS m WHERE f.id >= m.max_id ORDER BY f.id ASC LIMIT 1");
        $query->execute();
        if ($query->rowCount() > 0) {
            $row = $query->fetch(PDO::FETCH_ASSOC);
            $text = "<b>{$row['title']}</b>\n\n";
            $text .= $row['description'] . "\n";
            if (!empty($row['link'])) {
                $text .= "\n" . $row['link'];
            }
            $result = $text;
        } else {
            $result = "База пуста, милорд";
        }

        $result .= "\n\nДля добавления своей любимой фигни воспользуйтесь ботом @GarbageCollectionBot";

        return $result;
    }

    public function howLong($chatId, $userId)
    {
        $date = new DateTime();
        $randYear = rand(1, 65);
        $result = '';
        $query = $this->db->prepare("SELECT * FROM charts WHERE chat_id = :chat_id AND user_id = :user_id AND action_type = :action_type LIMIT 1");
        $query->execute(array(
            'chat_id' => $chatId,
            'user_id' => $userId,
            'action_type' => 'howlong'
        ));
        if ($query->rowCount() > 0) {
            $row = $query->fetch(PDO::FETCH_ASSOC);
            $str_date = $row['last_update'];
            $history['id'] = $row['id'];
            if (strtotime($str_date) <  time() - 60*60*4) {
                $statement = $this->db->prepare("UPDATE charts SET last_update = :last_update, counter = :counter WHERE id = :id");
                $statement->execute(array(
                    'counter' => $randYear,
                    'last_update' => $date->format('Y-m-d H:i:s'),
                    'id' => $row['id']
                ));
                $result = self::$_howLong[array_rand(self::$_howLong, 1)] . " {$this->declOfNum($randYear, self::$_yearNumberTitles)}";
            } else {
                $result = "Мы ведь с тобой уже все выяснили! Тебе осталось {$this->declOfNum($row['counter'], self::$_yearNumberTitles)}. Приходи попозже.";
            }
        } else {
            $statement = $this->db->prepare("INSERT INTO charts (chat_id, user_id, username, last_update, counter, action_type) VALUES (:chat_id, :user_id, :username, :last_update, :counter, :action_type)");
            $statement->execute(array(
                'chat_id' => $chatId,
                'user_id' => $userId,
                'username' => $this->body['message']['from']['username'],
                'counter' => $randYear,
                'last_update' => $date->format('Y-m-d H:i:s'),
                'action_type' => 'howlong'
            ));
            $result = self::$_howLong[array_rand(self::$_howLong, 1)] . " {$this->declOfNum($randYear, self::$_yearNumberTitles)}";
        }

        return $result;
    }

    public function getRandomYear()
    {
        $randYear = rand(1, 89);
        return self::$_howLong[array_rand(self::$_howLong, 1)] . " {$this->declOfNum($randYear, self::$_yearNumberTitles)}";

    }

    public function getHowLongList($chatId)
    {
        $text = "<b>Список долгожителей:</b>\n\n";
        $query = $this->db->prepare( "SELECT * FROM charts WHERE chat_id = :chat_id AND action_type = :action_type ORDER BY counter DESC LIMIT 15");
        $query->execute(array('action_type' => 'howlong', 'chat_id' => $chatId));
        if( $query->rowCount() > 0 ) {
            $rows = $query->fetchAll(PDO::FETCH_ASSOC);
            $index = 1;
            foreach ($rows as  $row) {
                $text .= "{$index}. <a href='t.me/{$row['username']}'>{$row['username']}</a> (Протянет еще <b>{$this->declOfNum($row['counter'], self::$_yearNumberTitles)}</b>)\n";
                $index++;
            }
        } else {
            $text .= "Наверное все умерли.";
        }

        return $text;
    }
}