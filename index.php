<?php

/*!
 * metowolf BilibiliHelper
 * https://i-meto.com/
 * Version 18.04.20 (0.7.1)
 *
 * Copyright 2018, metowolf
 * Released under the MIT license
 */

require 'vendor/autoload.php';

use Dotenv\Dotenv;
use metowolf\Bilibili\Curl;

use metowolf\Bilibili\Daily;
use metowolf\Bilibili\GiftSend;
use metowolf\Bilibili\Heart;
use metowolf\Bilibili\Login;
use metowolf\Bilibili\SignIn;
use metowolf\Bilibili\Silver;
use metowolf\Bilibili\Task;

// timezone
date_default_timezone_set('Asia/Shanghai');

// load config
$dotenv = new Dotenv(__DIR__, '.env');
$dotenv->load();
$dotenv = new Dotenv(__DIR__, 'config');
$dotenv->load();

// load ACCESS_KEY
Login::getAccessKey();
$dotenv->overload();

// run
while (true) {
    Daily::run();
    GiftSend::run();
    Heart::run();
    SignIn::run();
    Silver::run();
    Task::run();
    sleep(10);
}
