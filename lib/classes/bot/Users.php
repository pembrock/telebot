<?php
/**
 * Created by PhpStorm.
 * User: pasikuta
 * Date: 28.08.2018
 * Time: 10:01
 */

namespace Telebot\Lib\Bot;


use DateTime;
use Telebot\Lib\DB\Database;
use PDO;

class Users
{
    private $db;
    private $id;

    /**
     * Users constructor.
     * @param $userId
     */
    public function __construct($userId = null)
    {
        $this->id = $userId;
        $this->db = Database::getInstance();
    }

    /**
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param null $userId
     * @return bool|mixed
     */
    public function getUser($userId = null)
    {
        $id = is_null($userId) ? $this->id : $userId;

        return $this->fetchUser($id);
    }

    /**
     * @param null $id
     * @param array $data
     * @return bool|mixed|string
     */
    public function getUsername($id = null, $data = [])
    {
        return $this->concatUsername($id, $data);
    }

    /**
     * @param $id
     * @return bool|mixed
     */
    private function fetchUser($id)
    {
        $query = $this->db->prepare("SELECT * FROM users WHERE user_id = :id");
        $query->execute([
            'user_id' => $id
        ]);

        if ($query->rowCount() > 0) {
            return $query->fetch(\PDO::FETCH_ASSOC);
        } else {
            return false;
        }
    }

    /**
     * @param null $userId
     * @param array $data
     * @return bool|mixed|string
     */
    private function concatUsername($userId = null, $data = [])
    {
        if (!empty($data)) {
            if (!isset($data['username']) || empty($data['username'])) {
                return false;
            }

            if (!isset($data['first_name']) || empty($data['first_name'])) {
                return $data['username'];
            }

            if (isset($data['last_name']) && !empty($data['last_name'])) {
                return $data['first_name'] . " " . $data['last_name'];
            }

        } else {
            $id = is_null($userId) ? $this->id : $userId;
            $query = $this->db->prepare("SELECT username, first_name, last_name FROM users WHERE user_id = :id");
            $query->execute([
                'user_id' => $id
            ]);

            if ($query->rowCount() > 0) {
                $row = $query->fetch(\PDO::FETCH_ASSOC);
                if (empty($row['username'])) {
                    return false;
                }

                if (empty($row['first_name'])) {
                    return $data['username'];
                }

                if (!empty($row['last_name'])) {
                    return $row['first_name'] . " " . $row['last_name'];
                }
            } else {
                return false;
            }
        }
    }

    public function checkTodayBirthday($chatId)
    {
        $text = "<b>Сегодня днюшенька у:</b>\n\n";
        $query = $this->db->prepare("select * from ( select *, datediff(DATE_FORMAT(birthday,concat('%',YEAR(CURDATE()),'-%m-%d')),NOW()) as no_of_days from users union select *, datediff(DATE_FORMAT(birthday,concat('%',(YEAR(CURDATE())+1),'-%m-%d')),NOW()) as no_of_days from users ) AS upcomingbirthday WHERE no_of_days = 0 AND chat_id = :chat_id GROUP BY id ORDER BY username asc");
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
                $text .= "{$index}. <a href='t.me/{$row['username']}'>{$username}</a>\n";
                $index++;
            }
        } else {
            return false;
        }
        return $text;
    }

    /**
     * @param $chatId
     * @param $bot
     * @return string
     */
    public function updateUsersStatus($chatId, $bot)
    {
        $leftUsers = '';
        $date = new DateTime();
        $query = $this->db->prepare("SELECT value FROM system_action WHERE name = :name");
        $query->execute(['name' => 'check_users']);
        if ($query->rowCount() > 0) {
            $offset = $query->fetchColumn();
        } else {
            return false;
        }

        $query = $this->db->prepare("SELECT * FROM `users` WHERE chat_id = :chat_id LIMIT 20 OFFSET :offset");
        $query->bindValue(':offset', $offset);
        $query->execute(['chat_id' => $chatId]);
        if ($query->rowCount() > 0) {
            $rows = $query->fetchAll(PDO::FETCH_ASSOC);
            $index = 1;
            foreach ($rows as $row) {
                $chatMember = $bot->getChatMember($chatId, $row['user_id']);
                $userStatus = $chatMember->getStatus();
                file_put_contents('cron_check_users.txt', $row['user_id'] . " " . $userStatus . " " . $row['is_deleted'] . "\n", FILE_APPEND);
                if (in_array($userStatus, Bot::$_leftStatus) && $row['is_deleted'] == 0) {
                    $leftUsers = "{$index}. {$row['username']} {$date->format("d-m-Y")}\n";
                    $query = $this->db->prepare("UPDATE users SET is_deleted = 1 WHERE id = :id");
                    $query->execute([
                        'id' => $row['id']
                    ]);
                    $index++;
                }

                if (in_array($userStatus, Bot::$_rightStatus) && $row['is_deleted'] == 1) {
                    $query = $this->db->prepare("UPDATE users SET is_deleted = 0 WHERE id = :id");
                    $query->execute([
                        'id' => $row['id']
                    ]);
                }
            }
            $query = $this->db->prepare("UPDATE system_actions SET value = :value WHERE name = :name");
            $query->execute([
                'value' => $offset + 20,
                'name' => 'check_users'
            ]);
        } else {
            $query = $this->db->prepare("UPDATE system_actions SET value = :value WHERE name = :name");
            $query->execute([
                'value' => 0,
                'name' => 'check_users'
            ]);
        }

        return $leftUsers;
    }
}