<?php
//ini_set('display_errors', 1);
//ini_set('display_startup_errors', 1);
//ini_set("log_errors", 1);
//ini_set("error_log", "php-error1.log");
//error_reporting(E_ALL);

include "vendor/autoload.php";

// /usr/local/bin/php /home/gujkevin/domains/pembrock.name/public_html/bot/index.php tc3o

use Telebot\Lib\Bot\Main;

$bot = new Main();

$bot->index();