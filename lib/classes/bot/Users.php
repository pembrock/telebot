<?php
/**
 * Created by PhpStorm.
 * User: pasikuta
 * Date: 28.08.2018
 * Time: 10:01
 */

namespace Telebot\Lib\Bot;


use Telebot\Lib\DB\Database;

class Users
{
    private $db;
    private $id;

    /**
     * Users constructor.
     * @param $userId
     */
    public function __construct($userId)
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
}