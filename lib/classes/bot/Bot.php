<?php
/**
 * Created by PhpStorm.
 * User: pasikuta
 * Date: 24.08.2018
 * Time: 11:58
 */

namespace Telebot\Lib\Bot;


class Bot
{
    protected $congratsSticker = 'CAADAgADiQAD6st5AuZbw2Z4SeORAg';
    protected $bindPattern = '/^(bind) ([a-zA-Zа-яА-Я\s\d]+)/u';
    protected $unbindPattern = '/^(unbind) ([a-zA-Zа-яА-Я\s\d]+)/u';

    static protected $_adminStatus = ['creator', 'administrator'];
    static protected $_words = [
        'Ты - принц, Экли, детка', 'Ты - ужас, летящий на крыльях ночи', 'Ты - чмо', 'Ты - инженер на сотню рублей', 'Ты меня бесишь', 'Ты задрот и дрищ. Ты даже кота отпиздить не сможешь', 'Ты - принцесса', 'Ты старый', 'Ты жирный', 'Ты большой молодец', 'Ты человек летучая мышь', 'Ты мог бы быть лучше', 'Ты остался таким же как и был', 'Кто ты?', 'Ты чудо', 'Ты восхитителен', 'Ты правый', 'Ты левый', 'Ты такой же как все', 'Ты не лишен простоты', 'Ты не смешной', 'Ты рок звезда', 'Ты такой же как Путин', 'Ты рыжая из ВИА Гры', 'Ты твинк', 'Ты самый лучший человек на Земле'
    ];
    static protected $_awesome = [
        'И ты это все сам сделал! Какой ты молодец!', 'И пенис у тебя огромный', 'Как будто были сомнения', 'Но не так круто, как крут ты', 'Тупо', 'Как задница вон той чики', 'Можно и отдохнуть', 'Это был тяжелый год...', 'True story', 'Что ты можешь знать о крутости?', 'Не то что твоя жизнь', '😉', 'Базаришь'
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

    static protected $_stopBadWords = [
        'Хватит ругаться',
        'Тебя кто таким словам научил?',
        'Следи за языком!',
        'Фу, как грубо',
        'Материшься как сапожник',
        'Пиздец ты матершинник',
        'Будешь так выражаться, придется тебя наказать',
        'Банить вас надо за такие слова...',
        'Мат-перемат',
        'Мат на мате и матом погоняет',
    ];

    static protected $_horoSigns = [
        'овен' => 'aries',
        'телец' => 'taurus',
        'близнецы' => 'gemini',
        'рак' => 'cancer',
        'лев' => 'leo',
        'весы' => 'libra',
        'скорпион' => 'scorpio',
        'стрелец' => 'sagittarius',
        'козерог' => 'capricorn',
        'водолей' => 'aquarius',
        'дева' => 'virgo',
        'рыбы' => 'pisces'

    ];

    static protected $_magicRandom = [3];
    static protected $_excludeTriggers = ['список триггеров'];
    static protected $_commands = [
        'кто я' => 'whoAmI',
        'кто свалил' => 'whoLeft',
        'кто пришел' => 'whoJoin',
        'админы' => 'whoAdmin',
        'бескультурщина' => 'whoTopBadWords',
        'др' => 'getNextBirthday',
        'топ' => 'getCarmaList',
        'список триггеров' => 'getTriggersList'
    ];
}