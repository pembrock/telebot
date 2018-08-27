<?php
/**
 * Created by PhpStorm.
 * User: pasikuta
 * Date: 27.08.2018
 * Time: 9:45
 */

namespace Telebot\Lib\Bot;

use PDO;
use PHPHtmlParser\Dom;
use Telebot\Lib\Config\Config;
use Telebot\Lib\DB\Database;

class Horoscope
{
    protected $url;
    protected $sign;
    protected $id = null;
    private $db = null;

    public function __construct($sing)
    {
        $url = Config::get('horoscope_url');
        $url = str_replace('{sign}', $sing, $url);
        $this->url = $url;
        $this->sign = $sing;
        $this->db = Database::getInstance();
    }

    /**
     * Выводит гороском для заданного знака
     * @return string
     */
    public function get()
    {
        $text = "<b>Гороскоп на сегодня.</b>\n\n";
        /** Чтобы каждый раз не парсить сайт, проверяем есть ли актуальные данные в базе.
         * Если есть, проверяем не устаревшие ли они, иначе получаем с сайта свежие данные.
         */
        if (($this->isNeedUpdate($this->sign) && !is_null($this->id)) || (!$this->isNeedUpdate($this->sign) && is_null($this->id))) {
            $text .= $this->parse();
        } else {
            $text .= $this->getFromDb($this->id);
        }
        $this->id = null;
        return $text;
    }

    /**
     * Парсит сайт с гороскопом
     * @return string
     */
    private function parse()
    {
        $content = '';
        $dom = new Dom();
        $dom->loadFromUrl($this->url);
        $html = $dom->find('.article__item_html');

        $parag = $html->innerHtml;
        $pattern = '/((?<=\<p\>).*?(?=\<\/p\>))/';
        preg_match_all($pattern, $parag, $matches);
        foreach ($matches[0] as $match) {
            $content .= $match . "\n\n";
        }

        $score = $dom->find('.p-score-day__item');
        foreach ($score as $sc) {
            $item_text = $sc->find('.p-score-day__item__text')->innerHtml;
            $item_value = $sc->find('.p-score-day__item__value__inner')->innerHtml;

            $content .= "<b>{$item_text}: {$item_value}</b>\n";
        }
        $this->update($this->sign, $content);

        return $content;
    }

    /**
     * Проверяет нужно ли обновить данные (если с последнего обновления прошло больше 2-х часов
     * @param $sign
     * @return bool
     */
    private function isNeedUpdate($sign)
    {
        $query = $this->db->prepare("SELECT * FROM horoscope WHERE sign = :sign");
        $query->execute(array(
            'sign' => $sign
        ));
        if ($query->rowCount() > 0) {
            $row = $query->fetch(PDO::FETCH_ASSOC);
            $this->id = $row['id'];
            if (strtotime($row['last_update']) <  time() - 60*60*2) {
                return true;
            } else {
                return false;
            }
        } else {
            return false;
        }
    }

    /**
     * ОБновляет данные, либо добавляет, если их еще нет
     * @param $sign
     * @param $content
     */
    private function update($sign, $content)
    {
        $lastUpdate = new \DateTime();
        if (!is_null($this->id)) {
            $statement = $this->db->prepare("UPDATE horoscope SET last_update = :last_update, content = :content WHERE id = :id");
            $statement->execute([
                'last_update' => $lastUpdate->format('Y-m-d H:i:s'),
                'content'   =>  $content,
                'id'    => $this->id
            ]);
        } else {
            $statement = $this->db->prepare("INSERT INTO horoscope (sign, content, last_update) VALUES (:sign, :content, :last_update)");
            $statement->execute(array(
                'sign' => $sign,
                'content' => $content,
                'last_update' => $lastUpdate->format('Y-m-d H:i:s'),
            ));
        }
    }

    /**
     * Получает данные из базы
     * @param $id
     * @return mixed
     */
    private function getFromDb($id)
    {
        $query = $this->db->prepare("SELECT * FROM horoscope WHERE id = :id");
        $query->execute(array(
            'id' => $id
        ));
        if ($query->rowCount() > 0) {
            $row = $query->fetch(PDO::FETCH_ASSOC);
            return $row['content'];
        }

    }
}