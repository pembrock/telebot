<?php
/**
 * Created by PhpStorm.
 * User: pasikuta
 * Date: 29.08.2018
 * Time: 10:47
 */

namespace Telebot\Lib\Bot;


use Telebot\Lib\DB\Database;

class Chat
{
    /**
     * Возвращает количество участников чата
     * @param int $chatId
     * @return int
     */
    static function getCount(int $chatId)
    {
        $db = Database::getInstance();
        $query = $db->prepare("SELECT `count` FROM chats WHERE chat_id = :chat_id");
        $query->execute(['chat_id' => $chatId]);
        $count = $query->fetchColumn();

        return (int)$count;
    }
}