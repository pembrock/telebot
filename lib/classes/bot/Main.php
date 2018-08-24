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

class Main
{

    private $db = null;

    protected $bot;
    protected $body;

    private $congratsSticker = 'CAADAgADiQAD6st5AuZbw2Z4SeORAg';

    static protected $_adminStatus = ['creator', 'administrator'];
    static protected $_words = [
        'Ты - принц, Экли, детка', 'Ты - ужас, летящий на крыльях ночи', 'Ты - чмо', 'Ты - инженер на сотню рублей', 'Ты меня бесишь', 'Ты задрот и дрищ. Ты даже кота отпиздить не сможешь', 'Ты - принцесса', 'Ты старый', 'Ты жирный', 'Ты большой молодец', 'Ты человек летучая мышь', 'Ты мог бы быть лучше', 'Ты остался таким же как и был', 'Кто ты?', 'Ты чудо', 'Ты восхитителен', 'Ты правый', 'Ты левый', 'Ты такой же как все', 'Ты не лишен простоты', 'Ты не смешной', 'Ты рок звезда', 'Ты такой же как Путин', 'Ты рыжая из ВИА Гры', 'Ты твинк', 'Ты самый лучший человек на Земле'
    ];
    static protected $_awesome = [
        'И ты это все сам сделал! Какой ты молодец!', 'И пенис у тебя огромный', 'Как будто были сомнения', 'Но не так круто, как крут ты', 'Тупо', 'Как задница вон той чики', 'Можно и отдохнуть', 'Это был тяжелый год...', 'True story', 'Что ты можешь знать о крутости?', 'Не то что твоя жизнь', '😉'
    ];
    static protected $_vacation = [
        'Отпуск для слабаков!', 'А работать кто будет?', 'Опять?', 'Для отпуска нужно работать!', 'Давай, расскажи как тебе не хватает моря', 'Кто-то ноет про отпуск?', 'Можно и отдохнуть, но не тебе', 'Отпуск придумали капиталисты в 85-ом', 'Работать!', 'Не в этой жизни', 'Хватит прохлаждаться', 'Господи, займись уже делом'
    ];

    static protected  $_numberTitles = ['раз', 'раза', 'раз'];
    static protected  $_dayNumberTitles = ['день', 'дня', 'дней'];
    static protected $_monthTitle = [
        1 => 'января',
        2 => 'февраля',
        3 => 'марта',
        4 => 'апреля',
        5 => 'мая',
        6 => 'июня',
        7 => 'июля',
        8 => 'августа',
        9 => 'сентября',
        10 => 'октября',
        11 => 'ноября',
        12 => 'декабря',
    ];

    static protected $_congrats = [
        'Красавчик', 'Орёл', 'Молодец', 'Так держать', 'Топчик', 'Грацулевич', 'Умница', 'Гранч', 'Грац', 'Грач', 'Смотрю руки у тебя из правильного места', 'Ты просто космос', 'Это превосходно', 'Ор выше гор'
    ];

    static protected $_thanks = ['спасибо', 'спасиба', 'спс'];
    static protected $_thanksAnswer = ['500 рублей', 'Да уж есть за что', 'Спасибом пьян не будешь', 'Спасибо на хлеб не намажешь', 'Не за что', 'И тебе', '😘'];

    static protected $_carmaChange = ['+', '-'];

    static protected $_carmaFailMessage = [
        'next' => ['Подожди', 'Не торопись', 'Слишком быстро', 'Не так быстро', 'Угомонись!', 'Бля, да завязывай!', 'Я тебя забаню!', 'Воу воу, пологче', 'Еще раз и мы больше не увидимся...'],
        'last' => ['Я тебя предупреждал!', 'Прощай...', 'Не пиши мне больше', 'Извини, но мне пришлось тебя забанить', 'Ну епта, ты допрыгался пацан', 'Я устал, я ухожу', 'Я щас ливну', 'Отвали!', 'Я занят, зайди попозже', 'Я ушел на обед', 'Сейчас все операторы заняты', 'Мы вам перезвоним']
    ];

    static protected $_commands = [
        'кто я' => 'whoAmI',
        'кто я?' => 'whoAmI',
//        'ты кто?' => 'whoAmI',
//        'кто ты?' => 'whoAmI',
        'кто свалил' => 'whoLeft',
        'кто пришел' => 'whoJoin',
        'кто пришел?' => 'whoJoin',
        'кто ввалил?' => 'whoJoin',
        'кто ввалил' => 'whoJoin',
        'админы' => 'whoAdmin',
        'бескультурщина' => 'whoTopBadWords',
        'др' => 'getNextBirthday',
//        'топ' => 'getCarmaList'
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

//        ob_flush();
//        ob_start();
//        print_r($body);
//        file_put_contents('var_dump.txt', ob_get_flush(), FILE_APPEND);

        $this->checkUser($body['message']);

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

//        ob_flush();
//        ob_start();
//        print_r($body);
//        file_put_contents('var_dump.txt', ob_get_flush(), FILE_APPEND);

        $message = mb_strtolower($body['message']['text']);

        if (!ObsceneCensorRus::isAllowed($message)) {
            $this->addBadWords($body['message']['from']['id'], $body['message']['chat']['id'], $body['message']['from']['username']);
        }

        if (isset(self::$_commands[$message])) {
            $text = $this->{self::$_commands[$message]}($body['message']['chat']['id']);
            $bot->sendMessage($body['message']['chat']['id'], $text, 'html', true, $body['message']['message_id']);
        }

        if ($message == 'ping') {
            $bot->sendMessage($body['message']['chat']['id'], "pong", 'html', true, $body['message']['message_id']);
//            $bot->sendMessage($body['message']['chat']['id'], "<a href='t.me/evgeniyapuplikova'>test</a>", 'html', true, $body['message']['message_id']);
//    $bot->sendMessage("@stop_tc3o_nagging", "test");
        }

        if (mb_strpos($message, 'отпуск') !== false) {
            $bot->sendMessage($body['message']['chat']['id'], self::$_vacation[array_rand(self::$_vacation, 1)], null, false, $body['message']['message_id']);
        }

        if ($message == 'круто') {
            $bot->sendMessage($body['message']['chat']['id'], self::$_awesome[array_rand(self::$_awesome, 1)], null, false, $body['message']['message_id']);
        }

        if ($message == 'сука') {
            $bot->sendMessage($body['message']['chat']['id'], 'Запрягай коней!', null, false, $body['message']['message_id']);
        }

        if ($message == 'test') {
            $bot->sendPhoto($body['message']['chat']['id'], 'AgADAgADkKkxG9BwoUvplXGlGyhEqsOxqw4ABBCbK_dONsT7VrMEAAEC');
        }

        if (in_array($message, self::$_thanks)) {
            $bot->sendMessage($body['message']['chat']['id'], self::$_thanksAnswer[array_rand(self::$_thanksAnswer, 1)], null, false, $body['message']['message_id']);
        }

        if (in_array($message, self::$_carmaChange) && isset($body['message']['reply_to_message'])) {
            $toUserId = $body['message']['reply_to_message']['from']['id'];
            $fromUserId = $body['message']['from']['id'];
            if ($fromUserId != $toUserId) {
                $carmaResult = $this->changeCarma($body['message']['chat']['id'], $fromUserId, $toUserId, $message);
                if ($carmaResult != 'OK') {
                    $bot->sendMessage($body['message']['chat']['id'], $carmaResult, null, false, $body['message']['message_id']);
                }
            }
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
                    $admins[$admin->getUser()->getId()]['username'] = $admin->getUser()->getUsername();
                    $admins[$admin->getUser()->getId()]['id'] = $admin->getUser()->getId();
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
            $text .= "{$index}. <a href='t.me/{$admin['username']}'>{$admin['username']}</a>\n";
//            $text .= "{$index}. <a href='tg://user?id={$admin['id']}'>{$admin['username']}</a>\n";
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

    /**
     * Выводит список ближайших 10 дней рождений
     * @param $chatId
     * @return string
     */
    public function getNextBirthday($chatId)
    {
        $text = "<b>Ближайшие ДР:</b>\n\n";
        $query = $this->db->prepare("select * from ( select *, datediff(DATE_FORMAT(birthday,concat('%',YEAR(CURDATE()),'-%m-%d')),NOW()) as no_of_days from users union select *, datediff(DATE_FORMAT(birthday,concat('%',(YEAR(CURDATE())+1),'-%m-%d')),NOW()) as no_of_days from users ) AS upcomingbirthday WHERE no_of_days>0 AND chat_id = :chat_id GROUP BY id ORDER BY no_of_days asc LIMIT 10");
        $query->execute(array('chat_id' => $chatId));
        if( $query->rowCount() > 0 ) {
            $rows = $query->fetchAll(PDO::FETCH_ASSOC);
            $index = 1;
            foreach ($rows as  $row) {
                $date = new DateTime($row['birthday']);
                $date_str = $date->format('j') . ' ' . self::$_monthTitle[$date->format('n')];
                $text .= "{$index}. <a href='t.me/{$row['username']}'>{$row['username']}</a> (Через {$this->declOfNum($row['no_of_days'], self::$_dayNumberTitles)}) - {$date_str}\n";
                $index++;
            }

            $text .= "\nЕсли ты еще не скинул свой др, то скидывай сюда @NaggingFeedbackBot";
        } else {
            $text .= "Дни рождения - миф.";
        }
        return $text;
    }

    public function changeCarma($chatId, $fromUser, $toUser, $action)
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
            $result = 'OK';
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
        $query = $this->db->prepare( "SELECT u.username, ah.count FROM `action_history` ah INNER JOIN users u ON u.user_id = ah.to_user
			 WHERE ah.action_type = :action_type AND ah.chat_id = :chat_id AND ah.count != 0 ORDER BY ah.count DESC LIMIT 10" );
        $query->execute(array('action_type' => 'carma', 'chat_id' => $chatId));
        if( $query->rowCount() > 0 ) {
            $rows = $query->fetchAll(PDO::FETCH_ASSOC);
            $index = 1;
            foreach ($rows as  $row) {
                $text .= "{$index}. {$row['username']} (<b>{$row['count']}</b>)\n";
                $index++;
            }
        } else {
            $text .= "Пока все безкарменные.";
        }

        return $text;
    }

    /**
     * Установка триггера на сообщение
     * @param $triggerName
     * @param $replyMessage
     */
    public function setBind($triggerName, $replyMessage)
    {

    }

    /**
     * Удаление триггера
     * @param $triggerName
     */
    public function unsetBind($triggerName)
    {

    }
}